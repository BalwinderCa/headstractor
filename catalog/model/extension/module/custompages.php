<?php
class ModelExtensionModuleCustomPages extends Model
{
    public function getLayout($route)
    {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "layout_route` lr 
                LEFT JOIN `" . DB_PREFIX . "layout` l ON l.layout_id = lr.layout_id
            WHERE '" . $this->db->escape($route) . "' LIKE lr.route 
                AND lr.store_id = '" . (int)$this->config->get('config_store_id') . "' 
            ORDER BY lr.route DESC LIMIT 1
        ");

        return $query->row;
    }

    public function getLayoutSetting($layout_id)
    {
        $data = array();
        $result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "module` m WHERE m.code = 'custompages' AND m.setting LIKE '%\"layout_id\":\"" . (int)$layout_id . "\"%' ORDER BY m.module_id ASC LIMIT 1")->row;

        if (isset($result['module_id'])) {
            $data = json_decode($result['setting'], true);
            $data['module_id'] = $result['module_id'];
        }

        return $data;
    }
}
