/**
 * WhatsAppManager — Gestionnaire de connexion Baileys
 *
 * MODIFIÉ (15/08/2026) : useMultiFileAuthState (fichiers sur disque, dossier
 * auth/) remplacé par useDbAuthState (creds + clés Signal chiffrés
 * AES-256-GCM dans wa_sessions / wa_session_keys). Plus AUCUN accès
 * filesystem pour les sessions — conforme à la demande d'origine.
 *
 * Comportements conservés à l'identique :
 *  - Backoff exponentiel sur les reconnexions (1s, 2s, 4s, 8s, 16s, 30s)
 *  - Guard isInitializing contre la double initialisation (race condition)
 *  - Nettoyage de l'ancien socket avant d'en créer un nouveau
 *  - setMaxListeners(50)
 */
import makeWASocket, { DisconnectReason, fetchLatestBaileysVersion, } from '../vendor/baileys/lib/index.js';
import pino from 'pino';
import { EventEmitter } from 'events';
import { useDbAuthState, updateSessionMeta, deleteSessionData } from './db/sessionStore.js';
const logger = pino({ level: 'silent' });
// Délais de reconnexion : 1s, 2s, 4s, 8s, 16s, 30s max
const RECONNECT_DELAYS = [1_000, 2_000, 4_000, 8_000, 16_000, 30_000];
export class WhatsAppManager extends EventEmitter {
    sock = null;
    sessionId;
    isConnected = false;
    isInitializing = false; // guard contre la double init
    reconnectAttempt = 0; // compteur pour le backoff
    constructor(sessionId = 'primary-session') {
        super();
        // On accepte jusqu'à 50 écouteurs (webhook Laravel + éventuels
        // consommateurs internes futurs) avant l'avertissement Node.js
        this.setMaxListeners(50);
        this.sessionId = sessionId;
        // Plus de dossier auth/ à créer — tout vit en base de données.
    }
    // ─── Initialisation ───────────────────────────────────────────────────────
    async init() {
        // Guard : si une initialisation est déjà en cours, ne pas en lancer une deuxième
        if (this.isInitializing) {
            console.log('[Baileys] Initialisation déjà en cours — ignoré');
            return;
        }
        this.isInitializing = true;
        try {
            if (this.sock) {
                this._closeSocket(this.sock);
                this.sock = null;
            }
            const { state, saveCreds } = await useDbAuthState(this.sessionId);
            const { version } = await fetchLatestBaileysVersion();
            console.log(`[Baileys] Version : ${version.join('.')}`);
            this.sock = makeWASocket({
                version,
                logger,
                auth: state,
                syncFullHistory: false,
            });
            // ── Événements de connexion ────────────────────────────────────────
            this.sock.ev.on('connection.update', async (update) => {
                const { connection, lastDisconnect, qr } = update;
                if (qr) {
                    this.isConnected = false;
                    this.emit('qr', qr);
                }
                if (connection === 'close') {
                    this.isConnected = false;
                    const statusCode = lastDisconnect?.error?.output?.statusCode;
                    const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
                    console.log(`[Baileys] Connexion fermée — code: ${statusCode ?? 'inconnu'}` +
                        ` | reconnexion: ${shouldReconnect}`);
                    await updateSessionMeta(this.sessionId, { status: 'disconnected' });
                    this.emit('status_update', { status: shouldReconnect ? 'connecting' : 'disconnected' });
                    if (shouldReconnect) {
                        const delay = RECONNECT_DELAYS[Math.min(this.reconnectAttempt, RECONNECT_DELAYS.length - 1)] ?? 30_000;
                        this.reconnectAttempt++;
                        console.log(`[Baileys] Tentative #${this.reconnectAttempt} dans ${delay / 1000}s...`);
                        setTimeout(() => this.init(), delay);
                    }
                    else {
                        // Session révoquée côté WhatsApp → reset complet en DB
                        console.log('[Baileys] Session révoquée — nettoyage DB et nouveau QR');
                        this.sock = null;
                        await deleteSessionData(this.sessionId);
                        this.emit('logged_out');
                        setTimeout(() => this.init(), 500);
                    }
                }
                else if (connection === 'open') {
                    this.isConnected = true;
                    this.reconnectAttempt = 0;
                    console.log('[Baileys] Connexion ouverte — WhatsApp connecté ✅');
                    await updateSessionMeta(this.sessionId, {
                        phone: this.sock?.user?.id?.split(':')[0] ?? null,
                        displayName: this.sock?.user?.name ?? null,
                        status: 'connected',
                        connectedAt: new Date(),
                    });
                    this.emit('ready');
                }
            });
            this.sock.ev.on('creds.update', saveCreds);
            this.sock.ev.on('messages.upsert', (m) => {
                if (m.type === 'notify') {
                    this.emit('message', m);
                }
            });
        }
        catch (err) {
            console.error('[Baileys] Erreur lors de l\'initialisation :', err);
            const delay = RECONNECT_DELAYS[Math.min(this.reconnectAttempt, RECONNECT_DELAYS.length - 1)] ?? 30_000;
            this.reconnectAttempt++;
            console.log(`[Baileys] Réessai de l'init dans ${delay / 1000}s...`);
            setTimeout(() => this.init(), delay);
        }
        finally {
            this.isInitializing = false;
        }
    }
    // ─── Déconnexion ──────────────────────────────────────────────────────────
    async logout() {
        this.isConnected = false;
        if (this.sock) {
            try {
                await this.sock.logout();
            }
            catch {
                // Session déjà invalide ou réseau coupé — on nettoie quand même
            }
            this._closeSocket(this.sock);
            this.sock = null;
        }
        await deleteSessionData(this.sessionId);
        this.emit('logged_out');
        setTimeout(() => this.init(), 500);
    }
    // ─── Envoi de message ─────────────────────────────────────────────────────
    async sendMessage(remoteJid, text, timeoutMs = 15_000) {
        if (!this.sock) {
            throw new Error('WhatsApp socket non initialisé');
        }
        if (!this.isConnected) {
            throw new Error('WhatsApp non connecté — scannez le QR code');
        }
        const send = this.sock.sendMessage(remoteJid, { text });
        const timeout = new Promise((_, reject) => setTimeout(() => reject(new Error(`Timeout : pas de réponse après ${timeoutMs}ms`)), timeoutMs));
        return Promise.race([send, timeout]);
    }
    // ─── Envoi de fichier (document ou image) ─────────────────────────────────
    /**
     * Envoie un fichier via une URL (Baileys télécharge lui-même —
     * pas besoin de charger le fichier entier en mémoire ici).
     * type 'image' → aperçu inline WhatsApp ; sinon 'document' générique.
     */
    async sendFileMessage(remoteJid, opts, timeoutMs = 60_000) {
        if (!this.sock) {
            throw new Error('WhatsApp socket non initialisé');
        }
        if (!this.isConnected) {
            throw new Error('WhatsApp non connecté — scannez le QR code');
        }
        const isImage = opts.mimetype.startsWith('image/');
        const content = isImage
            ? { image: { url: opts.url }, caption: opts.caption || undefined }
            : {
                document: { url: opts.url },
                fileName: opts.fileName,
                mimetype: opts.mimetype,
                caption: opts.caption || undefined,
            };
        const send = this.sock.sendMessage(remoteJid, content);
        const timeout = new Promise((_, reject) => setTimeout(() => reject(new Error(`Timeout : pas de réponse après ${timeoutMs}ms`)), timeoutMs));
        return Promise.race([send, timeout]);
    }
    // ─── Statut ───────────────────────────────────────────────────────────────
    getStatus() {
        if (this.isConnected && this.sock?.user) {
            return {
                status: 'connected',
                user: {
                    id: this.sock.user.id,
                    name: this.sock.user.name ?? '',
                },
            };
        }
        return { status: 'disconnected' };
    }
    getSocket() {
        return this.sock;
    }
    // ─── Utilitaire privé ─────────────────────────────────────────────────────
    _closeSocket(sock) {
        const events = [
            'connection.update', 'creds.update', 'messaging-history.set',
            'messaging-history.status', 'chats.upsert', 'chats.update',
            'chats.delete', 'chats.lock', 'lid-mapping.update', 'presence.update',
            'contacts.upsert', 'contacts.update', 'messages.delete',
            'messages.update', 'messages.media-update', 'messages.upsert',
            'messages.reaction', 'message-receipt.update', 'message-capping.update',
            'groups.upsert', 'groups.update', 'group-participants.update',
            'group.join-request', 'group.member-tag.update', 'blocklist.set',
            'blocklist.update', 'call', 'labels.edit', 'labels.association',
            'newsletter.reaction', 'newsletter.view', 'newsletter-participants.update',
            'newsletter-settings.update', 'settings.update',
        ];
        for (const event of events) {
            try {
                sock.ev.removeAllListeners(event);
            }
            catch { /* ignoré */ }
        }
        try {
            sock.end(undefined);
        }
        catch { /* déjà fermé */ }
    }
}
