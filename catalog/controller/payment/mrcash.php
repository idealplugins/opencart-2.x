<?php

/**

	iDEALplugins.nl
    TargetPay plugin v1.1 for Opencart 1.5+

    (C) Copyright Yellow Melon 2013

 	@file 		TargetPay Catalog Controller
	@author		Yellow Melon B.V. / www.idealplugins.nl

 */

require_once ("system/helper/targetpay.class.php");

class ControllerPaymentmrcash extends Controller {

    /**
     *		Select bank
     */

	protected function index() {
    	$this->language->load('payment/mrcash');
		
		$this->data['text_credit_card'] = $this->language->get('text_credit_card');
		$this->data['text_wait'] = $this->language->get('text_wait');

		$this->data['entry_bank_id'] = $this->language->get('entry_bank_id');
		$this->data['button_confirm'] = $this->language->get('button_confirm');

        $this->data['custom'] = $this->session->data['order_id'];

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/mrcash.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/mrcash.tpl';
			} else {
			$this->template = 'default/template/payment/mrcash.tpl';
			}

		$this->render();
		}

    /**
    		Save txid/order_id pair in database
    */

    public function storeTxid ($method, $txid, $order_id) {

    	$sql = "INSERT IGNORE INTO `".DB_PREFIX."mrcash` SET ".
        	   "`order_id`='".$this->db->escape($order_id)."', ".
        	   "`method`='".$this->db->escape($method)."', ".
        	   "`mrcash_txid`='".$this->db->escape($txid)."'";
    	$this->db->query ($sql);
    	}

    /**
    		Get txid/order_id pair from database
    */

    public function getTxid ($order_id) {

    	$sql = "SELECT * FROM `".DB_PREFIX."mrcash` WHERE `order_id`='".$this->db->escape($order_id)."'";
    	$result = $this->db->query ($sql);
        return $result->rows[0];
    	}

    /**
    		Update txid/order_id pair in database
    */

    public function updateTxid ($order_id, $paid, $tpResponse=false) {

    	if ($paid) {
			$sql = "UPDATE `".DB_PREFIX."mrcash` SET `paid`=now() WHERE `order_id`='".$this->db->escape($order_id)."'";
            } else {
			$sql = "UPDATE `".DB_PREFIX."mrcash` SET `mrcash_response`='".$this->db->escape($tpResponse)."' WHERE `order_id`='".$this->db->escape($order_id)."'";
            }
    	$result = $this->db->query ($sql);
    	}


    /**
    		Start payment
    */

	public function send() {

		$payment_type = 'Sale';

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $rtlo = ($this->config->get('mrcash_rtlo')) ? $this->config->get('mrcash_rtlo') : 93929; // Default TargetPay

        if ($order_info['currency_code']!="EUR") {

			$this->log->write("Invalid currency code ".$order_info['currency_code']);
            $json['error'] = "Invalid currency code ".$order_info['currency_code'];
        	} else {

			$targetPay = new TargetPayCore ("MRC", $rtlo, "d949975cfdf8b2f037d393be79da93cb", "nl", false);
			$targetPay->setAmount ( round($order_info['total'] * 100));
			$targetPay->setDescription ( "Order #". $this->session->data['order_id'] );
			if (!empty($this->request->post['bank_id'])) {
            	 $targetPay->setBankId ( $this->request->post['bank_id'] );
                 }
			$targetPay->setCancelUrl ( $this->url->link('checkout/cart', '', 'SSL') );
			$targetPay->setReturnUrl ( $this->url->link('checkout/success', '', 'SSL') );
			$targetPay->setReportUrl ( $this->url->link('payment/mrcash/callback', 'order_id='.$this->session->data['order_id'], 'SSL') );

			$bankUrl = $targetPay->startPayment();

            $this->storeTxid ($targetPay->getPayMethod(), $targetPay->getTransactionId(), $this->session->data['order_id']);

			if (!$bankUrl) {
				$this->log->write('TargetPay start payment failed: '.$targetPay->getErrorMessage());
	            $json['error'] = 'TargetPay start payment failed: '.$targetPay->getErrorMessage();
				} else {
	            $json['success'] = $bankUrl;
	            }
            }

		$this->response->setOutput(json_encode($json));
		}


    /**
            Handle payment result
	*/


	public function callback() {

    	$order_id = 0;
    	if (!empty($_GET["order_id"])) {
	     	$order_id = (int) $_GET["order_id"];
            }

    	if (!empty($_GET["amp;order_id"])) {
	     	$order_id = (int) $_GET["amp;order_id"]; // Buggy redirects
            }

        if ($order_id==0) {
			$this->log->write('TargetPay callback(), no order_id passed');
            die();
        	}

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);

        $targetPayTx = $this->getTxid ($order_id);

        if (!$targetPayTx) {
			$this->log->write('Could not find TargetPay transaction data for order_id='.$order_id);
            die();
        	}
        $rtlo = ($this->config->get('mrcash_rtlo')) ? $this->config->get('mrcash_rtlo') : 93929; // Default TargetPay

		$targetPay = new TargetPayCore ("MRC", $rtlo, "d949975cfdf8b2f037d393be79da93cb", "nl", false);
		$targetPay->checkPayment ($targetPayTx["mrcash_txid"]);

		$order_status_id = $this->config->get('mrcash_order_status_id');
        if (!$order_status_id) {
        	$order_status_id = 1; // Default to 'pending' after payment
        	}

        if ($targetPay->getPaidStatus() || $this->config->get('mrcash_test')) {
        	$this->updateTxid ($order_id, true);
			$this->model_checkout_order->confirm($order_id, $order_status_id);
        	} else {
        	$this->updateTxid ($order_id, false, $targetPay->getErrorMessage() );
			$this->model_checkout_order->update($order_id, 7); // Cancelled = 7
            }

    	die ("45000");
		}
	}
?>
