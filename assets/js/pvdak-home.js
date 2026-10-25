/* PVDak homepage: real locally-hosted Swiper 14.3.0 + Persian landing search.
 * The browser no longer requests external JS or CSS CDNs.
 * A readable one-row scroll fallback is used if local assets are missing.
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

    function topicSetup() {
        var form = document.querySelector('.pvdak-home-search');
        var field = document.getElementById('pvdak-topic-input');
        var section = document.getElementById('topics');
        var container = document.querySelector('[data-pvdak-swiper]');
        var track = container && container.querySelector('[data-pvdak-swiper-track]');
        var slides = track ? Array.prototype.slice.call(track.querySelectorAll('[data-pvdak-topic]')) : [];
        var empty = document.getElementById('pvdak-search-empty');
        var controls = document.querySelector('.pvdak-topics-controls');
        var prev = document.querySelector('[data-pvdak-topic-prev]');
        var next = document.querySelector('[data-pvdak-topic-next]');
        var clear = document.querySelector('[data-pvdak-clear-search]');
        var visibleCount = document.querySelector('[data-pvdak-topic-count]');
        if (!container || !track || !slides.length) { return; }

        var swiper = null;
        var reduceMotion = !!(window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        var prefersReduced = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)');
        var hadFocus = false;

        function visibleSlots() {
            if (window.matchMedia('(max-width: 400px)').matches) { return 1; }
            if (window.matchMedia('(max-width: 700px)').matches) { return 2; }
            if (window.matchMedia('(max-width: 1100px)').matches) { return 3; }
            return 6;
        }
        function hasQuery() {
            return !!(field && normalize(field.value));
        }
        function canSlide() {
            // Loop mode requires an extra original slide; at 1.15 visible
            // slides two items are insufficient and can produce broken clones.
            return slides.filter(function (slide) { return !slide.hidden; }).length >=
                Math.max(3, visibleSlots() + 1);
        }
        function stop() {
            if (swiper) {
                swiper.destroy(true, true);
                swiper = null;
            }
            container.classList.remove('is-ready');
            if (controls) { controls.hidden = true; }
        }
        function initSwiper() {
            // filterTopics() already initializes the slider on first load.
            // A second call must NEVER hide controls of that valid instance.
            if (swiper) {
                if (controls) { controls.hidden = false; }
                return;
            }
            if (typeof window.Swiper !== 'function' || hasQuery() || !canSlide()) {
                if (controls) { controls.hidden = true; }
                return;
            }

            // All slides are original published WordPress Page links.
            // Swiper itself owns transitions, loop, drag, and autoplay.
            // Apply ready-state layout BEFORE Swiper measures widths and gaps.
            container.classList.add('is-ready');
            try {
                swiper = new window.Swiper(container, {
                direction: 'horizontal',
                slidesPerView: 6,
                slidesPerGroup: 1,
                spaceBetween: 12,
                speed: reduceMotion ? 0 : 650,
                loop: true,
                watchOverflow: true,
                grabCursor: true,
                allowTouchMove: true,
                keyboard: { enabled: true, onlyInViewport: true },
                // Arrow buttons are wired directly below to the live instance.
                // Keeping them outside the Navigation module avoids a silent
                // no-op when a cache/optimizer alters optional module binding.
                loopPreventsSliding: false,
                autoplay: reduceMotion ? false : {
                    delay: 4700,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                // Swiper's a11y module turns real <a> links into
                // role=group with numeric labels ("1 / 17"). Preserve
                // native link semantics/names from their visible headings.
                // The region, arrows and keyboard navigation remain labelled.
                a11y: { enabled: false },
                breakpoints: {
                    0: { slidesPerView: 1.15, spaceBetween: 9 },
                    401: { slidesPerView: 2, spaceBetween: 9 },
                    701: { slidesPerView: 3, spaceBetween: 12 },
                    1101: { slidesPerView: 6, spaceBetween: 14 }
                }
                });
            } catch (error) {
                container.classList.remove('is-ready');
                swiper = null;
                if (window.console && typeof window.console.warn === 'function') {
                    window.console.warn('PVDAK_SWIPER_INIT_FAILED:', error);
                }
                return;
            }
            if (controls) { controls.hidden = false; }
            updateAutoplay();
        }

        function moveTopics(direction) {
            // Buttons must command the ACTIVE Swiper instance. Search mode
            // deliberately destroys it and hides the controls.
            if (hasQuery()) { return; }
            if (!swiper || swiper.destroyed) { initSwiper(); }
            if (!swiper || swiper.destroyed) { return; }
            if (typeof swiper.update === 'function') { swiper.update(); }
            if (swiper.isLocked) { return; }
            var before = swiper.realIndex;
            var changed = direction > 0 ? swiper.slideNext() : swiper.slidePrev();
            // In loop mode one failed navigation command can occur near the
            // clone boundary. slideToLoop targets a REAL original Page instead.
            if (changed === false && !swiper.animating &&
                typeof swiper.slideToLoop === 'function') {
                var total = slides.length;
                swiper.slideToLoop((before + direction + total) % total);
            }
        }

        // Register ONCE, not on every Swiper destroy/recreate during search.
        if (next) {
            next.addEventListener('click', function () { moveTopics(1); });
        }
        if (prev) {
            prev.addEventListener('click', function () { moveTopics(-1); });
        }

        function syncSwiper() {
            // Loop mode and hidden slides should not mix: fully destroy it while
            // search results are being filtered, and reinitialize after reset.
            if (hasQuery()) {
                stop();
                return;
            }
            slides.forEach(function (slide) { slide.hidden = false; });
            if (swiper && !canSlide()) { stop(); }
            if (!swiper) { initSwiper(); }
        }

        function filterTopics() {
            var term = field ? normalize(field.value) : '';
            // Stop loop and restore original unmodified Page nodes before hiding.
            if (swiper) { stop(); }
            var found = 0;
            slides.forEach(function (slide) {
                var okay = !term || normalize(slide.textContent).indexOf(term) !== -1;
                slide.hidden = !okay;
                if (okay) { found++; }
            });
            if (empty) { empty.hidden = found !== 0; }
            if (visibleCount) {
                visibleCount.textContent = found.toLocaleString('fa-IR') + ' موضوع حقوقی قابل مشاهده است.';
            }
            if (clear) { clear.hidden = !term; }
            if (!term) { syncSwiper(); }
        }

        function updateAutoplay() {
            if (!swiper || !swiper.autoplay || reduceMotion) { return; }
            if (document.hidden || hadFocus) { swiper.autoplay.pause(); }
            else { swiper.autoplay.resume(); }
        }

        if (controls) { controls.hidden = true; }
        if (field && form && section) {
            if (clear) {
                clear.addEventListener('click', function () {
                    field.value = '';
                    filterTopics();
                    field.focus();
                });
            }
            field.addEventListener('input', filterTopics);
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                filterTopics();
                section.scrollIntoView({
                    behavior: reduceMotion ? 'auto' : 'smooth',
                    block: 'start'
                });
            });
            var query = new URLSearchParams(window.location.search).get('pvdak_topic');
            if (query) { field.value = query; }
        }

        document.addEventListener('visibilitychange', updateAutoplay);
        container.addEventListener('focusin', function () {
            hadFocus = true;
            updateAutoplay();
        });
        container.addEventListener('focusout', function () {
            window.setTimeout(function () {
                hadFocus = container.contains(document.activeElement);
                updateAutoplay();
            }, 0);
        });
        window.addEventListener('resize', function () {
            if (!swiper && !hasQuery()) { initSwiper(); }
            if (swiper && !canSlide()) { stop(); }
        });
        if (prefersReduced && typeof prefersReduced.addEventListener === 'function') {
            prefersReduced.addEventListener('change', function (event) {
                reduceMotion = event.matches;
                stop();
                syncSwiper();
            });
        }
        filterTopics();
        // WordPress declares Swiper as a script dependency, but a deferred
        // optimizer may still make the vendor arrive after DOMContentLoaded.
        // Retry once when all page resources have loaded; no polling loop.
        if (typeof window.Swiper !== 'function') {
            container.classList.add('pvdak-swiper-unavailable');
            window.addEventListener('load', function () {
                if (typeof window.Swiper === 'function') {
                    container.classList.remove('pvdak-swiper-unavailable');
                    syncSwiper();
                } else if (window.console && typeof window.console.warn === 'function') {
                    window.console.warn('PVDAK_SWIPER_MISSING: local Swiper vendor bundle was not loaded.');
                }
            }, { once: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', topicSetup);
    } else {
        topicSetup();
    }
}());
