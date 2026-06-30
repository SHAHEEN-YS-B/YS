<?php
/** @var TelegramBot\Telegram $tg */
/** @var array $message */

if ($message['text'][0] == '/') {
    $words = explode('_', $message['text']);
    $command = strtolower($words[0]);
    if ($command == '/help') {
        add_com($tg->update_from, 'help');

        $commands = command_list();
        $commands_text = "";

        foreach ($commands as $cmd) {
            $commands_text .= "/" . $cmd['command'] . ' - ' . $cmd['description'] . "\n";
        }

        $text = __("يمكنني مساعدتك في كل شيء! هل تشك في ذلك؟ جربها بنفسك.") . "\n\n" .
            __("العمليات التي يمكنك تنفيذها:") . "\n" .
            $commands_text . "\n" .
            __("اختر عنصراً من القائمة أدناه لمعرفة المزيد.");

        $tg->sendMessage([
            'chat_id' => $tg->update_from,
            'text' => $text,
            'reply_markup' => get_help_menu(),
        ]);

        exit;
    }
}
