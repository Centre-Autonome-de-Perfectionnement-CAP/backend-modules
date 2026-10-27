/*
 * Service worker de l'espace stagiaire (portée : /espace-stagiaire).
 *
 *  - pages : réseau d'abord (toujours la dernière version) ; hors connexion,
 *    la dernière page reçue, sinon un message clair ;
 *  - fichiers /assets/ (noms versionnés) : cache, puis réseau ;
 *  - API et tout le reste : jamais interceptés (pointage, présences et
 *    rapports sont toujours lus en direct).
 */
const VERSION = 'stg-v1'
const SHELL = `${VERSION}-shell`
const ASSETS = `${VERSION}-assets`
const MAX_ASSETS = 80

const OFFLINE_HTML = `<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hors connexion — Espace stagiaire</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f8fafc;color:#0f172a;padding:24px;box-sizing:border-box}
main{max-width:360px;text-align:center}h1{font-size:1.2rem;margin:0 0 8px}p{color:#475569;line-height:1.5}
button{margin-top:12px;height:44px;padding:0 20px;border:0;border-radius:12px;background:#326761;color:#fff;font-weight:600;font-size:15px}</style></head>
<body><main><h1>Vous êtes hors connexion</h1><p>Le pointage et vos informations ont besoin d'Internet. Vérifiez votre connexion puis réessayez.</p>
<button onclick="location.reload()">Réessayer</button></main></body></html>`

self.addEventListener('install', () => self.skipWaiting())

self.addEventListener('activate', event => {
  event.waitUntil((async () => {
    const keys = await caches.keys()
    await Promise.all(keys.filter(k => k.startsWith('stg-') && !k.startsWith(VERSION)).map(k => caches.delete(k)))
    await self.clients.claim()
  })())
})

const trim = async () => {
  const cache = await caches.open(ASSETS)
  const keys = await cache.keys()
  for (let i = 0; i < keys.length - MAX_ASSETS; i++) await cache.delete(keys[i])
}

self.addEventListener('fetch', event => {
  const req = event.request
  if (req.method !== 'GET') return
  const url = new URL(req.url)
  if (url.origin !== self.location.origin || url.pathname.startsWith('/api/')) return

  if (req.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        const res = await fetch(req)
        if (res.ok) {
          const copy = res.clone()
          caches.open(SHELL).then(c => c.put('/espace-stagiaire', copy)).catch(() => undefined)
        }
        return res
      } catch {
        const cached = await caches.match('/espace-stagiaire', { cacheName: SHELL })
        return cached || new Response(OFFLINE_HTML, { headers: { 'Content-Type': 'text/html; charset=utf-8' } })
      }
    })())
    return
  }

  if (url.pathname.startsWith('/assets/')) {
    event.respondWith((async () => {
      const cached = await caches.match(req, { cacheName: ASSETS })
      if (cached) return cached
      const res = await fetch(req)
      if (res.ok && res.type === 'basic') {
        const copy = res.clone()
        caches.open(ASSETS).then(c => c.put(req, copy)).then(trim).catch(() => undefined)
      }
      return res
    })())
  }
})
