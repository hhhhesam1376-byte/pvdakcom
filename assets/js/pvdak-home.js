/* PVDak home topics: Persian search + dependency-free RTL swiper-style carousel.
 * Seven visible topics on desktop, four on tablet, two on small screens.
 * Infinite loop reorders the real Page elements after each transition, without
 * duplicated links, network/CDN libraries or invisible focusable clones.
 */
(function () {
    'use strict';

    function normalize(s) {
        return String(s || '').toLocaleLowerCase()
            .replace(/ي/g, 'ی').replace(/ك/g, 'ک')
            .replace(/[\u064b-\u065f\u0670]/g, '')
            .replace(/[\u200c\u200f]/g, ' ')
            .replace(/\s+/g, ' ').trim();
    }

    function setupSwiper(field) {
        var slider = document.querySelector('[data-pvdak-swiper]');
        if (!slider) { return null; }
        var track = slider.querySelector('[data-pvdak-swiper-track]');
        var prev = document.querySelector('[data-pvdak-topic-prev]');
        var next = document.querySelector('[data-pvdak-topic-next]');
        var controls = document.querySelector('.pvdak-topics-controls');
        if (!track || !prev || !next || !controls) { return null; }

        var original = Array.prototype.slice.call(track.querySelectorAll('[data-pvdak-topic]'));
        var busy = false;
        var hovered = false;
        var focused = false;
        var swipeX = null;
        var swiped = false;
        var transitionHandler = null;
        var transitionTimer = null;
        var reduceMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function visibleItems() {
            return Array.prototype.filter.call(track.children, function (card) {
                return !card.hidden;
            });
        }
        function visibleCount() {
            if (window.matchMedia('(max-width: 600px)').matches) { return 2; }
            if (window.matchMedia('(max-width: 1024px)').matches) { return 4; }
            return 7;
        }
        function canRotate() {
            return visibleItems().length > visibleCount();
        }
        function cardStep() {
            var first = visibleItems()[0];
            if (!first) { return 0; }
            var styles = window.getComputedStyle(track);
            return first.getBoundingClientRect().width +
                (parseFloat(styles.columnGap || styles.gap) || 0);
        }
        function clearTransition() {
            if (transitionHandler) {
                track.removeEventListener('transitionend', transitionHandler);
                transitionHandler = null;
            }
            if (transitionTimer !== null) {
                window.clearTimeout(transitionTimer);
                transitionTimer = null;
            }
            track.style.transition = 'none';
            track.style.transform = 'translateX(0px)';
            busy = false;
        }
        function finishOnTransition(callback) {
            var ended = false;
            function finish() {
                if (ended) { return; }
                ended = true;
                if (transitionHandler) {
                    track.removeEventListener('transitionend', transitionHandler);
                    transitionHandler = null;
                }
                if (transitionTimer !== null) {
                    window.clearTimeout(transitionTimer);
                    transitionTimer = null;
                }
                callback();
                busy = false;
            }
            transitionHandler = function (event) {
                if (event.target === track && event.propertyName === 'transform') {
                    finish();
                }
            };
            track.addEventListener('transitionend', transitionHandler);
            transitionTimer = window.setTimeout(finish, 650);
        }
        function forward() {
            if (busy || !canRotate()) { return; }
            var cards = visibleItems();
            var first = cards[0];
            var step = cardStep();
            if (!first || step <= 0) { return; }
            if (reduceMotion) {
                track.appendChild(first);
                return;
            }
            busy = true;
            track.style.transition = 'transform 470ms ease';
            track.style.transform = 'translateX(' + step + 'px)';
            finishOnTransition(function () {
                track.style.transition = 'none';
                track.appendChild(first);
                track.style.transform = 'translateX(0px)';
            });
        }
        function backward() {
            if (busy || !canRotate()) { return; }
            var cards = visibleItems();
            var last = cards[cards.length - 1];
            var first = cards[0];
            var step = cardStep();
            if (!last || !first || step <= 0) { return; }
            track.insertBefore(last, first);
            if (reduceMotion) { return; }
            busy = true;
            track.style.transition = 'none';
            track.style.transform = 'translateX(' + step + 'px)';
            // Flush initial offset before smoothly sliding back to the start.
            void track.offsetWidth;
            track.style.transition = 'transform 470ms ease';
            track.style.transform = 'translateX(0px)';
            finishOnTransition(function () {
                track.style.transition = 'none';
                track.style.transform = 'translateX(0px)';
            });
        }
        function refresh() {
            clearTransition();
            // Return to stable WordPress ordering when searching/resizing.
            original.forEach(function (card) { track.appendChild(card); });
            var enabled = canRotate();
            controls.hidden = !enabled;
            prev.disabled = !enabled;
            next.disabled = !enabled;
        }
        function autoplayAllowed() {
            return !reduceMotion && !document.hidden && !hovered && !focused &&
                !(field && normalize(field.value)) && canRotate();
        }

        slider.classList.add('is-ready');
        refresh();

        next.addEventListener('click', forward);
        prev.addEventListener('click', backward);
        slider.addEventListener('mouseenter', function () { hovered = true; });
        slider.addEventListener('mouseleave', function () { hovered = false; });
        slider.addEventListener('focusin', function () { focused = true; });
        slider.addEventListener('focusout', function () {
            window.setTimeout(function () {
                focused = slider.contains(document.activeElement);
            }, 0);
        });
        slider.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'touch' || event.pointerType === 'pen') {
                swipeX = event.clientX;
                swiped = false;
            }
        });
        slider.addEventListener('pointerup', function (event) {
            if (swipeX === null) { return; }
            var distance = event.clientX - swipeX;
            swipeX = null;
            if (Math.abs(distance) > 45) {
                swiped = true;
                if (distance > 0) { forward(); } else { backward(); }
            }
        });
        slider.addEventListener('pointercancel', function () { swipeX = null; });
        slider.addEventListener('click', function (event) {
            if (swiped) {
                event.preventDefault();
                event.stopPropagation();
                swiped = false;
            }
        }, true);
        window.addEventListener('resize', refresh);
        // A single local timer; no network dependency. Stops naturally when
        // there are not enough pages or a visitor is using the controls.
        window.setInterval(function () {
            if (autoplayAllowed()) { forward(); }
        }, 3400);
        return { refresh: refresh };
    }

    function ready() {
        var form = document.querySelector('.pvdak-home-search');
        var field = document.getElementById('pvdak-topic-input');
        var section = document.getElementById('topics');
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-pvdak-topic]'));
        var empty = document.getElementById('pvdak-search-empty');
        var swiper = setupSwiper(field);
        if (!form || !field || !section || !items.length) { return; }

        function filterTopics() {
            var term = normalize(field.value);
            var found = 0;
            items.forEach(function (item) {
                var visible = !term || normalize(item.textContent).indexOf(term) !== -1;
                item.hidden = !visible;
                if (visible) { found++; }
            });
            if (empty) { empty.hidden = found !== 0; }
            if (swiper) { swiper.refresh(); }
            return found;
        }

        field.addEventListener('input', filterTopics);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            filterTopics();
            section.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'start'
            });
        });
        var params = new URLSearchParams(window.location.search);
        var start = params.get('pvdak_topic');
        if (start) { field.value = start; filterTopics(); }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else {
        ready();
    }
}());
