<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/classes/api/class.cdek.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/classes/api/class.telegram.php';

$tariffCode = 136;

$cdek = new Cdek();
$telegram = new Telegram();

// Получение типа запроса
$requestType = $_REQUEST['request_type'] ?? '';

switch ($requestType) {
	case 'get_pvz':
		getPvzList();
		break;
	
	case 'calculate_delivery':
		calculateDelivery();
		break;
	
	case 'create_order':
		createOrder();
		break;
	
	default:
		echo json_encode(['error' => 'Неизвестный тип запроса']);
}

function getPvzList()
{
	global $cdek;
	$pvzList = $cdek->getListPVZ();
	
	if (isset($pvzList['error'])) {
		header('Content-Type: application/json');
		echo json_encode([]);
		return;
	}
	
	header('Content-Type: application/json');
	echo json_encode($pvzList);
}

function calculateDelivery()
{
	global $cdek;
	
	$toLocation = $_REQUEST['toLocation'] ?? null;
	
	if (!$toLocation) {
		echo json_encode(['error' => 'Недостаточно данных для расчета']);
		return;
	}
	
	$result = $cdek->calculate($toLocation);
	
	if (isset($result['error'])) {
		echo json_encode(['error' => 'Ошибка при расчете доставки']);
		return;
	}
	
	header('Content-Type: application/json');
	echo json_encode($result);
}

function createOrder()
{
	global $cdek, $telegram;
	
	$recipientName  = $_REQUEST['recipientName'] ?? null;
	$recipientPhone = $_REQUEST['recipientPhone'] ?? null;
	$deliveryPoint  = $_REQUEST['deliveryPoint'] ?? null;
	$deliveryPrice  = $_REQUEST['deliveryPrice'] ?? null;
	$recipientEmail = $_REQUEST['email'] ?? '';
	$tshirtSize     = $_REQUEST['size'] ?? 'M';
	$pvzName        = $_REQUEST['pvzName'] ?? '';
	$pvzAddress     = $_REQUEST['pvzAddress'] ?? '';

	if (!isset($recipientName, $recipientPhone, $deliveryPoint, $deliveryPrice)) {
		echo json_encode(['error' => 'Недостаточно данных для создания заказа']);
		return;
	}
	
	$result = $cdek->createOrder(
		[
			'delivery_point'  => $deliveryPoint,
			'recipient_name'  => $recipientName,
			'recipient_phone' => $recipientPhone,
			'delivery_price' => $deliveryPrice,
		]
	);

	if (isset($result['error'])) {
		echo json_encode(['error' => 'Ошибка при создании заказа']);
		return;
	}
	
	$telegram->sendOrderNotification([
		'name' => $recipientName,
		'phone' => $recipientPhone,
		'email' => $recipientEmail,
		'size' => $tshirtSize,
		'pvz_name' => $pvzName,
		'pvz_address' => $pvzAddress,
		'delivery_price' => $deliveryPrice
	]);
	
	header('Content-Type: application/json');
	echo json_encode($result);
}

?>