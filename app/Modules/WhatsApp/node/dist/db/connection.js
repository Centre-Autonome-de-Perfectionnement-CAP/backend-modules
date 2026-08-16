/**
 * Pool de connexions MySQL partagé — mêmes credentials que Laravel (DB_*
 * du .env unique de backend-modules/). Aucune configuration séparée.
 */
import mysql from 'mysql2/promise';
import { config } from '../config.js';
export const pool = mysql.createPool({
    host: config.db.host,
    port: config.db.port,
    database: config.db.database,
    user: config.db.user,
    password: config.db.password,
    waitForConnections: true,
    connectionLimit: 10,
    charset: 'utf8mb4',
});
/**
 * Vérifie que la connexion DB fonctionne. Appelé une fois au démarrage
 * pour échouer vite et bruyamment si les credentials sont mauvais, plutôt
 * que de découvrir le problème au premier message envoyé.
 */
export async function assertDbConnection() {
    const conn = await pool.getConnection();
    try {
        await conn.ping();
        console.log('[DB] Connexion MySQL OK');
    }
    finally {
        conn.release();
    }
}
