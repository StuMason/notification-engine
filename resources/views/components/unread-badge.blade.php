<span
    id="unread-badge"
    class="hidden min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-xs font-medium text-white"
    aria-label="Unread notifications"
></span>

<script>
    (function () {
        const badge = document.getElementById('unread-badge');
        if (!badge) return;

        function updateBadge() {
            fetch('/api/notifications/unread-count', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
            })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.classList.remove('hidden');
                    badge.classList.add('inline-flex');
                } else {
                    badge.classList.add('hidden');
                    badge.classList.remove('inline-flex');
                }
            })
            .catch(() => {});
        }

        updateBadge();
        setInterval(updateBadge, 30000);
    })();
</script>
