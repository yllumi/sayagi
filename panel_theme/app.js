(() => {
    'use strict';

    const sidebar = document.getElementById('sidebar');
    const MOBILE_BREAKPOINT = 1200;

    // =========================================
    // Helpers
    // =========================================
    const isMobile = () => window.innerWidth < MOBILE_BREAKPOINT;

    // =========================================
    // Sidebar — show / hide
    // =========================================
    function showSidebar() {
        sidebar.classList.add('active');
        sidebar.classList.remove('inactive');
        if (isMobile()) createBackdrop();
    }

    function hideSidebar() {
        sidebar.classList.remove('active');
        sidebar.classList.add('inactive');
        removeBackdrop();
    }

    function toggleSidebar() {
        if (sidebar.classList.contains('inactive')) {
            showSidebar();
        } else {
            hideSidebar();
        }
    }

    // =========================================
    // Backdrop (mobile only)
    // =========================================
    function createBackdrop() {
        if (document.querySelector('.sidebar-backdrop')) return;
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        backdrop.addEventListener('click', hideSidebar);
        document.body.appendChild(backdrop);
    }

    function removeBackdrop() {
        const backdrop = document.querySelector('.sidebar-backdrop');
        if (backdrop) backdrop.remove();
    }

    // =========================================
    // Submenu accordion
    // =========================================
    function toggleSubmenu(submenu) {
        const isOpen = submenu.classList.contains('submenu-open');
        if (isOpen) {
            submenu.classList.remove('submenu-open');
            submenu.classList.add('submenu-closed');
        } else {
            submenu.classList.remove('submenu-closed');
            submenu.classList.add('submenu-open');
        }
    }

    function initSubmenus() {
        document.querySelectorAll('.sidebar-item.has-sub').forEach(item => {
            const link = item.querySelector(':scope > .sidebar-link');
            const submenu = item.querySelector(':scope > .submenu');
            if (!link || !submenu) return;

            // Set initial state
            if (item.classList.contains('active')) {
                submenu.classList.add('submenu-open');
            } else {
                submenu.classList.add('submenu-closed');
            }

            link.addEventListener('click', e => {
                e.preventDefault();
                toggleSubmenu(submenu);
            });
        });
    }

    // =========================================
    // Init sidebar state on load
    // =========================================
    function initSidebar() {
        if (!sidebar) return;

        if (isMobile()) {
            // Start hidden on mobile
            sidebar.classList.add('inactive');
        } else {
            // Start visible on desktop
            sidebar.classList.add('active');
        }
    }

    // =========================================
    // Resize handler
    // =========================================
    function onResize() {
        if (!isMobile()) {
            // Remove mobile backdrop when switching to desktop
            removeBackdrop();
            // Restore sidebar if it was hidden only by mobile logic
            if (!sidebar.classList.contains('inactive')) {
                sidebar.classList.add('active');
            }
        }
    }

    // =========================================
    // Dark mode toggle
    // =========================================
    function initDarkMode() {
        const toggler = document.getElementById('toggle-dark');
        const THEME_KEY = 'theme';
        const root = document.documentElement;

        function applyTheme(theme) {
            root.setAttribute('data-bs-theme', theme);
            document.body.classList.toggle('dark', theme === 'dark');
            localStorage.setItem(THEME_KEY, theme);
            if (toggler) toggler.checked = (theme === 'dark');
        }

        // Apply stored or system preference
        const stored = localStorage.getItem(THEME_KEY);
        if (stored) {
            applyTheme(stored);
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            applyTheme('dark');
        }

        if (toggler) {
            toggler.addEventListener('change', e => {
                applyTheme(e.target.checked ? 'dark' : 'light');
            });
        }

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', e => {
                if (!localStorage.getItem(THEME_KEY)) {
                    applyTheme(e.matches ? 'dark' : 'light');
                }
            });
    }

    // =========================================
    // Bootstrap tooltips & popovers (optional)
    // =========================================
    function initTooltips() {
        if (typeof bootstrap === 'undefined') return;
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach(el => new bootstrap.Tooltip(el));
        document.querySelectorAll('[data-bs-toggle="popover"]')
            .forEach(el => new bootstrap.Popover(el));
    }

    // =========================================
    // Event bindings
    // =========================================
    function bindEvents() {
        // Burger buttons (navbar)
        document.querySelectorAll('.burger-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                toggleSidebar();
            });
        });

        // Sidebar hide buttons (inside sidebar, mobile X)
        document.querySelectorAll('.sidebar-hide').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                hideSidebar();
            });
        });

        window.addEventListener('resize', onResize);
    }

    // =========================================
    // Boot
    // =========================================
    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initSubmenus();
        bindEvents();
        initDarkMode();
        initTooltips();
    });

})();
