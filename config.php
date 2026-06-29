<?php
date_default_timezone_set('UTC');

// Database configuration — read from environment, fallback to defaults
const DB_HOST = "127.0.0.1";
const DB_USER = "shaheen";
const DB_PASSWORD = "shaheen_pass_2026";
const DB_NAME = "shaheen_bot";

const DB_TABLE_PREFIX = "tbl_";
const DB_CHARSET = 'utf8mb4_general_ci';

// Main site URL (points to this Replit domain)
const MAIN_LINK = "https://fc4dad64-71ba-46d2-b1b4-2772638d2e2c-00-1lihs6440mkjm.sisko.replit.dev";

// Bot token — stored here for runtime; rotate via BotFather if exposed
const TOKEN = '8446137046:AAFfhP-O652Awf5OCmG1K6nQS7AehYLZ9BI';

// Bot identity
const BOT_USERNAME = "YShaheen";
const BOT_NAME = "𝚂𝙷𝙰𝙷𝙴𝙴𝙽 | 𝚈𝚂 𖠌";

// Attachment channel username (without @)
const ATTACH_CHANNEL = 'YShaheen';

// IMGBB integration (disabled)
const ATTACH_IMGBB_STATE = false;
const ATTACH_IMGBB_API_KEY = '';

// Error reporting chat ID (null = disabled)
const TG_ERROR_REPORTING_CHAT_ID = null;

// Sponsor channels (disabled)
const SPONSOR_CHANNEL_ENABLE = false;
const SPONSOR_CHANNELS = [];

// Language settings — Arabic as default
const DEFAULT_LANGUAGE = 'ar_AR';
const LANGUAGES = [
    'ar_AR' => [
        'code' => 'ar_AR',
        'name' => "🇸🇦 العربية",
        'timezone' => 'Asia/Riyadh',
    ],
    'en_US' => [
        'code' => 'en_US',
        'name' => "🇬🇧 English",
        'timezone' => 'UTC',
    ],
    'fa_IR' => [
        'code' => 'fa_IR',
        'name' => "🇮🇷 فارسی",
        'timezone' => 'Asia/Tehran',
    ],
];

// Welcome banner per language
const BANNER_LINK = [
    'ar_AR' => "https://i.postimg.cc/Mp6J1k1Q/Picsart-26-06-29-10-52-12-611-ezgif-com-video-to-gif-converter.gif",
    'en_US' => MAIN_LINK . "/img/banner-en.jpg",
    'fa_IR' => MAIN_LINK . "/img/banner-fa.jpg",
];

// Internal constants — do not change
const TEMP_FILES_DIR_PATH_PREFIX = 'files/temp';
const TEMP_FILES_DIR_FULL_PATH = __DIR__ . '/' . TEMP_FILES_DIR_PATH_PREFIX;
const TEMP_FILES_DIR_URL = MAIN_LINK . '/' . TEMP_FILES_DIR_PATH_PREFIX;
