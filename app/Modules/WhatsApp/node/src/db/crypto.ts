/**
 * Chiffrement AES-256-GCM pour les données de session Baileys stockées en DB.
 *
 * Pattern repris de D:\convessa\whatsapp-service (crypto.ts) :
 *   - clé dérivée par SHA-256 de WA_SESSION_ENCRYPTION_KEY (peu importe sa
 *     longueur d'origine, on obtient toujours 32 octets exploitables par AES-256)
 *   - IV aléatoire (12 octets, recommandé pour GCM) généré à CHAQUE opération
 *   - le tag d'authentification GCM est stocké séparément et est OBLIGATOIRE
 *     pour déchiffrer (garantit l'intégrité — toute donnée altérée en base
 *     fait échouer le déchiffrement plutôt que de renvoyer des données corrompues)
 */

import crypto from 'crypto'
import { config } from '../config.js'

const ALGORITHM = 'aes-256-gcm'
const IV_LENGTH  = 12

function deriveKey(): Buffer {
    return crypto.createHash('sha256').update(config.sessionEncryptionKey).digest()
}

const key = deriveKey()

export interface EncryptedPayload {
    iv: string
    tag: string
    data: string
}

export function encrypt(plaintext: string): EncryptedPayload {
    const iv = crypto.randomBytes(IV_LENGTH)
    const cipher = crypto.createCipheriv(ALGORITHM, key, iv)

    const encrypted = Buffer.concat([cipher.update(plaintext, 'utf-8'), cipher.final()])
    const tag = cipher.getAuthTag()

    return {
        iv:   iv.toString('hex'),
        tag:  tag.toString('hex'),
        data: encrypted.toString('hex'),
    }
}

export function decrypt(payload: EncryptedPayload): string {
    const iv     = Buffer.from(payload.iv, 'hex')
    const tag    = Buffer.from(payload.tag, 'hex')
    const data   = Buffer.from(payload.data, 'hex')

    const decipher = crypto.createDecipheriv(ALGORITHM, key, iv)
    decipher.setAuthTag(tag)

    const decrypted = Buffer.concat([decipher.update(data), decipher.final()])
    return decrypted.toString('utf-8')
}
