# 𝚂𝙷𝙰𝙷𝙴𝙴𝙽 | 𝚈𝚂 𖠌 — Telegram Bot

## Overview
A PHP-based Telegram bot for channel administrators. Features:
- Inline buttons builder
- Hypertext/hidden link creator
- File attachment tool
- Send without quotes (anonymous forwarding)
- Caption editor
- Multi-language: Arabic (default), English, Persian

## Tech Stack
- **Language:** PHP 8.2
- **Database:** MySQL 8.0 (local, socket: /tmp/mysql_shaheen.sock)
- **Framework:** Custom PHP Telegram Bot library (alikm6/php-telegram-bot)
- **Web Server:** PHP built-in server on port 5000

## Run
The workflow runs `bash start.sh` which:
1. Starts MySQL (background)
2. Creates DB + imports schema (once)
3. Registers Telegram webhook
4. Starts PHP web server on port 5000

## Configuration
- Bot token: stored in config.php (TOKEN constant)
- Bot username: YShaheen
- Default language: Arabic (ar_AR)
- Webhook URL: https://{REPLIT_DEV_DOMAIN}/webhook.php?token={TOKEN}

## User preferences
- Project name: 𝚂𝙷𝙰𝙷𝙴𝙴𝙽 | 𝚈𝚂 𖠌
- Default language: Arabic
- Welcome GIF: https://i.postimg.cc/Mp6J1k1Q/Picsart-26-06-29-10-52-12-611-ezgif-com-video-to-gif-converter.gif
