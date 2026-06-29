<?php

// ============================================================
// Always respond with 200 OK first — Telegram needs it.
// Any fatal / uncaught exception is logged, never displayed.
// ============================================================
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

// ------ Error configuration ---------------------------------
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('display_errors', '0');
ini_set('log_errors',     '1');
ini_set('error_log',      $logDir . '/php_errors.log');
error_reporting(E_ALL);

// Catch fatals that slip through try/catch
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[FATAL] ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
    }
});

// ============================================================
// Main webhook logic
// ============================================================
try {
    require realpath(__DIR__) . '/includes.php';

    // ----- 1. Token validation (query-string) ---------------
    if (empty($_GET['token']) || $_GET['token'] !== TOKEN) {
        echo json_encode(['ok' => false, 'error' => 'invalid token']);
        exit;
    }

    // ----- 2. Telegram secret_token header (when set) -------
    // If WEBHOOK_SECRET is configured, validate X-Telegram-Bot-Api-Secret-Token.
    // This prevents spoofed webhook calls from non-Telegram sources.
    $configuredSecret = getenv('WEBHOOK_SECRET') ?: '';
    if ($configuredSecret !== '') {
        $receivedSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
        if (!hash_equals($configuredSecret, $receivedSecret)) {
            error_log('[WEBHOOK] Secret-token mismatch — request rejected.');
            echo json_encode(['ok' => false, 'error' => 'forbidden']);
            exit;
        }
    }

    // NOTE: limit_access_to_telegram_only() is intentionally skipped.
    // Railway (and most PaaS) proxy requests so REMOTE_ADDR is the proxy IP,
    // not Telegram's. Token + secret_token checks above are sufficient.

    // ----- 3. Parse update ----------------------------------
    $db  = get_db();
    $tg  = new TelegramBot\Telegram(TOKEN, TG_ERROR_REPORTING_CHAT_ID);
    $tg->setTimeout(30);

    $insert_update_to_db = new InsertUpdateToDb($db);
    $update = $tg->parseUpdate();

    if (empty($update)) {
        echo json_encode(['ok' => true]);
        exit;
    }

    // Only load user language when we have a sender ID
    if (!empty($tg->update_from)) {
        set_language_by_user_id($tg->update_from);
    }

    // ----- 4. Route by update type -------------------------
    if (!empty($update['message'])) {
        if (!empty($update['message']['from'])) {
            $insert_update_to_db->insertUser($update['message']['from']);
        }
        if (!empty($update['message']['chat'])) {
            $insert_update_to_db->insertChat($update['message']['chat']);
        }
        require realpath(__DIR__) . '/update/message/index.php';

    } elseif (!empty($update['inline_query'])) {
        if (!empty($update['inline_query']['from'])) {
            $insert_update_to_db->insertUser($update['inline_query']['from']);
        }
        require realpath(__DIR__) . '/update/inline_query/index.php';

    } elseif (!empty($update['chosen_inline_result'])) {
        if (!empty($update['chosen_inline_result']['from'])) {
            // Fixed: was incorrectly referencing callback_query key here
            $insert_update_to_db->insertUser($update['chosen_inline_result']['from']);
        }
        require realpath(__DIR__) . '/update/chosen_inline_result/index.php';

    } elseif (!empty($update['callback_query'])) {
        if (!empty($update['callback_query']['from'])) {
            $insert_update_to_db->insertUser($update['callback_query']['from']);
        }
        require realpath(__DIR__) . '/update/callback_query/index.php';
    }

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    error_log(
        '[WEBHOOK ERROR] ' . $e->getMessage() .
        ' in ' . $e->getFile() . ':' . $e->getLine() .
        "\n" . $e->getTraceAsString()
    );
    // Still 200 — Telegram must NOT retry on our errors
    echo json_encode(['ok' => false, 'error' => 'internal error logged']);
}
