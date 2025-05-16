<?php

class Cdek
{
	private string $clientId     = 's74VR4n4xnjT6EWSeRpg09aCtU8MugRN'; # Test - wqGwiQx0gg8mLtiEKsUinjVSICCjtTEP | Prod - VUqIaHakri1PnH3TXio3zRPhgQXTVJPA
	private string $clientSecret = 'Ef75sxzDIsd66GXFGoQDADCbFOSYgLT0'; # Test - RmAmgvSgSl1yirlz9QupbzOJVqhCxcP5 | Prod - 2Ch27d924Smlc124NKEVdEG2Xnp0ili9
	private string $token;
	private string $baseUrl      = 'https://api.cdek.ru'; # Test - https://api.edu.cdek.ru | Prod - https://api.cdek.ru
	private int    $tariffCode   = 136;
	private int    $fromLocation = 44;
	
	private array $productDemension = [
		'weight' => 400,
		'length' => 11,
		'width'  => 11,
		'height' => 36,
	];
	
	public function __construct()
	{
		$this->token = $this->getToken();
	}
	
	private function sendRequest($method, $endpoint, $data = [])
	{
		$url = $this->baseUrl . $endpoint;
		
		$headers = [
			'content_type' => 'Content-Type: application/json',
			'Accept: application/json',
		];
		
		if (!empty($this->token)) {
			$headers[] = 'Authorization: Bearer ' . $this->token;
		} else {
			$headers['content_type'] = 'Content-Type: application/x-www-form-urlencoded';
		}
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			
			if (!empty($this->token)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			}
		}
		
		$response  = curl_exec($ch);
		$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);
		curl_close($ch);
		
		if ($response === false) {
			return ['error' => 'Ошибка запроса: ' . $curlError];
		}
		
		$result = json_decode($response, true);
		
		if ($httpCode >= 400) {
			return ['error' => "Ошибка запроса ($httpCode): " . ($result['message'] ?? 'Неизвестная ошибка')];
		}
		
		return $result;
	}
	
	public function getListPVZ()
	{
		if (!$this->token) {
			return ['error' => 'Не удалось получить токен для запроса'];
		}
		
		$result = $this->sendRequest('GET', '/v2/deliverypoints', ['type' => 'PVZ', 'size' => 10]);
		
		if (isset($result['error'])) {
			return ['error' => 'Не удалось получить список ПВЗ'];
		}
		
		return $result;
	}
	
	public function calculate($toLocation)
	{
		if (!$this->token) {
			return ['error' => 'Не удалось получить токен для расчёта стоимости'];
		}
		
		$data = [
			'from_location' => ['code' => $this->fromLocation],
			'to_location'   => ['code' => $toLocation],
			'tariff_code'   => $this->tariffCode,
			'packages'      => [
				[
					'weight' => $this->productDemension['weight'],
					'length' => $this->productDemension['length'],
					'width'  => $this->productDemension['width'],
					'height' => $this->productDemension['height'],
				],
			],
		];
		
		$result = $this->sendRequest('POST', '/v2/calculator/tariff', $data);
		
		if (isset($result['error'])) {
			return ['error' => 'Ошибка расчёта стоимости доставки'];
		}
		return $result;
	}
	
	public function createOrder(array $orderData)
	{
		if (!$this->token) {
			return ['error' => 'Не удалось получить токен для создания заказа'];
		}
		
		$data = [
			'tariff_code'    => $this->tariffCode,
			'number'         => 'FIRE_INSIDE_' . date('YmdHis'),
			'shipment_point' => 'RND84',
			'delivery_point' => $orderData['delivery_point'],
			'recipient'      => [
				'name'   => $orderData['recipient_name'],
				'phones' => [
					0 => [
						'number' => $orderData['recipient_phone'],
					],
				],
			],
			'packages'       => [
				'number' => '1',
				'weight' => $this->productDemension['weight'],
				'length' => $this->productDemension['length'],
				'width'  => $this->productDemension['width'],
				'height' => $this->productDemension['height'],
				'items'  => [
					'ware_key' => 'FIRE_INSIDE',
					'payment'  => [
						'value' => $orderData['delivery_price'] ?? 0,
					],
					'name'     => 'Набор футболки',
					'cost'     => 1990,
					'amount'   => 1,
					'weight'   => $this->productDemension['weight'],
				],
			],
		];
		
		$result = $this->sendRequest('POST', '/v2/orders', $data);
		
		if (isset($result['error'])) {
			return ['error' => 'Ошибка при создании заказа'];
		}
		
		return $result;
	}
	
	public function getOrderInfo(string $UUID)
	{
		if (!$this->token) {
			return ['error' => 'Не удалось получить токен для создания заказа'];
		}
		
		$result = $this->sendRequest('GET', "/v2/orders/$UUID");
		
		if (isset($result['error'])) {
			return ['error' => 'Ошибка при создании заказа'];
		}
		
		return $result;
	}
	
	private function getToken()
	{
		$result = $this->sendRequest(
			'POST',
			'/v2/oauth/token',
			[
				'grant_type'    => 'client_credentials',
				'client_id'     => $this->clientId,
				'client_secret' => $this->clientSecret,
			]
		);
		
		if (isset($result['error']) || empty($result['access_token'])) {
			return null;
		}
		
		return $result['access_token'];
	}
}
