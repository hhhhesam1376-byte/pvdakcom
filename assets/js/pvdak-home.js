/* PVDAK homepage topical search: progressively enhance the ACF topic tiles. */
(function () {
    'use strict';
    function ready() {
        var form = document.querySelector('.pvdak-home-search');
        var field = document.getElementById('pvdak-topic-input');
        var section = document.getElementById('topics');
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-pvdak-topic]'));
        var empty = document.getElementById('pvdak-search-empty');
        if (!form || !field || !section || !items.length) { return; }

        function normalize(s) {
            return String(s || '').toLocaleLowerCase()
                .replace(/ي/g, 'ی').replace(/ك/g, 'ک')
                .replace(/[\u200c\u200f]/g, ' ')
                .replace(/\s+/g, ' ').trim();
        }

        function filterTopics() {
            var term = normalize(field.value);
            var found = 0;
            items.forEach(function (item) {
                var visible = !term || normalize(item.textContent).indexOf(term) !== -1;
                item.hidden = !visible;
                if (visible) { found++; }
            });
            if (empty) { empty.hidden = found !== 0; }
            return found;
        }

        field.addEventListener('input', filterTopics);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            filterTopics();
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        var params = new URLSearchParams(window.location.search);
        var start = params.get('topic');
        if (start) { field.value = start; filterTopics(); }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else {
        ready();
    }
}());
