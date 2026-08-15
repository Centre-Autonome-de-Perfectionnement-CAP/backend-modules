/**
 * Journal des messages envoyés — table wa_message_log.
 *
 * Le Node écrit ici à chaque tentative d'envoi (texte ET fichier). Laravel
 * LIT cette même table directement (même base de données) pour les onglets
 * admin "Messages envoyés" / "Messages échoués" / "Statistiques" (avec
 * ventilation par module), et pilote le retry en rappelant
 * POST /send-message ou /send-file via WhatsAppBridgeClient (le Node n'a
 * donc pas besoin de fonctions de lecture ici).
 */

import type { ResultSetHeader } from 'mysql2/promise'
import { pool } from './connection.js'

export interface MessageLogInput {
    recipient: string
    message: string                 // texte du message, ou légende si fichier
    context?: string | null
    module?: string | null          // détecté côté Laravel (WhatsAppBridgeClient), transmis tel quel
    fileName?: string | null
    mediaType?: 'text' | 'image' | 'document'
    fileDisk?: string | null
    filePath?: string | null
}

export async function createMessageLog(input: MessageLogInput): Promise<number> {
    const [result] = await pool.query<ResultSetHeader>(
        `INSERT INTO wa_message_log
            (recipient, message, context, module, file_name, media_type, file_disk, file_path,
             status, attempts, queued_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'sending', 1, NOW(), NOW(), NOW())`,
        [
            input.recipient,
            input.message,
            input.context ?? null,
            input.module ?? null,
            input.fileName ?? null,
            input.mediaType ?? 'text',
            input.fileDisk ?? null,
            input.filePath ?? null,
        ]
    )
    return result.insertId
}

export async function markMessageSent(id: number): Promise<void> {
    await pool.query(
        `UPDATE wa_message_log SET status = 'sent', sent_at = NOW(), updated_at = NOW() WHERE id = ?`,
        [id]
    )
}

export async function markMessageFailed(id: number, error: string): Promise<void> {
    await pool.query(
        `UPDATE wa_message_log SET status = 'failed', last_error = ?, updated_at = NOW() WHERE id = ?`,
        [error.substring(0, 2000), id]
    )
}
