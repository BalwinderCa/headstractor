<?php 
class ControllerExtensionPaymentCustompm extends Controller {
	private $error = array(); 

	public function index() {
		$data['oc_licensing_home'] = 'https://licence.mikadesign.co.uk/'; 
		$data['extension_id'] = 20722;   
		$admin_support_email = 'support@mikadesign.co.uk';

		$this->load->language('oc_licensing/oc_licensing');
		
		$data['regerror_email'] = $this->language->get('regerror_email');
		$data['regerror_orderid'] = $this->language->get('regerror_orderid');
		$data['regerror_noreferer'] = $this->language->get('regerror_noreferer');
		$data['regerror_localhost'] = $this->language->get('regerror_localhost');
		$data['regerror_licensedupe'] = $this->language->get('regerror_licensedupe');
		$data['regerror_quote_msg'] = $this->language->get('regerror_quote_msg');
		$data['license_purchase_thanks'] = sprintf($this->language->get('license_purchase_thanks'), $admin_support_email);
		$data['license_registration'] = $this->language->get('license_registration');
		$data['license_opencart_email'] = $this->language->get('license_opencart_email');
		$data['license_opencart_orderid'] = $this->language->get('license_opencart_orderid');
		$data['check_email'] = $this->language->get('check_email');
		$data['check_orderid'] = $this->language->get('check_orderid');
		$data['server_error_curl'] = $this->language->get('server_error_curl');

		if(isset($this->request->get['emailmal'])){
			$data['emailmal'] = true;
		}

		if(isset($this->request->get['regerror'])){
		    if($this->request->get['regerror']=='emailmal'){
		    	$this->error['warning'] = $this->language->get('regerror_email');
		    }elseif($this->request->get['regerror']=='orderidmal'){
		    	$this->error['warning'] = $this->language->get('regerror_orderid');
		    }elseif($this->request->get['regerror']=='noreferer'){
		    	$this->error['warning'] = $this->language->get('regerror_noreferer');
		    }elseif($this->request->get['regerror']=='localhost'){
		    	$this->error['warning'] = $this->language->get('regerror_localhost');
		    }elseif($this->request->get['regerror']=='licensedupe'){
		    	$this->error['warning'] = $this->language->get('regerror_licensedupe');
		    }
		}

		$domainssl = explode("//", HTTPS_SERVER);
		$domainnonssl = explode("//", HTTP_SERVER);
		$domain = ($domainssl[1] != '' ? $domainssl[1] : $domainnonssl[1]);

		$data['licensed'] = @file_get_contents($data['oc_licensing_home'] . 'licensed.php?domain=' . $domain . '&extension=' . $data['extension_id']);

		if(!$data['licensed'] || $data['licensed'] == ''){
			if(extension_loaded('curl')) {
		        $post_data = array('domain' => $domain, 'extension' => $data['extension_id']);
		        $curl = curl_init();
		        curl_setopt($curl, CURLOPT_HEADER, false);
		        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		        curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
		        $follow_allowed = ( ini_get('open_basedir') || ini_get('safe_mode')) ? false : true;
		        if ($follow_allowed) {
		            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		        }
		        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 9);
		        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		        curl_setopt($curl, CURLOPT_AUTOREFERER, true); 
		        curl_setopt($curl, CURLOPT_VERBOSE, 1);
		        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		        curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
		        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		        curl_setopt($curl, CURLOPT_URL, $data['oc_licensing_home'] . 'licensed.php');
		        curl_setopt($curl, CURLOPT_POST, true);
		        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
		        $data['licensed'] = curl_exec($curl);
		        curl_close($curl);
		    }else{
		        $data['licensed'] = 'curl';
		    }
		}

		$data['licensed_md5'] = md5($data['licensed']);
		
		$this->load->language('extension/payment/custompm');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setTitle(strip_tags($this->language->get('heading_title_normal_pm')));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_custompm', $this->request->post);				
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}
		
		$this->event->register( 'view/extension/payment/custompm/before', new Action('extension/payment/custompm/theme') );
		
		$data['user_token'] = $this->session->data['user_token'];

 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		$this->load->model('localisation/language');
		
		$languages = $this->model_localisation_language->getLanguages();
		
		foreach ($languages as $language) {
			if (isset($this->error['name' . $language['language_id']])) {
				$data['error_name' . $language['language_id']] = $this->error['name' . $language['language_id']];
			} else {
				$data['error_name' . $language['language_id']] = '';
			}			
		}
		
  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
   		);

   		$data['breadcrumbs'][] = array(
       		'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)      		
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),			
			'href' => $this->url->link('extension/payment/custompm', 'user_token=' . $this->session->data['user_token'], true)      		
   		);
		
		// styles and scripts		
		$this->document->addStyle('view/stylesheet/custompm.css'); 				
		
		$data['action'] = $this->url->link('extension/payment/custompm', 'user_token=' . $this->session->data['user_token'], true);		
		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$this->load->model('localisation/language');
		
		foreach ($languages as $language) {
			if (isset($this->request->post['payment_custompm_name' . $language['language_id']])) {
				$data['payment_custompm_name' . $language['language_id']] = $this->request->post['payment_custompm_name' . $language['language_id']];
			} elseif ($this->config->get('payment_custompm_name' . $language['language_id'])) {
				$data['payment_custompm_name' . $language['language_id']] = $this->config->get('payment_custompm_name' . $language['language_id']);
			} else {
				$data['payment_custompm_name' . $language['language_id']] = $this->language->get('preconf_name');
			}
			
			if (isset($this->request->post['payment_custompm_description' . $language['language_id']])) {
				$data['payment_custompm_description' . $language['language_id']] = $this->request->post['payment_custompm_description' . $language['language_id']];
			} elseif ($this->config->get('payment_custompm_description' . $language['language_id'])) {
				$data['payment_custompm_description' . $language['language_id']] = $this->config->get('payment_custompm_description' . $language['language_id']);
			} else {
				$data['payment_custompm_description' . $language['language_id']] = $this->language->get('preconf_description');
			}				
		}
		
		$data['languages'] = $languages;
						
		if (isset($this->request->post['payment_custompm_order_status_id'])) {
			$data['payment_custompm_order_status_id'] = $this->request->post['payment_custompm_order_status_id'];
		} else {
			$data['payment_custompm_order_status_id'] = $this->config->get('payment_custompm_order_status_id'); 
		} 
		
		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
						
		$this->load->model('setting/extension');
		
		$installed_shipping_methods = $this->model_setting_extension->getInstalled('shipping');
		//var_dump($installed_shipping_methods); die();
		
		foreach ($installed_shipping_methods as $key => $value) {
			$this->load->language('extension/shipping/' . $value);
			$data['shipping_methods'][] = array(
				'title' => $this->language->get('heading_title'),
				'code' => $value
			);
		}
		
		if (isset($this->request->post['payment_custompm_shipping'])) {
			$data['payment_custompm_shipping'] = $this->request->post['payment_custompm_shipping'];
		} else {
			$data['payment_custompm_shipping'] = $this->config->get('payment_custompm_shipping'); 
		} 
						
		if (isset($this->request->post['payment_custompm_status'])) {
			$data['payment_custompm_status'] = $this->request->post['payment_custompm_status'];
		} else {
			$data['payment_custompm_status'] = $this->config->get('payment_custompm_status');
		}
		
		if (isset($this->request->post['payment_custompm_sort_order'])) {
			$data['payment_custompm_sort_order'] = $this->request->post['payment_custompm_sort_order'];
		} else {
			$data['payment_custompm_sort_order'] = $this->config->get('payment_custompm_sort_order');
		}		

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
				
		$this->response->setOutput($this->load->view('extension/payment/custompm', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/custompm')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();
		
		foreach ($languages as $language) {	
				if (empty($this->request->post['payment_custompm_name' . $language['language_id']])) {
				$this->error['name' .  $language['language_id']] = $this->language->get('error_name');
			}			
		}

		return !$this->error;
	}
	
	// pre event handler 
	public function theme(&$view, &$args) {
		// This is only here for compatibility with old templates
		if (substr($view, -3) == 'tpl') {
			$view = substr($view, 0, -3);
		}

		// get the old PHP template engine to work
		if (is_file(DIR_TEMPLATE . $view . '.twig')) {
			$this->config->set('template_engine', 'twig');
		} elseif (is_file(DIR_TEMPLATE . $view . '.tpl')) {
			$this->config->set('template_engine', 'template');
		}		
	}
}
?>