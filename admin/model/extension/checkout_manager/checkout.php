<?php
class ModelExtensionCheckoutManagerCheckout extends Model {

    public function createTables() {

        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "extendons_checkout_fields_data_columns ("
            . "db_col_id INT(11) AUTO_INCREMENT, "
            . "db_field_id INT(11), "
            . "customer_id INT(11), "
            . "db_data_id INT(11), "
            . "meta_key VARCHAR(100), "
            . "meta_value TEXT, "
            . "PRIMARY KEY (db_col_id))
            ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
        );

        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "extendons_checkout_fields_order ("
            . "db_order_id INT(11) AUTO_INCREMENT, "
            . "customer_id INT(11), "
            . "order_id INT(11), "
            . "meta_key VARCHAR(100), "
            . "meta_value VARCHAR(255), "
            . "checkout_section VARCHAR(50), "
            . "PRIMARY KEY (db_order_id))
            ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
        );

        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "extendons_checkout_fields_options ("
            . "db_op_id INT(11) AUTO_INCREMENT, "
            . "db_field_id INT(11), "
            . "op_name VARCHAR(100), "
            . "op_value VARCHAR(100), "
            . "default_value VARCHAR(30), "
            . "PRIMARY KEY (db_op_id))
            ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
        );

        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "extendons_checkout_fields_data ("
            . "db_data_id INT(11) AUTO_INCREMENT, "
            . "address_id INT(11), "
            . "customer_id INT(11), "
            . "firstname VARCHAR(50), "
            . "lastname VARCHAR(50), "
            . "company VARCHAR(100), "
            . "address_1 TEXT, "
            . "address_2 TEXT, "
            . "city VARCHAR(100), "
            . "postcode VARCHAR(50), "
            . "country_id INT(11), "
            . "zone_id INT(11), "
            . "custom_field TEXT, "
            . "created_at DATETIME, "
            . "updated_at DATETIME, "
            . "PRIMARY KEY (db_data_id))
            ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
        );
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "extendons_checkout_field_rules ("
            . "db_rule_id INT(11) AUTO_INCREMENT, "
            . "field_id INT(11), "
            . "conjunction VARCHAR(50), "
            . "operator VARCHAR(50), "
            . "parameter VARCHAR(50), "
            . "PRIMARY KEY (db_rule_id))
            ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
        );
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "extendons_checkout_field_sub_rules ("
            . "db_sub_r_id INT(11) AUTO_INCREMENT, "
            . "field_id INT(11), "
            . "rule_id INT(11), "
            . "value VARCHAR(50), "
            . "PRIMARY KEY (db_sub_r_id))
            ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
        );
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "extendons_checkout_fields ("
            . "db_field_id INT(11) AUTO_INCREMENT, "
            . "field VARCHAR(50), "
            . "field_label VARCHAR(50), "
            . "field_name VARCHAR(50), "
            . "field_id VARCHAR(50), "
            . "field_input_type VARCHAR(50), "
            // . "field_option TEXT DEFAULT NULL, "
            // . "field_value TEXT DEFAULT NULL, "
            // . "field_default_value VARCHAR(100) NULL, "
            . "field_placeholder VARCHAR(50), "
            . "field_condition VARCHAR(50), "
            . "field_sort_order int(11), "
            . "field_width VARCHAR(50), "
            . "field_to_show VARCHAR(200) DEFAULT 0, "
            . "field_visibility VARCHAR(80) DEFAULT 0, "
            . "status TINYINT DEFAULT 1, "
            . "field_existance VARCHAR(20) DEFAULT 'default', "
            . "created_at datetime NOT NULL, "
            . "updated_at datetime NOT NULL, "
            . "PRIMARY KEY (db_field_id))
            ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;"
        );

        $billingFields = [
            ['input', 'First Name', 'firstname', 'input-payment-firstname', 'text', 'First Name', 'required', 1, ''],
            ['input', 'Last Name', 'lastname', 'input-payment-lastname', 'text', 'Last Name', 'required', 2, ''],
            ['input', 'E-Mail', 'email', 'input-payment-email', 'text', 'E-Mail', 'required', 3, ''],
            ['input', 'Telephone', 'telephone', 'input-payment-telephone', 'text', 'Telephone', 'required', 4, ''],
            ['input', 'Password', 'password', 'input-payment-password', 'password', 'Password', 'required', 5, ''],
            ['input', 'Confirm Password', 'confirm', 'input-payment-confirm', 'password', 'Confirm Password', 'required', 6, ''],

            ['input', 'Company', 'company', 'input-payment-company', 'text', 'Company', 'not-required', 7, ''],

            ['input', 'Address 1', 'address_1', 'input-payment-address-1', 'text', 'Address 1', 'required', 8, ''],

            ['input', 'Address 2', 'address_2', 'input-payment-address-2', 'text', 'Address 2', 'not-required', 9, ''],

            ['input', 'City', 'city', 'input-payment-city', 'text', 'City', 'required', 10, ''],

            ['input', 'Post Code', 'postcode', 'input-payment-postcode', 'text', 'Post Code', 'required', 11, ''],
            ['select', 'Country', 'country_id', 'input-payment-country', 'select', '', 'required', 12, ''],
            ['select', 'Region / State', 'zone_id', 'input-payment-zone', 'select', '', 'required', 13, ''],
        ];

        $showDefaultFields = [1, 2, 5, 6, 7, 8];
        foreach ($billingFields as $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_fields SET field = '" . $value[0] . "', `field_label` = '" . $value[1] . "', `field_name` = '" . $value[2] . "', `field_id` = '" . $value[3] . "', `field_input_type` = '" . $value[4] . "', `field_placeholder` = '" . $value[5] . "', `field_condition` = '" . $value[6] . "', `field_sort_order` = '" . $value[7] . "', `field_width` = '" . $value[8] . "', `field_to_show` = '" . serialize($showDefaultFields) . "', `created_at` = NOW(), `updated_at` = NOW() ");
        }

    }

    public function deleteTables() {
        
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."extendons_checkout_fields_data_columns;");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."extendons_checkout_fields_order;");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."extendons_checkout_fields_options;");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."extendons_checkout_fields_data;");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."extendons_checkout_field_rules;");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."extendons_checkout_field_sub_rules;");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX ."extendons_checkout_fields;");
    }
    
    public function isModuleInstalled($module_code) {

        $flag = false;
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "extension` WHERE `code` = '" . $module_code . "'");
        if ($result->num_rows) {
            $flag = true;
        }

        return $flag;
    }

    public function getFields() {

        $get_fields_ids = $this->db->query("SELECT db_field_id FROM " . DB_PREFIX . "extendons_checkout_fields  WHERE status = 1 AND field_existance = 'custom' ORDER BY field_sort_order");

        $custom_input_files = array();

        if ($get_fields_ids->num_rows) {

            foreach ($get_fields_ids->rows as $key => $value) {

                // field id
                $f_id = $value['db_field_id'];

                // get single input field using its id
                $get_custom_field = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields WHERE db_field_id = ".$f_id);

                $field_to_show = @unserialize($get_custom_field->row['field_to_show']);
                $field_visibility = @unserialize($get_custom_field->row['field_visibility']);

                if ($field_to_show == true) {
                    $get_custom_field->row['field_to_show'] = $field_to_show;
                }
                if ($field_visibility == true) {
                    $get_custom_field->row['field_visibility'] = $field_visibility;
                }

                // if field has options
                $get_custom_field_options = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields_options WHERE db_field_id = ".$f_id);
                
                $get_custom_field->row['field_options'] = array();

                // if field_options exists
                if ($get_custom_field_options->num_rows) {
                    $get_custom_field->row['field_options'] = $get_custom_field_options->rows;
                }

                $custom_input_files[] = $get_custom_field->row;
            }
        }
        
        // echo "<pre>";print_r($custom_input_files);
        // exit;
        return $custom_input_files;
    }

    public function editCustomerAddress($customer_id, $data) {

        foreach ($data['address'] as $key => $value) {
            
            // we have address id in data array
            $address_id = $value['address_id'];

            $check_record = $this->db->query("SELECT db_data_id FROM " . DB_PREFIX . "extendons_checkout_fields_data WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$customer_id . "'");

            if ($check_record->num_rows) 
            {
                $sql = "UPDATE " . DB_PREFIX . "extendons_checkout_fields_data SET ";

            } else {

                $sql = "INSERT INTO " . DB_PREFIX . "extendons_checkout_fields_data SET ";
                $sql .= "`address_id` = '" . $address_id . "', `customer_id` = '" . $customer_id . "', ";
            }

            // loop through all default fields only
            foreach ($value as $key2 => $value2) {
                if (!is_array($value2)) {
                    if ($key2 != 'default') {
                        if ($key2 != 'address_id') {
                            $sql .= "`".$key2."` = '" . $value2 . "', ";
                        }
                    }
                }
            }

            // where
            if ($check_record->num_rows)
            {
                $sql .= "`updated_at` = NOW()";
                $sql .= " WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$customer_id . "'";

                $this->db->query($sql);

                $db_data_id = $check_record->row['db_data_id'];

            } else {

                $sql .= "`created_at` = NOW(), `updated_at` = NOW()";

                $this->db->query($sql);

                $db_data_id = $this->db->getLastId();
            }

            // Update or Insert custom field with value into column table
            if (isset($value['my_custom_fields']) && !empty($value['my_custom_fields'])) 
            {
                foreach ($value['my_custom_fields'] as $key3 => $value3) 
                {
                        // check if record already exist or not
                        $custom_field_exist = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields_data_columns  WHERE `customer_id` = '" . (int)$customer_id . "' AND `db_data_id` = '" . $db_data_id . "' AND `meta_key` = '" . $key3 . "' ");

                        // serialize if custom field value is an array
                        if (is_array($value3)) {
                            $value3 = serialize($value3);
                        }

                        if ($custom_field_exist->num_rows > 0) 
                        {
                            $this->db->query("UPDATE " . DB_PREFIX . "extendons_checkout_fields_data_columns SET `meta_value` = '" . $value3 . "' WHERE `customer_id` = '" . (int)$customer_id . "' AND `db_data_id` = '" . $db_data_id . "' AND `meta_key` = '" . $key3 . "' ");

                        } else {

                            // get custom created field id & store it in columns table as well
                            $custom_field = $this->db->query("SELECT db_field_id FROM " . DB_PREFIX . "extendons_checkout_fields WHERE `field_name` = '" . $key3 . "' ");

                            // field id
                            $db_field_id = $custom_field->row['db_field_id'];

                            $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_fields_data_columns SET `db_field_id` = '" . $db_field_id . "', `db_data_id` = '" . $db_data_id . "', `customer_id` = '" . (int)$customer_id . "', `meta_key` = '" . $key3 . "', `meta_value` = '" . $value3 . "' ");
                        }
                }
            }
        } // main foreach /-end        
    
    }

    // get total custom fields
    public function getTotalCustomFields() {
        
        $total_custom_fields = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "extendons_checkout_fields WHERE `field_existance` = 'custom' ");

        return $total_custom_fields->row['total'];
    }

    // get custom fields
    public function getCustomFields() {
        
        $custom_fields = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields WHERE `field_existance` = 'custom' ORDER BY created_at");
        // echo "<pre>";print_r($custom_fields);exit;

        return $custom_fields->rows;
    }

    // get total default fields
    public function getTotalDefaultFields() {
        
        $total_default_fields = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "extendons_checkout_fields WHERE `field_existance` = 'default' ");

        return $total_default_fields->row['total'];
    }

    // get default fields
    public function getDefaultFields() {
        
        $default_fields = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields WHERE `field_existance` = 'default' ORDER BY created_at");
        // echo "<pre>";print_r($default_fields);exit;

        return $default_fields->rows;
    }

    public function getProducts($data = array()) {
        
        $sql = "SELECT product_id, name FROM " . DB_PREFIX . "product_description ";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCategories($data = array()) {
        $sql = "SELECT category_id, name FROM " . DB_PREFIX . "category_description ";
        $query = $this->db->query($sql);
        // echo "<pre>";print_r($query);exit;

        return $query->rows;
    }

    public function getCustomerGroups($data = array()) {
        $sql = "SELECT customer_group_id, name FROM " . DB_PREFIX . "customer_group_description ";
        $query = $this->db->query($sql);
        // echo "<pre>";print_r($query);exit;

        return $query->rows;
    }

    protected function addOrEditFieldOptions($field_id, $optionsData) {

        // Delete existing options if exists agains this field_id, add new
        $this->db->query("DELETE FROM " . DB_PREFIX . "extendons_checkout_fields_options WHERE db_field_id = '" . (int) $field_id . "' ");
        
        foreach ($optionsData as $key => $option) {
            $sql = "INSERT INTO " . DB_PREFIX . "extendons_checkout_fields_options SET `db_field_id` = '" . $field_id . "', `op_name` = '" . $option['option_name'] . "', `op_value` = '" . $option['option_value'] . "', `default_value` = '" . (isset($option['default_value']) ? $option['default_value'] : '') . "'";
            // echo $sql;
            $this->db->query($sql);
        }

        // return true;
    }

    protected function addOrEditFieldFilters($field_id, $filter_data, $filter_conjunction) {

        // FIELD RULES
        $conj = isset($filter_conjunction) ? $filter_conjunction : '';

        $this->db->query("DELETE FROM " . DB_PREFIX . "extendons_checkout_field_sub_rules WHERE field_id = '" . (int)$field_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "extendons_checkout_field_rules WHERE field_id = '" . (int)$field_id . "'");

        foreach ($filter_data as $key => $filter) {
            
            $param = isset($filter['parameter']) ? $filter['parameter'] : '';
            $op = isset($filter['operator']) ? $filter['operator'] : '';

            if ($key == 'products') {

                    $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_field_rules SET `field_id` = '" . $field_id . "', `conjunction` = '" . $conj . "', `operator` = '" . $op . "', `parameter` = '" . $param . "' ");
                    $lastId = $this->db->getLastId();

                    $value = isset($filter['products_id']) ? $filter['products_id'] : '';

                    $this->insertFieldRules($field_id, $lastId, $value);
            } 
            if ($key == 'categories') {

                    $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_field_rules SET `field_id` = '" . $field_id . "', `conjunction` = '" . $conj . "', `operator` = '" . $op . "', `parameter` = '" . $param . "' ");
                    $lastId = $this->db->getLastId();

                    $value = isset($filter['categories_id']) ? $filter['categories_id'] : '';

                    $this->insertFieldRules($field_id, $lastId, $value);
            } 
            if ($key == 'cart_total') {

                    $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_field_rules SET `field_id` = '" . $field_id . "', `conjunction` = '" . $conj . "', `operator` = '" . $op . "', `parameter` = '" . $param . "' ");
                    $lastId = $this->db->getLastId();

                    $value = isset($filter['amount']) ? $filter['amount'] : '';

                    $this->insertFieldRules($field_id, $lastId, $value);
            } 
            if ($key == 'cart_items') {

                    $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_field_rules SET `field_id` = '" . $field_id . "', `conjunction` = '" . $conj . "', `operator` = '" . $op . "', `parameter` = '" . $param . "' ");
                    $lastId = $this->db->getLastId();

                    $value = isset($filter['items']) ? $filter['items'] : '';

                    $this->insertFieldRules($field_id, $lastId, $value);
            } 
            if ($key == 'customer_groups') {

                    $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_field_rules SET `field_id` = '" . $field_id . "', `conjunction` = '" . $conj . "', `operator` = '" . $op . "', `parameter` = '" . $param . "' ");
                    $lastId = $this->db->getLastId();

                    $value = isset($filter['groups']) ? $filter['groups'] : '';

                    $this->insertFieldRules($field_id, $lastId, $value);
            }
        }
        
        return true;

    }

    public function addNewField($data) {
        // echo "<pre>";print_r($data);
        // exit;

        $field = '';
        $type = isset($data['field_type']) ? $data['field_type'] : '';

        if ($type == 'text' || $type == 'radio' || $type == 'checkbox' || $type == 'file' || $type == 'image' || $type == 'date' || $type == 'time' || $type == 'datetime')
        {
            $field = 'input';
        } elseif ($data['field_type'] == 'textarea') {
            
            $field = 'textarea';
        } else {
            
            $field = 'select';
        }

        $label          = isset($data['field_label']) ? $data['field_label'] : 'Add Field Label';
        $fieldName      = strtolower(str_replace(" ", "_", $label.'_custom'));
        $fieldId        = strtolower(str_replace(" ", "-", 'input-payment-'.$label));
        $placeholder    = $label;
        $condition      = isset($data['field_condition']) ? $data['field_condition'] : '';
        $sort           = isset($data['field_sort_order']) ? $data['field_sort_order'] : '';
        $width          = isset($data['field_width']) ? $data['field_width'] : '';
        $show           = !empty($data['field_to_show']) ? serialize($data['field_to_show']) : 0;
        $visibility     = !empty($data['field_visibility']) ? serialize($data['field_visibility']) : 0;
        $status         = isset($data['field_status']) ? $data['field_status'] : '';
        $f_existance    = 'custom';

        $sql = "INSERT INTO " . DB_PREFIX . "extendons_checkout_fields SET
                `field` = '" . $field . "', `field_label` = '" . $label . "',
                `field_name` = '" . $fieldName . "', `field_id` = '" . $fieldId . "',
                `field_input_type` = '" . $type . "', `field_placeholder` = '" . $placeholder . "',
                `field_condition` = '" . ($condition == 'required' ? $condition : 'not-required') . "',
                `field_sort_order` = '" . $sort . "',
                `field_width` = '" . $width . "', `field_to_show` = '" . $show . "',
                `field_visibility` = '" . $visibility . "', `status` = '" . $status . "',
                `field_existance` = '" . $f_existance . "', `created_at` = NOW(), `updated_at` = NOW() ";

        $fieldInsert = $this->db->query($sql);

        $field_id  = $this->db->getLastId();

        // when data has options
        if (isset($data['options']) && !empty($data['options'])) {
            $this->addOrEditFieldOptions($field_id, $data['options']);
        }

        // FIELD RULES
        if (isset($data['filter_result']) && !empty($data['filter_result'])) {
            $this->addOrEditFieldFilters($field_id, $data['filter_result'], $data['filter_conjunction']);
        }

    }

    public function editField($field_id, $data) {
        // echo "<pre>";print_r($data);exit;

        $field = '';
        $type = isset($data['field_type']) ? $data['field_type'] : '';
        $field_existance = isset($data['field_existance']) ? $data['field_existance'] : ''; // default or custom created field

        if ($type == 'text' || $type == 'radio' || $type == 'checkbox' || $type == 'file' || $type == 'image' || $type == 'date' || $type == 'time' || $type == 'datetime') {
            $field = 'input';
        } elseif ($type == 'textarea') {
            $field = 'textarea';
        } else {
            $field = 'select';
        }

        $preDefinedFieldName = isset($data['pre_defined_field_name']) ? $data['pre_defined_field_name'] : '';

        $label          = $data['field_label'];
        $placeholder    = isset($data['field_label']) ? $data['field_label'] : '';
        $condition      = isset($data['field_condition']) ? $data['field_condition'] : '';
        $sort           = isset($data['field_sort_order']) ? $data['field_sort_order'] : '';
        $width          = isset($data['field_width']) ? $data['field_width'] : '';
        $show           = !empty($data['field_to_show']) ? serialize($data['field_to_show']) : 0;
        $visibility     = !empty($data['field_visibility']) ? serialize($data['field_visibility']) : 0;
        $status         = isset($data['field_status']) ? $data['field_status'] : '';

        // you can update only label of the default fields, but you can update every-things in custom fields
        if ($field_existance == 'default') {
            // when defualt field
            $fieldInsert = $this->db->query("UPDATE " . DB_PREFIX . "extendons_checkout_fields SET `field_label` = '" . $label . "', `field_placeholder` = '" . $placeholder . "', `field_condition` = '" . ($condition == 'required' ? $condition : 'not-required') . "', `field_sort_order` = '" . $sort . "', `field_width` = '" . $width . "', `field_to_show` = '" . $show . "', `field_visibility` = '" . $visibility . "', `status` = '" . $status . "', `updated_at` = NOW() WHERE db_field_id='" . $field_id . "' ");

        } else {
            // when custom field
            $fieldInsert = $this->db->query("UPDATE " . DB_PREFIX . "extendons_checkout_fields SET `field` = '" . $field . "', `field_label` = '" . $label . "', `field_input_type` = '" . $type . "', `field_placeholder` = '" . $placeholder . "', `field_condition` = '" . ($condition == 'required' ? $condition : 'not-required') . "', `field_sort_order` = '" . $sort . "', `field_width` = '" . $width . "', `field_to_show` = '" . $show . "', `field_visibility` = '" . $visibility . "', `status` = '" . $status . "', `updated_at` = NOW() WHERE db_field_id='" . $field_id . "' ");
        }
        
        // get custom created field id & store in columns table
        // $custom_field = $this->db->query("SELECT * FROM " . DB_PREFIX . "extendons_checkout_fields_data_columns WHERE `db_field_id` = '" . $field_id . "' ");

        // when data has options
        if (isset($data['options']) && !empty($data['options'])) {
            $this->addOrEditFieldOptions($field_id, $data['options']);
        }

        // FIELD RULES
        if (isset($data['filter_result']) && !empty($data['filter_result']))
        {
            $this->addOrEditFieldFilters($field_id, $data['filter_result'], $data['filter_conjunction']);
        }

    }
    
    protected function insertFieldRules($field_id, $ruleId, $data) {
        if (is_array($data)) {
            foreach ($data as $id) { // data contains ids
                $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_field_sub_rules SET `field_id` = '" . $field_id . "', `rule_id` = '" . $ruleId . "', `value` = '" . $id . "' ");
            }
        } else {
            if ($data != '') {
                $this->db->query("INSERT INTO " . DB_PREFIX . "extendons_checkout_field_sub_rules SET `field_id` = '" . $field_id . "', `rule_id` = '" . $ruleId . "', `value` = '" . $data . "' ");
            }
        }
    }

    public function deleteField($field_id) {

        $this->db->query("DELETE FROM " . DB_PREFIX . "extendons_checkout_fields WHERE db_field_id = '" . (int) $field_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "extendons_checkout_fields_options WHERE db_field_id = '" . (int) $field_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "extendons_checkout_fields_data_columns WHERE db_field_id = '" . (int) $field_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "extendons_checkout_field_rules WHERE field_id = '" . (int) $field_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "extendons_checkout_field_sub_rules WHERE field_id = '" . (int) $field_id . "'");

        // $this->cache->delete('events');
    }

    public function getField($field_id) {

        $inputField = $this->db->query("SELECT ecf.* FROM " . DB_PREFIX . "extendons_checkout_fields ecf
                        WHERE ecf.db_field_id = '" . $field_id . "' ");

        $inputField_options = $this->db->query("SELECT ecfo.op_name, ecfo.op_value, ecfo.default_value
                                                FROM " . DB_PREFIX . "extendons_checkout_fields_options ecfo WHERE ecfo.db_field_id = '" . $field_id . "' ");
        if ($inputField_options->num_rows) {
            $inputField->row['options'] = $inputField_options->rows;
        }

        $inputField->row['field_to_show'] = !empty($inputField->row['field_to_show']) ? unserialize($inputField->row['field_to_show']) : '';

        $inputField->row['field_visibility'] = !empty($inputField->row['field_visibility']) ? unserialize($inputField->row['field_visibility']) : '';

        // echo "<pre>";print_r($inputField->row);exit;
            
        return $inputField->row;
    }

    public function getProduct($product_id) {
        
        $sql = "SELECT product_id, name FROM " . DB_PREFIX . "product_description WHERE product_id =".$product_id;

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getCategory($cat_id) {
        $sql = "SELECT category_id, name FROM " . DB_PREFIX . "category_description WHERE category_id =".$cat_id;
        $query = $this->db->query($sql);
        // echo "<pre>";print_r($query);exit;

        return $query->row;
    }
    
    public function getFieldRules($field_id) {
        $field_rules = $this->db->query("SELECT ecfr.*, ecfsr.value
                                        FROM " . DB_PREFIX . "extendons_checkout_field_rules ecfr 
                                        LEFT JOIN " . DB_PREFIX . "extendons_checkout_field_sub_rules ecfsr 
                                        ON (ecfsr.rule_id = ecfr.db_rule_id)
                                        WHERE ecfr.field_id = '" . $field_id . "' ");
        // echo "<pre>";print_r($field_rules->rows);exit;
        $rules = array();
        $products = array();
        $categories = array();
        $cart_total = array();
        $cart_items = array();
        $customer_groups = array();

        if ($field_rules->num_rows) {
            foreach ($field_rules->rows as $key => $rule) {

                if ($rule['parameter'] == 'product_name') {
                    if (!in_array($rule['parameter'], $products)) {
                        $products['parameter'] = $rule['parameter'];
                    }
                    if (!in_array($rule['operator'], $products)) {
                        $products['operator'] = $rule['operator'];
                    }
                    if (!in_array($rule['conjunction'], $products)) {
                        $products['conjunction'] = $rule['conjunction'];
                    }
                    if ($rule['value']) {
                        $products['value'][$key] = $this->getProduct($rule['value']);
                    }
                }
                if ($rule['parameter'] == 'category_name') {
                    if (!in_array($rule['parameter'], $categories)) {
                        $categories['parameter'] = $rule['parameter'];
                    }
                    if (!in_array($rule['operator'], $categories)) {
                        $categories['operator'] = $rule['operator'];
                    }
                    if (!in_array($rule['conjunction'], $categories)) {
                        $categories['conjunction'] = $rule['conjunction'];
                    }
                    if ($rule['value']) {
                        $categories['value'][$key] = $this->getCategory($rule['value']);
                    }
                }
                if ($rule['parameter'] == 'customer_groups') {
                    if (!in_array($rule['parameter'], $customer_groups)) {
                        $customer_groups['parameter'] = $rule['parameter'];
                    }
                    if (!in_array($rule['operator'], $customer_groups)) {
                        $customer_groups['operator'] = $rule['operator'];
                    }
                    if (!in_array($rule['conjunction'], $customer_groups)) {
                        $customer_groups['conjunction'] = $rule['conjunction'];
                    }
                    if ($rule['value']) {
                        $customer_groups['value'][$key] = $rule['value'];
                    }
                }
                if ($rule['parameter'] == 'cart_total') {
                    $cart_total['parameter'] = $rule['parameter'];
                    $cart_total['operator'] = $rule['operator'];
                    $cart_total['conjunction'] = $rule['conjunction'];
                    $cart_total['value'] = $rule['value'];
                }
                if ($rule['parameter'] == 'cart_items') {
                    $cart_items['parameter'] = $rule['parameter'];
                    $cart_items['operator'] = $rule['operator'];
                    $cart_items['conjunction'] = $rule['conjunction'];
                    $cart_items['value'] = $rule['value'];
                }
            }
            $rules = array($products, $categories, $cart_total, $cart_items, $customer_groups);
            // echo "<pre>";print_r($rules);exit;
        }
        return $rules;
    }
    
    protected function getProductsNameByRuleValue($pIds)
    {
        $productIDs = unserialize($pIds);
        foreach ($productIDs as $id) {
            $query = $this->db->query("SELECT name FROM ".DB_PREFIX."product_description  WHERE product_id = ".$id);
            
            if ($query->num_rows) {
                $products_name[] = $query->row['name'];
            }
        }

        return implode(', ', $products_name); 
    }
    
    protected function getCategoriesNameByRuleValue($cIds)
    {
        $categoriesIds = unserialize($cIds);
        foreach ($categoriesIds as $id) {
            $query = $this->db->query("SELECT name FROM ".DB_PREFIX."category_description  WHERE category_id = ".$id);
            
            if ($query->num_rows) {
                $category_name[] = $query->row['name'];
            }
        }

        return implode(', ', $category_name); 
    }

    protected function getProductsIdByRuleFilter($filter, $op)
    {
        $filter = explode(', ', $filter);

        foreach ($filter as $name) {
            $name = strtolower($name);

            if ($op == 'like') {
                $filter = 'LIKE "%'.$name.'%"';
            }
            if ($op == 'not_like') {
                $filter = '<> '.$name;
            }
            if ($op == 'same_as') {
                $filter = '= '.$name;
            }
            $query = $this->db->query("SELECT product_id FROM ".DB_PREFIX."product_description  WHERE name $filter");

            if ($query->num_rows) {
                foreach ($query->rows as $value) {
                    $product_ids[] = $value['product_id'];
                }
            }
        }
        // echo "<pre>";
        // print_r($product_ids);exit;

          
        return serialize($product_ids);        
    }

    protected function getCategoriesIdByRuleFilter($filter, $op)
    {
        $filter = explode(',', $filter);

        foreach ($filter as $name) {
            
            if ($op == 'like') {
                $op = 'LIKE "%'.$name.'%"';
            }
            if ($op == 'not_like') {
                $op = '<> '.$name;
            }
            if ($op == 'same_as') {
                $op = '= '.$name;
            }
            $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category_description  WHERE name $op ");

            if ($query->num_rows) {
                foreach ($query->rows as $value) {
                    $category_ids[] = $value['category_id'];
                }
            }
        }

        // echo "<pre>";
        // print_r($category_ids);exit;
          
        return serialize($category_ids);
    }
}
