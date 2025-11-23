<?php
$_['custompages'] = array(
    'title'         => 'CustomPages',
    'name'          => $name = 'custompages',
    'version'       => '3.2.1' ,

    // Internal
    'code'          => 'module_' . $name,
    'path'          => 'extension/module/' . $name,
    'model'         => 'model_extension_module_' . $name,
    'ext_link'      => 'marketplace/extension',
    'ext_type'      => '&type=module',
    'url_token'     => 'user_token=%s',

    // Default setting
    'setting' => array(
        'status'           => 0,
    ),
    'page' => array(
        'layout_id'        => 0,
        'status'           => 0,
        'page_title'       => array(),
        'meta_title'       => array(),
        'meta_description' => array(),
        'meta_keyword'     => array(),
        'custom_code'      => '',
    )
);
