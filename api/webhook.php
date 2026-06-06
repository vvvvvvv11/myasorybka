<?php
// api/webhook.php

// Токен вашего бота
$botToken = '7755147962:AAGGFn1ZVautX47ul84VBm5fUVwUAF3qRpk';
$apiUrl = "https://api.telegram.org/bot{$botToken}/";

// ID чата для отправки заказов (ваш CHAT_ID)
$adminChatId = '7900316924'; // ВАШ CHAT_ID

// Получаем входящее обновление от Telegram
$update = json_decode(file_get_contents('php://input'), true);

// Проверяем, не пришел ли заказ с нашего сайта
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    // Это заказ с сайта
    handleOrder($_POST['order']);
    exit;
}

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
 * Обрабатывает заказ с сайта
 */
function handleOrder($orderData) {
    global $adminChatId, $apiUrl;
    
    // Декодируем JSON
    $order = json_decode($orderData, true);
    
    if (!$order) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order data']);
        return;
    }
    
    // Формируем красивое сообщение о заказе
    $message = "🛍️ <b>НОВЫЙ ЗАКАЗ!</b>\n\n";
    $message .= "👤 <b>Клиент:</b> {$order['name']}\n";
    $message .= "📱 <b>Телефон:</b> {$order['phone']}\n";
    $message .= "📍 <b>Адрес:</b> {$order['address']}\n";
    $message .= "💳 <b>Оплата:</b> {$order['payment']}\n\n";
    
    $message .= "📦 <b>Состав заказа:</b>\n";
    $message .= "────────────────\n";
    
    foreach ($order['items'] as $item) {
        $message .= "• {$item['name']}\n";
        $message .= "  {$item['quantity']} шт × {$item['price']}₽ = {$item['total']}₽\n";
    }
    
    $message .= "────────────────\n\n";
    $message .= "💰 <b>Сумма товаров:</b> {$order['subtotal']}₽\n";
    $message .= "🚚 <b>Доставка:</b> " . ($order['delivery'] == 0 ? "Бесплатно 🎉" : $order['delivery'] . "₽") . "\n";
    $message .= "💎 <b>ИТОГО К ОПЛАТЕ:</b> <b>{$order['total']}₽</b>\n\n";
    
    $message .= "⏰ " . date('d.m.Y H:i:s');
    
    // Отправляем заказ админу
    $result = sendRequest('sendMessage', [
        'chat_id' => $adminChatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ]);
    
    // Отвечаем сайту, что заказ принят
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Order received']);
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
