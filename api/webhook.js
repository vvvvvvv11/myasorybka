export default async function handler(req, res) {
  const BOT_TOKEN = '7755147962:AAGGFn1ZVautX47ul84VBm5fUVwUAF3qRpk';
  const ADMIN_CHAT_ID = '7900316924';
  
  if (req.method === 'GET') {
    return res.status(200).json({ status: 'Webhook is active' });
  }
  
  if (req.method === 'POST') {
    // Проверяем, пришел ли заказ с сайта
    if (req.body.order) {
      const order = JSON.parse(req.body.order);
      
      const message = formatOrderMessage(order);
      
      // Отправляем в Telegram
      const tgResponse = await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          chat_id: ADMIN_CHAT_ID,
          text: message,
          parse_mode: 'HTML'
        })
      });
      
      const result = await tgResponse.json();
      
      if (result.ok) {
        return res.status(200).json({ success: true });
      } else {
        return res.status(500).json({ error: 'Failed to send' });
      }
    }
  }
  
  return res.status(200).end();
}

function formatOrderMessage(order) {
  let message = `🛍️ <b>НОВЫЙ ЗАКАЗ!</b>\n\n`;
  message += `👤 <b>Клиент:</b> ${order.name}\n`;
  message += `📱 <b>Телефон:</b> ${order.phone}\n`;
  message += `📍 <b>Адрес:</b> ${order.address}\n`;
  message += `💳 <b>Оплата:</b> ${order.payment}\n\n`;
  message += `📦 <b>Состав заказа:</b>\n`;
  message += `────────────────\n`;
  
  order.items.forEach(item => {
    message += `• ${item.name}\n`;
    message += `  ${item.quantity} шт × ${item.price}₽ = ${item.total}₽\n`;
  });
  
  message += `────────────────\n\n`;
  message += `💰 <b>Сумма товаров:</b> ${order.subtotal}₽\n`;
  message += `🚚 <b>Доставка:</b> ${order.delivery === 0 ? 'Бесплатно 🎉' : order.delivery + '₽'}\n`;
  message += `💎 <b>ИТОГО К ОПЛАТЕ:</b> <b>${order.total}₽</b>\n\n`;
  message += `⏰ ${new Date().toLocaleString('ru-RU')}`;
  
  return message;
}
