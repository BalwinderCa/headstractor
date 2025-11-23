<?php 
#################################################################
## Open Cart Module:  REFINE SEARCH CATEGORIES 			       ##
##-------------------------------------------------------------##
## Copyright © 2017 MB "Programanija" All rights reserved.     ##
## http://www.opencartextensions.eu						       ##
## http://www.programanija.com 		       				       ##
##-------------------------------------------------------------##
## Permission is hereby granted, when purchased, to  use this  ##
## mod on one domain. This mod may not be reproduced, copied,  ##
## redistributed, published and/or sold.				       ##
##-------------------------------------------------------------##
## Violation of these rules will cause loss of future mod      ##
## updates and account deletion				      			   ##
#################################################################

class ControllerExtensionModuleRefineCategories extends Controller {
	
	private $error = array(); 
    
    
    public function install() {
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "category` ADD `module_refine_categories_image` VARCHAR( 255 ) NOT NULL");
    }

	public function uninstall() {
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "category` DROP COLUMN `module_refine_categories_image`");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE code='module_refine_categories'");
    }
	
	public function index() {
		
		$this->load->language('extension/module/refine_categories');

		$this->document->setTitle($this->language->get('heading_title_m'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_refine_categories', $this->request->post);				
			
			$this->session->data['success'] = $this->language->get('text_success');
			
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));

		}

        
 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

  		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title_m'),
			'href'      => $this->url->link('extension/module/refine_categories', 'user_token=' . $this->session->data['user_token'], true)
   		);
				
		$data['action'] = $this->url->link('extension/module/refine_categories', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		

        $variables_array = array('module_refine_categories_image', 'module_refine_categories_image_width', 'module_refine_categories_image_height', 'module_refine_categories_title', 'module_refine_categories_description', 'module_refine_categories_description_lenght', 'module_refine_categories_status', 'module_refine_categories_minimum_size', 'module_refine_categories_lg_class', 'module_refine_categories_md_class','module_refine_categories_sm_class','refine_xs_class');
       
        foreach($variables_array as $key){
            
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } else {
                $data[$key] = $this->config->get($key);
            }
            
        }
        

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('extension/module/refine_categories', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/refine_categories')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
				
		return !$this->error;
		
	}	
}