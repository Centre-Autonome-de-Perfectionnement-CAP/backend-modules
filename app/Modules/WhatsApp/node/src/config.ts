/**
 * Configuration — charge l'UNIQUE .env de backend-modules/.
 *
 * Pas de symlink (fragile au déploiement, casse différemment sous
 * Windows/Linux). Pas de chemin relatif en dur non plus (fragile si la
 * profondeur du dossier compilé change). À la place : recherche ascendante
 * depuis ce fichier jusqu'à trouver un .env qui a la signature d'un projet
 * Laravel (contient DB_CONNECTION). Échoue bruyamment au démarrage si
 * introuvable plutôt que de démarrer avec une config vide en silence.
 *
 * Ce module ne dépend QUE du .env de backend-modules/. Il n'y a pas de
 * node/.env séparé — conformément à la demande "un seul .env bien structuré,
 * le moins de configuration possible".
 */

import fs   from 'fs'
import path from 'path'
import dotenv from 'dotenv'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname   = path.dirname(__filename)

const MAX_LEVELS_UP = 10

function findLaravelEnvPath(startDir: string): string {
    let dir = startDir

    for (let i = 0; i < MAX_LEVELS_UP; i++) {
        const candidate = path.join(dir, '.env')
        if (fs.existsSync(candidate)) {
            const content = fs.readFileSync(candidate, 'utf-8')
            // Signature Laravel — évite de charger un .env qui ne serait pas
            // celui de backend-modules/ si un autre traînait plus haut.
            if (content.includes('DB_CONNECTION') || content.includes('APP_KEY')) {
                return candidate
            }
        }

        const parent = path.dirname(dir)
        if (parent === dir) break // racine du filesystem atteinte
        dir = parent
    }

    throw new Error(
        `[Config] Impossible de trouver le .env de backend-modules/ en remontant ` +
        `depuis ${startDir} (${MAX_LEVELS_UP} niveaux max). ` +
        `Le service WhatsApp Node doit vivre sous backend-modules/app/Modules/WhatsApp/node/.`
    )
}

const envPath = findLaravelEnvPath(__dirname)
dotenv.config({ path: envPath })

console.log(`[Config] .env chargé depuis : ${envPath}`)

// ─── Variables exploitées ─────────────────────────────────────────────────

function required(name: string): string {
    const value = process.env[name]
    if (!value) {
        throw new Error(`[Config] Variable requise manquante dans .env : ${name}`)
    }
    return value
}

export const config = {
    // Réseau
    port:       Number(process.env['WHATSAPP_NODE_PORT'] ?? 3005),
    nodeEnv:    process.env['NODE_ENV'] ?? 'production',
    isProd:     process.env['NODE_ENV'] === 'production',

    // Sécurité — partagée avec Laravel (un seul secret, deux sens d'usage)
    apiKey:     (process.env['WHATSAPP_BRIDGE_API_KEY'] ?? '').trim(),

    // Chiffrement des sessions en DB
    sessionEncryptionKey: process.env['WA_SESSION_ENCRYPTION_KEY'] ?? '',

    // Démarrage automatique (lu ici uniquement à titre indicatif dans les logs —
    // la décision de lancer le process appartient à Laravel/Supervisor, pas au Node lui-même)
    autoStart:  process.env['WA_AUTO_START'] === 'true',

    // Laravel — pour le relais webhook QR/statut
    laravelUrl: (process.env['APP_URL'] ?? 'http://127.0.0.1').replace(/\/+$/, ''),

    // Base de données — on réutilise TELS QUELS les noms de variables Laravel,
    // pas de renommage DB_NAME/DB_USER/DB_PASS qui n'apporterait rien.
    db: {
        host:     process.env['DB_HOST'] ?? '127.0.0.1',
        port:     Number(process.env['DB_PORT'] ?? 3306),
        database: process.env['DB_DATABASE'] ?? '',
        user:     process.env['DB_USERNAME'] ?? '',
        password: process.env['DB_PASSWORD'] ?? '',
    },
}

// ─── Validation au démarrage — échec rapide et explicite ──────────────────

const missing: string[] = []
if (!config.sessionEncryptionKey) missing.push('WA_SESSION_ENCRYPTION_KEY')
if (!config.db.database)          missing.push('DB_DATABASE')
if (!config.db.user)              missing.push('DB_USERNAME')

if (config.isProd && !config.apiKey) {
    missing.push('WHATSAPP_BRIDGE_API_KEY (obligatoire en production)')
}

if (missing.length > 0) {
    console.error(`[Config] ❌ Variables manquantes ou invalides : ${missing.join(', ')}`)
    process.exit(1)
}

if (!config.isProd && !config.apiKey) {
    console.warn('[Security] ⚠️  WHATSAPP_BRIDGE_API_KEY non définie — API non protégée (mode local uniquement)')
}
