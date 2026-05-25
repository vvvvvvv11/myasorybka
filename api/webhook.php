<?php
// api/webhook.php

// Токен вашего бота
$botToken = '7755147962:AAH8D-92Pii5wHlXq71uhk9FFSRN1BwaRrw';
$apiUrl = "https://api.telegram.org/bot{$botToken}/";

// Получаем входящее обновление от Telegram
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    http_response_code(400);
    exit;
}

// Обработка сообщений
if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = trim($message['text'] ?? '');
    $firstName = $message['from']['first_name'] ?? 'гость';
    
    // Обработка команды /start
    if ($text === '/start') {
        sendWelcomeMessage($chatId, $firstName);
    }
}

/**
 * Отправляет приветственное сообщение с кнопками
 */
function sendWelcomeMessage($chatId, $firstName)
{
    global $apiUrl;
    
    // Текст сообщения с именем пользователя
    $text = "Добро пожаловать, {$firstName}! 🥩\n\n"
          . "Лавка ремесленных колбас и мясных деликатесов Самары.\n\n"
          . "Мясные деликатесы на каждый день и праздник.\n\n"
          . "Для вашего стола:\n"
          . "— на каждый день: для бутербродов, салатов, горячих блюд\n"
          . "— на праздник: для эффектной мясной тарелки и особых блюд\n\n"
          . "Настоящий состав. Настоящий процесс.\n"
          . "• Только мясо и специи — никакой химии\n"
          . "• Настоящее время созревания — никаких ускорителей.";
    
    // URL вашего Mini App
    $miniAppUrl = "https://myasorybka.vercel.app/";
    
    // Клавиатура с двумя кнопками
    $keyboard = [
        'inline_keyboard' => [
            [
                [
                    'text' => '🛒 Открыть магазин',
                    'web_app' => ['url' => $miniAppUrl]
                ]
            ],
            [
                [
                    'text' => '📞 Связаться с менеджером',
                    'url' => 'https://t.me/myasorubka63'
                ]
            ]
        ]
    ];
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'reply_markup' => json_encode($keyboard),
        'parse_mode' => 'HTML'
    ];
    
    sendRequest('sendMessage', $data);
}

/**
 * Отправляет запрос к API Telegram
 */
function sendRequest($method, $data)
{
    global $apiUrl;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . $method);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

// Подтверждаем, что скрипт отработал
http_response_code(200);
echo 'ok';
