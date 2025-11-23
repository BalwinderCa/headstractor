<?php 
class ModelExtensionShippingCustomsm extends Model {
  	function getQuote($address) {
		$this->load->language('shipping/customsm');
		
		$allow_customer = false;
		switch($this->config->get('shipping_customsm_to')){
			case 'customer_all':
				$allow_customer = true;
			break;
			case 'customer_group':
				if ($this->config->get('shipping_customsm_customer_group_id') && in_array($this->customer->getGroupId(), $this->config->get('shipping_customsm_customer_group_id'))) {
					$allow_customer = true;
				}
			break;
			case 'customer':
				if ($this->config->get('shipping_customsm_customer') && in_array($this->customer->getId(), $this->config->get('shipping_customsm_customer'))) {
					 $allow_customer = true;
				}
			break;
		}
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_customsm_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		
		if (!$this->config->get('shipping_customsm_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}
		
		$this->load->model('localisation/zone');
		
		if (isset($this->session->data['zone_id'])) {
				$zone_id = $this->session->data['zone_id'];			
			} else {
				$zone_id = $address['zone_id'];
			}
		
    	$zone_info2 = $this->model_localisation_zone->getZone($zone_id);
		$zone2 = '';
		if ($zone_info2) {
			$zone2 = $zone_info2['name'];
		}
		
		/* START Check if cart contains category */
		if ($this->config->get('shipping_customsm_category')) {

				$this->load->model('catalog/product');
				
				foreach ($this->cart->getProducts() as $product) {

					$categories = $this->model_catalog_product->getCategories($product['product_id']);

					$status = false;
					foreach ($categories as $category) {
						if ($category['category_id'] == $this->config->get('shipping_customsm_category'))
							$status = true;
					}
				   }
				  }
			/* END Check if cart contains category */	
			
		// Stores	
		if (is_array($this->config->get('shipping_customsm_store')) && !in_array($this->config->get('config_store_id'),$this->config->get('shipping_customsm_store'))) {
			$status = false;	
		}
				
		// Total
		if ($this->cart->getSubTotal() <= $this->config->get('shipping_customsm_total')) {
			$status = false;
		}
	
		// Weight
		if ($this->cart->getWeight() < $this->config->get('shipping_customsm_weight')) { 
			$status = false;		
		}
		
		$method_data = array();
		
		if ($status && $allow_customer) {
			$quote_data = array();
			
      		$quote_data['customsm'] = array(
        		'code'         => 'customsm.customsm',        		
        		'title'        => sprintf($this->config->get('shipping_customsm_frontend_' . $this->config->get('config_language_id')),$this->config->get(' '),$zone2),				
        		'cost'         => 0.00,
        		'tax_class_id' => 0,
				'text'         => html_entity_decode($this->config->get('shipping_customsm_details_' . $this->config->get('config_language_id'))),
      		);	
			
			$method_data = array( 
        		'code'       => 'customsm',
        		'title'      => $this->config->get('shipping_customsm_name_' . $this->config->get('config_language_id')),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_customsm_sort_order'),
				'error'      => false
      		);
    	}	
   
    	return $method_data;
  	}
}
?>