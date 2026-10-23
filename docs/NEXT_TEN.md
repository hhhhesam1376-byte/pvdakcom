# بررسی‌های الزامی پس از پیش‌نویس 0.8.29 / Core 0.8.2

1. اجرای PHP lint و مجموعه تست‌های نسخه در مخزن کامل
2. بررسی DEPLOY_OK و نسخه قالب در هاست پس از تأیید انتشار
3. آزمایش ارسال و انقضای پیوند تأیید ایمیل
4. تست محدودیت ورود و بازیابی رمز
5. آزمون دوحسابی تیکت و درخواست‌های شخصی
6. تست پاسخ، بستن و بازکردن تیکت توسط مدیر
7. تست هم‌زمانی ارجاع در دیتابیس استیج
8. نصب/بررسی ایمن Core 0.8.2 و سهمیه‌های دستی
9. نهایی‌سازی متن حریم خصوصی، شرایط استفاده و اطلاعات تماس
10. بررسی نهایی ظاهر موبایل، دسترس‌پذیری، سئو و امنیت پیش از افتتاح

تا زمان اجرای این بررسی‌ها، قابلیت‌های عمومی حساس همچنان غیرفعال باشند.

## نتیجه بررسی واقعی هاست (2026-10-23)
- 37 checks, 0 failures, 7 missing/unpublished Page warnings (account-login, account-recovery, account-profile, my-pvdak-account, contact-us, privacy-policy, terms-of-use).
- Administrator wp-admin visit should trigger safe missing-only Page bootstrap on current release unless an old completion marker wrongly blocks it.
- Draft v0.8.29 fixes stale marker and adds per-Page state to read-only CLI. Do NOT publish without explicit user instruction.
