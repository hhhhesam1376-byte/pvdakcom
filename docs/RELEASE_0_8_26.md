# PVDak — Release 0.8.26 / Core source 0.8.1

Approved to proceed after user's 2026-10-23 instruction "برو توکارش".

Release contents:
- Remove stray 4-step connector; rebuild magazine comments form and article masthead; branded PV favicon, real public information/contact Pages and dedicated login/account.
- Replace ambiguous audience-selector with three real destinations and editable card copy.
- Branded recovery using WP native reset tokens; authenticated profile display name editor; pre-existing Core client/professional portal integrated.
- Registration remains default-OFF during maintenance and until explicit WordPress AND application opt-in; claimed lawyer status cannot confer access.
- Private account support tickets and manager responses; no public exposure.
- Core bundled source 0.8.1 adds fairness in manual matching and manager-only manual subscription quota, disabled-by-default; not a payment engine.

Crucial deployment distinction:
1. Updating GitHub main triggers the EXISTING cPanel cron which deploys the THEME ONLY. It copies bundled Core source into theme, NOT the separately installed/active pvdak-core WordPress plugin.
2. DO NOT claim that Core 0.8.1 was installed. A separately authorized, backed-up Core upgrade and a php syntax/policy test are required on host.
3. GitHub branch update is not proof of cPanel DEPLOY_OK. Verify against exact release SHA via the private .pvdak-deploy/deployed marker or logs.
4. Keep public maintenance ON, legal/professional intake OFF, registration OFF, payment OFF, subscription gate OFF.

Runtime acceptance before declaring done:
- cPanel deploy returns DEPLOY_OK; version from wp theme get shows 0.8.26; no 403 on style.css/rtl.css/JS/Swiper; PHP error log clean.
- First admin visit to wp-admin creates any missing public and account Pages without overwriting existing Pages.
- Admin-preview UI, custom login and recovery, contact, comments, FAQ, privacy/terms review and mobile behavior.
- Independent test of WordPress password-reset email/nonce; two-account privacy tests for tickets and professional portal.
- Active Core version remains at its old installed version until deliberate, separately verified upgrade.

Known limitations:
- This is not a completed ten-task launch. Code paths for gated registration, support, manual quota and profile exist but real functional/security/browser/mail tests are outstanding.
- Final privacy-policy/terms copy, verified contact coordinates, real membership validation/payment and Core subscription billing do not exist.
