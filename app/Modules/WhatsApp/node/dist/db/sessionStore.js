/**
 * useDbAuthState — remplace useMultiFileAuthState de Baileys.
 *
 * Reproduit exactement la même granularité que le stockage fichier
 * (un "fichier" = une ligne wa_session_keys, un par (type, id)), mais
 * chiffrée en base au lieu d'être en clair sur disque.
 *
 * NOTE SUR LES TYPES : les types Signal exacts (SignalDataTypeMap, etc.)
 * dépendent de la version exacte de Baileys utilisée (fork local du
 * projet). Les types ci-dessous sont volontairement souples (any aux
 * points de contact avec Baileys) pour rester compatibles quelle que
 * soit la version finale vendorisée — à resserrer une fois le fork
 * intégré si souhaité.
 */
import { initAuthCreds, BufferJSON, proto } from '../../vendor/baileys/lib/index.js';
import { pool } from './connection.js';
import { encrypt, decrypt } from './crypto.js';
export async function useDbAuthState(sessionId = 'primary-session') {
    // ─── Chargement initial des creds ─────────────────────────────────────
    async function readCreds() {
        const [rows] = await pool.query('SELECT creds_iv, creds_tag, creds_data FROM wa_sessions WHERE session_id = ? LIMIT 1', [sessionId]);
        const row = rows[0];
        if (!row?.creds_data || !row.creds_iv || !row.creds_tag) {
            return initAuthCreds();
        }
        try {
            const json = decrypt({ iv: row.creds_iv, tag: row.creds_tag, data: row.creds_data });
            return JSON.parse(json, BufferJSON.reviver);
        }
        catch (err) {
            console.error('[SessionStore] Échec déchiffrement des creds — réinitialisation d\'une session vierge', err);
            return initAuthCreds();
        }
    }
    let creds = await readCreds();
    async function saveCreds() {
        const json = JSON.stringify(creds, BufferJSON.replacer);
        const enc = encrypt(json);
        await pool.query(`INSERT INTO wa_sessions (session_id, creds_iv, creds_tag, creds_data, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                creds_iv = VALUES(creds_iv),
                creds_tag = VALUES(creds_tag),
                creds_data = VALUES(creds_data),
                updated_at = NOW()`, [sessionId, enc.iv, enc.tag, enc.data]);
    }
    // ─── Magasin de clés Signal (pre-keys, sessions, sender-keys...) ──────
    async function get(type, ids) {
        const result = {};
        if (ids.length === 0)
            return result;
        const placeholders = ids.map(() => '?').join(',');
        const [rows] = await pool.query(`SELECT key_id, value_iv, value_tag, value_data FROM wa_session_keys
             WHERE session_id = ? AND key_type = ? AND key_id IN (${placeholders})`, [sessionId, type, ...ids]);
        for (const row of rows) {
            try {
                const json = decrypt({ iv: row.value_iv, tag: row.value_tag, data: row.value_data });
                let value = JSON.parse(json, BufferJSON.reviver);
                // Reproduit exactement le comportement de useMultiFileAuthState :
                // les clés app-state-sync-key doivent être des instances protobuf,
                // pas de simples objets JSON, car Baileys appelle .encode() dessus.
                if (type === 'app-state-sync-key' && value) {
                    value = proto.Message.AppStateSyncKeyData.fromObject(value);
                }
                result[row.key_id] = value;
            }
            catch (err) {
                console.error(`[SessionStore] Échec déchiffrement clé ${type}/${row.key_id} — ignorée`, err);
            }
        }
        return result;
    }
    async function set(data) {
        for (const type of Object.keys(data)) {
            for (const id of Object.keys(data[type])) {
                const value = data[type][id];
                if (value === null || value === undefined) {
                    await pool.query('DELETE FROM wa_session_keys WHERE session_id = ? AND key_type = ? AND key_id = ?', [sessionId, type, id]);
                    continue;
                }
                const json = JSON.stringify(value, BufferJSON.replacer);
                const enc = encrypt(json);
                await pool.query(`INSERT INTO wa_session_keys (session_id, key_type, key_id, value_iv, value_tag, value_data, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                        value_iv = VALUES(value_iv),
                        value_tag = VALUES(value_tag),
                        value_data = VALUES(value_data),
                        updated_at = NOW()`, [sessionId, type, id, enc.iv, enc.tag, enc.data]);
            }
        }
    }
    return {
        state: { creds, keys: { get, set } },
        saveCreds,
    };
}
// ─── Métadonnées de connexion (onglet admin "Connexion") ──────────────────
export async function updateSessionMeta(sessionId, meta) {
    await pool.query(`INSERT INTO wa_sessions (session_id, phone, display_name, status, connected_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            phone = VALUES(phone),
            display_name = VALUES(display_name),
            status = VALUES(status),
            connected_at = COALESCE(VALUES(connected_at), connected_at),
            updated_at = NOW()`, [sessionId, meta.phone ?? null, meta.displayName ?? null, meta.status, meta.connectedAt ?? null]);
}
// ─── Suppression complète (logout / session révoquée) ─────────────────────
export async function deleteSessionData(sessionId) {
    await pool.query('DELETE FROM wa_session_keys WHERE session_id = ?', [sessionId]);
    await pool.query(`UPDATE wa_sessions
         SET creds_iv = NULL, creds_tag = NULL, creds_data = NULL,
             phone = NULL, display_name = NULL, status = 'disconnected',
             connected_at = NULL, updated_at = NOW()
         WHERE session_id = ?`, [sessionId]);
}
