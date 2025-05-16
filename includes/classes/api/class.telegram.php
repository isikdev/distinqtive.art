<?php

class Telegram
{
    private string $token = '7605417279:AAEwL-3-1hb5P1juQqdzaNSy_vG1wIsQPBk';
    private string $chatId = '-1002524785210';
    
    public function sendMessage(string $text): array
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        
        $data = [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            return ['error' => 'Ошибка запроса: ' . $curlError];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400 || !isset($result['ok']) || $result['ok'] !== true) {
            return ['error' => "Ошибка запроса ($httpCode): " . ($result['description'] ?? 'Неизвестная ошибка')];
        }
        
        return $result;
    }
    
    public function sendOrderNotification(array $orderData): array
    {
        $message = "<b>🔥 Новый заказ!</b>\n\n";
        $message .= "<b>Имя:</b> {$orderData['name']}\n";
        $message .= "<b>Телефон:</b> {$orderData['phone']}\n";
        
        if (isset($orderData['email'])) {
            $message .= "<b>Email:</b> {$orderData['email']}\n";
        }
        
        if (isset($orderData['size'])) {
            $message .= "<b>Размер одежды:</b> {$orderData['size']}\n";
        }
        
        $message .= "<b>ПВЗ:</b> {$orderData['pvz_name']}\n";
        $message .= "<b>Адрес ПВЗ:</b> {$orderData['pvz_address']}\n";
        $message .= "<b>Стоимость доставки:</b> {$orderData['delivery_price']} руб.\n";
        
        return $this->sendMessage($message);
    }
} 