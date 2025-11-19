/**
 * Pending Registrations Badge
 * Shows count of pending registrations in sidebar
 * Load this on all admin pages
 */
(function() {
    function updatePendingBadge() {
        fetch('/php/api/admin/pending-registrations.php?status=all')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const pending = data.data.counts.pending;
                    const badge = document.getElementById('pendingBadge');
                    if (badge && pending > 0) {
                        badge.textContent = pending;
                        badge.style.display = 'inline-block';
                    } else if (badge) {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(err => console.log('Badge update skipped:', err.message));
    }
    // Update badge when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updatePendingBadge);
    } else {
        updatePendingBadge();
    }
    // Update badge every 30 seconds
    setInterval(updatePendingBadge, 30000);
})();
