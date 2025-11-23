<?php
require_once(DIR_SYSTEM . 'library/equotix/shoppingcart/equotix.php');
class ControllerExtensionModuleShoppingCart extends Equotix {
	protected $version = '4.0.0';
	protected $code = 'shoppingcart';
	protected $extension = 'Shopping Cart';
	protected $extension_id = '70';
	protected $purchase_url = 'shopping-cart';
	protected $purchase_id = '5910';
	protected $error = array();

	public function index() {
		$this->load->language('extension/module/shoppingcart');

		$this->document->setTitle(strip_tags($this->language->get('heading_title')));
		
		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->model_setting_setting->editSetting('module_shoppingcart', $this->request->post);		
			
			$this->session->data['success'] = $this->language->get('text_success');
		
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}
		
		$data['heading_title'] = $this->language->get('heading_title');
	
		$data['entry_status'] = $this->language->get('entry_status');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled']	= $this->language->get('text_enabled');
		$data['text_disabled']	= $this->language->get('text_disabled');
		
		$data['tab_general'] = $this->language->get('tab_general');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		
 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true)
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
   		);
		
   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/shoppingcart', 'user_token=' . $this->session->data['user_token'], true)
   		);
		
		$data['action'] = $this->url->link('extension/module/shoppingcart', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		
		if (isset($this->request->post['module_shoppingcart_status'])) { 
			$data['module_shoppingcart_status'] = $this->request->post['module_shoppingcart_status']; 
		} else { 
			$data['module_shoppingcart_status'] = $this->config->get('module_shoppingcart_status');
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->generateOutput('extension/module/shoppingcart', $data);
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/shoppingcart') || !$this->validated()) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}