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
    if (!field || (field.tagName !== 'TEXTAREA' && field.tagName !== 'INPUT')) return;
    var length = Array.from(field.value).length;
    var limit = field.maxLength > 0 ? field.maxLength : 2500;
    counter.textContent = length.toLocaleString('fa-IR') + ' / ' + limit.toLocaleString('fa-IR') + ' نویسه';
    counter.classList.toggle('pvdak-ticket-counter--near', length >= Math.floor(limit * 0.9));
  }
  document.querySelectorAll('[data-pvdak-count-for]').forEach(function (counter) {
    var field = document.getElementById(counter.getAttribute('data-pvdak-count-for'));
    if (!field || (field.tagName !== 'TEXTAREA' && field.tagName !== 'INPUT')) return;
    field.addEventListener('input', function () { updateCount(counter); });
    updateCount(counter);
  });

  document.querySelectorAll('form[data-pvdak-support-form], form[data-pvdak-single-submit]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) return;
      if (form.dataset.pvdakSubmitting === '1') { event.preventDefault(); return; }
      form.dataset.pvdakSubmitting = '1';
      var button = form.querySelector('button[type="submit"]');
      if (!button || button.disabled) return;
      button.disabled = true;
      button.setAttribute('aria-disabled', 'true');
    });
  });
  // Focus server feedback only after a submission, without reading credentials.
  var params = new URLSearchParams(window.location.search);
  var feedback = params.has('support') ? 'pvdak-support-notice' :
    params.has('sent') ? 'pvdak-contact-result' :
    params.has('error') ? 'pvdak-login-result' :
    params.has('status') && document.querySelector('.pvdak-profile-page') ? 'pvdak-profile-result' : '';
  if (feedback) {
    var notice = document.getElementById(feedback);
    if (notice) notice.focus({ preventScroll: true });
  }
  var profileForm = document.querySelector('form[data-pvdak-profile-form]');
  var profileField = profileForm ? profileForm.querySelector('#account-display-name') : null;
  var originalProfile = profileField ? profileField.value : '';
  function refreshProfile() {
    if (!profileForm || !profileField) return;
    var changed = profileField.value.trim() !== originalProfile;
    var status = profileForm.querySelector('[data-pvdak-profile-status]');
    var button = profileForm.querySelector('[data-pvdak-profile-save]');
    if (status) status.textContent = changed ? 'تغییرات هنوز ذخیره نشده‌اند.' : 'تغییری برای ذخیره وجود ندارد.';
    if (button) button.disabled = !changed;
  }
  if (profileForm && profileField) {
    profileField.addEventListener('input', refreshProfile);
    profileForm.addEventListener('reset', function () { window.setTimeout(refreshProfile, 0); });
    refreshProfile();
  }

  var recoveryForm = document.querySelector('form[data-pvdak-recovery-change]');
  if (recoveryForm) {
    var first = recoveryForm.querySelector('#new-password');
    var again = recoveryForm.querySelector('#confirm-password');
    var match = document.getElementById('pvdak-password-match');
    function validateRecovery() {
      if (!first || !again) return true;
      var tooShort = Array.from(first.value).length < 12;
      var mismatch = again.value !== '' && first.value !== again.value;
      first.setCustomValidity(first.value && tooShort ? 'رمز عبور باید دست‌کم ۱۲ نویسه باشد.' : '');
      again.setCustomValidity(mismatch ? 'تکرار رمز عبور یکسان نیست.' : '');
      again.setAttribute('aria-invalid', mismatch ? 'true' : 'false');
      if (match) match.textContent = mismatch ? 'تکرار رمز عبور یکسان نیست.' :
        again.value ? 'تکرار رمز عبور یکسان است.' : 'رمز عبور و تکرار آن باید یکسان باشند.';
      return !tooShort && !mismatch;
    }
    if (first && again) {
      first.addEventListener('input', validateRecovery);
      again.addEventListener('input', validateRecovery);
      recoveryForm.addEventListener('submit', function (event) {
        if (!validateRecovery()) { event.preventDefault(); recoveryForm.reportValidity(); }
      }, true);
    }
  }

  window.addEventListener('pageshow', function () {
    document.querySelectorAll('form[data-pvdak-support-form], form[data-pvdak-single-submit]').forEach(function (form) {
      delete form.dataset.pvdakSubmitting;
      var button = form.querySelector('button[type="submit"]');
      if (button) { button.disabled = false; button.removeAttribute('aria-disabled'); }
    });
    refreshProfile();
  });
})();
