/**
 * Progressive enhancement for account navigation, support forms and password toggles.
 * Credentials are never read, transmitted or persisted by this script.
 */
(function () {
  'use strict';
  document.addEventListener('click', function (event) {
    var control = event.target.closest('[data-pvdak-password-toggle]');
    if (!control) return;
    var id = control.getAttribute('data-pvdak-password-toggle');
    var field = id ? document.getElementById(id) : null;
    if (!field || !control.parentElement.contains(field)) return;
    var visible = field.type === 'password';
    field.type = visible ? 'text' : 'password';
    control.textContent = visible ? 'پنهان' : 'نمایش';
    control.setAttribute('aria-pressed', visible ? 'true' : 'false');
    control.setAttribute('aria-label', visible ? 'پنهان‌کردن رمز عبور' : 'نمایش رمز عبور');
  });

  function refreshNavigation() {
    var nav = document.querySelector('.pvdak-account-menu');
    if (!nav) return;
    var hash = window.location.hash || '#pvdak-dashboard';
    var found = false;
    nav.querySelectorAll('a[href^="#"]').forEach(function (link) {
      var active = link.getAttribute('href') === hash;
      if (active) found = true;
      if (active) link.setAttribute('aria-current', 'location');
      else link.removeAttribute('aria-current');
    });
    if (!found) {
      var first = nav.querySelector('a[href="#pvdak-dashboard"]');
      if (first) first.setAttribute('aria-current', 'location');
    }
  }
  window.addEventListener('hashchange', refreshNavigation);
  refreshNavigation();

  function updateCount(counter) {
    var id = counter.getAttribute('data-pvdak-count-for');
    var field = id ? document.getElementById(id) : null;
    if (!field || field.tagName !== 'TEXTAREA') return;
    var length = Array.from(field.value).length;
    counter.textContent = length.toLocaleString('fa-IR') + ' / ۲۵۰۰ نویسه';
  }
  document.querySelectorAll('[data-pvdak-count-for]').forEach(function (counter) {
    var field = document.getElementById(counter.getAttribute('data-pvdak-count-for'));
    if (!field || field.tagName !== 'TEXTAREA') return;
    field.addEventListener('input', function () { updateCount(counter); });
    updateCount(counter);
  });

  document.querySelectorAll('form[data-pvdak-support-form]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var button = form.querySelector('button[type="submit"]');
      if (!button || button.disabled) return;
      button.disabled = true;
      button.setAttribute('aria-disabled', 'true');
    });
  });
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('form[data-pvdak-support-form] button[type="submit"]').forEach(function (button) {
      button.disabled = false;
      button.removeAttribute('aria-disabled');
    });
  });
})();
