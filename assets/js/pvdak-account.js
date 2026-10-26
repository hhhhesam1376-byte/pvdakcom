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
    var groups = Array.prototype.slice.call(authPicker.querySelectorAll('[data-pvdak-auth-group]'));
    var switches = Array.prototype.slice.call(document.querySelectorAll('[data-pvdak-auth-switch]'));
    var copies = Array.prototype.slice.call(document.querySelectorAll('[data-pvdak-auth-copy]'));
    var modes = tabs.map(function (tab) { return tab.getAttribute('data-pvdak-auth-tab'); });
    var query = new URLSearchParams(window.location.search);
    var defaultMode = modes.indexOf('password') !== -1 ? 'password' : modes[0];
    var smsMode = modes.indexOf('sms-login') >= 0 ? 'sms-login' :
      (modes.indexOf('sms-register') >= 0 ? 'sms-register' : '');
    var activeMode = modes.indexOf(query.get('auth')) >= 0 ? query.get('auth') :
      (query.has('state') && modes.indexOf('register-email') >= 0 ? 'register-email' :
       (query.has('sms_status') && smsMode ? smsMode : defaultMode));
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
      var isRegister = mode === 'register-email' || mode === 'sms-register';
      groups.forEach(function (group) {
        group.hidden = group.getAttribute('data-pvdak-auth-group') !== (isRegister ? 'register' : 'login');
      });
      switches.forEach(function (block) {
        block.hidden = block.getAttribute('data-pvdak-auth-switch') !== (isRegister ? 'login' : 'register');
      });
      copies.forEach(function (node) {
        node.hidden = node.getAttribute('data-pvdak-auth-copy') !== (isRegister ? 'register' : 'login');
      });
      if (remember && window.history && window.history.replaceState) {
        var link = new URL(window.location.href);
        if (mode === defaultMode) link.searchParams.delete('auth');
        else link.searchParams.set('auth', mode);
        link.searchParams.delete('sms_status');
        link.searchParams.delete('error');
        link.searchParams.delete('state');
        // A prior "account created" notice must not reappear merely because
        // someone switched tabs after a completed request.
        ['pvdak-register-email-result', 'pvdak-register-sms-result',
         'pvdak-login-result', 'pvdak-sms-login-result'].forEach(function (id) {
          var oldNotice = document.getElementById(id);
          if (oldNotice) oldNotice.remove();
        });
        window.history.replaceState({}, '', link.pathname + link.search + link.hash);
      }
    }
    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        showAuthMode(tab.getAttribute('data-pvdak-auth-tab'), false, true);
      });
      tab.addEventListener('keydown', function (event) {
        var group = tab.closest('[data-pvdak-auth-group]') || authPicker;
        var groupTabs = Array.prototype.slice.call(group.querySelectorAll('[data-pvdak-auth-tab]'));
        var within = groupTabs.indexOf(tab);
        var next = within;
        if (event.key === 'ArrowLeft') next = (within + 1) % groupTabs.length;
        else if (event.key === 'ArrowRight') next = (within + groupTabs.length - 1) % groupTabs.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = groupTabs.length - 1;
        else return;
        event.preventDefault();
        showAuthMode(groupTabs[next].getAttribute('data-pvdak-auth-tab'), true, true);
      });
    });
    document.querySelectorAll('[data-pvdak-auth-go]').forEach(function (link) {
      link.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 ||
            event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var mode = link.getAttribute('data-pvdak-auth-go');
        if (modes.indexOf(mode) === -1) return;
        event.preventDefault();
        showAuthMode(mode, true, true);
      });
    });
    showAuthMode(activeMode, false, false);
    // BFCache restores the DOM, not necessarily the tab that the current URL
    // now selects. Respect the server's SMS feedback tab without a reload.
    window.addEventListener('pageshow', function (event) {
      if (!event.persisted) return;
      var restored = new URLSearchParams(window.location.search);
      var mode = modes.indexOf(restored.get('auth')) >= 0 ? restored.get('auth') :
        (restored.has('state') && modes.indexOf('register-email') >= 0 ? 'register-email' :
         (restored.has('sms_status') && smsMode ? smsMode : defaultMode));
      showAuthMode(mode, false, false);
    });
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
        (params.has('pvdak_page') || params.has('request_type') ||
        params.has('request_status') || params.has('request_search') ||
        params.has('request_order')) ? 'pvdak-requests' :
        (params.has('support') || params.has('ticket_page') || params.has('ticket_status') ||
        params.has('ticket_search') || params.has('ticket_order') ||
        params.has('ticket_open')) ? 'pvdak-support' :
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
      var button = form.querySelector('button[type="submit"]');
      // Never enable or mark server-disabled controls (e.g. SMS without a
      // provider) as if this script had disabled them for submission.
      if (!button || button.disabled) return;
      form.dataset.pvdakSubmitting = '1';
      button.dataset.pvdakDisabledBySubmit = '1';
      button.disabled = true;
      button.setAttribute('aria-disabled', 'true');
    });
  });
  // Focus server feedback only after a submission, without reading credentials.
  var params = new URLSearchParams(window.location.search);
  var feedback = params.has('support') ? 'pvdak-support-notice' :
    params.has('sent') ? 'pvdak-contact-result' :
    params.has('error') ? 'pvdak-login-result' :
    params.has('sms_status') && document.getElementById('pvdak-sms-login-result') ? 'pvdak-sms-login-result' :
    params.has('sms_status') && document.getElementById('pvdak-register-sms-result') ? 'pvdak-register-sms-result' :
    params.has('state') && document.getElementById('pvdak-register-email-result') ? 'pvdak-register-email-result' :
    params.has('status') && document.getElementById('pvdak-recovery-result') ? 'pvdak-recovery-result' :
    params.has('status') && document.getElementById('pvdak-login-reset-result') ? 'pvdak-login-reset-result' :
    params.has('status') && document.querySelector('[data-pvdak-profile-form]') ? 'pvdak-profile-result' : '';
  if (feedback) {
    var notice = document.getElementById(feedback);
    if (notice && !notice.closest('[hidden]')) {
      notice.focus({ preventScroll: feedback !== 'pvdak-profile-result' });
    }
  }
  var profileForm = document.querySelector('form[data-pvdak-profile-form]');
  var profileFields = profileForm ?
    Array.prototype.slice.call(profileForm.querySelectorAll(
      '#account-display-name, #pvdak-first-name, #pvdak-last-name')) : [];
  var profileOriginals = profileFields.map(function (field) { return field.value.trim(); });
  function refreshProfile() {
    if (!profileForm || !profileFields.length) return;
    var changed = profileFields.some(function (field, index) {
      return field.value.trim() !== profileOriginals[index];
    });
    var status = profileForm.querySelector('[data-pvdak-profile-status]');
    var button = profileForm.querySelector('[data-pvdak-profile-save]');
    if (status) status.textContent = changed ? 'تغییرات هنوز ذخیره نشده‌اند.' : 'تغییری برای ذخیره وجود ندارد.';
    if (button) button.disabled = !changed;
  }
  if (profileForm && profileFields.length) {
    profileFields.forEach(function (field) {
      field.addEventListener('input', refreshProfile);
    });
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
    // Restore the correct private panel after browser back/forward cache.
    refreshNavigation();
    document.querySelectorAll('form[data-pvdak-support-form], form[data-pvdak-single-submit]').forEach(function (form) {
      delete form.dataset.pvdakSubmitting;
      var button = form.querySelector('button[type="submit"]');
      if (button && button.dataset.pvdakDisabledBySubmit === '1') {
        delete button.dataset.pvdakDisabledBySubmit;
        button.disabled = false;
        button.removeAttribute('aria-disabled');
      }
    });
    refreshProfile();
  });
})();
