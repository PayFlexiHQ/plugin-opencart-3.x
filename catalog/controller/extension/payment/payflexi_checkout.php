<?php
class ControllerExtensionPaymentPayflexiCheckout extends Controller
{ 
    public function index()
    {
        $this->load->model('checkout/order');

        $this->load->language('extension/payment/payflexi_checkout');

        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['livemode'] = $this->config->get('payment_payflexi_checkout_live');

        if ($this->config->get('payment_payflexi_checkout_live')) {
            $data['key'] = $this->config->get('payment_payflexi_checkout_live_public');
        } else {
            $data['key'] = $this->config->get('payment_payflexi_checkout_test_public');
        }

        if ($this->config->get('payment_payflexi_checkout_gateway_id')) {
            $data['gateway'] = $this->config->get('payment_payflexi_checkout_gateway_id');
        } else {
            $data['gateway'] = 'stripe';
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {

            $data['currency'] = $order_info['currency_code'];
            $data['ref']      = uniqid('' . $this->session->data['order_id'] . '-');
            $data['amount']   = intval($order_info['total']);
            $data['email']    = $order_info['email'];
            $data['name'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
            $data['callback'] = $this->url->link('extension/payment/payflexi_checkout/callback', 'trxref=' . rawurlencode($data['ref']), 'SSL');
            $data['cancel_return'] = $this->url->link('checkout/checkout', '', true);

            $product_names = '';
            $product_descriptions = '';
            $product_url = '';
            $product_urls = array();
            $product_image = '';
            $product_images = array();

            foreach ($this->cart->getProducts() as $product) {
                $name  = htmlspecialchars($product['name']);
                $quantity = $product['quantity'];
                $product_names .= $name . ' (Qty: ' . $quantity . ')';
                $product_names .= ' | ';

                $description = (string)substr($product['name'], 0, 26);
                $product_descriptions .= $description . '</br></br>';

                $url = $this->url->link('product/product', 'product_id=' . $product['product_id']);
                $product_url = $url;
                $product_urls[] = array('name' => $name, 'url' => $url);

                $image = $this->config->get('config_url') . 'image/' . $product['image'];
                $product_image = $image;
                $product_images[] = $image;
            } 
            
            $data['product_names'] = rtrim($product_names, ' | ' );
            $data['product_descriptions'] = rtrim($product_descriptions, '</br></br>');
            $data['product_url'] = $product_url;
            $data['product_urls'] = json_encode($product_urls);
            $data['product_image'] = $product_image;
            $data['product_images'] = json_encode($product_images);       
        }

        return $this->load->view('extension/payment/payflexi_checkout', $data);
    }

    private function query_api_transaction_verify($reference)
    {
        if ($this->config->get('payment_payflexi_checkout_live')) {
            $skey = $this->config->get('payment_payflexi_checkout_live_secret');
        } else {
            $skey = $this->config->get('payment_payflexi_checkout_test_secret');
        }

        $context = stream_context_create(
            array(
                'http'=>array(
                'method'=>"GET",
                'header'=>"Authorization: Bearer " .  $skey,
                'user-agent'=>"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
                )
            )
        );
        $url = 'https://api.payflexi.co/merchants/transactions/'. rawurlencode($reference);
        $request = file_get_contents($url, false, $context);
        return json_decode($request, true);
    }

    private function redir_and_die($url, $onlymeta = false)
    {
        if (!headers_sent() && !$onlymeta) {
            header('Location: ' . $url);
        }
        echo "<meta http-equiv=\"refresh\" content=\"0;url=" . addslashes($url) . "\" />";
        die();
    }

    public function callback()
    {
        if (isset($this->request->get['trxref'])) {
            $trxref = $this->request->get['trxref'];

            // order id is what comes before the first dash in trxref
            $order_id = substr($trxref, 0, strpos($trxref, '-'));
            // if no dash were in transation reference, we will have an empty order_id
            if (!$order_id) {
                $order_id = 0;
            }

            $this->load->model('checkout/order');

            $this->load->model('extension/payment/payflexi_checkout');

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {

                if ($this->config->get('payment_payflexi_checkout_debug')) {
                    $this->log->write('PAYFLEXI CHECKOUT :: CALLBACK DATA: ' . print_r($this->request->get, true));
                }

                // Callback payflexi to get real transaction status
                $payflexi_api_response = $this->query_api_transaction_verify($trxref);

                $order_status_id = $this->config->get('config_order_status_id');

                if (array_key_exists('data', $payflexi_api_response) && array_key_exists('status', $payflexi_api_response['data']) && ($payflexi_api_response['data']['status'] === 'approved')) {

                    $total_amount_paid = $payflexi_api_response['data']['txn_amount'];

                    $total_paid_match = ((float) $total_amount_paid == $this->currency->format($order_info['total'], $order_info['currency_code'], false, false));

                    if ($total_paid_match) {
                        $order_status_id = $this->config->get('payment_payflexi_checkout_completed_status_id');
                    }else{
                        $order_status_id = $this->config->get('payment_payflexi_checkout_order_status_id');
                    }
                    $redir_url = $this->url->link('checkout/success');
                } elseif (array_key_exists('data', $payflexi_api_response) && array_key_exists('status', $payflexi_api_response['data']) && ($payflexi_api_response['data']['status'] === 'failed')) {
                    $order_status_id = $this->config->get('payment_payflexi_checkout_declined_status_id');
                    $redir_url = $this->url->link('checkout/checkout', '', 'SSL');
                } else {
                    $order_status_id = $this->config->get('payment_payflexi_checkout_canceled_status_id');
                    $redir_url = $this->url->link('checkout/checkout', '', 'SSL');
                }

                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);

				$payflexi_order_data = array(
					'order_id' => $order_id,
					'status' => $payflexi_api_response['data']['status'],
                    'currency_code' => $payflexi_api_response['data']['currency'],
                    'total_order_amount' => $payflexi_api_response['data']['amount'],
                    'payment_plans' => 0,
                    'no_of_instalments' => 0,
                    'instalments_paid' => 0,
                    'total_amount_paid' => $payflexi_api_response['data']['txn_amount']
				);

               $payflexi_order_id = $this->model_extension_payment_payflexi_checkout->addOrder($payflexi_order_data);

                $payflexi_transaction_data = array(
					'payflexi_order_id' => $payflexi_order_id,
                    'transaction_id' => $payflexi_api_response['data']['reference'],
                    'amount' => $payflexi_api_response['data']['txn_amount'],
                    'status' => $payflexi_api_response['data']['status'],
                    'note' => $payflexi_api_response['message']
				);

                $this->model_extension_payment_payflexi_checkout->addTransaction($payflexi_transaction_data);

                $this->redir_and_die($redir_url);
            }
        }
    }

    public function webhook()
    {
        if ( ( strtoupper( $_SERVER['REQUEST_METHOD'] ) != 'POST' ) || ! array_key_exists('HTTP_X_PAYFLEXI_SIGNATURE', $_SERVER) ) {
            exit;
        }

        if ($this->config->get('payment_payflexi_checkout_live')) {
            $secret_key = $this->config->get('payment_payflexi_checkout_live_secret');
        } else {
            $secret_key = $this->config->get('payment_payflexi_checkout_test_secret');
        }

        $json = file_get_contents( "php://input" );

        // validate event do all at once to avoid timing attack
        if ( $_SERVER['HTTP_X_PAYFLEXI_SIGNATURE'] !== hash_hmac( 'sha512', $json, $secret_key ) ) {
            exit;
        }

        $event = json_decode($json);

        $this->log->write(['Webhook Transaction' => $event]);

        if ('transaction.approved' == $event->event && 'approved' == $event->data->status) {

            $this->response->addHeader('HTTP/1.1 200 OK');
            $this->response->addHeader('Content-Type: application/json');

            $initial_transaction_reference = $event->data->initial_reference;
            $transaction_reference = $event->data->reference;
            // order id is what comes before the first dash in trxref
            $order_id = substr($initial_transaction_reference, 0, strpos($initial_transaction_reference, '-'));
            // if no dash were in transation reference, we will have an empty order_id
            if (!$order_id) {
                $order_id = 0;
            }

            $this->load->model('checkout/order');

            $this->load->model('extension/payment/payflexi_checkout');

            $order_info = $this->model_checkout_order->getOrder($order_id);

            $payflexi_transaction_data = array(
                'transaction_id' => $event->data->reference,
                'amount' => $event->data->txn_amount,
                'status' => $event->data->status,
                'note' => $event->event
            );

            if ($order_info) {

                $payflexiOrder =  $this->model_extension_payment_payflexi_checkout->getOrder($order_id);

                if(!$payflexiOrder){

                    $payflexi_order_data = array(
                        'order_id' => $order_id,
                        'status' => $event->data->status,
                        'currency_code' =>  $event->data->currency,
                        'total_order_amount' =>  $event->data->amount,
                        'payment_plans' => $event->data->instalment->available ? 1 : 0,
                        'no_of_instalments' => $event->data->instalment->instalments,
                        'instalments_paid' => 0,
                        'total_amount_paid' => $event->data->total_amount_paid
                    );

                    $payflexi_order_id = $this->model_extension_payment_payflexi_checkout->addOrder($payflexi_order_data);

                    $payflexi_transaction_data['payflexi_order_id'] = $payflexi_order_id;

                    $this->model_extension_payment_payflexi_checkout->addTransaction($payflexi_transaction_data);

                    $payflexiOrder =  $this->model_extension_payment_payflexi_checkout->getOrder($order_id);

                }else{

                    $payflexi_order_data = array(
                        'status' => $event->data->status,
                        'currency_code' =>  $event->data->currency,
                        'total_order_amount' =>  $event->data->amount,
                        'payment_plans' => $event->data->instalment->available ? 1 : 0,
                        'no_of_instalments' => $event->data->instalment->instalments,
                        'instalments_paid' => 0,
                        'total_amount_paid' => $event->data->total_amount_paid
                    );

                    $payflexi_order_id = $this->model_extension_payment_payflexi_checkout->updateOrder($payflexi_order_data, $order_id);

                    $total_amount_paid = $event->data->total_amount_paid;
                    $total_order_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);
                    
                    if ($total_amount_paid < $total_order_amount ) {
                        if($transaction_reference === $initial_transaction_reference){
                            $order_status_id = $this->config->get('payment_payflexi_checkout_order_status_id');
                            $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, '', true);

                            $payflexi_transaction_data['payflexi_order_id'] = $payflexi_order_id;
        
                            $this->model_extension_payment_payflexi_checkout->addTransaction($payflexi_transaction_data);
                        }
                        if($transaction_reference !== $initial_transaction_reference){
                            $total_amount_paid = $event->data->total_amount_paid;
                            if($total_amount_paid >= $total_order_amount){
                                $order_status_id = $this->config->get('payment_payflexi_checkout_completed_status_id');
                                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, '', true);

                                $payflexi_transaction_data['payflexi_order_id'] = $payflexi_order_id;
        
                                $this->model_extension_payment_payflexi_checkout->addTransaction($payflexi_transaction_data);
                            }else{
                                $order_status_id = $this->config->get('payment_payflexi_checkout_order_status_id');
                                $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, '', true);

                                $payflexi_transaction_data['payflexi_order_id'] = $payflexi_order_id;
        
                                $this->model_extension_payment_payflexi_checkout->addTransaction($payflexi_transaction_data);
                            }
                        }
                    }
                    
                    if ($total_amount_paid >= $total_order_amount ) {
                        $order_status_id = $this->config->get('payment_payflexi_checkout_completed_status_id');
                        $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, '', true);
                        
                        $payflexi_transaction_data['payflexi_order_id'] = $payflexi_order_id;
        
                        $this->model_extension_payment_payflexi_checkout->addTransaction($payflexi_transaction_data);
                    }

                }


            }
        }

        exit;
       
    }
}
