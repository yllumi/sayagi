<!-- Script Packages -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pinecone-router@7.5.x/dist/router.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="<?= asset_url('/page_theme/js/heroic.js') ?>"></script>
<script src="<?= asset_url('/page_theme/js/main.js') ?>"></script>

<script>
    let base_url = `<?= getenv('app.url') ?>`
    let api_url = `<?= getenv('api_url') ?? '' ?>`
    let enableSW = <?= (getenv('enable_sw') ?? 'false') === 'true' ? 'true' : 'false' ?>;

    if (!enableSW) {
        // Unregister existing service worker if previously registered
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker
                .getRegistrations()
                .then((registrations) => {
                    for (let registration of registrations) {
                        registration.unregister().then((success) => {
                            if (success) {
                                console.log("Service worker unregistered.");

                                // Clear all cache storage
                                if (caches) {
                                    caches.keys().then((names) => {
                                        for (let name of names) {
                                            caches.delete(name);
                                        }
                                    });
                                }
                            } else {
                                console.error("Service worker unregistration failed.");
                            }
                        });
                    }
                })
                .catch((err) => {
                    console.error("Error fetching service worker registrations:", err);
                });
        } else {
            console.debug("Service-worker not supported");
        }
        console.debug("Service-worker disabled by server configuration");
    } else {
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", () => {
                navigator.serviceWorker
                    .register(`/sw.js`)
                    .then((registration) => {
                        console.log("Service worker registered.");
                        swRegistration = registration;
                    })
                    .catch((err) => {
                        console.error("Service worker registration failed:", err);
                    });
            });
        } else {
            console.debug("Service-worker not supported");
        }

        async function subscribeToPush() {
            if (!swRegistration) {
                console.error("Service worker not yet registered");
                return;
            }

            try {
                const subscription = await swRegistration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey),
                });

                const formData = new FormData();
                formData.append("subscription", JSON.stringify(subscription));

                // Kirim menggunakan Heroic fetch
                const response = await $sayagi.fetch("/api/push/register", formData);
                alert(response.data.status);
            } catch (err) {
                console.error("Push subscription error:", err);
            }
        }
    }
</script>