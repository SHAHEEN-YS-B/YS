#!/bin/bash
# NOTE: intentionally no `set -e` — DB/MySQL operations can fail temporarily
# and we handle each failure explicitly below.

# ============================================================
# Unified start script — works on both Replit and Railway
# ============================================================

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
BOT_TOKEN="${BOT_TOKEN:-8446137046:AAFfhP-O652Awf5OCmG1K6nQS7AehYLZ9BI}"
PORT="${PORT:-5000}"

mkdir -p "$APP_DIR/logs"
mkdir -p "$APP_DIR/files/temp"
chmod 775 "$APP_DIR/logs" "$APP_DIR/files/temp" 2>/dev/null || true

# ============================================================
# Detect environment (Railway takes priority)
# ============================================================
IS_RAILWAY=false
IS_REPLIT=false

if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    IS_RAILWAY=true
    PUBLIC_DOMAIN="$RAILWAY_PUBLIC_DOMAIN"
    echo "[START] Environment: Railway → $PUBLIC_DOMAIN"
elif [ -n "${RAILWAY_ENVIRONMENT:-}" ] || [ -n "${RAILWAY_SERVICE_NAME:-}" ]; then
    IS_RAILWAY=true
    PUBLIC_DOMAIN="${RAILWAY_STATIC_URL:-${RAILWAY_SERVICE_NAME:-railwayapp}.up.railway.app}"
    echo "[START] Environment: Railway (alt) → $PUBLIC_DOMAIN"
elif [ -n "${REPLIT_DEV_DOMAIN:-}" ]; then
    IS_REPLIT=true
    PUBLIC_DOMAIN="$REPLIT_DEV_DOMAIN"
    echo "[START] Environment: Replit → $PUBLIC_DOMAIN"
else
    echo "[WARN] No public domain detected — webhook registration will be skipped."
    PUBLIC_DOMAIN="localhost:${PORT}"
fi

# ============================================================
# Composer — install if vendor is missing
# ============================================================
if [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    echo "[START] Running composer install..."
    cd "$APP_DIR" && composer install --no-dev --optimize-autoloader || true
fi

# ============================================================
# RAILWAY: Start PHP immediately, then do DB/webhook in background
# ============================================================
if [ "$IS_RAILWAY" = "true" ]; then
    echo "[START] PHP server on port ${PORT} (Railway)..."
    echo "[START] Bot @${BOT_USERNAME:-YShaheen} is live!"

    # Background task: MySQL schema + webhook registration
    (
        DB_HOST_R="${MYSQLHOST:-127.0.0.1}"
        DB_PORT_R="${MYSQLPORT:-3306}"
        DB_USER_R="${MYSQLUSER:-root}"
        DB_PASS_R="${MYSQLPASSWORD:-}"
        DB_NAME_R="${MYSQLDATABASE:-railway}"
        SCHEMA_FILE="$APP_DIR/database.sql"

        echo "[BG] Railway MySQL: ${DB_HOST_R}:${DB_PORT_R} / ${DB_NAME_R}"

        MYSQL_READY=false
        for i in $(seq 1 30); do
            if mysql -h "$DB_HOST_R" -P "$DB_PORT_R" -u "$DB_USER_R" -p"$DB_PASS_R" \
                     "$DB_NAME_R" -e "SELECT 1;" >/dev/null 2>&1; then
                MYSQL_READY=true
                echo "[BG] Railway MySQL ready!"
                break
            fi
            echo "[BG] Attempt $i/30 — retrying in 2s..."
            sleep 2
        done

        if [ "$MYSQL_READY" = "false" ]; then
            echo "[BG][WARN] MySQL not reachable — schema import skipped."
        else
            TABLE_EXISTS=$(mysql -h "$DB_HOST_R" -P "$DB_PORT_R" -u "$DB_USER_R" -p"$DB_PASS_R" \
                "$DB_NAME_R" -sse \
                "SELECT COUNT(*) FROM information_schema.tables \
                 WHERE table_schema='${DB_NAME_R}' AND table_name='tbl_users';" 2>/dev/null || echo "0")
            TABLE_EXISTS="${TABLE_EXISTS:-0}"

            if [ "$TABLE_EXISTS" = "0" ]; then
                echo "[BG] Importing schema to Railway MySQL..."
                mysql -h "$DB_HOST_R" -P "$DB_PORT_R" -u "$DB_USER_R" -p"$DB_PASS_R" \
                      "$DB_NAME_R" < "$SCHEMA_FILE" 2>/dev/null && \
                mysql -h "$DB_HOST_R" -P "$DB_PORT_R" -u "$DB_USER_R" -p"$DB_PASS_R" \
                      "$DB_NAME_R" <<SQL 2>/dev/null || true
ALTER TABLE tbl_settings  MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ar_AR';
ALTER TABLE tbl_inlinekey MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_US';
SQL
                echo "[BG] Schema imported."
            else
                echo "[BG] Schema already present, skipping."
            fi
        fi

        # Register Telegram webhook after DB is ready
        echo "[BG] Registering Telegram webhook on Railway..."
        WEBHOOK_URL="https://${PUBLIC_DOMAIN}/webhook.php?token=${BOT_TOKEN}"
        RESULT=$(curl -s \
            "https://api.telegram.org/bot${BOT_TOKEN}/setWebhook" \
            --data-urlencode "url=${WEBHOOK_URL}" \
            -d "max_connections=40" \
            -d "drop_pending_updates=true" \
            2>/dev/null || echo '{"ok":false,"description":"curl failed"}')
        echo "$RESULT" | grep -o '"ok":[^,}]*\|"description":"[^"]*"' | tr '\n' ' '
        echo ""
        echo "[BG] Background init complete."
    ) &

    # Start PHP server as main process (keeps container alive)
    exec php -S "0.0.0.0:${PORT}" -t "$APP_DIR"
fi

# ============================================================
# REPLIT: Local MySQL starts first (it's on the same host)
# ============================================================
if [ "$IS_REPLIT" = "true" ]; then
    MYSQL_DATADIR="/home/runner/mysql-data"
    MYSQL_SOCKET="/tmp/mysql_shaheen.sock"
    MYSQL_LOGFILE="/home/runner/mysql-logs/mysql.err"
    LOCAL_DB_NAME="${DB_NAME:-shaheen_bot}"
    LOCAL_DB_USER="${DB_USER:-shaheen}"
    LOCAL_DB_PASS="${DB_PASSWORD:-shaheen_pass_2026}"
    SCHEMA_FILE="$APP_DIR/database.sql"
    SCHEMA_FLAG="$MYSQL_DATADIR/.schema_imported"

    mkdir -p /home/runner/mysql-logs
    rm -f "$MYSQL_SOCKET" /tmp/mysql_shaheen.pid 2>/dev/null || true

    if [ ! -d "$MYSQL_DATADIR" ] || [ ! -f "$MYSQL_DATADIR/ibdata1" ]; then
        echo "[START] Initializing MySQL data directory..."
        rm -rf "$MYSQL_DATADIR"
        mysqld --initialize-insecure --datadir="$MYSQL_DATADIR" --user=runner \
               --log-error="$MYSQL_LOGFILE" 2>&1 | tail -3 || true
        echo "[START] MySQL data directory initialized."
    fi

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
            rm -f "$MYSQL_SOCKET" /tmp/mysql_shaheen.pid 2>/dev/null || true
        done
    }
    start_mysql &

    echo "[START] Waiting for MySQL..."
    MYSQL_READY=false
    for i in $(seq 1 40); do
        if mysql -S "$MYSQL_SOCKET" -u root -e "SELECT 1;" >/dev/null 2>&1; then
            MYSQL_READY=true
            echo "[START] MySQL ready!"
            break
        fi
        sleep 1
    done

    if [ "$MYSQL_READY" = "false" ]; then
        echo "[ERROR] MySQL did not become ready in time — continuing anyway."
    else
        mysql -S "$MYSQL_SOCKET" -u root <<SQL 2>/dev/null || true
CREATE DATABASE IF NOT EXISTS \`$LOCAL_DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '$LOCAL_DB_USER'@'localhost'  IDENTIFIED BY '$LOCAL_DB_PASS';
CREATE USER IF NOT EXISTS '$LOCAL_DB_USER'@'127.0.0.1' IDENTIFIED BY '$LOCAL_DB_PASS';
GRANT ALL PRIVILEGES ON \`$LOCAL_DB_NAME\`.* TO '$LOCAL_DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$LOCAL_DB_NAME\`.* TO '$LOCAL_DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

        if [ ! -f "$SCHEMA_FLAG" ]; then
            echo "[START] Importing schema..."
            mysql -S "$MYSQL_SOCKET" -u root "$LOCAL_DB_NAME" < "$SCHEMA_FILE" 2>/dev/null && \
            mysql -S "$MYSQL_SOCKET" -u root "$LOCAL_DB_NAME" <<SQL 2>/dev/null || true
ALTER TABLE tbl_settings  MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ar_AR';
ALTER TABLE tbl_inlinekey MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_US';
SQL
            touch "$SCHEMA_FLAG"
            echo "[START] Schema imported."
        else
            echo "[START] Schema already imported, skipping."
        fi
    fi

    echo "[START] Replit dev mode — webhook registration skipped (Railway owns the webhook)."
fi

# ============================================================
# Start PHP built-in server (Replit / fallback)
# ============================================================
echo "[START] PHP server on port ${PORT}..."
echo "[START] Bot @${BOT_USERNAME:-YShaheen} is live!"
exec php -S "0.0.0.0:${PORT}" -t "$APP_DIR"
