#!/bin/bash
set -euo pipefail

# ============================================================
# Unified start script — works on both Replit and Railway
# ============================================================

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
BOT_TOKEN="${BOT_TOKEN:-8446137046:AAFfhP-O652Awf5OCmG1K6nQS7AehYLZ9BI}"
PORT="${PORT:-5000}"

mkdir -p "$APP_DIR/logs"
mkdir -p "$APP_DIR/files/temp"
chmod 775 "$APP_DIR/logs" "$APP_DIR/files/temp"

# ============================================================
# Detect environment
# ============================================================
IS_RAILWAY=false
IS_REPLIT=false

if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    IS_RAILWAY=true
    PUBLIC_DOMAIN="$RAILWAY_PUBLIC_DOMAIN"
    echo "[START] Environment: Railway → $PUBLIC_DOMAIN"
elif [ -n "${REPLIT_DEV_DOMAIN:-}" ]; then
    IS_REPLIT=true
    PUBLIC_DOMAIN="$REPLIT_DEV_DOMAIN"
    echo "[START] Environment: Replit → $PUBLIC_DOMAIN"
else
    PUBLIC_DOMAIN="localhost:${PORT}"
    echo "[START] Environment: local → $PUBLIC_DOMAIN"
fi

# ============================================================
# Local MySQL — only on Replit (Railway provides MySQL service)
# ============================================================
if [ "$IS_REPLIT" = "true" ]; then
    MYSQL_DATADIR="/home/runner/mysql-data"
    MYSQL_SOCKET="/tmp/mysql_shaheen.sock"
    MYSQL_LOGFILE="/home/runner/mysql-logs/mysql.err"
    DB_NAME="${DB_NAME:-shaheen_bot}"
    DB_USER="${DB_USER:-shaheen}"
    DB_PASS="${DB_PASSWORD:-shaheen_pass_2026}"
    SCHEMA_FILE="$APP_DIR/database.sql"
    SCHEMA_FLAG="$MYSQL_DATADIR/.schema_imported"

    mkdir -p /home/runner/mysql-logs
    rm -f "$MYSQL_SOCKET" /tmp/mysql_shaheen.pid

    echo "[START] Starting local MySQL..."

    start_mysql() {
        while true; do
            mysqld \
              --datadir="$MYSQL_DATADIR" \
              --socket="$MYSQL_SOCKET" \
              --pid-file=/tmp/mysql_shaheen.pid \
              --port=3306 \
              --user=runner \
              --bind-address=127.0.0.1 \
              --mysqlx=0 \
              --log-error="$MYSQL_LOGFILE" \
              --skip-name-resolve
            echo "[RESTART] MySQL exited, restarting in 3s..."
            sleep 3
            rm -f "$MYSQL_SOCKET" /tmp/mysql_shaheen.pid
        done
    }
    start_mysql &

    echo "[START] Waiting for MySQL..."
    for i in $(seq 1 40); do
        if mysql -S "$MYSQL_SOCKET" -u root -e "SELECT 1;" >/dev/null 2>&1; then
            echo "[START] MySQL ready!"
            break
        fi
        sleep 1
    done

    # Create DB + user
    mysql -S "$MYSQL_SOCKET" -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost'  IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

    # Import schema once
    if [ ! -f "$SCHEMA_FLAG" ]; then
        echo "[START] Importing schema..."
        mysql -S "$MYSQL_SOCKET" -u root "$DB_NAME" < "$SCHEMA_FILE"
        mysql -S "$MYSQL_SOCKET" -u root "$DB_NAME" <<SQL
ALTER TABLE tbl_settings  MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ar_AR';
ALTER TABLE tbl_inlinekey MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_US';
SQL
        touch "$SCHEMA_FLAG"
        echo "[START] Schema imported."
    else
        echo "[START] Schema already imported, skipping."
    fi
fi

# ============================================================
# On Railway — import schema if tables are missing
# ============================================================
if [ "$IS_RAILWAY" = "true" ]; then
    DB_HOST_R="${MYSQLHOST:-127.0.0.1}"
    DB_PORT_R="${MYSQLPORT:-3306}"
    DB_USER_R="${MYSQLUSER:-shaheen}"
    DB_PASS_R="${MYSQLPASSWORD:-shaheen_pass_2026}"
    DB_NAME_R="${MYSQLDATABASE:-shaheen_bot}"
    SCHEMA_FILE="$APP_DIR/database.sql"

    echo "[START] Railway MySQL: ${DB_HOST_R}:${DB_PORT_R} / ${DB_NAME_R}"

    # Wait for Railway MySQL to become available
    echo "[START] Waiting for Railway MySQL..."
    for i in $(seq 1 30); do
        if mysql -h "$DB_HOST_R" -P "$DB_PORT_R" -u "$DB_USER_R" -p"$DB_PASS_R" \
                 "$DB_NAME_R" -e "SELECT 1;" >/dev/null 2>&1; then
            echo "[START] Railway MySQL ready!"
            break
        fi
        echo "[START] Attempt $i/30 — retrying in 2s..."
        sleep 2
    done

    # Check if schema has been imported by looking for a known table
    TABLE_EXISTS=$(mysql -h "$DB_HOST_R" -P "$DB_PORT_R" -u "$DB_USER_R" -p"$DB_PASS_R" \
        "$DB_NAME_R" -sse \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME_R}' AND table_name='tbl_users';" 2>/dev/null || echo "0")

    if [ "$TABLE_EXISTS" = "0" ]; then
        echo "[START] Importing schema to Railway MySQL..."
        mysql -h "$DB_HOST_R" -P "$DB_PORT_R" -u "$DB_USER_R" -p"$DB_PASS_R" \
              "$DB_NAME_R" < "$SCHEMA_FILE"
        mysql -h "$DB_HOST_R" -P "$DB_PORT_R" -u "$DB_USER_R" -p"$DB_PASS_R" \
              "$DB_NAME_R" <<SQL
ALTER TABLE tbl_settings  MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ar_AR';
ALTER TABLE tbl_inlinekey MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_US';
SQL
        echo "[START] Schema imported to Railway MySQL."
    else
        echo "[START] Schema already present, skipping import."
    fi
fi

# ============================================================
# Composer — install if vendor is missing
# ============================================================
if [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    echo "[START] Running composer install..."
    cd "$APP_DIR" && composer install --no-dev --optimize-autoloader
fi

# ============================================================
# Register Telegram webhook
# ============================================================
echo "[START] Registering Telegram webhook..."
WEBHOOK_URL="https://${PUBLIC_DOMAIN}/webhook.php?token=${BOT_TOKEN}"
RESULT=$(curl -sf \
    "https://api.telegram.org/bot${BOT_TOKEN}/setWebhook" \
    --data-urlencode "url=${WEBHOOK_URL}" \
    -d "max_connections=40" \
    -d "drop_pending_updates=true" 2>/dev/null || echo '{"ok":false}')
echo "$RESULT" | grep -o '"ok":[^,}]*\|"description":"[^"]*"' | tr '\n' ' '
echo ""

# ============================================================
# Start PHP built-in server
# ============================================================
echo "[START] PHP server on port ${PORT}..."
echo "[START] Bot @${BOT_USERNAME:-YShaheen} is live!"
exec php -S "0.0.0.0:${PORT}" -t "$APP_DIR"
