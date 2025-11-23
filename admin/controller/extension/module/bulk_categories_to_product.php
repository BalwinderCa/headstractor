<?php
class ControllerExtensionModuleBulkCategoriestoProduct extends Controller { 
	private $error = array();

	public function index() {
		$this->load->language('extension/module/bulk_categories_to_product');

		$this->document->setTitle($this->language->get('heading_title'));
 
 		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
   			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
   			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/bulk_categories_to_product', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => ' :: '
   		);

   		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/bulk_categories_to_product', $data));
	}

	public function install() {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent('bulk_categories_to_product_admin_menu', 'admin/view/common/column_left/before', 'extension/module/bulk_categories_to_product/adminMenu');
	}

	public function uninstall() {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('bulk_categories_to_product_admin_menu');
	}

	public function adminMenu($route, &$data) {
        $this->load->language('extension/module/bulk_categories_to_product');

        if ($this->user->hasPermission('access', 'extension/module/bulk_categories_to_product')) {
            $easy_menu[] = array(
                'name'     => $this->language->get('heading_title'),
                'href'     => $this->url->link('extension/module/bulk_categories_to_product', 'user_token=' . $this->session->data['user_token'], true),
                'children' => array()
            );

        	foreach ($data['menus'] as $key=>$menu) {
        		if ($menu['id'] == 'menu-catalog') {
        			$data['menus'][$key]['children'] = array_merge($data['menus'][$key]['children'], $easy_menu);
        		}
        	}
        }
    }

	public function getCategories() {
		$categoryfilter = $this->request->get['categoryfilter'];

		$this->load->model('extension/module/bulk_categories_to_product');

		$data['bulk_categories'] = array();
		$categories = $this->model_extension_module_bulk_categories_to_product->getCategories($categoryfilter);
		foreach ($categories as $category) {
			$data['bulk_categories'][] = array(
				'category_id' => $category['category_id'], 
				'name'        => strip_tags(html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8'))
			);
		}

		if (isset($data['bulk_categories'])){
			$this->response->setOutput(json_encode($data['bulk_categories']));	
		}else{
			$this->response->setOutput(json_encode(null));
		}
	}

	public function productCategories() {
		$product_id=json_decode($this->request->get['product_id']);

		$this->load->model('extension/module/bulk_categories_to_product');

		$data['bulk_productcategories'] = array();
		$categories = $this->model_extension_module_bulk_categories_to_product->getProductCategories((int)$product_id);
		foreach ($categories as $category) {
			$data['bulk_productcategories'][] = array(
				'category_id' => $category['category_id']
			);
		}

		if (isset($data['bulk_productcategories'])){
			$this->response->setOutput(json_encode($data['bulk_productcategories']));	
		}else{
			$this->response->setOutput(json_encode(null));
		}
	}

	public function InsertAssignment() {
		$product_id=json_decode($this->request->get['product_id']);
		$category_id=json_decode($this->request->get['category_id']);

		$this->load->model('extension/module/bulk_categories_to_product');
		$result = $this->model_extension_module_bulk_categories_to_product->InsertAssignment((int)$product_id, (int)$category_id);

		$this->response->setOutput(json_encode('success'));
	}

	public function DeleteAssignment() {
		$product_id=json_decode($this->request->get['product_id']);
		$category_id=json_decode($this->request->get['category_id']);

		$this->load->model('extension/module/bulk_categories_to_product');
		$result = $this->model_extension_module_bulk_categories_to_product->DeleteAssignment((int)$product_id, (int)$category_id);

		$this->response->setOutput(json_encode('success'));
	}

	public function autocomplete() {
		$json = array();
		
		if (isset($this->request->get['filter_name'])) {
			$this->load->model('extension/module/bulk_categories_to_product');
			
			if (isset($this->request->get['filter_name'])) {
				$filter_name = $this->request->get['filter_name'];
			} else {
				$filter_name = '';
			}			
						
			$data = array(
				'filter_name'  => $filter_name,
				'start'        => 0,
				'limit'        => 20
			);
			
			$results = $this->model_extension_module_bulk_categories_to_product->getProducts($data);
			
			foreach ($results as $result) {
				$json[] = array(
					'product_id' => $result['product_id'],
					'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);	
			}
		}

		$this->response->setOutput(json_encode($json));
	}
}
?>
