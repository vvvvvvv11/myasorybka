<?php
// api/webhook.php

// --- КОНФИГУРАЦИЯ ---
$adminBotToken = '7857746746:AAGppBRxVA72xVEhXmvPYsWxoV3fDpvKdZY';
$adminChatId = '7900316924';

// --- ОБРАБОТКА ЗАКАЗА С САЙТА ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    $order = json_decode($_POST['order'], true);
    if ($order) {
        sendOrderToAdmin($order);
        // Успешный ответ для сайта
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// --- ОБРАБОТКА POST-ЗАПРОСОВ ОТ TELEGRAM (ВЕБХУК) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и логируем входящие данные (полезно для отладки)
    $input = file_get_contents('php://input');
    $update = json_decode($input, true);

    // Здесь можно добавить обработку команд бота (например, /start)
    if ($update && isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = trim($update['message']['text'] ?? '');
        $firstName = $update['message']['from']['first_name'] ?? 'гость';
        
        if ($text === '/start') {
            sendWelcomeMessage($chatId, $firstName);
        }
    }

    // **КЛЮЧЕВОЙ МОМЕНТ**: ВСЕГДА отвечаем кодом 200 и {"ok":true}
    // Это сигнал Telegram, что вебхук принял запрос.
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

// --- ЕСЛИ ЗАПРОС НЕ POST (НАПРИМЕР, GET) ---
header('Content-Type: application/json');
http_response_code(200); // Можно вернуть 200 или 405. 200 проще для проверки.
echo json_encode(['status' => 'Webhook is active. Please use POST requests.']);

// --- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ---

function sendWelcomeMessage($chatId, $firstName) {
    global $adminBotToken; // Используем тот же бот? Или у вас другой? Если другой - добавьте параметр.
    // Для простоты примера, отправим приветствие через админ-бота.
    // Но лучше создать отдельный бот для клиентов.
    $text = "Добро пожаловать, {$firstName}! 🥩\n\nОткройте магазин по кнопке ниже.";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '🛒 Открыть магазин', 'web_app' => ['url' => 'https://myasorybka.vercel.app/']]]
        ]
    ];
    sendTelegramMessage($chatId, $text, $adminBotToken, $keyboard);
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
    sendTelegramMessage($adminChatId, $message, $adminBotToken);
}

function sendTelegramMessage($chatId, $text, $botToken, $keyboard = null) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    
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
