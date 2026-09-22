           // Mobile Menu Toggle
            document.addEventListener('DOMContentLoaded', function () {
                const hamburger = document.getElementById('hamburgerBtn');
                const mobileNav = document.getElementById('mobileNav');
                const overlay = document.getElementById('mobileOverlay');
                const body = document.body;
                const searchBtn = document.getElementById('mobileSearchBtn');
                const searchBar = document.getElementById('mobileSearchBar');

                // Toggle menu
                function toggleMenu() {
                    hamburger.classList.toggle('active');
                    mobileNav.classList.toggle('active');
                    overlay.classList.toggle('active');
                    body.classList.toggle('menu-open');
                }

                // Hamburger click - also closes the search bar, so only one is open at a time
                hamburger.addEventListener('click', function () {
                    if (searchBar && searchBar.classList.contains('active')) {
                        searchBar.classList.remove('active');
                    }
                    toggleMenu();
                });

                // Overlay click
                overlay.addEventListener('click', toggleMenu);

                // Search icon toggle - independent of the hamburger drawer
                if (searchBtn && searchBar) {
                    searchBtn.addEventListener('click', function () {
                        searchBar.classList.toggle('active');
                        if (searchBar.classList.contains('active')) {
                            const input = searchBar.querySelector('.mobile-search-input');
                            if (input) input.focus();
                        }
                    });
                }

                // Submenu toggle - a dedicated caret button, separate from the category link
                // itself, so tapping "Politics"/"Movies" navigates like any other menu item and
                // only the caret opens the submenu (previously the whole row was one <a> with
                // preventDefault(), so it could only ever open the submenu, never navigate).
                const submenuCarets = document.querySelectorAll('.submenu-caret');
                submenuCarets.forEach(function (caret) {
                    caret.addEventListener('click', function () {
                        const parent = this.closest('.has-submenu');
                        parent.classList.toggle('active');
                    });
                });
            });
