self.addEventListener("install", (e) => {
  e.waitUntil(caches.open("kat-v1").then((c) => c.addAll([
    "./",
    "./pwa/manifest.json"
  ])));
});

self.addEventListener("fetch", (e) => {
  if (e.request.mode === 'navigate') {
    e.respondWith(fetch(e.request));
  } else {
    e.respondWith(
      caches.match(e.request).then((r) => r || fetch(e.request))
    );
  }
});