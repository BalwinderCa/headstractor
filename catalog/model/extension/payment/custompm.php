<?php 
class ModelExtensionPaymentCustompm extends Model {
  	public function getMethod($address, $total) {
		$this->load->language('extension/payment/custompm');
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_custompm_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		
		if(!empty($this->session->data['shipping_method']['code'])) {
			$value=explode('.',$this->session->data['shipping_method']['code']);
			$shipping_code = array_shift($value);		
		
		if ($this->config->get('payment_custompm_total') > 0 && $this->config->get('payment_custompm_total') > $total) {
			$status = false;
		} elseif  (is_array($this->config->get('payment_custompm_shipping')) && !in_array($shipping_code,$this->config->get('payment_custompm_shipping'))) {
			$status = false;		
		} elseif (!$this->config->get('payment_custompm_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}	
		
		$method_data = array();
	
		if ($status) {  
      		$method_data = array( 
        		'code'       => 'custompm',
        		'title'      => html_entity_decode($this->config->get('payment_custompm_name' . $this->config->get('config_language_id'))),
				'terms'      => '',				
				'sort_order' => $this->config->get('payment_custompm_sort_order')
      		);
    	}
   
    	return $method_data;
		}
	}
}
?>