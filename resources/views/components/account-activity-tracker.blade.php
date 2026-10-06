<script>
    (() => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!csrfToken) {
            console.error('Account activity tracking requires a CSRF token.');
            return;
        }

        let lastInteraction = Date.now();
        const markActive = () => {
            lastInteraction = Date.now();
        };

        ['click', 'keydown', 'scroll', 'mousemove', 'touchstart'].forEach((eventName) => {
            document.addEventListener(eventName, markActive, { passive: true });
        });

        window.setInterval(async () => {
            const idleFor = Date.now() - lastInteraction;

            if (document.visibilityState !== 'visible' || idleFor > 15 * 60 * 1000) {
                return;
            }

            try {
                const response = await fetch(@json(route('account.activity')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (!response.ok) {
                    console.error(`Account activity heartbeat failed: ${response.status}`);
                }
            } catch (error) {
                console.error('Account activity heartbeat failed.', error);
            }
        }, 5 * 60 * 1000);
    })();
</script>
