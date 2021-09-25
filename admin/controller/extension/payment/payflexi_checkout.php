<?php
class ControllerExtensionPaymentPayflexiCheckout extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/payflexi_checkout');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_payflexi_checkout', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token']. '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['keys'])) {
            $data['error_keys'] = $this->error['keys'];
        } else {
            $data['error_keys'] = '';
        }

        $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_home'),
        'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
        'text' => $this->language->get('text_payment'),
        'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
        'text' => $this->language->get('heading_title'),
        'href' => $this->url->link('extension/payment/payflexi_checkout', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/payflexi_checkout', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['payment_payflexi_checkout_gateways'] = array();

		$data['payment_payflexi_checkout_gateways'][] = array(
			'name' => 'Stripe',
			'code' => 'stripe'
		);

		$data['payment_payflexi_checkout_gateways'][] = array(
			'name' => 'Paystack',
			'code' => 'paystack'
		);

		$data['payment_payflexi_checkout_gateways'][] = array(
			'name' => 'Flutterwave',
			'code' => 'flutterwave'
		);

		$data['payment_payflexi_checkout_gateways'][] = array(
			'name' => 'PayFlexi (For Nigeria Merchants Only)',
			'code' => 'payflexi'
		);
        
        if (isset($this->request->post['payment_payflexi_checkout_gateway_id'])) {
            $data['payment_payflexi_checkout_gateway_id'] = $this->request->post['payment_payflexi_checkout_gateway_id'];
        } else {
            $data['payment_payflexi_checkout_gateway_id'] = $this->config->get('payment_payflexi_checkout_gateway_id');
        }
        
        if (isset($this->request->post['payment_payflexi_checkout_live_secret'])) {
            $data['payment_payflexi_checkout_live_secret'] = $this->request->post['payment_payflexi_checkout_live_secret'];
        } else {
            $data['payment_payflexi_checkout_live_secret'] = $this->config->get('payment_payflexi_checkout_live_secret');
        }
 
        if (isset($this->request->post['payment_payflexi_checkout_live_public'])) {
            $data['payment_payflexi_checkout_live_public'] = $this->request->post['payment_payflexi_checkout_live_public'];
        } else {
            $data['payment_payflexi_checkout_live_public'] = $this->config->get('payment_payflexi_checkout_live_public');
        }
 
        if (isset($this->request->post['payment_payflexi_checkout_test_secret'])) {
            $data['payment_payflexi_checkout_test_secret'] = $this->request->post['payment_payflexi_checkout_test_secret'];
        } else {
            $data['payment_payflexi_checkout_test_secret'] = $this->config->get('payment_payflexi_checkout_test_secret');
        }
 
        if (isset($this->request->post['payment_payflexi_checkout_test_public'])) {
            $data['payment_payflexi_checkout_test_public'] = $this->request->post['payment_payflexi_checkout_test_public'];
        } else {
            $data['payment_payflexi_checkout_test_public'] = $this->config->get('payment_payflexi_checkout_test_public');
        }
 
        if (isset($this->request->post['payment_payflexi_checkout_live'])) {
            $data['payment_payflexi_checkout_live'] = $this->request->post['payment_payflexi_checkout_live'];
        } else {
            $data['payment_payflexi_checkout_live'] = $this->config->get('payment_payflexi_checkout_live');
        }

        if (isset($this->request->post['payment_payflexi_checkout_debug'])) {
            $data['payment_payflexi_checkout_debug'] = $this->request->post['payment_payflexi_checkout_debug'];
        } else {
            $data['payment_payflexi_checkout_debug'] = $this->config->get('payment_payflexi_checkout_debug');
        }

        if (isset($this->request->post['payment_payflexi_checkout_total'])) {
            $data['payment_payflexi_checkout_total'] = $this->request->post['payment_payflexi_checkout_total'];
        } else {
            $data['payment_payflexi_checkout_total'] = $this->config->get('payment_payflexi_checkout_total');
        }

        if (isset($this->request->post['payment_payflexi_checkout_order_status_id'])) {
            $data['payment_payflexi_checkout_order_status_id'] = $this->request->post['payment_payflexi_checkout_order_status_id'];
        } else {
            $data['payment_payflexi_checkout_order_status_id'] = $this->config->get('payment_payflexi_checkout_order_status_id');
        }

        if (isset($this->request->post['payment_payflexi_checkout_pending_status_id'])) {
            $data['payment_payflexi_checkout_pending_status_id'] = $this->request->post['payment_payflexi_checkout_pending_status_id'];
        } else {
            $data['payment_payflexi_checkout_pending_status_id'] = $this->config->get('payment_payflexi_checkout_pending_status_id');
        }

        if (isset($this->request->post['payment_payflexi_checkout_completed_status_id'])) {
            $data['payment_payflexi_checkout_completed_status_id'] = $this->request->post['payment_payflexi_checkout_completed_status_id'];
        } else {
            $data['payment_payflexi_checkout_completed_status_id'] = $this->config->get('payment_payflexi_checkout_completed_status_id');
        }

        if (isset($this->request->post['payment_payflexi_checkout_canceled_status_id'])) {
            $data['payment_payflexi_checkout_canceled_status_id'] = $this->request->post['payment_payflexi_checkout_canceled_status_id'];
        } else {
            $data['payment_payflexi_checkout_canceled_status_id'] = $this->config->get('payment_payflexi_checkout_canceled_status_id');
        }

        if (isset($this->request->post['payment_payflexi_checkout_failed_status_id'])) {
            $data['payment_payflexi_checkout_failed_status_id'] = $this->request->post['payment_payflexi_checkout_failed_status_id'];
        } else {
            $data['payment_payflexi_checkout_failed_status_id'] = $this->config->get('payment_payflexi_checkout_failed_status_id');
        }

        if ($this->config->get('payment_payflexi_checkout_live')) {
            $secret_key = $this->config->get('payment_payflexi_checkout_live_secret');
        } else {
            $secret_key = $this->config->get('payment_payflexi_checkout_test_secret');
        }

        $data['payment_payflexi_checkout_webhook_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/payflexi_checkout/webhook';

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_payflexi_checkout_geo_zone_id'])) {
            $data['payment_payflexi_checkout_geo_zone_id'] = $this->request->post['payment_payflexi_checkout_geo_zone_id'];
        } else {
            $data['payment_payflexi_checkout_geo_zone_id'] = $this->config->get('payment_payflexi_checkout_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_payflexi_checkout_status'])) {
            $data['payment_payflexi_checkout_status'] = $this->request->post['payment_payflexi_checkout_status'];
        } else {
            $data['payment_payflexi_checkout_status'] = $this->config->get('payment_payflexi_checkout_status');
        }

        if (isset($this->request->post['payment_payflexi_checkout_sort_order'])) {
            $data['payment_payflexi_checkout_sort_order'] = $this->request->post['payment_payflexi_checkout_sort_order'];
        } else {
            $data['payment_payflexi_checkout_sort_order'] = $this->config->get('payment_payflexi_checkout_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/payflexi_checkout', $data));
    }

    public function install() {
		$this->load->model('extension/payment/payflexi_checkout');
		$this->model_extension_payment_payflexi_checkout->install();
	}

	public function uninstall() {
		$this->load->model('extension/payment/payflexi_checkout');
		$this->model_extension_payment_payflexi_checkout->uninstall();
	}
    
    private function valid_key($value, $mode, $access)
    {
        return (substr_compare($value, ('pf_'.$mode.'_'.substr($access, 0, 1).'k'), 0, 8, true)===0);
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/payflexi_checkout')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        $live_secret = $this->request->post['payment_payflexi_checkout_live_secret'];
        $live_public = $this->request->post['payment_payflexi_checkout_live_public'];
        $test_secret = $this->request->post['payment_payflexi_checkout_test_secret'];
        $test_public = $this->request->post['payment_payflexi_checkout_test_public'];
 
        if ($this->request->post['payment_payflexi_checkout_live'] && (!$this->valid_key($live_secret, 'live', 'secret') || !$this->valid_key($live_public, 'live', 'public'))) {
            $this->error['keys'] = $this->language->get('error_live_keys');
        }

        if (!$this->request->post['payment_payflexi_checkout_live'] && (!$this->valid_key($test_secret, 'test', 'secret') || !$this->valid_key($test_public, 'test', 'public'))) {
            $this->error['keys'] = $this->language->get('error_test_keys');
        }

        return !$this->error;
    }
    
}
