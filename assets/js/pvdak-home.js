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
        if (!container || !track || !slides.length) { return; }

        var swiper = null;
        var reduceMotion = !!(window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        var prefersReduced = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)');
        var hadFocus = false;

        function visibleSlots() {
            if (window.matchMedia('(max-width: 700px)').matches) { return 2; }
            if (window.matchMedia('(max-width: 1100px)').matches) { return 3; }
            return 6;
        }
        function hasQuery() {
            return !!(field && normalize(field.value));
        }
        function canSlide() {
            return slides.filter(function (slide) { return !slide.hidden; }).length > visibleSlots();
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
            if (swiper || typeof window.Swiper !== 'function' || hasQuery() || !canSlide()) {
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
                navigation: {
                    nextEl: next,
                    prevEl: prev
                },
                autoplay: reduceMotion ? false : {
                    delay: 3000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                },
                a11y: {
                    enabled: true,
                    prevSlideMessage: 'موضوع قبلی',
                    nextSlideMessage: 'موضوع بعدی'
                },
                breakpoints: {
                    0: { slidesPerView: 2, spaceBetween: 9 },
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
            if (!term) { syncSwiper(); }
        }

        function updateAutoplay() {
            if (!swiper || !swiper.autoplay || reduceMotion) { return; }
            if (document.hidden || hadFocus) { swiper.autoplay.pause(); }
            else { swiper.autoplay.resume(); }
        }

        if (controls) { controls.hidden = true; }
        if (field && form && section) {
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
        if (typeof window.Swiper === 'function') {
            initSwiper();
        } else {
            // Fail transparently, not by pretending that scroll = Swiper.
            // The real bundle is installed once via the private host installer.
            container.classList.add('pvdak-swiper-unavailable');
            if (window.console && typeof window.console.warn === 'function') {
                window.console.warn('PVDAK_SWIPER_MISSING: local Swiper vendor bundle was not loaded.');
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', topicSetup);
    } else {
        topicSetup();
    }
}());
