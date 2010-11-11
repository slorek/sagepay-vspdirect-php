<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once('../includes/model/SagePay.php');
require_once('../includes/model/Config.php');
require_once('../includes/model/DB.php');

// Establish the database connection.
$db = new DB(Config::DATABASE);

$dbTransactionAuth	= $db->table('transactions_auth');
$dbTransaction		= $db->table('transactions');

if (isset($_POST['submit'])) {

	set_time_limit(290);

	$sagepay = new SagePay();
	$sagepay->url = Config::SAGEPAY_URL;
	
	$transactionId = uniqid();
	$amount        = $_POST['amount'];
	$description   = $_POST['description'];

	$card 		 = array();
	$billingaddr = array();

	$card['holder'] = $_POST['cardholder'];
	$card['number'] = $_POST['cardnumber'];
	$card['type']   = $_POST['cardtype'];
	$card['expiry'] = $_POST['cardexpiry'];
	$card['cv2']    = $_POST['cardcv2'];

	$billingaddr['address']    = $_POST['billingaddress'];
	$billingaddr['postalcode'] = $_POST['billingpostcode'];
	
	try {
		
		$result = $sagepay->authenticate($transactionId,
									   $amount,
									   $description,
									   $card,
									   $billingaddr);
		
		$dbTransactionAuth->create(array(
			'transaction_id' 		=> $transactionId,
			'sagepay_id'       		=> $result['VPSTxId'],
			'amount'         		=> $amount,
			'sagepay_security_key'	=> $result['SecurityKey'],
			'description'   		=> $description,
			'created_at'     		=> date('Y-m-d H:i:s')));
		
		header('Location: ' . $_SERVER['PHP_SELF']);
		
	} catch (Exception $e) {
		echo 'Transaction failed: ' . $e;
	}
	
} elseif (isset($_POST['authorise'])) {

	set_time_limit(290);

	$sagepay = new SagePay();
	$sagepay->url = Config::SAGEPAY_AUTHORISE_URL;
	
	$transactionId 		= uniqid();
	$oldTransactionId	= $_POST['authorise'];
	$amount        		= $_POST['amount'];
	$description   		= $_POST['description'];
	
	$oldTransaction = $dbTransactionAuth->find($oldTransactionId);
	
	try {
		
		$result = $sagepay->authorise($transactionId,
								 	$amount,
								 	$description,
								 	$oldTransaction->transaction_id,
								 	$oldTransaction->sagepay_id,
								 	$oldTransaction->sagepay_security_key);
		
		$dbTransaction->create(array(
			'transaction_id' 		=> $transactionId,
			'auth_id'				=> $oldTransaction->id,
			'sagepay_id'       		=> $result['VPSTxId'],
			'amount'         		=> $amount,
			'sagepay_security_key'	=> $result['SecurityKey'],
			'sagepay_auth_no'  		=> $result['TxAuthNo'],
			'description'   		=> $description,
			'created_at'     		=> date('Y-m-d H:i:s')));
		
		header('Location: ' . $_SERVER['PHP_SELF']);
		
	} catch (Exception $e) {
		echo 'Transaction failed: ' . $e;
	}
}


// Find any existing transactions.
$transactions = $dbTransactionAuth->find();

if ($transactions instanceof DB_Record) {
	$transactions = array($transactions);
}

foreach ($transactions as $transaction) {
	if ($auth_total = $dbTransaction->query("SELECT SUM(amount) AS total_debited FROM `transactions` WHERE `auth_id`='" . mysql_real_escape_string($transaction->id) . "'")) {
		$transaction->total_debited = $auth_total->total_debited;
	}
}

include('../includes/view/index.phtml');