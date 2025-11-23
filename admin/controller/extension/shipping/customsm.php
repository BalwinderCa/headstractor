<?php 
class ControllerExtensionShippingCustomsm extends Controller {
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
						
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` MODIFY `shipping_method` VARCHAR( 1000 ) NOT NULL");
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "order_total` MODIFY `title` VARCHAR( 1000 ) NOT NULL");
				
		$this->load->language('extension/shipping/customsm');
		
		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setTitle(strip_tags($this->language->get('heading_title_normal')));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {			
			
			$this->model_setting_setting->editSetting('shipping_customsm', $this->request->post);			
			
				$this->session->data['success'] = $this->language->get('text_success');			

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}
		
		$this->event->register( 'view/extension/shipping/customsm/before', new Action('extension/shipping/customsm/theme') );		
		
		$data['user_token'] = $this->session->data['user_token'];		

 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
				
		$this->load->model('localisation/language');
		
		$languages = $this->model_localisation_language->getLanguages();
		
		foreach ($languages as $language) {
			if (isset($this->error['name_' . $language['language_id']])) {
				$data['error_name_' . $language['language_id']] = $this->error['name_' . $language['language_id']];
			} else {
				$data['error_name_' . $language['language_id']] = '';
			}
			
			if (isset($this->error['details_' . $language['language_id']])) {
				$data['error_details_' . $language['language_id']] = $this->error['details_' . $language['language_id']];
			} else {
				$data['error_details_' . $language['language_id']] = '';
			}
		}
		
  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)      		
   		);

   		$data['breadcrumbs'][] = array(
       		'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)      		
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),			
			'href' => $this->url->link('extension/shipping/customsm', 'user_token=' . $this->session->data['user_token'], true)      		
   		);
		
		// styles and scripts		
		$this->document->addStyle('view/stylesheet/customsm.css');				
		
		$data['action'] = $this->url->link('extension/shipping/customsm', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
		
		foreach ($languages as $language) {
			if (isset($this->request->post['shipping_customsm_name_' . $language['language_id']])) {
				$data['shipping_customsm_name_' . $language['language_id']] = $this->request->post['shipping_customsm_name_' . $language['language_id']];
			} elseif ($this->config->get('shipping_customsm_name_' . $language['language_id'])) {
				$data['shipping_customsm_name_' . $language['language_id']] = $this->config->get('shipping_customsm_name_' . $language['language_id']);
			} else {
				$data['shipping_customsm_name_' . $language['language_id']] = $this->language->get('preconf_name');
			}			
			
			if (isset($this->request->post['shipping_customsm_details_' . $language['language_id']])) {
				$data['shipping_customsm_details_' . $language['language_id']] = $this->request->post['shipping_customsm_details_' . $language['language_id']];
			} elseif ($this->config->get('shipping_customsm_details_' . $language['language_id'])) {
				$data['shipping_customsm_details_' . $language['language_id']] = $this->config->get('shipping_customsm_details_' . $language['language_id']);
			} else {
				$data['shipping_customsm_details_' . $language['language_id']] = $this->language->get('preconf_details');
			}
			
			if (isset($this->request->post['shipping_customsm_frontend_' . $language['language_id']])) {
				$data['shipping_customsm_frontend_' . $language['language_id']] = $this->request->post['shipping_customsm_frontend_' . $language['language_id']];
			} elseif ($this->config->get('shipping_customsm_frontend_' . $language['language_id'])) {
				$data['shipping_customsm_frontend_' . $language['language_id']] = $this->config->get('shipping_customsm_frontend_' . $language['language_id']);
			} else {
				$data['shipping_customsm_frontend_' . $language['language_id']] = $this->language->get('preconf_frontend');
			}					
		}
		
		$data['languages'] = $languages;
		
		if (isset($this->request->post['shipping_customsm_total'])) {
			$data['shipping_customsm_total'] = $this->request->post['shipping_customsm_total'];
		} else {
			$data['shipping_customsm_total'] = $this->config->get('shipping_customsm_total'); 
		}
						
		if (isset($this->request->post['shipping_customsm_geo_zone_id'])) {
			$data['shipping_customsm_geo_zone_id'] = $this->request->post['shipping_customsm_geo_zone_id'];
		} else {
			$data['shipping_customsm_geo_zone_id'] = $this->config->get('shipping_customsm_geo_zone_id'); 
		} 
		
		$this->load->model('localisation/geo_zone');
										
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['shipping_customsm_status'])) {
			$data['shipping_customsm_status'] = $this->request->post['shipping_customsm_status'];
		} else {
			$data['shipping_customsm_status'] = $this->config->get('shipping_customsm_status');
		}
		
		if (isset($this->request->post['shipping_customsm_sort_order'])) {
			$data['shipping_customsm_sort_order'] = $this->request->post['shipping_customsm_sort_order'];
		} else {
			$data['shipping_customsm_sort_order'] = $this->config->get('shipping_customsm_sort_order');
		}
		
		/* category */
		$this->load->model('catalog/category');
		$data['categories'] = $this->model_catalog_category->getCategories(array());
		
		if (isset($this->request->post['shipping_customsm_category'])) {
			$data['shipping_customsm_category'] = $this->request->post['shipping_customsm_category'];
		} else {
			$data['shipping_customsm_category'] = $this->config->get('shipping_customsm_category');
		}		
		
		// Stores
		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();
		
		if (isset($this->request->post['shipping_customsm_all'])) {
			$data['shipping_customsm_all'] = $this->request->post['shipping_customsm_all'];
		} else {
			$data['shipping_customsm_all'] = $this->config->get('shipping_customsm_all');
		}

		if (isset($this->request->post['shipping_customsm_store']) && $data['shipping_customsm_all'] == 'stores') {
			$data['shipping_customsm_store'] = $this->request->post['shipping_customsm_store'];
		} elseif ($this->config->get('shipping_customsm_store') && $data['shipping_customsm_all'] == 'stores') {
			$data['shipping_customsm_store'] = $this->config->get('shipping_customsm_store');
		} else {
			$data['shipping_customsm_store'] = array();
		}
		
		/*Weight*/
		if (isset($this->request->post['shipping_customsm_weight'])) {
			$data['shipping_customsm_weight'] = $this->request->post['shipping_customsm_weight'];
		} else {
			$data['shipping_customsm_weight'] = $this->config->get('shipping_customsm_weight');
		}
				
		// Customer selection		
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups(0);

		if (isset($this->request->post['shipping_customsm_to'])) {
			$data['shipping_customsm_to'] = $this->request->post['shipping_customsm_to'];
		} else {
			$data['shipping_customsm_to'] = $this->config->get('shipping_customsm_to');
		}
		
		if (isset($this->request->post['shipping_customsm_customer_group_id']) && $data['shipping_customsm_to'] == 'customer_group') {
			$data['shipping_customsm_customer_group_id'] = $this->request->post['shipping_customsm_customer_group_id'];
		} elseif ($this->config->get('shipping_customsm_customer_group_id') && $data['shipping_customsm_to'] == 'customer_group') {
			$data['shipping_customsm_customer_group_id'] = $this->config->get('shipping_customsm_customer_group_id');
		} else {
			$data['shipping_customsm_customer_group_id'] = array();
		}

		if (isset($this->request->post['shipping_customsm_customer']) && $data['shipping_customsm_to'] == 'customer') {
			$customers = $this->request->post['shipping_customsm_customer'];
		} elseif ($this->config->get('shipping_customsm_customer') && $data['shipping_customsm_to'] == 'customer') {
			$customers = $this->config->get('shipping_customsm_customer');
		} else {
			$customers = array();
		}

		$data['customers'] = array();

		$this->load->model('customer/customer');

		foreach ($customers as $customer_id) {
			$customer_info = $this->model_customer_customer->getCustomer($customer_id);

			if ($customer_info) {
				$customer_group = $this->model_customer_customer_group->getCustomerGroupDescriptions($customer_info['customer_group_id']);

				$data['customers'][] = array(
					'customer_id'    => $customer_info['customer_id'],
					'name'           => $customer_info['firstname'] . ' ' . $customer_info['lastname'],
					'customer_group' => $customer_group[$this->config->get('config_language_id')]['name']
				);
			}
		}
	// End Customer Selection
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/customsm', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/customsm')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();
		
		foreach ($languages as $language) {	
			if (empty($this->request->post['shipping_customsm_name_' . $language['language_id']])) {
				$this->error['name_' .  $language['language_id']] = $this->language->get('error_name');
			}
			
			if (empty($this->request->post['shipping_customsm_details_' . $language['language_id']])) {
				$this->error['details_' .  $language['language_id']] = $this->language->get('error_details');
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