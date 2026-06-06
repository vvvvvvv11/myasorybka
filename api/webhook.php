<?php
// api/webhook.php

// Бот для общения с клиентами (Mini App)
$clientBotToken = '7755147962:AAGGFn1ZVautX47ul84VBm5fUVwUAF3qRpk';

// Бот для получения заказов (админ)
$adminBotToken = '7857746746:AAGppBRxVA72xVEhXmvPYsWxoV3fDpvKdZY';
$adminChatId = '7900316924';

// Получаем данные от Telegram
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// Обработка заказа с сайта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    $order = json_decode($_POST['order'], true);
    
    // Отправляем заказ АДМИНСКОМУ боту
    sendOrderToAdmin($order, $adminChatId, $adminBotToken);
    
    echo json_encode(['success' => true]);
    exit;
}

// Обработка команд от КЛИЕНТСКОГО бота
if ($update && isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text = trim($update['message']['text'] ?? '');
    $firstName = $update['message']['from']['first_name'] ?? 'гость';
    
    if ($text === '/start') {
        sendWelcomeMessage($chatId, $firstName, $clientBotToken);
    }
}

echo 'ok';

function sendWelcomeMessage($chatId, $firstName, $botToken) {
    $text = "Добро пожаловать, {$firstName}! 🥩\n\n"
          . "Лавка ремесленных колбас и мясных деликатесов Самары.\n\n"
          . "Настоящий состав. Настоящий процесс.\n"
          . "• Только мясо и специи — никакой химии\n"
          . "• Настоящее время созревания — никаких ускорителей.\n\n"
          . "🛒 Нажмите кнопку ниже, чтобы открыть магазин:";
    
    $keyboard = [
        'inline_keyboard' => [
            [
                [
                    'text' => '🛒 Открыть магазин',
                    'web_app' => ['url' => 'https://myasorybka.vercel.app/']
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
    
    sendRequest('sendMessage', $data, $botToken);
}

function sendOrderToAdmin($order, $adminChatId, $botToken) {
    $message = "🛍️ <b>НОВЫЙ ЗАКАЗ!</b>\n\n"
             . "👤 <b>Клиент:</b> {$order['name']}\n"
             . "📱 <b>Телефон:</b> {$order['phone']}\n"
             . "📍 <b>Адрес:</b> {$order['address']}\n"
             . "💳 <b>Оплата:</b> {$order['payment']}\n\n"
             . "📦 <b>Состав заказа:</b>\n"
             . "────────────────\n";
    
    foreach ($order['items'] as $item) {
        $message .= "• {$item['name']}\n";
        $message .= "  {$item['quantity']} шт × {$item['price']}₽ = {$item['total']}₽\n";
    }
    
    $message .= "────────────────\n\n";
    $message .= "💰 <b>Сумма:</b> {$order['subtotal']}₽\n";
    $message .= "🚚 <b>Доставка:</b> " . ($order['delivery'] == 0 ? "Бесплатно 🎉" : $order['delivery'] . "₽") . "\n";
    $message .= "💎 <b>ИТОГО:</b> <b>{$order['total']}₽</b>\n\n";
    $message .= "⏰ " . date('d.m.Y H:i:s');
    
    sendRequest('sendMessage', [
        'chat_id' => $adminChatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ], $botToken);
}

function sendRequest($method, $data, $botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/{$method}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>
