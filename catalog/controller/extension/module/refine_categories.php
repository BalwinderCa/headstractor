<?php
#################################################################
## Open Cart Module:  REFINE SEARCH CATEGORIES 			       ##
##-------------------------------------------------------------##
## Copyright © 2019 MB "Programanija" All rights reserved.     ##
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
	
    public function index($url) {
        
        if($this->config->get('module_refine_categories_status') == 1){
        
		$this->load->language('extension/module/refine_categories');

		$data['heading_title'] = $this->language->get('heading_title');
        $data['button_view_all_items'] = $this->language->get('button_view_all_items');
            
            
		
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
		
        $this->load->model('tool/image');
               
        $variables_array = array('module_refine_categories_image','module_refine_categories_image_width','module_refine_categories_image_height','module_refine_categories_title','module_refine_categories_description',
                                'module_refine_categories_description_lenght','module_refine_categories_status','module_refine_categories_row_nb','module_refine_categories_minimum_size',
                                'module_refine_categories_lg_class','module_refine_categories_md_class','module_refine_categories_sm_class','module_refine_categories_xs_class');
       
        foreach($variables_array as $key){
            
            if($key == 'module_refine_categories_lg_class' || $key == 'module_refine_categories_md_class' || $key == 'module_refine_categories_sm_class' || $key == 'module_refine_categories_xs_class'){
                
                $data[$key] = 12 / (int)$this->config->get($key);
                
            } else {
                
                $data[$key] = $this->config->get($key);
            }
        }
        
        $data['categories'] = array();
        $parts = explode('_', (string)$this->request->get['path']);
		$category_id = (int)array_pop($parts);
         
		$results = $this->model_catalog_category->getCategories($category_id);
        
       
        
        foreach ($results as $result) {

            if($result['module_refine_categories_image']) {
                $image = $result['module_refine_categories_image'];
            } else {
                $image = $result['image'];
            }
          
            
            if (!$image) {
                $image = 'placeholder.png';
            }
            
            $filter_data = array(
                'filter_category_id'  => $result['category_id'],
                'filter_sub_category' => true
            );
            
            $data['categories'][] = array(
                'name'  => $result['name'],
                    'thumbnail' => $this->model_tool_image->resize($image, (($this->config->get('module_refine_categories_image_width')>0)?$this->config->get('module_refine_categories_image_width'):150),(($this->config->get('module_refine_categories_image_height') > 0)?$this->config->get('module_refine_categories_image_height'):150)),
					
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id'] . (!is_array($url)?$url:'')),
                'description' => substr(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'),0,
				
				(($this->config->get('module_refine_categories_description_lenght') > 0)?$this->config->get('module_refine_categories_description_lenght'):0))
				);
			}
      
            return $this->load->view('extension/module/refine_categories', $data);
        }
    }
}