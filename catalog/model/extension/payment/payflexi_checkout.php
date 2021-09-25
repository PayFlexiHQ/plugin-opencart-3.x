<?php
class ModelExtensionPaymentPayflexiCheckout extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/payflexi_checkout');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_payflexi_checkout_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('payment_payflexi_checkout_total') > 0 && $this->config->get('payment_payflexi_checkout_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('payment_payflexi_checkout_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => 'payflexi_checkout',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => $this->config->get('payment_payflexi_checkout_sort_order')
            );
        }

        return $method_data;
    }

    public function getOrder($order_id) {

		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "payflexi_checkout_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($qry->num_rows) {
			$order = $qry->row;
			return $order;
		} else {
			return false;
		}
	}

    public function getTransactions($payflexi_order_id, $currency_code) {
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
    

    public function addOrder($order_data) {

		$this->db->query("INSERT INTO `" . DB_PREFIX . "payflexi_checkout_order` SET
			`order_id` = '" . (int)$order_data['order_id'] . "',
			`status` = '" . $this->db->escape($order_data['status']) . "',
			`currency_code` = '" . $this->db->escape($order_data['currency_code']) . "',
			`total_order_amount` = '" . (float)$order_data['total_order_amount'] . "',
            `payment_plans` = '" . $this->db->escape($order_data['payment_plans']) . "',
            `no_of_instalments` = '" . $this->db->escape($order_data['no_of_instalments']) . "',
            `instalments_paid` = '" . $this->db->escape($order_data['instalments_paid']) . "',
            `total_amount_paid` = '" . (float)$order_data['total_amount_paid'] . "',
            `date_added` = NOW(),
			`date_modified` = NOW()");

		return $this->db->getLastId();
	}

    public function addTransaction($transaction_data) {

		$this->db->query("INSERT INTO `" . DB_PREFIX . "payflexi_checkout_order_transaction` SET
			`payflexi_order_id` = '" . (int)$transaction_data['payflexi_order_id'] . "',
			`transaction_id` = '" . $this->db->escape($transaction_data['transaction_id']) . "',
            `amount` = '" . (float)$transaction_data['amount'] . "',
            `status` = '" . $this->db->escape($transaction_data['status']) . "',
			`date_added` = NOW(),
			`note` = '" . $this->db->escape($transaction_data['note']) . "'");
	}

	public function updateOrder($order_data, $order_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "payflexi_checkout_order` SET 
        `date_modified` = now(), 
        `payment_plans` = '" . $this->db->escape($order_data['payment_plans']) . "',
        `no_of_instalments` = '" . $this->db->escape($order_data['no_of_instalments']) . "',
        `instalments_paid` = '" . $this->db->escape($order_data['instalments_paid']) . "',
        `status` = '" . $this->db->escape($order_data['status']) . "' WHERE `order_id` = '" . (int)$order_id . "'");
	}

    public function log($data) {
		if ($this->config->get('payment_payflexi_checkout_debug')) {
			$log = new Log('payflexi_checkout_debug.log');
			$backtrace = debug_backtrace();
			$log->write($backtrace[6]['class'] . '::' . $backtrace[6]['function'] . ' Data:  ' . print_r($data, 1));
		}
	}
}
