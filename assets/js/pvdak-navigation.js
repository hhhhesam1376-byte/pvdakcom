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
        var trigger = menu.querySelector('summary');
        var drawer = menu.querySelector('#pvdak-mobile-drawer');
        var backdrop = menu.querySelector('.pvdak-mobile-backdrop');
        var closeButton = menu.querySelector('.pvdak-mobile-drawer-close');

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

        function closeMenu(restoreFocus) {
            if (!menu.open) { return; }
            menu.open = false;
            document.body.classList.remove('pvdak-mobile-drawer-open');
            if (restoreFocus && trigger) { trigger.focus(); }
        }

        // A native details trigger stays functional if JavaScript is blocked.
        // When enhanced, the drawer occupies the viewport, locks background
        // scrolling, and restores the keyboard to the original menu control.
        menu.addEventListener('toggle', function () {
            document.body.classList.toggle('pvdak-mobile-drawer-open', menu.open);
            if (menu.open) {
                if (closeButton) { closeButton.focus(); }
                return;
            }
            toggles.forEach(function (entry) {
                entry.submenu.hidden = true;
                entry.button.setAttribute('aria-expanded', 'false');
                entry.button.setAttribute('aria-label', 'باز کردن زیرمنوی ' + entry.name);
            });
        });
        if (backdrop) {
            backdrop.addEventListener('click', function () { closeMenu(true); });
        }
        if (closeButton) {
            closeButton.addEventListener('click', function () { closeMenu(true); });
        }
        document.addEventListener('keydown', function (event) {
            if (!menu.open) { return; }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeMenu(true);
                return;
            }
            if (event.key !== 'Tab' || !drawer) { return; }
            var focusable = drawer.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            if (!focusable.length) { return; }
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && (document.activeElement === first || !drawer.contains(document.activeElement))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey &&
                (document.activeElement === last || !drawer.contains(document.activeElement))) {
                event.preventDefault();
                first.focus();
            }
        });
        document.addEventListener('click', function (event) {
            if (menu.open && !menu.contains(event.target)) { closeMenu(false); }
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 1030) { closeMenu(false); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileNavigation);
    } else {
        initMobileNavigation();
    }
}());
