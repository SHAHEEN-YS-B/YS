<?php
/** @var TelegramBot\Telegram $tg */
/** @var MysqliDb $db */
/** @var array $callback_data */
/** @var array $callback_query */

if (!isset($callback_data['action'])) return;

if ($callback_data['action'] === 'contact_send') {
    $comm = get_com($tg->update_from);

    if (empty($comm) || $comm['name'] !== 'contact') {
        $tg->answerCallbackQuery([
            'callback_query_id' => $callback_query['id'],
            'text' => __("لا توجد رسائل مسجلة للإرسال."),
            'show_alert' => true,
        ], ['send_error' => false]);
        exit;
    }

    $messages_id = !empty($comm['col2']) ? explode(',', $comm['col2']) : [];

    if (empty($messages_id)) {
        $tg->answerCallbackQuery([
            'callback_query_id' => $callback_query['id'],
            'text' => __("لا توجد رسائل مسجلة للإرسال."),
            'show_alert' => true,
        ], ['send_error' => false]);
        exit;
    }

    $tg->answerCallbackQuery([
        'callback_query_id' => $callback_query['id'],
        'text' => __("جارٍ الإرسال ..."),
    ], ['send_error' => false]);

    // Delete the inline keyboard message
    if (!empty($comm['col3'])) {
        $tg->deleteMessage([
            'chat_id' => $tg->update_from,
            'message_id' => $comm['col3'],
        ], ['send_error' => false]);
    }

    // Do the actual sending
    $main_contact = false;
    if (!empty($comm['col1'])) {
        $main_contact = $db->rawQueryOne("select * from contact where id = ? limit 1", [
            'id' => $comm['col1'],
        ]);
    }

    $contact_id = $db->insert('contact', [
        'user_id' => $tg->update_from,
        'main_contact_id' => $main_contact ? $main_contact['id'] : null,
        'messages_id' => $comm['col2'],
        'date' => time(),
    ]);

    if (!$contact_id) {
        $tg->sendMessage([
            'chat_id' => $tg->update_from,
            'text' => __("حدث خطأ غير محدد. يرجى المحاولة مرة أخرى."),
        ]);
        exit;
    }

    $receiver_users = [];
    $q = "select * from admins where notify_contact = 1 and user_id != ?";
    $admins = $db->rawQuery($q, ['user_id' => $tg->update_from]);

    foreach ($admins as $admin) {
        $receiver_users[$admin['user_id']] = ['user_id' => $admin['user_id'], 'is_admin' => true];
    }

    if ($main_contact && $tg->update_from != $main_contact['user_id'] && !isset($receiver_users[$main_contact['user_id']])) {
        $receiver_users[$main_contact['user_id']] = ['user_id' => $main_contact['user_id'], 'is_admin' => false];
    }

    $main_contact_id = $main_contact ? $main_contact['id'] : $contact_id;

    foreach ($receiver_users as $receiver_user) {
        set_language_by_user_id($receiver_user['user_id']);

        $tg->sendMessage([
            'chat_id' => $receiver_user['user_id'],
            'text' => "#contact" . $main_contact_id . "\n\n" .
                ($receiver_user['is_admin']
                    ? "<b>" . __("معرف المستخدم: ") . "</b>" . "<a href='tg://user?id={$tg->update_from}'>{$tg->update_from}</a>" . "\n\n"
                    : "") .
                "<b>" . __("عدد الرسائل: ") . "</b>" . count($messages_id),
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
        ], ['send_error' => false]);

        foreach ($messages_id as $message_id) {
            $tg->copyMessage([
                'chat_id' => $receiver_user['user_id'],
                'from_chat_id' => $tg->update_from,
                'message_id' => $message_id,
            ], ['send_error' => false]);
        }

        $tg->sendMessage([
            'chat_id' => $receiver_user['user_id'],
            'text' => "<b>" . __("الرد: ") . "</b>" . "/reply_" . $main_contact_id,
            'parse_mode' => 'html',
        ], ['send_error' => false]);
    }

    set_language_by_user_id($tg->update_from);
    empty_com($tg->update_from);
    add_stats_info($tg->update_from, 'Contact');

    $tg->sendMessage([
        'chat_id' => $tg->update_from,
        'text' => "#contact" . $main_contact_id . "\n\n" . __("تم إرسال رسائلك بنجاح."),
        'reply_markup' => mainMenu(),
    ]);

    exit;
}
