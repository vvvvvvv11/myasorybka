<?php
// api/webhook.php - ТОЛЬКО ДЛЯ КЛИЕНТСКОГО БОТА

$clientBotToken = '7755147962:AAGGFn1ZVautX47ul84VBm5fUVwUAF3qRpk';
$adminBotToken = '7857746746:AAGppBRxVA72xVEhXmvPYsWxoV3fDpvKdZY';
$adminChatId = '7900316924';

// Обработка заказа с сайта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    $order = json_decode($_POST['order'], true);
    sendOrderToAdmin($order);
    echo json_encode(['success' => true]);
    exit;
}

// Обработка команд от Telegram (только для клиентского бота)
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if ($update && isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text = trim($update['message']['text'] ?? '');
    $firstName = $update['message']['from']['first_name'] ?? 'гость';
    
    // Только команда /start
    if ($text === '/start') {
        sendWelcomeMessage($chatId, $firstName);
    }
}

// Всегда отвечаем OK для Telegram
http_response_code(200);
echo 'ok';

function sendWelcomeMessage($chatId, $firstName) {
    global $clientBotToken;
    
    $text = "Добро пожаловать, {$firstName}! 🥩\n\n"
          . "Лавка ремесленных колбас и мясных деликатесов Самары.\n\n"
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
    
    sendTelegramRequest('sendMessage', [
        'chat_id' => $chatId,
        'text' => $text,
        'reply_markup' => json_encode($keyboard),
        'parse_mode' => 'HTML'
    ], $clientBotToken);
}

function sendOrderToAdmin($order) {
    global $adminBotToken, $adminChatId;
    
    $message = "🛍️ <b>НОВЫЙ ЗАКАЗ!</b>\n\n"
             . "👤 <b>Клиент:</b> {$order['name']}\n"
             . "📱 <b>Телефон:</b> {$order['phone']}\n"
             . "📍 <b>Адрес:</b> {$order['address']}\n"
             . "💳 <b>Оплата:</b> {$order['payment']}\n\n"
             . "📦 <b>Состав заказа:</b>\n";
    
    foreach ($order['items'] as $item) {
        $message .= "• {$item['name']} - {$item['quantity']} шт = {$item['total']}₽\n";
    }
    
    $message .= "\n💰 <b>Итого:</b> {$order['total']}₽";
    
    sendTelegramRequest('sendMessage', [
        'chat_id' => $adminChatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ], $adminBotToken);
}

function sendTelegramRequest($method, $data, $token) {
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    
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
