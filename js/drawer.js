           // Mobile Menu Toggle
            document.addEventListener('DOMContentLoaded', function () {
                const hamburger = document.getElementById('hamburgerBtn');
                const mobileNav = document.getElementById('mobileNav');
                const overlay = document.getElementById('mobileOverlay');
                const body = document.body;

                // Toggle menu
                function toggleMenu() {
                    hamburger.classList.toggle('active');
                    mobileNav.classList.toggle('active');
                    overlay.classList.toggle('active');
                    body.classList.toggle('menu-open');
                }

                // Hamburger click
                hamburger.addEventListener('click', toggleMenu);

                // Overlay click
                overlay.addEventListener('click', toggleMenu);

                // Submenu toggle
                const submenuToggles = document.querySelectorAll('.submenu-toggle');
                submenuToggles.forEach(function (toggle) {
                    toggle.addEventListener('click', function (e) {
                        e.preventDefault();
                        const parent = this.closest('.has-submenu');
                        parent.classList.toggle('active');
                    });
                });
            });
