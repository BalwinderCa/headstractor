<?php
class ModelExtensionModuleCustomPages extends Model
{
    private $module = array();

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->config->load('isenselabs/custompages');
        $this->module = $this->config->get('custompages');

        $this->module['url_token'] = sprintf($this->module['url_token'], $this->session->data['user_token']);
    }

    public function getItems($param)
    {
        $items = array();
        $url_form = $this->url->link($this->module['path'] . '/form', $this->module['url_token'], true);

        $results = $this->db->query(
            "SELECT * FROM `" . DB_PREFIX . "module` m
            WHERE m.code = 'custompages'
            ORDER BY module_id ASC
            LIMIT " . (int)$param['start'] . "," . (int)$param['limit']
        );

        foreach ($results->rows as $key => $value) {
            $items[$key] = $value;
            $items[$key]['setting']  = json_decode($value['setting'], true);
            $items[$key]['url_form'] = $url_form . '&module_id=' . $value['module_id'];

            $items[$key]['layout_name'] = '';
            if (!empty($items[$key]['setting']['layout_id'])) {
                $layout = $this->db->query("SELECT * FROM `" . DB_PREFIX . "layout` WHERE layout_id = " . $items[$key]['setting']['layout_id'])->row;

                if (isset($layout['name'])) {
                    $items[$key]['layout_name'] = $layout['name'];
                }
            }
        }

        return $items;
    }

    public function getTotalItems()
    {
        $results = $this->db->query("SELECT COUNT(DISTINCT module_id) AS total FROM `" . DB_PREFIX . "module` WHERE `code` = 'custompages'");

        return $results->row['total'];
    }
}
