<?php
class ControllerExtensionModuleInventoryReport extends Controller {
	private $error = array(); 
	
	public function index() {   
		$this->language->load('extension/module/inventoryreport');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('extension/module/inventoryreport');
		$this->load->model('catalog/product');
        
				
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
		
			$this->model_extension_module_inventoryreport->setSetting($this->request->post);		
			
		  $this->session->data['success'] = $this->language->get('text_success');
						
		
        $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}
				
		$data['heading_title'] = $this->language->get('heading_title');
		

		
		

	
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		


		
		
		$data['button_save'] = $this->language->get('button_save');
	
  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => ' :: '
   		);
		
   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/inventoryreport', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => ' :: '
   		);
		
		$data['action'] = $this->url->link('extension/module/inventoryreport', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['cancel'] = $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['modules'] = array();
		

		 
     $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/inventoryreport', $data));
		 
		
		/*$this->template = 'module/inventoryreport.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());*/
	}
	
	public function install() {
       $this->load->model('extension/module/inventoryreport');
       $this->model_extension_module_inventoryreport->createTable(); 
 
       $this->load->model('setting/setting');
       $this->model_setting_setting->editSetting('inventoryreport', array('inventoryreport_status'=>1));
   }
   
   public function uninstall() {
        $this->load->model('extension/module/inventoryreport');
        $this->model_extension_module_inventoryreport->deleteTable();
         
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('inventoryreport', array('inventoryreport_status'=>0));
    }
	
	protected function validate() {
		/*if (!$this->user->hasPermission('modify', 'module/inventoryreport')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}*/
					
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}
?>