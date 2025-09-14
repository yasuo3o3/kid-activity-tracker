self.addEventListener("install", (e) => {
  e.waitUntil(caches.open("kat-v1").then((c) => c.addAll([
    "./",
    "./pwa/manifest.json"
  ])));
});

self.addEventListener("fetch", (e) => {
  const url = new URL(e.request.url);

  if (e.request.mode === 'navigate') {
    e.respondWith(
      caches.match('./').then((response) => {
        if (response) {
          const newUrl = new URL(response.url);
          newUrl.search = url.search;
          return fetch(newUrl.toString()).catch(() => response);
        }
        return fetch(e.request);
      })
    );
  } else {
    e.respondWith(
      caches.match(e.request).then((r) => r || fetch(e.request))
    );
  }
});