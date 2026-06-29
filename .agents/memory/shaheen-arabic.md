---
name: Shaheen Bot Arabic Language
description: Arabic (ar_AR) integration details for the Shaheen bot
---

ar_AR is the default language (DEFAULT_LANGUAGE in config.php).
Files: languages/ar_AR.po + ar_AR.mo (compiled with msgfmt).
Detection: start.php checks language_code prefix 'ar' → ar_AR.
RTL keyboard: apply_rtl_to_keyboard() triggers for fa_IR and ar_AR.
Date formatting: ar_AR uses Gregorian date() — NOT Jalali CalendarUtils (which is Persian only).

**Why:** The original fa_IR check in convert_time_to_text() used CalendarUtils::strftime() (Persian calendar). Extending that to ar_AR would produce wrong dates — Arabic uses Gregorian.

**How to apply:** If adding new languages with RTL, add to apply_rtl_to_keyboard(). If Arabic uses Hijri calendar in future, add a separate branch using IntlDateFormatter with 'ar_SA@calendar=islamic'.
