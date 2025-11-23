<?php
class ControllerExtensionModuleLitextension extends Controller {
    private $error = array();

    const DEFAULT_MODULE_SETTINGS = [
        'name' => 'Migrate to Opencart',
        'redirect_url' => 'http://www.example.org',
        'status' => 1 /* Enabled by default*/
    ];

    const APP_LINK = 'https://cm.litextension.com';
    const APP_LINK_HOME = 'https://litextension.com/';

    public function index(){
        if (isset($this->request->get['module_id'])) {
            $this->configure($this->request->get['module_id']);
        } else {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('module_litextension', ['module_litextension_status'=>1]);
            $this->response->redirect($this->url->link('extension/module/litextension','&user_token='.$this->session->data['user_token'].'&module_id='.$this->db->getLastId()));
        }

    }

    protected function configure($module_id){
        $this->load->model('setting/module');
        $this->load->language('extension/module/litextension');

        $this->document->setTitle($this->language->get('heading_title'));
        $data = array();

        $data['text_edit'] = $this->language->get('text_edit');
        $data['url_home'] = rtrim(self::APP_LINK, '/') . '/my-migrations?target_url=' . HTTPS_CATALOG . '&target_type=opencart';

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/litextension', $data));
    }

    public function validate(){}

    public function install(){
        $this->load->model('setting/setting');
        $this->load->model('setting/module');

        $this->model_setting_setting->editSetting('module_litextension', ['module_litextension_status'=>1]);
        $this->model_setting_module->addModule('litextension', self::DEFAULT_MODULE_SETTINGS);
    }

    public function uninstall(){
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_litextension');
    }
}