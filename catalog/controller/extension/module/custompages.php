<?php
class ControllerExtensionModuleCustomPages extends Controller
{
    private $modulePath = 'extension/module/custompages';

    /**
     * Prevent issue from accidentally assigned to Layout
     */
    public function index()
    {
        return;
    }

    public function load($data = array())
    {
        if (empty($this->request->get['route']) || !$this->config->get('module_custompages_status')) {
            return;
        }

        $this->load->model($this->modulePath);

        $route    = $this->request->get['route'];
        $layout   = $this->model_extension_module_custompages->getLayout($route);
        $template = 'extension/module/custompages/default';

        if (empty($layout['layout_id'])) {
            return;
        }
        
        $output      = null; // reset
        $language_id = $this->config->get('config_language_id');
        $setting     = $this->model_extension_module_custompages->getLayoutSetting($layout['layout_id']);

        $this->document->setTitle($setting['meta_title'][$language_id]);
        $this->document->setDescription($setting['meta_description'][$language_id]);
        $this->document->setKeywords($setting['meta_keyword'][$language_id]);

        $this->language->data['heading_title'] = $layout['name'];
        if (!empty($setting['title'][$language_id])) {
            $this->language->data['heading_title'] = $setting['title'][$language_id];
        }

        // Feature to use custom template per route. 
        // Ex. route=custom/landing create 'catalog/view/theme/default/template/extension/module/custompages/custom_landing.twig
        $tplRoute =  str_replace('/', '_', $route);
        if (file_exists(DIR_TEMPLATE . 'default/template/extension/module/custompages/' . $tplRoute . '.twig')) {
            $template = 'extension/module/custompages/' . $tplRoute;
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );
        $data['breadcrumbs'][] = array(
            'text' => $layout['name'],
            'href' => $this->url->link($route)
        );

        $data['setting'] = $setting;
        $data['setting']['custom_code'] = html_entity_decode($setting['custom_code'], ENT_COMPAT, 'UTF-8');

        $data['column_left']    = $this->load->controller('common/column_left');
        $data['column_right']   = $this->load->controller('common/column_right');
        $data['content_top']    = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer']         = $this->load->controller('common/footer');
        $data['header']         = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view($template, $data));
    }

    //=== Backward compatible
    public function init($eventRoute, &$data) {}
    public function tryCustomPage($eventRoute, &$data, &$output) {}
}
