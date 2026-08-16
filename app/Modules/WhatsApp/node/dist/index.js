/**
 * WhatsApp Bridge — Serveur Express (interne, jamais exposé à un navigateur)
 *
 * Expose :
 *   POST /send-message  → envoyer un message WhatsApp (protégé par clé API)
 *   GET  /status        → état de la connexion (protégé par clé API)
 *   DELETE /logout      → déconnecter et réinitialiser la session (protégé par clé API)
 *
 * MODIFIÉ (15/08/2026) — changements structurels majeurs :
 *
 *   1. PLUS DE VUE : le dashboard HTML (public/) est supprimé. Le QR code
 *      passe exclusivement par Laravel (voir webhook ci-dessous), affiché
 *      dans le module admin du progiciel, jamais servi directement par ce
 *      process Node.
 *
 *   2. PLUS DE CORS NI DE SOCKET.IO : ce service n'est appelé QUE par
 *      Laravel, côté serveur, jamais par un navigateur. CORS n'a de sens
 *      que pour des requêtes cross-origin depuis un navigateur — supprimé.
 *      Socket.io n'a plus de consommateur (le frontend admin communique
 *      avec Laravel, qui poll ce service, pas l'inverse) — supprimé.
 *      Conséquence : moins de dépendances, moins de surface d'attaque,
 *      config plus simple.
 *
 *   3. RELAIS WEBHOOK : à chaque événement (qr, ready, logged_out,
 *      status_update), ce process notifie Laravel via
 *      POST {APP_URL}/api/whatsapp/internal/webhook (header X-Api-Key).
 *      Laravel met en cache et le frontend admin récupère par polling
 *      sur GET /api/whatsapp/admin/status.
 *
 *   4. JOURNALISATION : chaque appel à /send-message ou /send-file crée
 *      une ligne dans wa_message_log (queued→sending→sent|failed), avec
 *      le module d'origine détecté automatiquement côté Laravel — lue
 *      ensuite par Laravel pour les onglets Messages/Statistiques/Retry,
 *      filtrable module par module.
 *
 *   5. ENVOI DE FICHIERS : POST /send-file (NOUVEAU, endpoint séparé —
 *      /send-message garde sa signature strictement inchangée). Reçoit une
 *      URL interne Laravel (fileUrl) que Baileys télécharge lui-même —
 *      pas de base64 volumineux à faire transiter.
 *
 * Signature de /send-message, /status, /logout INCHANGÉE — aucune
 * régression pour WhatsAppBridgeClient.php côté Laravel.
 */
import express from 'express';
import { WhatsAppManager } from './whatsapp.js';
import qrcode from 'qrcode';
import { config } from './config.js';
import { assertDbConnection } from './db/connection.js';
import { createMessageLog, markMessageSent, markMessageFailed } from './db/messageStore.js';
// ─── Validation de la configuration au démarrage ─────────────────────────────
// (la validation stricte des variables requises est déjà faite dans config.ts,
//  qui process.exit(1) si quelque chose d'essentiel manque)
// ─── Serveur Express ──────────────────────────────────────────────────────────
const app = express();
app.use(express.json({ limit: '10kb' }));
app.disable('x-powered-by');
// ─── Middleware : authentification par clé API ────────────────────────────────
const requireApiKey = (req, res, next) => {
    if (!config.apiKey) {
        next();
        return;
    }
    const provided = req.headers['x-api-key']?.trim();
    if (!provided) {
        res.status(401).json({ success: false, error: 'Header X-Api-Key manquant' });
        return;
    }
    if (!timingSafeEqual(provided, config.apiKey)) {
        res.status(401).json({ success: false, error: 'Clé API invalide' });
        return;
    }
    next();
};
function timingSafeEqual(a, b) {
    if (a.length !== b.length)
        return false;
    let diff = 0;
    for (let i = 0; i < a.length; i++) {
        diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
    }
    return diff === 0;
}
// ─── Rate limiting simple (protection contre le spam) ────────────────────────
const rateLimitMap = new Map();
const RATE_LIMIT_MAX = 30;
const RATE_LIMIT_WINDOW = 60_000;
const rateLimit = (req, res, next) => {
    const ip = req.headers['x-forwarded-for']?.split(',')[0]?.trim()
        ?? req.socket.remoteAddress
        ?? 'unknown';
    const now = Date.now();
    const entry = rateLimitMap.get(ip);
    if (!entry || now > entry.resetAt) {
        rateLimitMap.set(ip, { count: 1, resetAt: now + RATE_LIMIT_WINDOW });
        next();
        return;
    }
    if (entry.count >= RATE_LIMIT_MAX) {
        res.status(429).json({ success: false, error: 'Trop de requêtes — réessayez dans un moment' });
        return;
    }
    entry.count++;
    next();
};
setInterval(() => {
    const now = Date.now();
    for (const [ip, entry] of rateLimitMap) {
        if (now > entry.resetAt)
            rateLimitMap.delete(ip);
    }
}, 5 * 60_000);
// ─── Relais webhook vers Laravel ───────────────────────────────────────────────
/**
 * Notifie Laravel d'un changement d'état (qr, ready, logged_out, status_update).
 * Ne lève JAMAIS d'exception — un Laravel temporairement indisponible ne doit
 * jamais faire planter ce process. Utilise fetch natif (Node 18+).
 */
async function notifyLaravel(event, payload) {
    try {
        const response = await fetch(`${config.laravelUrl}/api/whatsapp/internal/webhook`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...(config.apiKey ? { 'X-Api-Key': config.apiKey } : {}),
            },
            body: JSON.stringify({ event, payload }),
            signal: AbortSignal.timeout(5000),
        });
        if (!response.ok) {
            console.warn(`[Webhook] Laravel a répondu ${response.status} pour l'événement "${event}"`);
        }
    }
    catch (err) {
        console.warn(`[Webhook] Échec de notification Laravel pour "${event}" :`, err.message);
    }
}
// ─── WhatsApp Manager ─────────────────────────────────────────────────────────
const waManager = new WhatsAppManager();
waManager.on('qr', async (qr) => {
    try {
        const qrImage = await qrcode.toDataURL(qr);
        await notifyLaravel('qr', { qr: qrImage });
    }
    catch (e) {
        console.error('[Webhook] Erreur génération/envoi QR :', e);
    }
});
waManager.on('ready', () => {
    const status = waManager.getStatus();
    void notifyLaravel('ready', {
        status: 'connected',
        phone: status.user?.id?.split(':')[0] ?? null,
        displayName: status.user?.name ?? null,
        connectedAt: new Date().toISOString(),
    });
});
waManager.on('logged_out', () => {
    void notifyLaravel('logged_out', {});
});
waManager.on('status_update', (status) => {
    void notifyLaravel('status_update', status);
});
// ─── Résolution JID partagée (texte + fichier) ─────────────────────────────
/**
 * Résout un numéro nettoyé vers un JID WhatsApp existant, avec les mêmes
 * fallbacks béninois que l'implémentation d'origine. Factorisé ici pour
 * que /send-message et /send-file ne dupliquent pas cette logique.
 */
async function resolveJid(sock, cleanTo) {
    const jid = cleanTo.includes('@s.whatsapp.net') ? cleanTo : `${cleanTo}@s.whatsapp.net`;
    const waResults = await sock.onWhatsApp(jid);
    const result = waResults?.[0];
    if (result?.exists)
        return result.jid;
    if (cleanTo.startsWith('22901') && cleanTo.length === 13) {
        const fallbackJid = `229${cleanTo.substring(5)}@s.whatsapp.net`;
        const fb = (await sock.onWhatsApp(fallbackJid))?.[0];
        if (fb?.exists)
            return fb.jid;
    }
    if (cleanTo.startsWith('229') && !cleanTo.startsWith('22901') && cleanTo.length === 11) {
        const fallbackJid = `22901${cleanTo.substring(3)}@s.whatsapp.net`;
        const fb = (await sock.onWhatsApp(fallbackJid))?.[0];
        if (fb?.exists)
            return fb.jid;
    }
    return null;
}
// ─── REST : POST /send-message ────────────────────────────────────────────────
app.post('/send-message', rateLimit, requireApiKey, async (req, res) => {
    const { to, text, context, module } = req.body;
    if (typeof to !== 'string' || typeof text !== 'string') {
        res.status(400).json({ success: false, error: 'Paramètres invalides : to et text doivent être des chaînes' });
        return;
    }
    const toTrimmed = to.trim();
    const textTrimmed = text.trim();
    const contextStr = typeof context === 'string' ? context.trim().substring(0, 255) : null;
    const moduleStr = typeof module === 'string' ? module.trim().substring(0, 100) : null;
    if (!toTrimmed || !textTrimmed) {
        res.status(400).json({ success: false, error: 'Paramètres manquants : to et text sont requis' });
        return;
    }
    if (textTrimmed.length > 4096) {
        res.status(400).json({ success: false, error: 'Message trop long (max 4096 caractères)' });
        return;
    }
    if (!/^[+\d\s]+$/.test(toTrimmed)) {
        res.status(400).json({ success: false, error: 'Format de numéro invalide' });
        return;
    }
    console.log(`[Bridge] Demande d'envoi vers : ${toTrimmed.substring(0, 8)}... (module: ${moduleStr ?? 'inconnu'})`);
    const cleanTo = toTrimmed.replace(/[+\s\-]/g, '');
    // Journalisation systématique — créée AVANT la tentative d'envoi
    // pour que même un crash inattendu laisse une trace "sending" en base
    // plutôt qu'aucune trace du tout.
    let logId = null;
    try {
        logId = await createMessageLog({ recipient: cleanTo, message: textTrimmed, context: contextStr, module: moduleStr });
    }
    catch (err) {
        // Un échec de journalisation ne doit pas empêcher l'envoi réel
        console.error('[MessageStore] Échec création du log — envoi tenté quand même :', err);
    }
    try {
        const sock = waManager.getSocket();
        if (!sock) {
            if (logId)
                await markMessageFailed(logId, 'Service WhatsApp non initialisé');
            res.status(503).json({ success: false, error: 'Service WhatsApp non initialisé' });
            return;
        }
        const jid = await resolveJid(sock, cleanTo);
        if (!jid) {
            if (logId)
                await markMessageFailed(logId, 'Numéro non enregistré sur WhatsApp');
            res.status(404).json({ success: false, error: 'Numéro non enregistré sur WhatsApp' });
            return;
        }
        await waManager.sendMessage(jid, textTrimmed);
        if (logId)
            await markMessageSent(logId);
        res.json({ success: true });
    }
    catch (error) {
        const message = error instanceof Error ? error.message : 'Erreur interne';
        console.error('[Bridge] Erreur envoi :', error);
        if (logId)
            await markMessageFailed(logId, message);
        res.status(500).json({
            success: false,
            error: config.isProd ? 'Erreur lors de l\'envoi du message' : message,
        });
    }
});
// ─── REST : POST /send-file (NOUVEAU) ──────────────────────────────────────────
app.post('/send-file', rateLimit, requireApiKey, async (req, res) => {
    const { to, fileUrl, fileName, mimeType, caption, context, module, fileDisk, filePath } = req.body;
    if (typeof to !== 'string' || typeof fileUrl !== 'string' || typeof fileName !== 'string' || typeof mimeType !== 'string') {
        res.status(400).json({ success: false, error: 'Paramètres invalides : to, fileUrl, fileName, mimeType sont requis' });
        return;
    }
    const toTrimmed = to.trim();
    const captionStr = typeof caption === 'string' ? caption.trim().substring(0, 1024) : '';
    const contextStr = typeof context === 'string' ? context.trim().substring(0, 255) : null;
    const moduleStr = typeof module === 'string' ? module.trim().substring(0, 100) : null;
    if (!toTrimmed || !/^[+\d\s]+$/.test(toTrimmed)) {
        res.status(400).json({ success: false, error: 'Format de numéro invalide' });
        return;
    }
    console.log(`[Bridge] Demande d'envoi fichier "${fileName}" vers : ${toTrimmed.substring(0, 8)}... (module: ${moduleStr ?? 'inconnu'})`);
    const cleanTo = toTrimmed.replace(/[+\s\-]/g, '');
    const mediaType = mimeType.startsWith('image/') ? 'image' : 'document';
    let logId = null;
    try {
        logId = await createMessageLog({
            recipient: cleanTo,
            message: captionStr,
            context: contextStr,
            module: moduleStr,
            fileName,
            mediaType,
            fileDisk: typeof fileDisk === 'string' ? fileDisk : null,
            filePath: typeof filePath === 'string' ? filePath : null,
        });
    }
    catch (err) {
        console.error('[MessageStore] Échec création du log (fichier) — envoi tenté quand même :', err);
    }
    try {
        const sock = waManager.getSocket();
        if (!sock) {
            if (logId)
                await markMessageFailed(logId, 'Service WhatsApp non initialisé');
            res.status(503).json({ success: false, error: 'Service WhatsApp non initialisé' });
            return;
        }
        const jid = await resolveJid(sock, cleanTo);
        if (!jid) {
            if (logId)
                await markMessageFailed(logId, 'Numéro non enregistré sur WhatsApp');
            res.status(404).json({ success: false, error: 'Numéro non enregistré sur WhatsApp' });
            return;
        }
        await waManager.sendFileMessage(jid, { url: fileUrl, fileName, mimetype: mimeType, caption: captionStr });
        if (logId)
            await markMessageSent(logId);
        res.json({ success: true });
    }
    catch (error) {
        const message = error instanceof Error ? error.message : 'Erreur interne';
        console.error('[Bridge] Erreur envoi fichier :', error);
        if (logId)
            await markMessageFailed(logId, message);
        res.status(500).json({
            success: false,
            error: config.isProd ? 'Erreur lors de l\'envoi du fichier' : message,
        });
    }
});
// ─── REST : GET /status ───────────────────────────────────────────────────────
app.get('/status', requireApiKey, (_req, res) => {
    res.json(waManager.getStatus());
});
// ─── REST : DELETE /logout ────────────────────────────────────────────────────
app.delete('/logout', requireApiKey, async (_req, res) => {
    try {
        await waManager.logout();
        res.json({ success: true, message: 'Session supprimée — QR code requis au prochain démarrage' });
    }
    catch (error) {
        console.error('[Bridge] Erreur logout :', error);
        res.status(500).json({ success: false, error: 'Erreur lors de la déconnexion' });
    }
});
// ─── Route fallback — 404 propre ──────────────────────────────────────────────
app.use((_req, res) => {
    res.status(404).json({ success: false, error: 'Route introuvable' });
});
// ─── Gestion des erreurs non catchées ─────────────────────────────────────────
process.on('uncaughtException', (err) => {
    console.error('[Process] Exception non catchée :', err);
});
process.on('unhandledRejection', (reason) => {
    console.error('[Process] Promise rejetée non gérée :', reason);
});
// ─── Démarrage ────────────────────────────────────────────────────────────────
async function start() {
    await assertDbConnection();
    app.listen(config.port, '127.0.0.1', async () => {
        console.log('');
        console.log('╔══════════════════════════════════════════════════╗');
        console.log(`║  WhatsApp Bridge   http://127.0.0.1:${config.port}         ║`);
        console.log(`║  Environnement : ${config.isProd ? 'production  ' : 'local       '}              ║`);
        console.log(`║  API Key        : ${config.apiKey ? '✅ configurée  ' : '⚠️  non définie'}           ║`);
        console.log(`║  Webhook Laravel: ${config.laravelUrl}`);
        console.log('╚══════════════════════════════════════════════════╝');
        console.log('');
        await waManager.init();
    });
}
start().catch((err) => {
    console.error('[Bridge] Échec critique au démarrage :', err);
    process.exit(1);
});
