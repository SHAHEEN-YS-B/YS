<?php
/** @var MysqliDb $db */
/** @var TelegramBot\Telegram $tg */
/** @var array $message */

if ($message['text'][0] == '/') {
    $words = explode('_', $message['text']);
    $command = $words[0];
    if ($command == '/contact') {
        add_com($tg->update_from, 'contact');
        $tg->sendMessage([
            'chat_id' => $tg->update_from,
            'text' => __("يرجى إرسال اقتراحك أو ملاحظتك أو مشكلتك.") . "\n\n" .
                __("يمكنك إرسال أي نوع من الرسائل ورسائل متعددة.") .
                cancel_text(),
            'reply_markup' => $tg->replyKeyboardRemove(),
        ]);
        exit;
    }
}

$comm = get_com($tg->update_from);
if (!empty($comm) && $comm['name'] == "contact") {
    $messages_id = !empty($comm['col2']) ? explode(',', $comm['col2']) : [];

    if (!empty($comm['col3'])) {
        $tg->deleteMessage([
            'chat_id' => $tg->update_from,
            'message_id' => $comm['col3'],
        ], ['send_error' => false]);
    }

    // Handle "contact_send" via inline button — col4 flag set by callback
    if (!empty($comm['col4']) && $comm['col4'] === 'send' && !empty($messages_id)) {
        _contact_do_send($tg, $db, $comm, $messages_id);
        exit;
    }

    if (count($messages_id) == 10) {
        $tg->deleteMessage([
            'chat_id' => $tg->update_from,
            'message_id' => $message['message_id'],
        ], ['send_error' => false]);

        $m = $tg->sendMessage([
            'chat_id' => $tg->update_from,
            'text' => __("يمكنك إرسال 10 رسائل كحد أقصى لكل طلب، لذلك لا يمكنك إضافة رسائل جديدة.") . "\n\n" .
                __("لإرسال الرسائل المسجلة، اضغط على زر «✅ إرسال».") .
                cancel_text(),
            'reply_markup' => $tg->inlineKeyboardMarkup([
                'inline_keyboard' => [[
                    ['text' => "✅ " . __("إرسال"), 'callback_data' => encode_callback_data(['action' => 'contact_send'])],
                ]],
            ]),
        ]);

        edit_com($tg->update_from, ['col3' => $m['message_id']]);
        exit;
    }

    $messages_id[] = $message['message_id'];
    edit_com($tg->update_from, ['col2' => implode(',', $messages_id)]);

    $m = $tg->sendMessage([
        'chat_id' => $tg->update_from,
        'text' => __("تم استلام رسالتك من قِبَل البوت.") . "\n\n" .
            __("لإرسال الرسائل المسجلة، اضغط على زر «✅ إرسال».") . "\n\n" .
            __("أرسل رسالة جديدة للبوت لإضافتها إلى القائمة.") .
            cancel_text(),
        'reply_markup' => $tg->inlineKeyboardMarkup([
            'inline_keyboard' => [[
                ['text' => "✅ " . __("إرسال"), 'callback_data' => encode_callback_data(['action' => 'contact_send'])],
            ]],
        ]),
    ]);

    edit_com($tg->update_from, ['col3' => $m['message_id']]);
    exit;
}

function _contact_do_send($tg, $db, $comm, $messages_id)
{
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
        send_error(__("حدث خطأ غير محدد. يرجى المحاولة مرة أخرى."), 51);
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
                (
                $receiver_user['is_admin']
                    ?
                    "<b>" . __("المستخدم: ") . "</b>" . tgUserToText($tg->parseUpdate()['message']['from'] ?? [], 'html') . "\n" .
                    "<b>" . __("معرف المستخدم: ") . "</b>" . "<a href='tg://user?id={$tg->update_from}'>{$tg->update_from}</a>" . "\n\n"
                    :
                    ""
                ) .
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
            'disable_web_page_preview' => true,
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
}
