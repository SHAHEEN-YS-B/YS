<?php
/** @var TelegramBot\Telegram $tg */
/** @var MysqliDb $db */
/** @var array $callback_data */
/** @var array $callback_query */

if (!isset($callback_data['action'])) return;

if ($callback_data['action'] === 'help') {
    $key = $callback_data['key'] ?? '';
    $help_config = get_help_config();
    $help_item = null;

    foreach ($help_config as $h) {
        if ($h['key'] === $key) {
            $help_item = $h;
            break;
        }
    }

    $tg->answerCallbackQuery([
        'callback_query_id' => $callback_query['id'],
    ], ['send_error' => false]);

    if ($help_item) {
        $base = [
            'chat_id' => $tg->update_from,
            'reply_markup' => get_help_menu(),
        ];

        foreach ($help_item['messages'] as $idx => $help_message) {
            $type = $help_message['type'];
            unset($help_message['type']);

            $payload = array_merge($base, $help_message);
            // Only attach keyboard to last message
            if ($idx < count($help_item['messages']) - 1) {
                unset($payload['reply_markup']);
            }

            if ($type === 'text') {
                $tg->sendMessage($payload);
            } elseif ($type === 'animation') {
                $tg->sendAnimation($payload);
            }
        }

        add_stats_info($tg->update_from, 'Help');
    } else {
        $tg->sendMessage([
            'chat_id' => $tg->update_from,
            'text' => __("العنصر المطلوب غير موجود."),
            'reply_markup' => get_help_menu(),
        ]);
    }

    exit;
}

if ($callback_data['action'] === 'menu_back' || (isset($callback_data['action']) && $callback_data['action'] === 'menu_back')) {
    $tg->answerCallbackQuery(['callback_query_id' => $callback_query['id']], ['send_error' => false]);
    $tg->sendMessage([
        'chat_id' => $tg->update_from,
        'text' => __("القائمة الرئيسية"),
        'reply_markup' => mainMenu(),
    ]);
    exit;
}
