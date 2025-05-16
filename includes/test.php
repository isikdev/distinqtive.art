<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<pre>';

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/classes/api/class.cdek.php';

$cdek = new Cdek();

//$pvzList = $cdek->createOrder([]);
//$pvzList = $cdek->getOrderInfo('b140ae2f-a089-458a-a091-999a43f30783');
//print_r($pvzList);

$calc = $cdek->getListPVZ();
print_r($calc);

/*// Расчёт стоимости доставки
$calc = $cdek->calculate(44, 137, 136, 500, 20, 15, 10);
print_r($calc);

// Создание заказа
$orderData = [
	'tariff_code' => 136,
	'from_location' => 44,
	'to_location' => 137,
	'recipient_name' => 'Иван Иванов',
	'recipient_phone' => '+79999999999',
	'weight' => 500,
	'length' => 20,
	'width' => 15,
	'height' => 10,
];
$order = $cdek->createOrder($orderData);
print_r($order);*/
