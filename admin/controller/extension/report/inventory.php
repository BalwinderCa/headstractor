<?php
static $config = NULL;
static $log = NULL;

// Error Handler
function error_handler_for_export($errno, $errstr, $errfile, $errline) {
	global $config;
	global $log;
	
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$errors = "Notice";
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$errors = "Warning";
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$errors = "Fatal Error";
			break;
		default:
			$errors = "Unknown";
			break;
	}
		
	if (($errors=='Warning') || ($errors=='Unknown')) {
		return true;
	}

	if ($config->get('config_error_display')) {
		echo '<b>' . $errors . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
	}
	
	if ($config->get('config_error_log')) {
		$log->write('PHP ' . $errors . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	return true;
}


function fatal_error_shutdown_handler_for_export()
{
	$last_error = error_get_last();
	if ($last_error['type'] === E_ERROR) {
		// fatal error
		error_handler_for_export(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
	}
}





class ControllerExtensionReportInventory extends Controller {
	public function index() {
		
		$data['url_product'] = (HTTPS_SERVER . 'index.php?route=catalog/product/update&product_id=');
	     
		$this->load->language('extension/report/inventory');

		$this->document->setTitle($this->language->get('heading_title'));
		
		
 		if (isset($this->session->data['warning'])) {
			$data['error_warning'] = $this->session->data['warning'];
			
			unset($this->session->data['warning']);
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
		
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		if (isset($this->request->get['filter_category'])) {
			$data['filter_category'] = $this->request->get['filter_category'];
		} else {
			$data['filter_category'] = '0';
		}
		
		if (isset($this->request->get['filter_calc'])) {
			$data['filter_calc'] = $this->request->get['filter_calc'];
		} else {
			$data['filter_calc'] = '0';
		}
		
		if (isset($this->request->get['filter_store'])) {
			$data['filter_store'] = $this->request->get['filter_store'];
		} else {
			$data['filter_store'] = '0';
		}
		
		
		if (isset($this->request->get['sort'])) {
			$data['sort'] = $this->request->get['sort'];
			$data['direction'] = substr($this->request->get['sort'], -1) == 'a' ? 'b' : 'a';
		} else {
			$data['sort'] = '';
			$data['direction'] = 'a';
		}
		
		if (isset($this->request->get['option2'])) {
			$option2 = $this->request->get['option2'] ;
		}else{
			$option2 = 'filter' ;
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';
		
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/report/inventory', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);
		
		
		$data['action'] = $this->url->link('extension/report/inventory', 'user_token=' . $this->session->data['user_token'], 'SSL');
		
		$this->load->model('extension/report/inventory');
		
			if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			if ((isset( $this->request->files['upload'] )) && (is_uploaded_file($this->request->files['upload']['tmp_name']))) {
				$file = $this->request->files['upload']['tmp_name'];
				
				if ($this->model_extension_report_inventory->upload($file)) {
					$this->session->data['success'] = $this->language->get('text_success');
					$this->response->redirect($this->url->link('extension/report/inventory', 'user_token=' . $this->session->data['user_token'], 'SSL'));
				}
				 else {
				
					$this->session->data['warning'] = $this->language->get('error_upload');
					$this->response->redirect($this->url->link('extension/report/inventory', 'user_token=' . $this->session->data['user_token'], 'SSL'));
					
 				}
			}
				
		}
		
		$this->load->model('setting/store');
		
		$data['stores'] = $this->model_setting_store->getStores();
		
	//	print_r($data['filter_store']);
	//	exit;
				
		if ($option2!='csv'){
		$count = 100000;
		
		$data['products'] = $this->model_extension_report_inventory->getProductinventoryReport($page , 15, $count, $data['filter_category'],$data['sort'],$data['filter_calc'],$data['filter_store'] );
		 
 		$data['heading_title'] = $this->language->get('heading_title');
		 
		$data['text_no_results'] = $this->language->get('text_no_results');
		
		$data['column_name'] = $this->language->get('column_name');
		$data['column_model'] = $this->language->get('column_model');
		$data['column_subtract'] = $this->language->get('column_subtract');
		$data['column_sku'] = $this->language->get('column_sku');
		$data['column_stock'] = $this->language->get('column_stock');
		$data['column_pricesell'] = $this->language->get('column_pricesell');
		$data['column_pricebuy'] = $this->language->get('column_pricebuy');
		$data['column_stockval'] = $this->language->get('column_stockval');
		$data['column_margin'] = $this->language->get('column_margin');
		
		$data['entry_category'] = $this->language->get('entry_category');
		$data['entry_calc'] = $this->language->get('entry_calc');
		$data['entry_calc1'] = $this->language->get('entry_calc1');
		$data['entry_store'] = $this->language->get('entry_store');
		
		
	
		$data['button_csv'] = $this->language->get('button_csv');
		$data['button_import'] = $this->language->get('button_import');
		$data['button_filter'] = $this->language->get('button_filter');
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$this->load->model('catalog/category');
				
		$data['categories'] = $this->model_catalog_category->getCategories(0);
	
				
		$pagination = new Pagination();
		$pagination->total = $count;
		$pagination->page = $page;
		$pagination->limit = 15;
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('extension/report/inventory', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}&filter_category='.$data['filter_category'].'&sort='.$data['sort'].'&filter_store='.$data['filter_store'],$data['filter_calc']);
			
		$data['pagination'] = $pagination->render();
		 
			$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/report/inventory', $data));
		 
		/*$this->template = 'report/inventory.tpl';
		$this->children = array(
			'common/header',	
			'common/footer'	
		);
		
		$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
		*/
	}
	elseif ($option2 =='csv')
	
	{
		$this->model_extension_report_inventory->createXLS($data['filter_category'],$data['filter_store']);
		
	
	}
	
}	
	
	private function validate() {
		/*if (!$this->user->hasPermission('modify', 'report/inventory')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}*/
		
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
}
?>