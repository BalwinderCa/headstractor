<?php
class ModelExtensionModuleBulkCategoriestoProduct extends Model {

	public function getCategories($categoryfilter) {

		$sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' &gt; ') AS name, c.parent_id, c.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c ON (cp.path_id = c.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (c.category_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
		if (!empty($categoryfilter)) {
			$sql .= " AND cd2.name LIKE '%".$this->db->escape($categoryfilter)."%'";
		}
		
		$sql .= " GROUP BY cp.category_id ORDER BY name";
		

		$query = $this->db->query($sql);
	
		return $query->rows;
	}

	public function getProductCategories($product_id) {
		$sql = "SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "product_to_category ptc ON (c.category_id = ptc.category_id)";
				
		$sql .= " WHERE ptc.product_id = '" . (int)$product_id . "'"; 
		
		$sql .= " GROUP BY c.category_id";

		$query = $this->db->query($sql);
	
		return $query->rows;
	}	

	public function InsertAssignment($product_id, $category_id) {
		if (isset($product_id) && isset($category_id)){
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$category_id . "' AND product_id = '" . (int)$product_id . "'");
			if ($query->num_rows==0){
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET category_id = '" . (int)$category_id . "', product_id = '" . (int)$product_id . "'");
				return 'success';
			}else{
				return null;
			}
		}else{
			return null;
		}
	}

	public function DeleteAssignment($product_id, $category_id) {
		if (isset($product_id) && isset($category_id)){
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$category_id . "' AND product_id = '" . (int)$product_id . "'");
			return 'success';
		}else{
			return null;
		}
	}

	public function getProducts($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";
				
		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'"; 
		
		if (!empty($data['filter_name'])) {
			$sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
		}
		
		$sql .= " GROUP BY p.product_id";
					
		$sort_data = array(
			'pd.name'
		);	
		
		$sql .= " ORDER BY pd.name";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		
		$query = $this->db->query($sql);
	
		return $query->rows;
	}

}
?>
