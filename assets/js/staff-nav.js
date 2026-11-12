// ClinicFlow - Accessible Staff Portal Navigation & Mobile Drawer
(function() {
    function initStaffNav() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const backdrop = document.getElementById('mobile-sidebar-backdrop');
        const sidebar = document.getElementById('staff-sidebar');

        if (!sidebar) return;

        function toggleSidebar(open) {
            const isOpen = open !== undefined ? open : !sidebar.classList.contains('open');
            sidebar.classList.toggle('open', isOpen);
            if (backdrop) backdrop.classList.toggle('active', isOpen);
            if (menuBtn) menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            document.body.classList.toggle('nav-open', isOpen);

            if (isOpen && closeBtn) {
                closeBtn.focus();
            } else if (!isOpen && menuBtn) {
                menuBtn.focus();
            }
        }

        if (menuBtn) {
            menuBtn.addEventListener('click', () => toggleSidebar());
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', () => toggleSidebar(false));
        }

        if (backdrop) {
            backdrop.addEventListener('click', () => toggleSidebar(false));
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                toggleSidebar(false);
            }
        });

        // Close sidebar on navigation item click (mobile)
        sidebar.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar(false);
                }
            });
        });

        // Sync mobile avatar & name from desktop sidebar
        const sidebarAvatar = document.getElementById('sidebar-avatar') || document.getElementById('user-avatar');
        const mobileAvatar = document.getElementById('mobile-avatar');
        if (sidebarAvatar && mobileAvatar) {
            const observer = new MutationObserver(() => {
                if (sidebarAvatar.textContent && sidebarAvatar.textContent !== '...' && sidebarAvatar.textContent !== 'Loading...') {
                    mobileAvatar.textContent = sidebarAvatar.textContent.trim().charAt(0).toUpperCase();
                }
            });
            observer.observe(sidebarAvatar, { childList: true, characterData: true, subtree: true });
            if (sidebarAvatar.textContent && sidebarAvatar.textContent !== '...' && sidebarAvatar.textContent !== 'Loading...') {
                mobileAvatar.textContent = sidebarAvatar.textContent.trim().charAt(0).toUpperCase();
            }
        }

        // Dynamic clinic branding sync (Option C)
        try {
            const cached = sessionStorage.getItem('clinic_name');
            if (cached) {
                document.querySelectorAll('.brand-title').forEach(el => el.textContent = cached);
            }
            fetch('../backend/get_public_settings.php')
                .then(r => r.json())
                .then(d => {
                    if (d?.success && d?.settings?.clinic_name) {
                        sessionStorage.setItem('clinic_name', d.settings.clinic_name);
                        document.querySelectorAll('.brand-title').forEach(el => el.textContent = d.settings.clinic_name);
                    }
                })
                .catch(() => {});
        } catch(e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStaffNav);
    } else {
        initStaffNav();
    }
})();

