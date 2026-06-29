#!/bin/bash

MYSQL_DATADIR="/home/runner/mysql-data"
MYSQL_SOCKET="/tmp/mysql_shaheen.sock"
MYSQL_PORT=3306
MYSQL_LOGFILE="/home/runner/mysql-logs/mysql.err"
DB_NAME="shaheen_bot"
DB_USER="shaheen"
DB_PASS="shaheen_pass_2026"
SCHEMA_FILE="/home/runner/workspace/database.sql"
SCHEMA_FLAG="/home/runner/mysql-data/.schema_imported"
BOT_TOKEN="8446137046:AAFfhP-O652Awf5OCmG1K6nQS7AehYLZ9BI"

mkdir -p /home/runner/mysql-logs
rm -f "$MYSQL_SOCKET" /tmp/mysql_shaheen.pid

echo "[START] Starting MySQL..."

# Start MySQL with auto-restart loop in background
start_mysql() {
    while true; do
        mysqld \
          --datadir="$MYSQL_DATADIR" \
          --socket="$MYSQL_SOCKET" \
          --pid-file=/tmp/mysql_shaheen.pid \
          --port=$MYSQL_PORT \
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

# Wait for MySQL to be ready
echo "[START] Waiting for MySQL to be ready..."
for i in $(seq 1 40); do
    if mysql -S "$MYSQL_SOCKET" -u root -e "SELECT 1;" > /dev/null 2>&1; then
        echo "[START] MySQL is ready!"
        break
    fi
    sleep 1
done

# Setup database and user
echo "[START] Setting up database..."
mysql -S "$MYSQL_SOCKET" -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# Import schema only once
if [ ! -f "$SCHEMA_FLAG" ]; then
    echo "[START] Importing database schema..."
    mysql -S "$MYSQL_SOCKET" -u root "$DB_NAME" < "$SCHEMA_FILE"
    mysql -S "$MYSQL_SOCKET" -u root "$DB_NAME" <<SQL
ALTER TABLE tbl_settings MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ar_AR';
ALTER TABLE tbl_inlinekey MODIFY COLUMN language_code enum('en_US','fa_IR','ar_AR') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_US';
SQL
    touch "$SCHEMA_FLAG"
    echo "[START] Schema imported successfully!"
else
    echo "[START] Schema already imported, skipping."
fi

# Register Telegram webhook (token masked in logs)
echo "[START] Registering Telegram webhook..."
WEBHOOK_URL="https://${REPLIT_DEV_DOMAIN}/webhook.php?token=${BOT_TOKEN}"
WEBHOOK_RESULT=$(curl -s "https://api.telegram.org/bot${BOT_TOKEN}/setWebhook?url=${WEBHOOK_URL}&max_connections=40&drop_pending_updates=true")
# Log only ok/description, not the full URL with token
echo "$WEBHOOK_RESULT" | grep -o '"ok":[^,]*\|"description":"[^"]*"' | tr '\n' ' '
echo ""

echo "[START] PHP server starting on port 5000..."
echo "[START] Bot @YShaheen is live!"

# Run PHP in foreground — keeps workflow alive
exec php -S 0.0.0.0:5000 -t /home/runner/workspace
