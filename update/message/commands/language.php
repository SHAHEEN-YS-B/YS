<?php
/** @var MysqliDb $db */
/** @var TelegramBot\Telegram $tg */
/** @var array $message */
/** @var array $setting */

// اللغة العربية هي اللغة الوحيدة — لا توجد خيارات أخرى
if ($message['text'][0] == '/') {
    $words = explode('_', $message['text']);
    $command = $words[0];

    if ($command == '/language' && count($words) == 1) {
        $tg->sendMessage([
            'chat_id' => $tg->update_from,
            'text' => "🇸🇦 لغة البوت هي <b>العربية</b> وهي اللغة الوحيدة المتاحة.",
            'parse_mode' => 'html',
            'reply_markup' => mainMenu(),
        ]);
        exit;
    }
}
