<?php
class ControllerExtensionModuleCheckoutManager extends Controller {

    public function install() {
        $this->load->model('extension/checkout_manager/checkout');
        $this->model_extension_checkout_manager_checkout->createTables();
        
        // $this->load->model('setting/setting');
        // $this->model_setting_setting->editSetting('checkout_manager', array('module_checkout_manager_status' => 1));
    }

    public function uninstall() {

        $this->load->model('extension/checkout_manager/checkout');
        $this->model_extension_checkout_manager_checkout->deleteTables();

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_checkout_manager', array('module_checkout_manager_status' => 0));
    }

    public function index()
    {
        $this->load->language('extension/module/checkout_manager');
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('setting/setting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate())
        {
            $this->model_setting_setting->editSetting('module_checkout_manager', $this->request->post);     

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }
        
        
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
    
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        
        
        if (isset($this->error['warning'])) {
                $data['error_warning'] = $this->error['warning'];
        } else {
                $data['error_warning'] = '';
        }

        
        
        
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_home'),
                'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
                'separator' => false
        );

        $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_module'),
                'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true),
                'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('heading_title'),
                'href'      => $this->url->link('extension/module/checkout_manager', 'user_token=' . $this->session->data['user_token'], true),
                'separator' => ' :: '
        );
        
        $data['action'] = $this->url->link('extension/module/checkout_manager', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];
        
        

        if (isset($this->request->post['module_checkout_manager_status'])) {
                $data['module_checkout_manager_status'] = $this->request->post['module_checkout_manager_status'];
        } elseif ($this->config->get('module_checkout_manager_status')) { 
                $data['module_checkout_manager_status'] = $this->config->get('module_checkout_manager_status');
        } else { $data['module_checkout_manager_status'] = 0; }
        
        $this->load->model('design/layout');

        $data['layouts'] = $this->model_design_layout->getLayouts();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/checkout_manager/status', $data));
    }
    
    protected function validate() {
            if (!$this->user->hasPermission('modify', 'extension/module/checkout_manager')) {
                    $this->error['warning'] = $this->language->get('error_permission');
            }

            if (!$this->error) {
                    return true;
            } else {
                    return false;
            }   
    }

}



