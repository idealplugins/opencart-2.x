<?php

/**

	iDEALplugins.nl
    TargetPay plugin v1.1 for Opencart 1.5+

    (C) Copyright Yellow Melon 2013

 	@file 		TargetPay Admin Model
	@author		Yellow Melon B.V. / www.idealplugins.nl

 */

class ModelPaymentmrcash extends Model {

  	public function createTable() {

		$sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."mrcash` (
				`order_id` VARCHAR(64) DEFAULT NULL,
			    `method` VARCHAR(6) DEFAULT NULL,
				`mrcash_txid` VARCHAR(64) DEFAULT NULL,
			    `mrcash_response` VARCHAR(128) DEFAULT NULL,
			    `paid` DATETIME DEFAULT NULL,
				PRIMARY KEY (`order_id`))";

    	$result = $this->db->query ($sql);
	  	}

	}
?>
