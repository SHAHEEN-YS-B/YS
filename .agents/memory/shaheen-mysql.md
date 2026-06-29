---
name: Shaheen Bot MySQL Setup
description: How MySQL is started and managed for the Shaheen Telegram bot on Replit
---

MySQL 8.0 is started inside start.sh using an auto-restart loop (not --daemonize).
Socket: /tmp/mysql_shaheen.sock
Port: 3306 (127.0.0.1 only)
DB: shaheen_bot / user: shaheen
Schema import is idempotent — guarded by /home/runner/mysql-data/.schema_imported flag file.
Both 'shaheen'@'localhost' and 'shaheen'@'127.0.0.1' are granted to support socket + TCP connections.
DB_HOST in config.php is "127.0.0.1" (TCP).

**Why:** mysqld --daemonize exits the background job immediately; running it inside a while loop keeps it alive under the workflow's PHP foreground process.

**How to apply:** If MySQL stops connecting, check /home/runner/mysql-logs/mysql.err and run: pkill mysqld then restart the workflow.
