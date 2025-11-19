/**
 * Admin Badge Loader
 * Updates pending approval and registration badges on all admin pages
 */

(function() {
    'use strict';

    // Update approval badge
    function updateApprovalBadge() {
        fetch('/php/api/admin/approval-history.php?filter=pending')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('approvalsBadge');
                    if (badge) {
                        const count = data.count || 0;
                        if (count > 0) {
                            badge.textContent = count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(err => console.error('[BADGE] Approval badge error:', err));
    }

    // Update registration badge
    function updateRegistrationBadge() {
        fetch('/php/api/admin/pending-registrations.php?status=pending')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('pendingBadge') || document.getElementById('pendingRegBadge');
                    if (badge) {
                        const registrations = data.data?.registrations || [];
                        const pending = registrations.filter(r => r.registration_status === 'pending');
                        const count = pending.length;
                        if (count > 0) {
                            badge.textContent = count;
                            badge.style.display = 'inline-block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                }
            })
            .catch(err => console.error('[BADGE] Registration badge error:', err));
    }

    // Update both badges
    function updateAllBadges() {
        updateApprovalBadge();
        updateRegistrationBadge();
    }

    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateAllBadges);
    } else {
        updateAllBadges();
    }

    // Refresh every 30 seconds
    setInterval(updateAllBadges, 30000);

    // Export for manual refresh
    window.updateAdminBadges = updateAllBadges;
})();

