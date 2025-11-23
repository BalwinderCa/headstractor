<?php
class ControllerExtensionModuleCustomPages extends Controller
{
    private $module = array();
    private $data   = array();
    private $error  = array();

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->config->load('isenselabs/custompages');
        $this->module = $this->config->get('custompages');

        $this->load->model('setting/setting');
        $this->load->model($this->module['path']);

        $this->module['model'] = $this->{$this->module['model']};
        $this->module['url_token'] = sprintf($this->module['url_token'], $this->session->data['user_token']);
        $this->module['url_module'] = $this->url->link($this->module['path'], $this->module['url_token'], true);
        $this->module['url_module_form'] = $this->url->link($this->module['path'] . '/form', $this->module['url_token'], true);
        $this->module['url_extension'] = $this->url->link($this->module['ext_link'], $this->module['url_token'] . $this->module['ext_type'], true);

        $this->module['module_id'] = 0;
        if (isset($this->request->get['module_id'])) {
            $this->module['module_id'] = (int)$this->request->get['module_id'];
        }

        // Module setting
        $setting = $this->model_setting_setting->getSetting($this->module['code']);
        $this->module['setting'] = array_replace_recursive(
            $this->module['setting'],
            !empty($setting[$this->module['code'] . '_setting']) ? $setting[$this->module['code'] . '_setting'] : array()
        );

        // Language variables
        $language_vars = $this->load->language($this->module['path'], $this->module['name']);
        $this->data = $language_vars[$this->module['name']]->all();
    }

    /**
     * Standarize index() and form()
     */
    private function initPage()
    {
        $this->document->setTitle($this->module['title']);

        $this->document->addStyle('view/stylesheet/' . $this->module['name'] . '.css?v=' .  $this->module['version']);
        $this->document->addScript('view/javascript/' . $this->module['name'] . '.js?v=' .  $this->module['version']);

        $data = $this->data;

        // Error notification
        $data['warning'] = '';
        if (isset($this->error['warning'])) {
            $data['warning'] = $this->error['warning'];
        }
        $data['success'] = '';
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $data['breadcrumbs']   = array();
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/dashboard', $this->module['url_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text'      => $data['text_modules'],
            'href'      => $this->module['url_extension'],
        );
        $data['breadcrumbs'][] = array(
            'text'      => $data['heading_title'],
            'href'      => $this->module['url_module'],
        );

        $data['module']        = $this->module;
        $data['heading_title'] = $this->module['title'] . ' ' . $this->module['version'];

        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        foreach ($data['languages'] as $key => $value) {
            $data['languages'][$key]['flag_url'] = 'language/'.$data['languages'][$key]['code'].'/'.$data['languages'][$key]['code'].'.png';
        }

        return $data;
    }

    public function index()
    {
        if ($this->module['module_id']) {
            $this->response->redirect($this->module['url_module_form'] . '&module_id=' . $this->module['module_id']);
        }

        $this->updates();

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validatePermission()) {
            $module_status = $this->request->post[$this->module['code'] . '_setting']['status'];

            if (!empty($this->module['setting']['LicensedOn'])) {
                $_POST['OaXRyb1BhY2sgLSBDb21'] = $this->module['setting']['LicensedOn'];
                
                if (!empty($this->module['setting']['License'])) {
                    $_POST['cHRpbWl6YXRpb24ef4fe'] = base64_encode(json_encode($this->module['setting']['License']));
                }
            }

            if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
                $this->request->post[$this->module['code'] . '_setting']['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
            }
            if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
                $this->request->post[$this->module['code'] . '_setting']['License'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']), true);
            }

            $post = array_replace_recursive(
                $this->request->post,
                array($this->module['code'] . '_status' => $module_status)
            );

            $this->model_setting_setting->editSetting($this->module['code'], $post);

            $this->session->data['success'] = $this->data['text_success'];
            $this->response->redirect($this->module['url_module']);
        }

        $data = $this->initPage();

        // Content
        $data['tab_setting']    = $this->load->view($this->module['path'] .'/tab_setting', $data);
        $data['tab_pages']      = $this->load->view($this->module['path'] .'/tab_pages', $data);
        $data['tab_support']    = $this->load->view($this->module['path'] .'/tab_support', $data);

        // Support
        $data['unlicensedHtml']    = empty($this->module['setting']['LicensedOn']) ? base64_decode('ICAgIDxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LWRhbmdlciBmYWRlIGluIj4NCiAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCIgYXJpYS1oaWRkZW49InRydWUiPsOXPC9idXR0b24+DQogICAgICAgIDxoND5XYXJuaW5nISBVbmxpY2Vuc2VkIHZlcnNpb24gb2YgdGhlIG1vZHVsZSE8L2g0Pg0KICAgICAgICA8cD5Zb3UgYXJlIHJ1bm5pbmcgYW4gdW5saWNlbnNlZCB2ZXJzaW9uIG9mIHRoaXMgbW9kdWxlISBZb3UgbmVlZCB0byBlbnRlciB5b3VyIGxpY2Vuc2UgY29kZSB0byBlbnN1cmUgcHJvcGVyIGZ1bmN0aW9uaW5nLCBhY2Nlc3MgdG8gc3VwcG9ydCBhbmQgdXBkYXRlcy48L3A+PGRpdiBzdHlsZT0iaGVpZ2h0OjVweDsiPjwvZGl2Pg0KICAgICAgICA8YSBjbGFzcz0iYnRuIGJ0bi1kYW5nZXIiIGhyZWY9ImphdmFzY3JpcHQ6dm9pZCgwKSIgb25jbGljaz0iJCgnYVtocmVmPSNpc2Vuc2Vfc3VwcG9ydF0nKS50cmlnZ2VyKCdjbGljaycpIj5FbnRlciB5b3VyIGxpY2Vuc2UgY29kZTwvYT4NCiAgICA8L2Rpdj4=') : '';
        $data['encodedLicense']    = !empty($this->module['setting']['License']) ? base64_encode(json_encode($this->module['setting']['License'])) : '';
        $data['supportTicketLink'] = 'https://isenselabs.com/tickets/open/' . base64_encode('Support Request').'/'.base64_encode('414').'/'. base64_encode($_SERVER['SERVER_NAME']);

        // Page element
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->module['path'], $data));
    }

    public function itemlist()
    {
        $reqGet = $this->request->get;
        $limit  = 25;
        $page   = isset($reqGet['page']) && (int)$reqGet['page'] > 0 ? (int)$reqGet['page'] : 1;
        $data   = array(
            'items'      => [],
            'output'     => '<tr><td class="list-no-content" colspan="5">' . $this->data['text_no_record'] . '</td></tr>',
            'pagination' => '',
            'pagination_info' => '',
        );

        $data['language_id'] = $this->config->get('config_language_id');

        $params = array(
            'page'  => $page,
            'limit' => $limit,
            'start' => ($page - 1) * $limit,
        );

        $data['items']      = $this->module['model']->getItems($params);
        $total_item         = $this->module['model']->getTotalItems($params);

        $pagination         = new Pagination();
        $pagination->total  = $total_item;
        $pagination->page   = $page;
        $pagination->limit  = $limit;
        $pagination->url    = $this->url->link($this->module['path'] . '/itemList', $this->module['url_token'] . '&page={page}', true);

        $data['output']     = $this->load->view($this->module['path'] . '/item_list', $data) ?: $data['output'];
        $data['pagination'] = $pagination->render();
        $data['pagination_info'] = sprintf($this->language->get('text_pagination'), ($total_item) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total_item - $limit)) ? $total_item : ((($page - 1) * $limit) + $limit), $total_item, ceil($total_item / $limit));

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data)); 
    }

    public function itemUpdate()
    {
        $data = array();

        if ($this->validatePermission()) {
            $post = $this->request->post;

            $this->load->model('setting/module');

            // Delete
            if (!empty($post['action']) && $post['action'] == 'delete') {
                $this->model_setting_module->deleteModule((int)$post['id']);
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
        } else {
            $data['error'] = true;
            $data['error_message'] = $this->error['warning'];

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($data));
        }
    }

    // ====================================

    public function form()
    {
        $this->load->model('setting/module');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && !empty($this->request->post[$this->module['code'] . '_item']) && $this->validatePermission()) {
            $post = $this->request->post[$this->module['code'] . '_item'];

            $post['name'] = $post['title'][$this->config->get('config_language_id')];

            if ($this->module['module_id']) {
                $this->model_setting_module->editModule($this->module['module_id'], $post);
            } else {
                $this->model_setting_module->addModule($this->module['name'], $post);
            }

            $this->session->data['success'] = $this->data['text_success'];
            $this->response->redirect($this->module['url_module']);
        }

        $data = $this->initPage();
        $data['sub_title'] = $this->module['module_id'] ? $data['text_edit'] . ' #' . $this->module['module_id'] : $data['text_add'];

        $data['breadcrumbs'][]  = array(
            'text' => $data['sub_title'],
            'href' => $data['module']['url_module_form'] = $this->module['url_module_form'] . '&module_id=' . $this->module['module_id'],
        );

        // ===

        if (isset($this->request->post[$this->module['code'] . '_item'])) {
            $data['module']['page'] = array_replace_recursive($data['module']['page'], $this->request->post[$this->module['code'] . '_item']);
        } elseif ($this->module['module_id']) {
            $data['module']['page'] = array_replace_recursive($data['module']['page'], $this->model_setting_module->getModule($this->module['module_id']));
        }

        $this->load->model('design/layout');

        $layouts = $this->model_design_layout->getLayouts(array('start' => 0, 'limit' => 250));
        $data['layouts'] = array();
        foreach ($layouts as $key => $layout) {
            $data['layouts'][$key] = $layout;

            $data['layouts'][$key]['routes'] = $this->model_design_layout->getLayoutRoutes($layout['layout_id']);
            $data['layouts'][$key]['url']    = $this->url->link('design/layout/edit', 'layout_id=' . $layout['layout_id'] . '&' . $this->module['url_token'], true);
        }

        // Page element
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->module['path'] . '/form', $data));
    }

    private function validatePermission()
    {
        if (!$this->user->hasPermission('modify', $this->module['path'])) {
            $this->error['warning'] = $this->data['error_permission'];
        }

        return !$this->error;
    }

    /**
     * Extension module callback
     */
    public function install() {}
    public function uninstall() {}
    
    public function updates()
    {
        // v3.2.0 - no longer use events
        $this->model_setting_event->deleteEventByCode($this->module['name']);
    }
}
