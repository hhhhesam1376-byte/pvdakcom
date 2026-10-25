/* PVDak mobile nav: three levels (parent, child, grandchild).
 * Links remain links; separate buttons expand their children.
 * Uses the primary WordPress menu, without duplicating or changing its data.
 */
(function () {
    'use strict';

    function initMobileNavigation() {
        var menu = document.querySelector('.pvdak-mobile-menu');
        if (!menu) { return; }
        var list = menu.querySelector('.pvdak-mobile-links');
        if (!list || menu.classList.contains('is-enhanced')) { return; }
        var toggles = [];
        var serial = 0;

        Array.prototype.forEach.call(list.querySelectorAll('li'), function (item) {
            var submenu = null;
            var link = null;
            Array.prototype.forEach.call(item.children, function (child) {
                if (child.tagName === 'UL' && child.classList.contains('sub-menu')) { submenu = child; }
                if (child.tagName === 'A') { link = child; }
            });
            if (!submenu) { return; }
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'pvdak-mobile-branch-toggle';
            button.setAttribute('aria-expanded', 'false');
            var id = submenu.id || 'pvdak-mobile-submenu-' + (++serial);
            submenu.id = id;
            button.setAttribute('aria-controls', id);
            var name = link ? link.textContent.trim() : 'این بخش';
            button.setAttribute('aria-label', 'باز کردن زیرمنوی ' + name);
            button.innerHTML = '<span aria-hidden="true">⌄</span>';
            submenu.hidden = true;
            item.insertBefore(button, submenu);
            item.classList.add('pvdak-mobile-has-branch');
            toggles.push({ button: button, submenu: submenu, name: name });

            button.addEventListener('click', function () {
                var willOpen = submenu.hidden;
                submenu.hidden = !willOpen;
                button.setAttribute('aria-expanded', String(willOpen));
                button.setAttribute('aria-label', (willOpen ? 'بستن' : 'باز کردن') + ' زیرمنوی ' + name);
                if (!willOpen) {
                    // A collapsed branch cannot retain open, invisible descendants.
                    toggles.forEach(function (entry) {
                        if (submenu.contains(entry.button)) {
                            entry.submenu.hidden = true;
                            entry.button.setAttribute('aria-expanded', 'false');
                            entry.button.setAttribute('aria-label', 'باز کردن زیرمنوی ' + entry.name);
                        }
                    });
                }
            });
        });

        // Hide descendants only after their independent controls exist.
        menu.classList.add('is-enhanced');

        function closeMenu() {
            if (!menu.open) { return; }
            menu.open = false;
        }
        menu.addEventListener('toggle', function () {
            if (menu.open) { return; }
            toggles.forEach(function (entry) {
                entry.submenu.hidden = true;
                entry.button.setAttribute('aria-expanded', 'false');
                entry.button.setAttribute('aria-label', 'باز کردن زیرمنوی ' + entry.name);
            });
        });
        menu.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && menu.open) {
                closeMenu();
                var summary = menu.querySelector('summary');
                if (summary) { summary.focus(); }
            }
        });
        document.addEventListener('click', function (event) {
            if (menu.open && !menu.contains(event.target)) { closeMenu(); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileNavigation);
    } else {
        initMobileNavigation();
    }
}());
