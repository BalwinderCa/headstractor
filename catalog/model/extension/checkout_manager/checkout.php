<?php
class ModelExtensionCheckoutManagerCheckout extends Model {
    
    public function isModuleInstalled($module_code) {

        $flag = false;
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension` WHERE `code` = '" . $module_code . "'");
        if ($result->num_rows) {
            $flag = true;
        }

        return $flag;
    }
    
    // v1.2 updated
    protected function getRulesOfFieldByCartProductInstances($fId, $cPid, $cTotal, $cQuantiy, $groupId) {
        // get category id to which cart product belongs
        $catId = $this->getCategoryIdByProductId($cPid);

        $field_rules_exist = $this->db->query("SELECT ecfr.*, ecfsr.value FROM " . DB_PREFIX . "extendons_checkout_field_rules ecfr LEFT JOIN " . DB_PREFIX . "extendons_checkout_field_sub_rules ecfsr ON (ecfsr.rule_id = ecfr.db_rule_id) WHERE ecfsr.field_id = " . $fId);

        // echo "<pre>";
        // print_r($field_rules_exist->rows);
        // exit;

        $ids = '';
        if ($field_rules_exist->num_rows > 0) {

            $sql = "SELECT ecfr.*, ecfsr.value FROM " . DB_PREFIX . "extendons_checkout_field_rules ecfr";
            $sql .= " LEFT JOIN " . DB_PREFIX . "extendons_checkout_field_sub_rules ecfsr";
            $sql .= " ON (ecfsr.rule_id = ecfr.db_rule_id)";
            $sql .= " WHERE ecfr.field_id = ".$fId . " AND ecfsr.value IN (";

            foreach ($field_rules_exist->rows as $key => $rule) {
                if ($rule['parameter'] == 'product_name' && isset($rule['value'])) {
                    // $ids .= $cPid . ", ";
                    $ids .= $rule['value'] . ", ";
                    // break;
                }
            }
            foreach ($field_rules_exist->rows as $key => $rule) {
                if ($rule['parameter'] == 'category_name' && isset($rule['value'])) {
                    // $ids .= $catId . ", ";
                    $ids .= $rule['value'] . ", ";
                    // break;
                }
            }
            foreach ($field_rules_exist->rows as $key => $rule) {
                if ($rule['parameter'] == 'cart_total' && isset($rule['value'])) {
                    // $ids .= $cTotal . ", ";
                    $ids .= $rule['value'] . ", ";
                    // break;
                }
            }
            foreach ($field_rules_exist->rows as $key => $rule) {
                if ($rule['parameter'] == 'cart_items' && isset($rule['value'])) {
                    // $ids .= $cQuantiy . ", ";
                    $ids .= $rule['value'] . ", ";
                    // break;
                }
            }
            foreach ($field_rules_exist->rows as $key => $rule) {
                if ($rule['parameter'] == 'customer_groups' && isset($rule['value'])) {
                    // $ids .= $groupId . ", ";
                    $ids .= $rule['value'] . ", ";
                    // break;
                }
            }
            // remove ended comma
            $sql .= rtrim(trim($ids, ', '));

            $sql .= ")";

            // echo "<pre>";
            // echo $sql;
            // exit;

            $field_rules = $this->db->query($sql);

            // print_r($field_rules->rows);
            // exit;

            $found = false;

            if ($field_rules->num_rows) {

                $carProducts = $this->cart->getProducts();
                $totalCartProducts = count($this->cart->getProducts());
                $subtotal = $this->cart->getSubTotal();
                    // print_r($carProducts);

                foreach ($field_rules->rows as $value) {
                    
                    if ($value['parameter'] == 'product_name' && !empty($value['value'])) {
                        
                        for ($i=0; $i < count($carProducts); $i++) { 
                            // echo $carProducts[$i]['product_id'];
                            if ($value['value'] == $carProducts[$i]['product_id']) {
                                $found = true;
                            }
                        }
                    }
                    if ($value['parameter'] == 'category_name' && !empty($value['value'])) {

                        for ($i=0; $i < count($carProducts); $i++) { 
                            // print_r($carProducts[$i]);
                            $catId = $this->getCategoryIdByProductId($carProducts[$i]['product_id']);
                            // echo $carProducts[$i]['product_id'];
                            if ($value['value'] == $catId) {
                                $found = true;
                            }
                        }
                    }

                    if ($value['parameter'] == 'cart_total' && !empty($value['value'])) {

                        // for ($i=0; $i < count($carProducts); $i++) { 
                            // print_r($carProducts[$i]);
                            // echo $carProducts[$i]['product_id'];
                            if ($value['value'] <= $subtotal) {
                                $found = true;
                            }
                        // }
                    }
                    
                    if ($value['parameter'] == 'cart_items' && !empty($value['value'])) {

                        // for ($i=0; $i < count($carProducts); $i++) { 
                            // print_r($carProducts[$i]);
                            // echo $carProducts[$i]['product_id'];
                            if ($value['value'] <= $totalCartProducts) {
                                $found = true;
                            }
                        // }
                    }
                
                    if ($this->customer->getGroupId()) {
                        if ($value['parameter'] == 'customer_groups' && !empty($value['value'])) {

                                if ($value['value'] == $this->customer->getGroupId()) {
                                    $found = true;
                                }
                        }
                    }
                    // echo $found;exit;
                }
            }
            // echo $found;
            // exit;

            // if ($field_rules->num_rows) {
            if ($found == 1) {
                return $fId;
            } else {
                return 'not_found';
            }

        } else {
            return 'no_rules';
        }
    }

    protected function getCategoryIdByProductId($pId) {
        $product_to_category = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = ".$pId);
        foreach ($product_to_category->rows as $value) {
            $arr[] = $value['category_id'];
        }
        // echo "<pre>";print_r($arr);exit();
        return $arr[0];
        // return $product_to_category->row['category_id'];
    }

    public function getFields() {

        $get_field_id = $this->db->query("SELECT db_field_id FROM " . DB_PREFIX . "extendons_checkout_fields WHERE status = 1 ORDER BY field_sort_order");

        $field_rules_result = '';

        foreach ($get_field_id->rows as $value) 
        {
            $field_id = $value['db_field_id'];
            
            // get cart products
            $products = $this->cart->getProducts();
            $customerGroupId = $this->customer->getGroupId();

            if (!empty($products)) {
                foreach ($products as $product) {
                    $cart_pId = $product['product_id'];
                    $cart_pName = $product['name'];
                    $cart_subtotal = $product['total'];
                    $cart_quantity = $product['quantity'];

                    $field_rules_result = $this->getRulesOfFieldByCartProductInstances($field_id, $cart_pId, $cart_subtotal, $cart_quantity, $customerGroupId);

                    if ($field_rules_result == 0) {
                        break;
                    } else if ($field_rules_result == $field_id){
                        break;
                    }
                }
            }
            // echo $field_rules_result;
            // exit;

            if ($field_rules_result != 'not_found') {
                
                // get single input field using its id
                $inputField = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields WHERE status = 1 AND db_field_id = " . $field_id);

                $fieldToShow = !empty($inputField->row['field_to_show']) ? unserialize($inputField->row['field_to_show']) : 0;
                $fieldVisibility = !empty($inputField->row['field_visibility']) ? unserialize($inputField->row['field_visibility']) : 0;
                
                // if field has options
                $inputField_options = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields_options WHERE db_field_id = " . $field_id);
                
                $inputField->row['options'] = array();

                if ($inputField_options->num_rows) {
                    $inputField->row['options'] = $inputField_options->rows;
                }

                $inputFieldsArray[$field_id] = array(
                    'db_field_id'       => $inputField->row['db_field_id'],
                    'field'             => $inputField->row['field'],
                    'field_label'       => $inputField->row['field_label'],
                    'field_name'        => $inputField->row['field_name'],
                    'field_id'          => $inputField->row['field_id'],
                    'field_input_type'  => $inputField->row['field_input_type'],
                    // 'field_option'      => $fieldOption,
                    // 'field_value'       => $fieldValue,
                    'field_placeholder' => $inputField->row['field_placeholder'],
                    'field_condition'   => $inputField->row['field_condition'],
                    'field_sort_order'  => $inputField->row['field_sort_order'],
                    'field_width'       => $inputField->row['field_width'],
                    'field_to_show'     => $fieldToShow,
                    'field_visibility'  => $fieldVisibility,
                    'status'            => $inputField->row['status'],
                    'field_existance'   => $inputField->row['field_existance'],
                    'field_options'     => $inputField->row['options'],
                );
            }
        }

        // echo "<pre>";print_r($inputFieldsArray);
        // exit;

        return $inputFieldsArray;
    }

    public function addCustomAddress($data) {
        
        // echo "<pre>";
        // print_r($data);
        // exit();
        foreach ($data as $key => $value) 
        {
            // array diff key use to remove element from data arr with value required or not-required
            if (is_array($value)) 
            {
                foreach ($value as $key2 => $v) {
                    if ($v == 'not-required') {
                        $data['billing_address'] = array_diff_key($value, [$key2 => "not-required"]);
                    }
                    
                    if ($v == 'required') {
                        $data['billing_address'] = array_diff_key($value, [$key2 => "required"]);
                    }
                }

            } else {
                if ($value == 'not-required') {
                    $data = array_diff_key($data, [$key => "not-required"]);
                }
                
                if ($value == 'required') {
                    $data = array_diff_key($data, [$key => "required"]);
                }
            }
        }


        $sql = "INSERT INTO " . DB_PREFIX . "extendons_checkout_fields_data SET ";
        // the address_id is the original opencart sotred address id
        $sql .= "`address_id` = '" . $data['address_id'] . "', `customer_id` = '" . $this->customer->getId() . "', ";

        $sql .= "`firstname` = '" . (isset($data['firstname']) ? $this->db->escape($data['firstname']) : '') . "', ";
        $sql .= "`lastname` = '" . (isset($data['lastname']) ? $this->db->escape($data['lastname']) : '') . "', ";
        $sql .= "`company` = '" . (isset($data['company']) ? $this->db->escape($data['company']) : '') . "', ";
        $sql .= "`address_1` = '" . (isset($data['address_1']) ? $this->db->escape($data['address_1']) : '') . "', ";
        $sql .= "`address_2` = '" . (isset($data['address_2']) ? $this->db->escape($data['address_2']) : '') . "', ";
        $sql .= "`city` = '" . (isset($data['city']) ? $this->db->escape($data['city']) : '') . "', ";
        $sql .= "`postcode` = '" . (isset($data['postcode']) ? $this->db->escape($data['postcode']) : '') . "', ";
        $sql .= "`country_id` = '" . (isset($data['country_id']) ? (int)$data['country_id'] : '') . "', ";
        $sql .= "`zone_id` = '" . (isset($data['zone_id']) ? (int)$data['zone_id'] : '') . "', ";
        $sql .= "`created_at` = NOW(), `updated_at` = NOW()";

        $this->db->query($sql);

        // this address_id is my custom saved address id, and to store it in my another custom table
        $my_custom_address_id = $this->db->getLastId();

        // Insert custom field with value into column table
        if (isset($data['billing_address'])) {
            foreach ($data['billing_address'] as $key1 => $value1) {
                
                // get custom created field id & store this id in columns table
                $custom_field = $this->db->query("SELECT db_field_id FROM " . DB_PREFIX . "extendons_checkout_fields WHERE `field_name` = '" . $key1 . "' ");
                
                if ($custom_field->num_rows > 0) {
                    $db_field_id = $custom_field->row['db_field_id'];
                } else {
                    $db_field_id = '';
                }

                if (is_array($value1)) {
                    $value1 = serialize($value1);
                }
                
                $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_fields_data_columns SET `db_field_id` = '" . $db_field_id . "', `db_data_id` = '" . $my_custom_address_id . "', `customer_id` = '" . (int)$this->customer->getId() . "', `meta_key` = '" . $key1 . "', `meta_value` = '" . $value1 . "' ");
            }
        }

        // echo "<pre>";
        // print_r($data);
        // exit();
        
    }

    public function editCustomAddress($address_id, $data) {
        // echo "<pre>";
        // echo $address_id;
        // print_r($data);
        // exit();

        foreach ($data as $key => $value) 
        {
            // array diff key use to remove element from data arr with value required or not-required
            if (is_array($value)) 
            {
                foreach ($value as $key2 => $v) {
                    if ($v == 'not-required') {
                        $data['billing_address'] = array_diff_key($value, [$key2 => "not-required"]);
                    }
                    
                    if ($v == 'required') {
                        $data['billing_address'] = array_diff_key($value, [$key2 => "required"]);
                    }
                }

            } else {
                if ($value == 'not-required') {
                    $data = array_diff_key($data, [$key => "not-required"]);
                }
                
                if ($value == 'required') {
                    $data = array_diff_key($data, [$key => "required"]);
                }
            }
        }
        // echo "<pre>";
        // echo $address_id;
        // print_r($data);
        // exit();

        // this address_id is my_custom saved address_id, and store it in my another custom_column table
        $custom_data_q = $this->db->query("SELECT db_data_id FROM " . DB_PREFIX . "extendons_checkout_fields_data WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

        // if exist then update, if not then insert
        if ($custom_data_q->num_rows) 
        {
            $sql = "UPDATE " . DB_PREFIX . "extendons_checkout_fields_data SET ";            
        } else {

            $sql = "INSERT INTO " . DB_PREFIX . "extendons_checkout_fields_data SET ";
            // the address_id is the original opencart sotred address id
            $sql .= "`address_id` = '" . $address_id . "', `customer_id` = '" . $this->customer->getId() . "', ";
        }

        foreach ($data as $key2 => $value2) {
            if (!is_array($value2)) {
                if ($key2 != 'default') {
                    $sql .= "`".$key2 . "` = '" . $value2 . "', ";
                }
            }
        }

        if ($custom_data_q->num_rows)
        {
            $sql .= "`updated_at` = NOW()";
            $sql .= " WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'";

            $this->db->query($sql);

            $db_data_id = $custom_data_q->row['db_data_id'];

        } else {
            $sql .= "`created_at` = NOW(), `updated_at` = NOW()";

            $this->db->query($sql);

            $db_data_id = $this->db->getLastId();
        }


        // Update or Insert custom field with value into column table
        if (isset($data['billing_address'])) 
        {
            foreach ($data['billing_address'] as $key3 => $value3) 
            {
                // check if record already exist or not
                $check_if_exist = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields_data_columns  WHERE `customer_id` = '" . (int)$this->customer->getId() . "' AND `db_data_id` = '" . $db_data_id . "' AND `meta_key` = '" . $key3 . "' ");

                if (is_array($value3)) {
                    $value3 = serialize($value3);
                }

                if ($check_if_exist->num_rows > 0) 
                {
                    $this->db->query("UPDATE " . DB_PREFIX . "extendons_checkout_fields_data_columns SET `meta_value` = '" . $value3 . "' WHERE `customer_id` = '" . (int)$this->customer->getId() . "' AND `db_data_id` = '" . $db_data_id . "' AND `meta_key` = '" . $key3 . "' ");

                } else {

                    // get custom created field id & store it in columns table
                    $custom_field = $this->db->query("SELECT db_field_id FROM " . DB_PREFIX . "extendons_checkout_fields WHERE `field_name` = '" . $key3 . "' ");

                    $db_field_id = $custom_field->row['db_field_id'];

                    $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_fields_data_columns SET `db_field_id` = '" . $db_field_id . "', `db_data_id` = '" . $db_data_id . "', `customer_id` = '" . (int)$this->customer->getId() . "', `meta_key` = '" . $key3 . "', `meta_value` = '" . $value3 . "' ");
                }
            }
        }
        
    }

    public function addOrder($data) {

        foreach ($data as $k1 => $v1) {

            if ($k1 == 'shipping_address') {
                if (!empty($v1['custom_shipping_address'])) {

                    foreach ($v1['custom_shipping_address'] as $k2 => $v2) {
                        if (is_array($v2)) {
                            $v2 = serialize($v2);
                        }
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "extendons_checkout_fields_order` SET customer_id = '" . (isset($data['customer_id']) ? (int)$data['customer_id'] : 0) . "', order_id = '" . (int)$data['order_id'] . "', meta_key = '" . $this->db->escape($k2) . "', meta_value = '" . $this->db->escape($v2) . "', checkout_section = '" . $this->db->escape($k1) . "' ");
                    }
                }
            }

            if ($k1 == 'payment_address') {
                if (!empty($v1['custom_payment_address'])) {

                    foreach ($v1['custom_payment_address'] as $k2 => $v2) {
                        if (is_array($v2)) {
                            $v2 = serialize($v2);
                        }
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "extendons_checkout_fields_order` SET customer_id = '" . (isset($data['customer_id']) ? (int)$data['customer_id'] : 0) . "', order_id = '" . (int)$data['order_id'] . "', meta_key = '" . $this->db->escape($k2) . "', meta_value = '" . $this->db->escape($v2) . "', checkout_section = '" . $this->db->escape($k1) . "' ");
                    }
                }
            }

            if ($k1 == 'custom_shipping_method') {
                if (!empty($v1)) {

                    foreach ($v1 as $k2 => $v2) {
                        if (is_array($v2)) {
                            $v2 = serialize($v2);
                        }
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "extendons_checkout_fields_order` SET customer_id = '" . (isset($data['customer_id']) ? (int)$data['customer_id'] : 0) . "', order_id = '" . (int)$data['order_id'] . "', meta_key = '" . $this->db->escape($k2) . "', meta_value = '" . $this->db->escape($v2) . "', checkout_section = 'shipping_method' ");
                    }
                }
            }

            if ($k1 == 'custom_payment_method') {
                if (!empty($v1)) {

                    foreach ($v1 as $k2 => $v2) {
                        if (is_array($v2)) {
                            $v2 = serialize($v2);
                        }
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "extendons_checkout_fields_order` SET customer_id = '" . (isset($data['customer_id']) ? (int)$data['customer_id'] : 0) . "', order_id = '" . (int)$data['order_id'] . "', meta_key = '" . $this->db->escape($k2) . "', meta_value = '" . $this->db->escape($v2) . "', checkout_section = 'payment_method' ");
                    }
                }
            }

        }

    }


}