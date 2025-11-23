<?php
class ControllerExtensionPaymentCustompm extends Controller {
	public function index() {
		$this->load->language('extension/payment/custompm');		
		
		$data['name'] = html_entity_decode($this->config->get('payment_custompm_name' . $this->config->get('config_language_id')));		
		
		$data['description'] = html_entity_decode($this->config->get('payment_custompm_description' . $this->config->get('config_language_id')));		
		
		return $this->load->view('extension/payment/custompm', $data);
	}
	
	public function confirm() {
		if ($this->session->data['payment_method']['code'] == 'custompm') {
		$this->load->language('extension/payment/custompm');
		
		$this->load->model('checkout/order');
		
		$comment  = $this->language->get('text_instruction') . "\n\n";
		$comment .= html_entity_decode($this->config->get('payment_custompm_description' . $this->config->get('config_language_id'))) . "\n\n";
		$comment .= $this->language->get('text_payment');
		
		$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_custompm_order_status_id'), $comment, true);
		
		$json['redirect'] = $this->url->link('checkout/success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	}
}
?>