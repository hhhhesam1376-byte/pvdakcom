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

  /* Reuse the two-tab switcher on the login and registration pages.
   * The first available tab is the default unless the URL names another. */
  var authPicker = document.querySelector('[data-pvdak-auth-picker]');
  if (authPicker) {
    var tabs = Array.prototype.slice.call(authPicker.querySelectorAll('[data-pvdak-auth-tab]'));
    var panes = Array.prototype.slice.call(document.querySelectorAll('[data-pvdak-auth-pane]'));
    var modes = tabs.map(function (tab) { return tab.getAttribute('data-pvdak-auth-tab'); });
    var query = new URLSearchParams(window.location.search);
    var defaultMode = modes.indexOf('password') !== -1 ? 'password' : modes[0];
    var activeMode = modes.indexOf(query.get('auth')) >= 0 ? query.get('auth') : defaultMode;
    function showAuthMode(mode, focus, remember) {
      if (modes.indexOf(mode) === -1) mode = defaultMode;
      activeMode = mode;
      tabs.forEach(function (tab) {
        var on = tab.getAttribute('data-pvdak-auth-tab') === mode;
        tab.setAttribute('aria-selected', on ? 'true' : 'false');
        tab.tabIndex = on ? 0 : -1;
        if (on && focus) tab.focus();
      });
      panes.forEach(function (pane) {
        pane.hidden = pane.getAttribute('data-pvdak-auth-pane') !== mode;
      });
      if (remember && window.history && window.history.replaceState) {
        var link = new URL(window.location.href);
        if (mode === defaultMode) link.searchParams.delete('auth');
        else link.searchParams.set('auth', mode);
        link.searchParams.delete('sms_status');
        link.searchParams.delete('error');
        link.searchParams.delete('state');
        window.history.replaceState({}, '', link.pathname + link.search + link.hash);
      }
    }
    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        showAuthMode(tab.getAttribute('data-pvdak-auth-tab'), false, true);
      });
      tab.addEventListener('keydown', function (event) {
        var next = index;
        if (event.key === 'ArrowLeft') next = (index + 1) % tabs.length;
        else if (event.key === 'ArrowRight') next = (index + tabs.length - 1) % tabs.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = tabs.length - 1;
        else return;
        event.preventDefault();
        showAuthMode(modes[next], true, true);
      });
    });
    showAuthMode(activeMode, false, false);
  }

  /* Progressive one-dashboard navigation. Without JavaScript all sections remain
   * accessible as ordinary document anchors; no account data is stored here. */
  function refreshNavigation() {
    var nav = document.querySelector('.pvdak-account-menu');
    var workspace = document.querySelector('[data-pvdak-workspace]');
    if (!nav || !workspace) return;
    var panels = Array.prototype.slice.call(workspace.querySelectorAll('[data-pvdak-panel]'));
    if (!panels.length) return;
    var hash = window.location.hash;
    var id = 'pvdak-dashboard';
    if (hash) {
      try { id = decodeURIComponent(hash.slice(1)); }
      catch (err) { id = hash.slice(1); }
    }
    var target = id ? document.getElementById(id) : null;
    var panel = target && workspace.contains(target) ?
      target.closest('[data-pvdak-panel]') : null;
    if (!panel) {
      var params = new URLSearchParams(window.location.search);
      var fallback = params.has('status') ? 'pvdak-profile' :
        (params.has('support') || params.has('ticket_page') || params.has('ticket_status') ||
        params.has('ticket_search') || params.has('ticket_order')) ? 'pvdak-support' :
        'pvdak-dashboard';
      var match = document.getElementById(fallback);
      panel = match && workspace.contains(match) ? match.closest('[data-pvdak-panel]') : null;
    }
    if (!panel) panel = panels[0];
    panels.forEach(function (section) {
      section.hidden = section !== panel;
    });
    nav.querySelectorAll('a[href^="#"]').forEach(function (link) {
      var linkId = link.getAttribute('href').slice(1);
      var linkTarget = document.getElementById(linkId);
      var isActive = linkTarget && linkTarget.closest('[data-pvdak-panel]') === panel;
      if (isActive) link.setAttribute('aria-current', 'location');
      else link.removeAttribute('aria-current');
    });
    document.querySelectorAll('.pvdak-dashboard-quicklinks a[href^="#"]').forEach(function (link) {
      var linkTarget = document.getElementById(link.getAttribute('href').slice(1));
      var active = linkTarget && linkTarget.closest('[data-pvdak-panel]') === panel;
      if (active) link.setAttribute('aria-current', 'location');
      else link.removeAttribute('aria-current');
    });
  }
  window.addEventListener('hashchange', function () {
    refreshNavigation();
    var id = window.location.hash.slice(1);
    var target = id ? document.getElementById(id) : null;
    if (target && target.closest('[data-pvdak-panel]')) {
      var destination = target.hasAttribute('tabindex') ? target : target.closest('[data-pvdak-panel]');
      destination.focus({ preventScroll: true });
      target.scrollIntoView({ behavior: 'auto', block: 'start' });
    }
  });
  refreshNavigation();

  /* Native details remains fully functional without JS. Escape closes it for
   * keyboard users and returns focus to the trigger. */
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    var menu = document.querySelector('.pvdak-mobile-menu[open]');
    if (!menu) return;
    menu.open = false;
    var summary = menu.querySelector('summary');
    if (summary) summary.focus();
  });

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
    params.has('status') && document.querySelector('[data-pvdak-profile-form]') ? 'pvdak-profile-result' : '';
  if (feedback) {
    var notice = document.getElementById(feedback);
    if (notice) notice.focus({ preventScroll: feedback !== 'pvdak-profile-result' });
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
