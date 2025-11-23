<?php
class ControllerExtensionModuleCheckoutManagerCheckout extends Controller {

    private $error = array();

    public function index() {
        $this->language->load('extension/module/checkout_manager/checkout');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/checkout_manager/checkout');

        if (!$this->model_extension_checkout_manager_checkout->isModuleInstalled('checkout_manager')) {
            $this->session->data['error'] = 'First install the module!';
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_no_results'] = $this->language->get('text_no_results');
        
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        
        $data['column_field'] = $this->language->get('column_field');
        $data['column_field_label'] = $this->language->get('column_field_label');
        $data['column_field_name'] = $this->language->get('column_field_name');
        $data['column_field_id'] = $this->language->get('column_field_id');
        $data['column_field_input_type'] = $this->language->get('column_field_input_type');
        $data['column_field_placeholder'] = $this->language->get('column_field_placeholder');
        $data['column_field_condition'] = $this->language->get('column_field_condition');
        $data['column_field_width'] = $this->language->get('column_field_width');
        $data['column_field_sort_order'] = $this->language->get('column_field_sort_order');

        $data['column_field_visibility'] = $this->language->get('column_field_visibility');
        $data['column_field_show_section'] = $this->language->get('column_field_show_section');
        $data['column_field_existance'] = $this->language->get('column_field_existance');
        $data['column_all_section'] = $this->language->get('column_all_section');
        
        $data['entry_editable'] = $this->language->get('entry_editable');
        $data['entry_un_editable'] = $this->language->get('entry_un_editable');
        $data['entry_hide'] = $this->language->get('entry_hide');
        $data['entry_billing_details'] = $this->language->get('entry_billing_details');
        $data['entry_delivery_details'] = $this->language->get('entry_delivery_details');
        $data['entry_delivery_method'] = $this->language->get('entry_delivery_method');
        $data['entry_payment_method'] = $this->language->get('entry_payment_method');
        $data['entry_register_user'] = $this->language->get('entry_register_user');
        $data['entry_guest_payment'] = $this->language->get('entry_guest_payment');
        $data['entry_guest_shipping'] = $this->language->get('entry_guest_shipping');
        $data['entry_location_not_given'] = $this->language->get('entry_location_not_given');
        
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');
        
        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_filter'] = $this->language->get('button_filter');

        $data['user_token'] = $this->session->data['user_token'];


        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['warning'])) {
            $data['warning'] = $this->session->data['warning'];

            unset($this->session->data['warning']);
        } else {
            $data['warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/checkout_manager/checkout', 'user_token=' . $this->session->data['user_token'] . $url, true),
            'separator' => ' :: '
        );

        $data['add'] = $this->url->link('extension/module/checkout_manager/checkout/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('extension/module/checkout_manager/checkout/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);


        $total_default_fields = $this->model_extension_checkout_manager_checkout->getTotalDefaultFields();
        $default_fields = $this->model_extension_checkout_manager_checkout->getDefaultFields();

        $data['default_fields'] = array();
        foreach ($default_fields as $row) {
            // if ($row['field_existance'] == 'default') {
                $field_visibility = @unserialize($row['field_visibility']);
                if ($field_visibility == true) {
                    $row['field_visibility'] = $field_visibility;
                }
                $field_to_show = @unserialize($row['field_to_show']);
                if ($field_to_show == true) {
                    $row['field_to_show'] = $field_to_show;
                }
                
                $data['default_fields'][] = array(
                    'db_field_id'       => $row['db_field_id'],
                    'field'             => $row['field'],
                    'field_label'       => $row['field_label'],
                    'field_name'        => $row['field_name'],
                    'field_id'          => $row['field_id'],
                    'field_input_type'  => $row['field_input_type'],
                    'field_placeholder' => $row['field_placeholder'],
                    'field_condition'   => $row['field_condition'],
                    'field_width'       => $row['field_width'],
                    'field_visibility'  => $row['field_visibility'],
                    'field_to_show'     => $row['field_to_show'],
                    'field_sort_order'  => $row['field_sort_order'],
                    'field_existance'   => $row['field_existance'],
                    'status'            => $row['status'],
                    'edit'              => $this->url->link('extension/module/checkout_manager/checkout/edit', 'user_token=' . $this->session->data['user_token'] . '&field_id=' . $row['db_field_id'] . $url, true)
                );
            // }
        }

        $pagination = new Pagination();
        $pagination->total = $total_default_fields;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('extension/module/checkout_manager/checkout', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
    
        $data['pageresults'] = sprintf($this->language->get('text_pagination'), ($total_default_fields) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($total_default_fields - $this->config->get('config_limit_admin'))) ? $total_default_fields : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $total_default_fields, ceil($total_default_fields / $this->config->get('config_limit_admin')));


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/checkout_manager/default_fields', $data));

    }

    public function add() {
        $this->language->load('extension/module/checkout_manager/checkout');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/checkout_manager/checkout');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            // echo "<pre>";print_r($this->request->post);exit;

            $data = $this->request->post;

            $this->model_extension_checkout_manager_checkout->addNewField($data);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('extension/module/checkout_manager/checkout', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();

    }

    public function edit() {
        $this->language->load('extension/module/checkout_manager/checkout');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/checkout_manager/checkout');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {

            $this->model_extension_checkout_manager_checkout->editField($this->request->get['field_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('extension/module/checkout_manager/checkout', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();

    }

    public function delete() {
        $this->language->load('extension/module/checkout_manager/checkout');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/checkout_manager/checkout');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            
            foreach ($this->request->post['selected'] as $field_id) {

                $default = $this->checkIfNotDefaultField($field_id);

                if ($default == true) {
                    $this->model_extension_checkout_manager_checkout->deleteField($field_id);
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('extension/module/checkout_manager/checkout', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->index();
    }

    protected function checkIfNotDefaultField($field_id) {

        $getField = $this->db->query("SELECT field_existance FROM " . DB_PREFIX . "extendons_checkout_fields WHERE db_field_id = '".$field_id."' ");

        if ($getField->row['field_existance'] == 'default') {
            $this->session->data['warning'] = $this->language->get('error_default_field');

            $url = '';
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }
            $this->response->redirect($this->url->link('extension/module/checkout_manager/checkout', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        return true;
    }

    protected function getForm() {

        // Title
        $data['heading_title'] = $this->language->get('heading_title');
        // Buttons
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_view'] = $this->language->get('button_view');
        $data['button_add_filter'] = $this->language->get('button_add_filter');
        $data['button_remove_filter'] = $this->language->get('button_remove_filter');
        
        // Label
        $data['entry_field'] = $this->language->get('entry_field');
        $data['entry_field_label'] = $this->language->get('entry_field_label');
        $data['entry_field_name'] = $this->language->get('entry_field_name');
        $data['entry_field_id'] = $this->language->get('entry_field_id');
        $data['entry_field_input_type'] = $this->language->get('entry_field_input_type');
        $data['entry_field_placeholder'] = $this->language->get('entry_field_placeholder');
        $data['entry_field_condition'] = $this->language->get('entry_field_condition');
        $data['entry_field_width'] = $this->language->get('entry_field_width');
        $data['entry_field_sort_order'] = $this->language->get('entry_field_sort_order');
        $data['entry_curr_status'] = $this->language->get('entry_curr_status');

        $data['entry_field_visibility'] = $this->language->get('entry_field_visibility');
        $data['entry_un_editable'] = $this->language->get('entry_un_editable');
        $data['entry_hide'] = $this->language->get('entry_hide');

        $data['entry_field_to_show'] = $this->language->get('entry_field_to_show');
        $data['entry_billing_details'] = $this->language->get('entry_billing_details');
        $data['entry_delivery_details'] = $this->language->get('entry_delivery_details');
        $data['entry_delivery_method'] = $this->language->get('entry_delivery_method');
        $data['entry_payment_method'] = $this->language->get('entry_payment_method');
        // Text
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_form'] = $this->language->get('text_add');
        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_field_rules'] = $this->language->get('tab_field_rules');
    $data['text_filters_conjunction'] = $this->language->get('text_filters_conjunction');
        $data['text_and'] = $this->language->get('text_and');
        $data['text_or'] = $this->language->get('text_or');
        $data['text_filter_tip'] = $this->language->get('text_filter_tip');
        $data['text_products'] = $this->language->get('text_products');
        $data['text_categories'] = $this->language->get('text_categories');
        $data['text_cart_total'] = $this->language->get('text_cart_total');
        $data['text_cart_items'] = $this->language->get('text_cart_items');
        $data['text_customer_groups'] = $this->language->get('text_customer_groups');
        $data['text_like'] = $this->language->get('text_like');
        $data['text_not_like'] = $this->language->get('text_not_like');
        $data['text_same_as'] = $this->language->get('text_same_as');
        // information notes
        $data['text_field_visibility'] = $this->language->get('text_field_visibility');
        $data['note_numeric_values'] = $this->language->get('note_numeric_values');
        $data['text_field_show'] = $this->language->get('text_field_show');

        if (isset($this->error['warning'])) {
                $data['error_warning'] = $this->error['warning'];
        } else {
                $data['error_warning'] = '';
        }

        if (isset($this->error['error_columns_exists'])) {
            $data['error_columns_exists'] = $this->error['error_columns_exists'];
        } else {
            $data['error_columns_exists'] = array();
        }

        if (isset($this->error['var_error_option_name_empty'])) {
            $data['var_error_option_name_empty'] = $this->error['var_error_option_name_empty'];
        } else {
            $data['var_error_option_name_empty'] = array();
        }

        if (isset($this->error['var_error_option_value_empty'])) {
            $data['var_error_option_value_empty'] = $this->error['var_error_option_value_empty'];
        } else {
            $data['var_error_option_value_empty'] = array();
        }

        if (isset($this->error['var_error_options'])) {
            $data['var_error_options'] = $this->error['var_error_options'];
        } else {
            $data['var_error_options'] = array();
        }
        
        if (isset($this->error['var_error_limit'])) {
            $data['var_error_limit'] = $this->error['var_error_limit'];
        } else {
            $data['var_error_limit'] = array();
        }
        
        if (isset($this->error['message'])) {
                $data['error_message'] = $this->error['message'];
        } else {
                $data['error_message'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/checkout_manager/checkout', 'user_token=' . $this->session->data['user_token'], true),
            'separator' => ' :: '
        );

        $data['user_token'] = $this->session->data['user_token'];

        if ( !isset($this->request->get['field_id']) ) {
            $data['action'] = $this->url->link('extension/module/checkout_manager/checkout/add', 'user_token=' . $this->session->data['user_token'], true);
        } elseif ( isset($this->request->get['field_id']) ) {
            $data['action'] = $this->url->link('extension/module/checkout_manager/checkout/edit', 'user_token=' . $this->session->data['user_token'] . '&field_id=' . $this->request->get['field_id'], true);
            // hidden field value, send when updating any field to know about field name
            $data['edit_field_id'] = $this->request->get['field_id'];
        }

        // we are getting these all, to use it in select2 library and make our multi-selection easy
        // $data['products'] = $this->model_extension_checkout_manager_checkout->getProducts();
        // $data['categories'] = $this->model_extension_checkout_manager_checkout->getCategories();
        $data['customer_groups'] = $this->model_extension_checkout_manager_checkout->getCustomerGroups();

        if (isset($this->request->get['field_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST'))
        {
            // Field Info
            $data['field_info'] = $this->model_extension_checkout_manager_checkout->getField($this->request->get['field_id']);

            // Field Rules
            $data['field_rules'] = $this->model_extension_checkout_manager_checkout->getFieldRules($this->request->get['field_id']);
        }

        $data['cancel'] = $this->url->link('extension/module/checkout_manager/checkout', 'user_token=' . $this->session->data['user_token'], true);
        
        

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/checkout_manager/checkout_form', $data));
    }

    protected function validateForm() {

        if (!$this->user->hasPermission('modify', 'extension/module/checkout_manager/checkout')) {
            $this->error['error_warning'] = $this->language->get('error_permission');
        }
        
        $field_name = isset($this->request->post['pre_defined_field_name']) ? (string)$this->request->post['pre_defined_field_name'] : '';

        if (($field_name == 'country_id') || ($field_name == 'zone_id')) {
            // do nothing
        } else {
            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                $fieldType1 = isset($this->request->post['field_type']) ? $this->request->post['field_type'] : '';

                if ($fieldType1 == 'select' || $fieldType1 == 'multi-select' || $fieldType1 == 'checkbox' || $fieldType1 == 'radio') {
                    
                    if (isset($this->request->post['options']) && !empty($this->request->post['options'])) 
                    {
                        $options = $this->request->post['options'];
                        foreach ($options as $option)
                        {
                            // option name
                            if (empty($option['option_name'])) {
                                $this->error['var_error_option_name_empty'] = $this->language->get('error_option_name_empty');
                            }
                            // option value
                            if (empty($option['option_value'])) {
                                $this->error['var_error_option_value_empty'] = $this->language->get('error_option_value_empty');
                            }
                        }
                    } else {
                        $this->error['var_error_options'] = $this->language->get('error_options');
                    }
                }
            }
        }
        
        // label validation
        if ((utf8_strlen($this->request->post['field_label']) < 1) || (utf8_strlen($this->request->post['field_label']) > 255)) {
            $this->error['var_error_limit'] = $this->language->get('error_limit_reached');
        }

        $column = strtolower(str_replace(" ", "_", $this->request->post['field_label'].'_custom'));
        // check for column if it is already created
        $colExist = $this->db->query("SELECT meta_key FROM " . DB_PREFIX . "extendons_checkout_fields_data_columns WHERE `meta_key`='".$column."' LIMIT 1 ");
        
        if ($colExist->num_rows == 1 && !$this->request->get['field_id']) {
            $this->error['error_columns_exists'] = $this->language->get('error_columns_exists');
        }

        // warnings
        if ($this->error && !isset($this->error['error_warning'])) {
            $this->error['error_warning'] = $this->language->get('error_warning');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }


    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'extension/module/checkout_manager/checkout')) {
            $this->error['error_warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

}