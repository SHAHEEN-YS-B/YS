<?php
date_default_timezone_set('UTC');

// ---------------------------------------------------------------------------
// Database — reads Railway env vars first, then Replit-local fallbacks
// ---------------------------------------------------------------------------
define('DB_HOST',     getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: '127.0.0.1');
define('DB_PORT',     (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306));
define('DB_USER',     getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'shaheen');
define('DB_PASSWORD', getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: 'shaheen_pass_2026');
define('DB_NAME',     getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'shaheen_bot');

define('DB_TABLE_PREFIX', 'tbl_');
define('DB_CHARSET',      'utf8mb4');        // charset only — NOT collation
define('DB_COLLATION',    'utf8mb4_general_ci'); // used in CREATE TABLE, not in mysqli::set_charset

// ---------------------------------------------------------------------------
// Public URL — Railway → Replit → localhost fallback
// ---------------------------------------------------------------------------
$_domain = getenv('RAILWAY_PUBLIC_DOMAIN')
         ?: getenv('REPLIT_DEV_DOMAIN')
         ?: ('localhost:' . (getenv('PORT') ?: '5000'));
define('MAIN_LINK', 'https://' . rtrim($_domain, '/'));
unset($_domain);

// ---------------------------------------------------------------------------
// Bot credentials
// ---------------------------------------------------------------------------
define('TOKEN',        getenv('BOT_TOKEN')       ?: '8446137046:AAFfhP-O652Awf5OCmG1K6nQS7AehYLZ9BI');
define('BOT_USERNAME', '@shaheen_ys_bot');
define('BOT_NAME',     '𝚂𝙷𝙰𝙷𝙴𝙴𝙽 | 𝚈𝚂 𖠌');

// ---------------------------------------------------------------------------
// Attachment channel (without @)
// ---------------------------------------------------------------------------
define('ATTACH_CHANNEL',      'shaheen_ys');

// ---------------------------------------------------------------------------
// IMGBB integration (disabled)
// ---------------------------------------------------------------------------
define('ATTACH_IMGBB_STATE',   false);
define('ATTACH_IMGBB_API_KEY', '');

// ---------------------------------------------------------------------------
// Error-reporting chat (null = disabled)
// ---------------------------------------------------------------------------
define('TG_ERROR_REPORTING_CHAT_ID', null);

// ---------------------------------------------------------------------------
// Sponsor channels (disabled)
// ---------------------------------------------------------------------------
define('SPONSOR_CHANNEL_ENABLE', false);
define('SPONSOR_CHANNELS',       []);

// ---------------------------------------------------------------------------
// Languages — Arabic only
// ---------------------------------------------------------------------------
define('DEFAULT_LANGUAGE', 'ar_AR');
define('LANGUAGES', [
    'ar_AR' => [
        'code'     => 'ar_AR',
        'name'     => "🇸🇦 العربية",
        'timezone' => 'Asia/Riyadh',
    ],
]);

// ---------------------------------------------------------------------------
// Welcome banner
// ---------------------------------------------------------------------------
define('BANNER_LINK', [
    'ar_AR' => 'https://i.postimg.cc/Mp6J1k1Q/Picsart-26-06-29-10-52-12-611-ezgif-com-video-to-gif-converter.gif',
]);

// ---------------------------------------------------------------------------
// Support channel
// ---------------------------------------------------------------------------
define('SUPPORT_CHANNEL_URL', 'https://t.me/shaheen_ys');

// ---------------------------------------------------------------------------
// Internal paths — do not change
// ---------------------------------------------------------------------------
define('TEMP_FILES_DIR_PATH_PREFIX', 'files/temp');
define('TEMP_FILES_DIR_FULL_PATH',   __DIR__ . '/' . TEMP_FILES_DIR_PATH_PREFIX);
define('TEMP_FILES_DIR_URL',         MAIN_LINK . '/' . TEMP_FILES_DIR_PATH_PREFIX);
