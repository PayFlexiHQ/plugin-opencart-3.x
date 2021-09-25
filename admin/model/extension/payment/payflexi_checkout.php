<?php

class ModelExtensionPaymentPayFlexiCheckout extends Model {

    public function install() {
        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payflexi_checkout_order` (
			  `payflexi_order_id` int(11) NOT NULL AUTO_INCREMENT,
			  `order_id` int(11) NOT NULL,
			  `status` CHAR(20) DEFAULT NULL,
			  `currency_code` CHAR(3) NOT NULL,
			  `total_order_amount` DECIMAL( 10, 2 ) NOT NULL,
              `payment_plans` INT(1) DEFAULT NULL,
              `no_of_instalments` INT(11) DEFAULT NULL,
              `instalments_paid` INT(11) DEFAULT NULL,
              `total_amount_paid` DECIMAL( 10, 2 ) NOT NULL,
              `date_added` DATETIME NOT NULL,
			  `date_modified` DATETIME NOT NULL,
			  PRIMARY KEY (`payflexi_order_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci
		");

		$this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payflexi_checkout_order_transaction` (
            `payflexi_order_transaction_id` int(11) NOT NULL AUTO_INCREMENT,
            `payflexi_order_id` int(11) NOT NULL,
            `transaction_id` CHAR(50) NOT NULL,
            `amount` DECIMAL( 10, 2 ) NOT NULL,
            `status` CHAR(20) DEFAULT NULL,
            `date_added` DATETIME NOT NULL,
            `note` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`payflexi_order_transaction_id`)
            ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci
        ");

    }
    
    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "payflexi_checkout_order`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "payflexi_checkout_order_transaction`");
    }

    public function getOrder($order_id) {

		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "payflexi_checkout_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($qry->num_rows) {
			$order = $qry->row;
			$order['transactions'] = $this->getTransactions($order['payflexi_order_id'], $qry->row['currency_code']);

			return $order;
		} else {
			return false;
		}
	}

    private function getTransactions($payflexi_order_id, $currency_code) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "payflexi_checkout_order_transaction` WHERE `payflexi_order_id` = '" . (int)$payflexi_order_id . "'");

		$transactions = array();
		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				$row['amount'] = $this->currency->format($row['amount'], $currency_code, false);
				$transactions[] = $row;
			}
			return $transactions;
		} else {
			return false;
		}
	}

	public function log($message) {
		if ($this->config->get('payment_payflexi_checkout_debug')) {
			$log = new Log('payflexi-checkout.log');
			$log->write($message);
		}
	}

}