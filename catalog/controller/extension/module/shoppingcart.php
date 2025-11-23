<?php 
require_once(DIR_SYSTEM . 'library/equotix/shoppingcart/equotix.php');
class ControllerExtensionModuleShoppingCart extends Equotix {
	protected $code = 'shoppingcart';
	protected $extension_id = '70';
	
	public function index() {
		$this->document->addStyle('catalog/view/theme/default/stylesheet/shoppingcart.css');
	
		$this->load->language('extension/module/shoppingcart');
		
      	if (isset($this->request->get['remove'])) {
          	$this->cart->remove($this->request->get['remove']);
			
			unset($this->session->data['vouchers'][$this->request->get['remove']]);
      	}
			
		// Totals
		$this->load->model('setting/extension');
		
		$totals = array();					
		$total = 0;
		$taxes = $this->cart->getTaxes();
		
		$total_data = array(
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		);
		
		if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
			$sort_order = array(); 
			
			$results = $this->model_setting_extension->getExtensions('total');
			
			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
			}
			
			array_multisort($sort_order, SORT_ASC, $results);
			
			foreach ($results as $result) {
				if ($this->config->get($result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);
		
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}			
			}
			
			$total_data = $totals;
			
			$sort_order = array(); 
		  
			foreach ($total_data as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $total_data);
		}
		
		$data['totals'] = $total_data;
		
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_items'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
		
		$data['text_empty'] = $this->language->get('text_empty');
		$data['text_cart'] = $this->language->get('text_cart');
		$data['text_checkout'] = $this->language->get('text_checkout');
		
		$data['button_remove'] = $this->language->get('button_remove');
		
		$this->load->model('tool/image');
		$this->load->model('tool/upload');
		
		$data['products'] = array();
			
		foreach ($this->cart->getProducts() as $product) {
			$image_width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width');
			$image_height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height');

			if ($product['image']) {
				$image = $this->model_tool_image->resize($product['image'], $image_width, $image_height);
			} else {
				$image = '';
			}
			
			$option_data = array();
			
			foreach ($product['option'] as $option) {
				if ($option['type'] != 'file') {
					$value = $option['value'];
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

					if ($upload_info) {
						$value = $upload_info['name'];
					} else {
						$value = '';
					}
				}			
				
				$option_data[] = array(								   
					'name'  => $option['name'],
					'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
					'type'  => $option['type']
				);
			}
		
			if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
				$total = $this->currency->format($this->tax->calculate($product['total'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$total = false;
			}
			
			$data['products'][] = array(
				'key'      => isset($product['cart_id']) ? $product['cart_id'] : $product['key'],
				'thumb'    => $image,
				'name'     => $product['name'],
				'model'    => $product['model'], 
				'option'   => $option_data,
				'quantity' => $product['quantity'],
				'price'    => $price,	
				'total'    => $total,	
				'href'     => $this->url->link('product/product', 'product_id=' . $product['product_id'])		
			);
		}
		
		// Gift Voucher
		$data['vouchers'] = array();
		
		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $key => $voucher) {
				$amount = $this->currency->format($voucher['amount'], $this->session->data['currency']);
				
				$data['vouchers'][] = array(
					'key'         => $key,
					'description' => $voucher['description'],
					'amount'      => $amount
				);
			}
		}
		
		$data['totals'] = array();

		foreach ($total_data as $result) {
			$text = $this->currency->format($result['value'], $this->session->data['currency']);
			
			$data['totals'][] = array(
				'title' => $result['title'],
				'text'  => $text
			);
		}
		
		$data['cart'] = $this->url->link('checkout/cart');
						
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		
		if (!$this->validated()) {
			return;
		}
		
		if (!isset($this->request->get['direct'])) {
			return $this->load->view('extension/module/shoppingcart', $data);
		} else {
			$this->response->setOutput($this->load->view('extension/module/shoppingcart', $data));
		}
	}
}