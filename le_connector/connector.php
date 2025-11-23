<?php

define('LECM_TOKEN', '3c87c28c02df5d15fa07533b968dd921');
define('CONNECTOR_VERSION', '1.0.7');

class LECM_Connector
{
    static $dev_mode = false;
    var $action = null;
    var $adapter = null;
    var $response = null;

    function getResponse()
    {
        if (!$this->response) {
            $this->response = new LECM_Connector_Response();
        }
        return $this->response;
    }

    function getAdapter()
    {
        if (!$this->adapter) {
            $this->adapter = new LECM_Connector_Adapter();
        }
        return $this->adapter;
    }

    function getAction()
    {
        if (!$this->action) {
            $this->action = new LECM_Connector_Action();
        }
        return $this->action;
    }

    function run()
    {
        if (empty($_GET)) {
            echo $this->getConnectorInstalledMessage();
            if (self::$dev_mode) {
                $this->devModeCheck();
            }
            return;
        }

        if (!$this->checkToken()) {
            $response = $this->getResponse();
            $response->token('Token is false !', null);
            return;
        }

        $action = $this->getAction();
        $action->setConnector($this);
        $action->run();
        return;
    }

    function checkToken()
    {
        if (isset($_GET['token']) && $_GET['token'] == LECM_TOKEN) {
            return true;
        } else {
            return false;
        }
    }

    public static function log($msg, $log_type = 'exception')
    {
        if(!self::$dev_mode){
            return false;
        }
        $log_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . $log_type . '.log';
        if (is_array($msg)) {
            $msg = serialize($msg);
        }
        $msg       .= "\r\n";
        $date_time = date('Y-m-d H:i:s');
        @file_put_contents($log_file, $date_time . ' : ' . $msg, FILE_APPEND);
    }

    function devModeCheck()
    {
        echo "<br />";
        echo "Developer mode is enabled. Status check: <br />";

        $connector_folder = dirname(__FILE__);
        echo "Connector folder is writable ...";
        if (is_writable($connector_folder)) {
            echo " ok";
        } else {
            echo "failed";
        }
        echo "<br />";
    }
    private function getConnectorInstalledMessage()
    {
        return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb"><head> <title>Connector is successfully Installed!</title> <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,500,600,700,800,600"> <style>body{overflow: hidden; width: 100vw; height: 100vh; display: flex; align-items: center; justify-content: center;}.checkmark{width: 56px; height: 56px; border-radius: 50%; display: block; stroke-width: 2; stroke: #fff; stroke-miterlimit: 10; box-shadow: inset 0px 0px 0px #7ac142; animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;}.checkmark__circle{stroke-dasharray: 166; stroke-dashoffset: 166; stroke-width: 2; stroke-miterlimit: 10; stroke: #7ac142; fill: none; animation: stroke .6s cubic-bezier(0.650, 0.000, 0.450, 1.000) forwards;}.checkmark__check{transform-origin: 50% 50%; stroke-dasharray: 48; stroke-dashoffset: 48; animation: stroke .3s cubic-bezier(0.650, 0.000, 0.450, 1.000) .8s forwards;}@keyframes stroke{100%{stroke-dashoffset: 0;}}@keyframes scale{0%, 100%{transform: none;}50%{transform: scale3d(1.1, 1.1, 1);}}@keyframes fill{100%{box-shadow: inset 0px 0px 0px 30px #7ac142;}}</style></head><body style=""><div "> <div style="margin-left: calc(50% - 28px)"> <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg> </div><div class="" style="margin-top: 20px;font-size: 18px;font-family: Arial;">Connector is successfully installed!</div></div></body></html>';
    }
}

class LECM_Connector_Response
{
    function createResponse($result, $msg, $obj, $error = null)
    {
        $response           = array();
        $response['result'] = $result;
        $response['msg']    = $msg;
        $response['data']   = $obj;
        $response['error']   = $error;
        $res = json_encode($response);
        if(!isset($_GET['encode']) || $_GET['encode'] != 'no'){
            $res = base64_encode(gzdeflate($res));
        }
        echo $res;
        return;
    }

    function error($msg = null, $obj = null, $error = null)
    {
        $this->createResponse('error', $msg, $obj);
    }

    function token($msg = null, $obj = null, $error = null)
    {
        $this->createResponse('token', $msg, $obj);
    }

    function success($msg = null, $obj = null, $error = null)
    {
        $this->createResponse('success', $msg, $obj, $error);
    }
}

class LECM_Connector_Action
{
    var $type = null;
    var $connector = null;

    function setConnector($connector)
    {
        $this->connector = $connector;
    }

    function run()
    {
        if (isset($_GET['action']) && $action = $this->getActionType($_GET['action'])) {
            $action->setConnector($this->connector);
            $action->run();
        } else {
            $response = $this->connector->getResponse();
            $response->createResponse('error', 'Action not found!', null);
            return;
        }
    }

    function getActionType($action_type)
    {
        $action      = null;
        $action_type = strtolower($action_type);
        $class_name  = __CLASS__ . '_' . ucfirst($action_type);
        if (class_exists($class_name)) {
            $action = new $class_name();
        }
        return $action;
    }

    function getResponse()
    {
        return $this->connector->getResponse();
    }

    function getAdapter()
    {
        return $this->connector->getAdapter();
    }

    function getCart($check = false)
    {
        $adapter = $this->getAdapter();
        $cart    = $adapter->getCart($check);
        return $cart;
    }

    function getParams($key, $params, $default = null)
    {
        return isset($params[$key]) ? $params[$key] : $default;
    }

    function getRealPath($path)
    {
        $path      = ltrim($path, '/');
        $full_path = LECM_STORE_BASE_DIR . $path;
        return $full_path;
    }

    function createParentDir($path, $mode = 0777)
    {
        $result = true;
        if (!is_dir(dirname($path))) {
            $result = @mkdir(dirname($path), 0777, true);
        }
        return $result;
    }

    function createFileSuffix($file_path, $suffix, $character = '_')
    {
        $new_path  = '';
        $dir_name  = pathinfo($file_path, PATHINFO_DIRNAME);
        $file_name = pathinfo($file_path, PATHINFO_FILENAME);
        $file_ext  = pathinfo($file_path, PATHINFO_EXTENSION);
        if ($dir_name && $dir_name != '.') $new_path .= $dir_name . '/';
        $new_path .= $file_name . $character . $suffix . '.' . $file_ext;
        return $new_path;
    }
    function request_decode($request){
        return @gzinflate(base64_decode($request));
    }
}

class LECM_Connector_Action_Check_Image extends LECM_Connector_Action{
    var $_file;
    function run()
    {
        $url = $this->getParams('url', $_REQUEST);
        $save_path = $this->getParams('save_path', $_REQUEST);
        $rename = $this->getParams('rename', $_REQUEST, False);
        $file   = $this->getActionFile();
        $result = $file->download($save_path, array('url' => $url, 'rename'=> $rename));
        var_dump($result);exit;
        return;
    }
    function getActionFile()
    {
        if (!$this->_file) {
            $this->_file = new LECM_Connector_Action_File();
            $this->_file->setType('image');
        }
        return $this->_file;
    }
}

class LECM_Connector_Action_Check extends LECM_Connector_Action
{
    function run()
    {
        $response   = $this->getResponse();
        $adapter    = $this->getAdapter();
        $cart       = $this->getCart(true);
//        $cart_type = $_REQUEST['cart_type'];
//        $list_cart_type = $adapter->detectCartType();
        if ($cart) {
            $obj['cms']                = $adapter->getType();
            $obj['image']              = $cart->imageDir;
            $obj['image_category']     = $cart->imageDirCategory;
            $obj['image_product']      = $cart->imageDirProduct;
            $obj['image_manufacturer'] = $cart->imageDirManufacturer;
            $obj['table_prefix']       = $cart->tablePrefix;
            $obj['version']            = $cart->version;
            $obj['charset']            = $cart->charset;
            $obj['cookie_key']         = $cart->cookie_key;
            $obj['extend']             = $cart->extend;
            $obj['connector_version']             = CONNECTOR_VERSION;
            $obj['download_image']             = function_exists('curl_init') || @ini_get('allow_url_fopen');
            if(isset($cart->view)){
                $obj['view'] = $cart->view;
            }
            if(isset($cart->site_id) && $cart->site_id){
                $obj['site_id'] = $cart->site_id;
            }
            $dbConnect                 = LECM_Db::getInstance($cart);
            if($dbConnect->getError()){
                if($cart->getHostPort() != 'localhost'){
                    $cart->setHostPort('localhost');
                    $new_dbConnect                 = LECM_Db::getInstance($cart, true);
                    if(!$new_dbConnect->getError()){
                        $dbConnect = $new_dbConnect;
                    }
                }
            }
            if ($dbConnect->getError()) {
                $obj['connect'] = array(
                    'result' => 'error',
                    'msg' => 'Not connect to database! Error: ' . $dbConnect->getError()
                );
            } else {
                $obj['connect'] = array(
                    'result' => 'success',
                    'msg' => 'Successful connect to database!'
                );
            }
        }

        $response->success('Successful check CMS!', $obj);
        return;
    }

}

class LECM_Connector_Action_Phpinfo extends LECM_Connector_Action
{
    function run()
    {
        phpinfo();
        return;
    }

}
class LECM_Connector_Action_Opcache extends LECM_Connector_Action
{
    function run()
    {
        opcache_reset();
        return;
    }

}
class LECM_Connector_Action_Directory extends LECM_Connector_Action
{
    function run()
    {
        $data     = array();
        $response = $this->getResponse();
        if (isset($_REQUEST['folders'])) {
            $folders = json_decode($this->request_decode($_REQUEST['folders']), true);
            foreach ($folders as $key => $folder) {
                $params     = isset($folder['params']) ? $folder['params'] : array();
                $data[$key] = $this->getDir($folder['type'], $folder['folder'], $params);
            }
        }
        return $response->success(null, $data);
    }

    function getDir($type, $folder, $params = array())
    {
        $result = false;
        switch ($type) {
            case 'writable';
                $result = $this->writable($folder, $params);
                break;
            case 'exists';
                $result = $this->exists($folder, $params);
                break;
            case 'dir';
                $result = $this->dir($folder, $params);
                break;
            case 'tree';
                $result = $this->tree($folder, $params);
                break;
            case 'delete';
                $result = $this->delete($folder, $params);
                break;
            case 'create';
                $result = $this->create($folder, $params);
                break;
            default:
                break;
        }
        return $result;
    }

    function writable($folder, $params = array())
    {
        $result = false;
        if (!$this->exists($folder)) {
            return $result;
        }
        $path   = $this->getRealPath($folder);
        $result = is_writable($path);
        return $result;
    }

    function exists($folder, $params = array())
    {
        $path = $this->getRealPath($folder);
        return is_dir($path);
    }

    function dir($folder, $params = array())
    {
        $result = array();
        if (!$this->exists($folder)) {
            return $result;
        }
        $path   = $this->getRealPath($folder);
        $result = $this->readDir($path, false);
        return $result;
    }

    function tree($folder, $params = array())
    {
        $result = array();
        if (!$this->exists($folder)) {
            return $result;
        }
        $path   = $this->getRealPath($folder);
        $result = $this->readDir($path, true);
        return $result;
    }

    function delete($folder, $params = array())
    {
        $result = false;
        if (!$this->exists($folder)) {
            return true;
        }
        if (!$this->writable($folder)) {
            return $result;
        }
        $path   = $this->getRealPath($folder);
        $self   = $this->getParams('self', $params);
        $result = $this->deleteDir($path, $self);
        return $result;
    }

    function create($folder, $params = array())
    {
        $result = true;
        if ($this->exists($folder)) {
            return $result;
        }
        $path   = $this->getRealPath($folder);
        $result = @mkdir(dirname($path), 0777, true);
        return $result;
    }

    function deleteDir($path, $self = true)
    {
        $path  = rtrim($path, '/\\');
        $items = glob($path . '/*', GLOB_MARK);
        foreach ($items as $item) {
            if (is_dir($item)) {
                $this->deleteDir($item, true);
            } else {
                @unlink($item);
            }
        }
        if ($self) {
            @rmdir($path);
        }
        return true;
    }

    function readDir($path, $content = false)
    {
        $result = array();
        $path   = rtrim($path, '/\\');
        $items  = glob($path . '/*', GLOB_MARK);
        foreach ($items as $item) {
            if (is_dir($item)) {
                $folder_data = array(
                    'type' => 'folder',
                    'path' => basename($item),
                );
                if ($content) {
                    $folder_data['content'] = $this->readDir($item, true);
                }
                $result[] = $folder_data;
            } else {
                $result[] = array(
                    'type' => 'file',
                    'path' => basename($item)
                );
            }
        }
        return $result;
    }
}

class LECM_Connector_Action_File extends LECM_Connector_Action
{
    var $type = 'file';
    const IMAGE_SUFFIX = 'nd';
    function run()
    {
        $data     = array();
        $response = $this->getResponse();
        if (isset($_REQUEST['files'])) {
            $files = json_decode($this->request_decode($_REQUEST['files']), true);
            foreach ($files as $key => $file) {
                $params     = isset($file['params']) ? $file['params'] : array();
                $data[$key] = $this->processFile($file['type'], $file['path'], $params);
            }
        }
        return $response->success(null, $data);
    }

    function setType($type){
        $this->type = $type;
    }

    function getSize($file){
        if($this->type == 'image'){
            return @getimagesize($file);
        }
        return @filesize($file);

    }

    function processFile($type, $path, $params = array())
    {
        $result = false;
        switch ($type) {
            case 'download':
                $result = $this->download($path, $params);
                break;
            case 'exists':
                $result = $this->exists($path, $params);
                break;
            case 'rename':
                $result = $this->rename($path, $params);
                break;
            case 'delete':
                $result = $this->delete($path, $params);
                break;
            case 'content':
                $result = $this->content($path, $params);
                break;
            case 'copy':
                $result = $this->copy($path, $params);
                break;
            case 'move':
                $result = $this->move($path, $params);
                break;
            default:
                break;
        }
        return $result;
    }

    function download($path, $params = array(), $time = 1)
    {
        $result = false;
        if (!$time) {
            return $result;
        }
        $override = $this->getParams('override', $params);
        $rename   = $this->getParams('rename', $params);
        $url      = $this->getParams('url', $params);
        $http_auth      = $this->getParams('http_auth', $params);
        if (!$url) {
            return $result;
        }
        if ($this->exists($path)) {
            $full_path = $this->getRealPath($path);

            if(filesize($full_path)){
//                if($time < 1){
//                    return $path;
//                }
                if ($rename) {
                    $path  = $this->rename($path, '2nd');
                } else {
                    if (!$override) {
                        $path      = ltrim($path, '/');
                        return $path;
                    }
                    $delete_file = $this->delete($path);
                    if (!$delete_file) {
                        return $result;
                    }
                }
            }else{
                $this->delete($path);
            }

        }
        $full_path = $this->getRealPath($path);
        $this->createParentDir($full_path);
        $fp = @fopen($full_path, 'wb');
        if (!$fp) {
            return '[LECM_ERROR]' . "Can't open file ". $full_path;
        }
        if(function_exists('curl_init')){

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/apng,*/*;q=0.8',
                'accept-encoding: deflate',
                'accept-language: en-US,en;q=0.8,uk;q=0.6',
                'user-agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201'
            ));
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            if($http_auth){
                $user = $this->getParams('user', $http_auth, '');
                $pass = $this->getParams('pass', $http_auth, '');
                curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
            }
            curl_setopt($ch, CURLOPT_FILE, $fp);
            try{
                $data      = curl_exec($ch);
            }catch (Exception $e){
                if($time <= 1){
                    return '[LECM_ERROR]' . $e->getMessage();
                }
                $data = false;
            }
            if (curl_errno($ch) && $time <= 1) {
                return '[LECM_ERROR]' . curl_error($ch);
            }
            curl_close($ch);
        }elseif(@ini_get('allow_url_fopen')){
            $context = null;
            if($http_auth){
                $user = $this->getParams('user', $http_auth, '');
                $pass = $this->getParams('pass', $http_auth, '');
                $context = stream_context_create(array(
                    'http' => array(
                        'header'  => "Authorization: Basic " . base64_encode("$user:$pass")
                    )
                ));
            }
            $content = @file_get_contents($url);
            if(!$content){
                return '[LECM_ERROR]' . 'url not found or file get content error';
            }
            $result = file_put_contents($full_path, $content);
        }else{
            return '[LECM_ERROR]' . 'curl and file get contents error';

        }
        @flush($fp);
        @fclose($fp);
        if ($this->getSize($full_path)) {
            $result = $path;
        }else{
            $this->delete($path);
        }
        if (!$result) {
            $time--;
            $result = $this->download($path, $params, $time);
        }
        return $result;
    }

    function exists($path, $params = array())
    {
        $full_path = $this->getRealPath($path);
        return file_exists($full_path);
    }

    function rename($path, $params = array())
    {
        $path      = ltrim($path, '/');
        $new_path  = $path;
        $full_path = $this->getRealPath($new_path);
        $dir_name  = pathinfo($path, PATHINFO_DIRNAME);
        $file_name = pathinfo($path, PATHINFO_FILENAME);
        $file_ext  = pathinfo($path, PATHINFO_EXTENSION);
        $i         = 2;
        if(substr($file_name, -2) == self::IMAGE_SUFFIX){
            $file_name_exp = explode('_', $file_name);
            if(is_numeric(str_replace(self::IMAGE_SUFFIX, '', $file_name_exp[count($file_name_exp) - 1]))){

                $i = str_replace(self::IMAGE_SUFFIX, '', $file_name_exp[count($file_name_exp) - 1]) + 1;
                if(count($file_name_exp) > 1){

                    unset($file_name_exp[count($file_name_exp)-1]);
                }
                $file_name = implode('_', $file_name_exp);
                $new_path = '';
                if ($dir_name && $dir_name != '.'){
                    $new_path .= $dir_name . '/';
                }
                $new_path .= $file_name . '.' . $file_ext;
                $path = $new_path;
            }
        }
        while (file_exists($full_path)) {
            $new_path  = $this->createFileSuffix($path, $i.self::IMAGE_SUFFIX);
            $full_path = $this->getRealPath($new_path);
            $i++;
        }
        return $new_path;
    }

    function delete($path, $params = array())
    {
        $result = true;
        if (!$this->exists($path)) {
            return $result;
        }
        $full_path = $this->getRealPath($path);
        $result    = @unlink($full_path);
        return $result;
    }

    function content($path, $params = array())
    {
        $result    = '';
        $full_path = $this->getRealPath($path);
        if (!$this->exists($path)) {
            return $result;
        }
        $result = @file_get_contents($full_path);
        return $result;
    }

    function copy($path, $params = array())
    {
        $result    = false;
        $override  = $this->getParams('override', $params);
        $copy_path = $this->getParams('copy', $params);
        if (!$copy_path) {
            return $result;
        }
        if (!$this->exists($path)) {
            return $result;
        }
        if ($this->exists($copy_path)) {
            if (!$override) {
                return $result;
            }
            $delete_file = $this->delete($copy_path);
            if (!$delete_file) {
                return $result;
            }
        }
        $full_path      = $this->getRealPath($path);
        $full_copy_path = $this->getRealPath($copy_path);
        $this->createParentDir($full_copy_path);
        $result = @copy($full_path, $full_copy_path);
        return $result;
    }

    function move($path, $params = array())
    {
        $result    = false;
        $override  = $this->getParams('override', $params);
        $move_path = $this->getParams('move', $params);
        $rename= $this->getParams('rename', $params);
        $url = $this->getParams('url', $params);
        if (!$move_path) {

            return $result;
        }
        if ($this->exists($move_path)) {

            if (!$override) {
                if($rename){
                    $move_path = $this->rename($move_path);
                }else{
                    return $move_path;
                }
            }else{
                $delete_file = $this->delete($move_path);
                if (!$delete_file) {
                    return $result;
                }
            }
        }
        if (!$this->exists($path) ) {


            if(!$url){
                return $result;
            }
            $download_params = array(
                'url' => $params['url'],
                'override' => false,
                'rename' => false,
            );
            return $this->download($move_path, $download_params);
        }

        $full_path      = $this->getRealPath($path);
        $full_move_path = $this->getRealPath($move_path);
        $this->createParentDir($full_move_path);
        $result = rename($full_path, $full_move_path);
        if($result){
            return $move_path;
        }else{
            return '[LECM_ERROR]' . ' Folder Image does not writable';
        }
    }

}

class LECM_Connector_Action_Image extends LECM_Connector_Action
{
    var $_file = null;

    function run()
    {
        $data     = array();
        $response = $this->getResponse();
        $error = '';
        if (isset($_REQUEST['images'])) {
            $images = json_decode($this->request_decode($_REQUEST['images']), true);
            foreach ($images as $key => $image) {
                $params     = isset($image['params']) ? $image['params'] : array();
                $result = $this->processImage($image['type'], $image['path'], $params);
                if(strpos($result, '[LECM_ERROR]') !== false){
                    $error = str_replace('[LECM_ERROR]', '', $result);
                    $result = false;
                }
                $data[$key] = $result;
            }
        }
        return $response->success(null, $data, $error);
    }

    function processImage($type, $path, $params = array())
    {
        $result = false;
        switch ($type) {
            case 'download':
                $result = $this->download($path, $params);
                break;
            case 'exists':
                $result = $this->exists($path, $params);
                break;
            case 'rename':
                $result = $this->rename($path, $params);
                break;
            case 'delete':
                $result = $this->delete($path, $params);
                break;
            case 'copy':
                $result = $this->copy($path, $params);
                break;
            case 'move':
                $result = $this->move($path, $params);
                break;
            case 'resize':
                $result = $this->resize($path, $params);
                break;
            default:
                break;
        }
        return $result;
    }

    function download($path, $params = array())
    {
        $file   = $this->getActionFile();
        $result = $file->download($path, $params);
        return $result;
    }

    function exists($path, $params = array())
    {
        $file   = $this->getActionFile();
        $result = $file->exists($path, $params);
        return $result;
    }

    function rename($path, $params = array())
    {
        $file   = $this->getActionFile();
        $result = $file->rename($path, $params);
        return $result;
    }

    function delete($path, $params = array())
    {
        $file   = $this->getActionFile();
        $result = $file->delete($path, $params);
        return $result;
    }

    function copy($path, $params = array())
    {
        $file   = $this->getActionFile();
        $result = $file->copy($path, $params);
        return $result;
    }

    function move($path, $params = array())
    {
        $file   = $this->getActionFile();
        $result = $file->move($path, $params);
        return $result;
    }

    function resize($path, $params = array())
    {
        $desc_path    = $this->getParams('desc', $params);
        $width        = $this->getParams('width', $params);
        $height       = $this->getParams('height', $params);
        $crop         = $this->getParams('crop', $params);
        $proportional = $this->getParams('proportional', $params);
        $quality      = $this->getParams('quality', $params);
        if (!$quality) {
            $quality = 100;
        }
        $result = $this->resizeImage($path, $desc_path, $width, $height, $crop, $proportional, $quality);
        return $result;
    }

    function getActionFile()
    {
        if (!$this->_file) {
            $this->_file = new LECM_Connector_Action_File();
            $this->_file->setType('image');
        }
        return $this->_file;
    }

    function resizeImage($src_path, $desc_path, $width = 0, $height = 0, $crop = false, $proportional = false, $quality = 100)
    {
        $result = false;
        if ($height <= 0 && $width <= 0) {
            return $result;
        }
        if ($this->exists($desc_path)) {
            $delete = $this->delete($desc_path);
            if (!$delete) {
                return $result;
            }
        }
        $src_image  = $this->getRealPath($src_path);
        if (!$this->exists($src_path)) {
            return $result;
        }
        $desc_image = $this->getRealPath($desc_path);
        $imageInfo  = getimagesize($src_image);
        list($src_width, $src_height) = $imageInfo;
        $cropHeight = $cropWidth = 0;
        if ($proportional) {
            if ($width == 0) {
                $factor = $height / $src_height;
            } elseif ($height == 0) {
                $factor = $width / $src_width;
            } else {
                $factor = min($width / $src_width, $height / $src_height);
            }
            $final_width  = round($src_width * $factor);
            $final_height = round($src_height * $factor);
        } else {
            $final_width  = ($width <= 0) ? $src_width : $width;
            $final_height = ($height <= 0) ? $src_height : $height;
            if ($crop) {
                $widthX     = $src_width / $width;
                $heightX    = $src_height / $height;
                $x          = min($widthX, $heightX);
                $cropWidth  = ($src_width - $width * $x) / 2;
                $cropHeight = ($src_height - $height * $x) / 2;
            }
        }
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($src_image);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($src_image);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($src_image);
                break;
            default:
                return false;
        }
        $image_resize = imagecreatetruecolor($final_width, $final_height);
        if (($imageInfo[2] == IMAGETYPE_GIF) || ($imageInfo[2] == IMAGETYPE_PNG)) {
            $transparency = imagecolortransparent($image);
            $pallet_size  = imagecolorstotal($image);
            if ($transparency >= 0 && $transparency < $pallet_size) {
                $transparent_color = imagecolorsforindex($image, $transparency);
                $transparency      = imagecolorallocate($image_resize, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                imagefill($image_resize, 0, 0, $transparency);
                imagecolortransparent($image_resize, $transparency);
            } elseif ($imageInfo[2] == IMAGETYPE_PNG) {
                imagealphablending($image_resize, false);
                $color = imagecolorallocatealpha($image_resize, 0, 0, 0, 127);
                imagefill($image_resize, 0, 0, $color);
                imagesavealpha($image_resize, true);
            }
        }
        imagecopyresampled($image_resize, $image, 0, 0, $cropWidth, $cropHeight, $final_width, $final_height, $src_width - 2 * $cropWidth, $src_height - 2 * $cropHeight);
        $this->createParentDir($desc_image);
        switch ($imageInfo[2]) {
            case IMAGETYPE_GIF:
                $result = imagegif($image_resize, $desc_image);
                break;
            case IMAGETYPE_JPEG:
                $result = imagejpeg($image_resize, $desc_image, $quality);
                break;
            case IMAGETYPE_PNG:
                $quality = 9 - (int)((0.9 * $quality) / 10.0);
                $result  = imagepng($image_resize, $desc_image, $quality);
                break;
            default:
                return false;
        }
        return $result;
    }
}

class LECM_Connector_Action_Path extends LECM_Connector_Action{
    function run()
    {
        $path = dirname(dirname(__FILE__));
        $path = str_replace('le_connector', '', $path);
        echo $path;
        return;
    }
}
class LECM_Connector_Action_Adminer extends LECM_Connector_Action{
    const URL_DOWNLOAD = 'https://github.com/vrana/adminer/releases/download/v4.7.7/adminer-4.7.7.php';
    var $_file = null;
    function run()
    {
        $file_name = md5('litextension' . LECM_TOKEN) . '.php';
        $path = $this->getPathFileAdminer() . 'le_connector'. DIRECTORY_SEPARATOR .$file_name;
        $full_path = $this->getRealPath($path);
        if(file_exists($full_path)){
            return $this->login();
        }
        $file   = $this->getActionFile();
        $params = array(
            'url' => self::URL_DOWNLOAD,
            'override' => true,
            'rename' => false
        );
        $result = $file->download($path, $params);
        if($result && strpos($result, '[LECM_ERROR]') === false){
            return $this->login();
        }
        echo $result;
    }
    function getPathFileAdminer(){
        $special_uri = array('litextension-data-migration-to-woocommerce');
        $cart_uri = '';
        foreach ($special_uri as $uri){
            if(stripos($_SERVER['REQUEST_URI'], $uri) !== false){
                $cart_uri = $uri;
                break;
            }
        }
        $path = '';
        if(MODULE_CONNECTOR){
            switch ($cart_uri){
                case 'litextension-data-migration-to-woocommerce':
                    $path = 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $cart_uri . DIRECTORY_SEPARATOR;
                    break;
                default:
                    break;

            }
        }
        return $path;
    }
    function getActionFile()
    {
        if (!$this->_file) {
            $this->_file = new LECM_Connector_Action_File();
        }
        return $this->_file;
    }
    function styleAdminer(){
        $file_name = md5('litextension' . LECM_TOKEN) . '.php';
        $path = $this->getPathFileAdminer() . 'le_connector'. DIRECTORY_SEPARATOR .$file_name;
        $full_path = $this->getRealPath($path);
        if(@$_GET['type'] =='target'){
            $connector_type = 'Target';
            $background = '';
        }else{
            $connector_type = 'Source';
            $background = 'background-color: red';
        }
        $adminer = file_get_contents($full_path);
        $adminer = str_replace("{return\"<a href='https://www.adminer.org/'\".target_blank().\" id='h1'>Adminer</a>\";}", "{return\"<a href='https://www.adminer.org/'\".target_blank().\" id='h1'><span style='font-weight: bold; font-size: 40px'>$connector_type</span></a>\";}", $adminer);
        $adminer = str_replace('<h2>$oi</h2>', '<h2 style=\''.$background.'\'>$oi</h2>', $adminer);

        file_put_contents($full_path, $adminer);
    }
    function login(){
        $this->styleAdminer();
        $file_name = md5('litextension' . LECM_TOKEN) . '.php';
        $cart     = $this->getCart();
        echo '<form name="fr" action="'.$file_name.'" method=POST>
        <input type="hidden" name=auth[username] value="'.$cart->username.'">
        <input type="hidden" name=auth[driver] value="server">
        <input type="hidden" name=auth[password] value="'.$cart->password.'">
        <input type="hidden" name=auth[db] value="'.$cart->database.'">
        <input type="hidden" name=auth[server] value="'.$cart->host.'">
        <input type="hidden" name=auth[permanent] value="1">
        </form>
        <script type="text/javascript">
            document.fr.submit();
        </script>';
    }
}
class LECM_Connector_Action_Remove_Adminer extends LECM_Connector_Action_Adminer {
    var $_file = null;
    function run()
    {
        $file_name = md5('litextension' . LECM_TOKEN) . '.php';
        $path = $this->getPathFileAdminer() . 'le_connector'. DIRECTORY_SEPARATOR .$file_name;
        $full_path = $this->getRealPath($path);
        $res = 'success';
        if(file_exists($full_path)){
            if(!@unlink($full_path)){
                $res = 'fail';
            }
        }
        echo $res;
    }
    function getPathFileAdminer(){
        $special_uri = array('litextension-data-migration-to-woocommerce');
        $cart_uri = '';
        foreach ($special_uri as $uri){
            if(stripos($_SERVER['REQUEST_URI'], $uri) !== false){
                $cart_uri = $uri;
                break;
            }
        }
        $path = '';
        if(MODULE_CONNECTOR){
            switch ($cart_uri){
                case 'litextension-data-migration-to-woocommerce':
                    $path = 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $cart_uri . DIRECTORY_SEPARATOR;
                    break;
                default:
                    break;

            }
        }
        return $path;
    }
    function getActionFile()
    {
        if (!$this->_file) {
            $this->_file = new LECM_Connector_Action_File();
        }
        return $this->_file;
    }
    function login(){
        $file_name = md5('litextension' . LECM_TOKEN) . '.php';
        $cart     = $this->getCart();
        echo '<form name="fr" action="'.$file_name.'" method=POST>
        <input type="hidden" name=auth[username] value="'.$cart->username.'">
        <input type="hidden" name=auth[driver] value="server">
        <input type="hidden" name=auth[password] value="'.$cart->password.'">
        <input type="hidden" name=auth[db] value="'.$cart->database.'">
        <input type="hidden" name=auth[server] value="'.$cart->host.'">
        <input type="hidden" name=auth[permanent] value="1">
        </form>
        <script type="text/javascript">
            document.fr.submit();
        </script>';
    }
}
class LECM_Connector_Action_Execute extends LECM_Connector_Action
{
    function run(){
        $query = isset($_REQUEST['query'])?$_REQUEST['query']:'';
        $res = '';
        $type = '';
        $error = '';
        if($query){
            $cart       = $this->getCart(true);
            $dbConnect = LECM_Db::getInstance($cart);
            $query = strtolower($query);
            $type = explode(' ', $query);
            $type = $type[0];
            if($type == 'select' && strpos($query, 'limit') === false){
                $query = trim($query, ';,') . ' limit 50';
            }
            if(!in_array($type, array('select', 'delete', 'insert', 'update'))){
                $type = 'select';
            }

            $processQuery = $dbConnect->processQuery($type, $query);
            if($processQuery['result']){
                $res = $processQuery['data'];
            }else{
                $res = $processQuery['msg'];
            }
            $error = $dbConnect->getError();
        }
        $this->render($type, $query, $res,$error);
    }
    function render($type, $query, $res, $error = null){
        echo '<form action="" method="post">
        <div>
        <textarea name="query" id="" cols="30" rows="10" style="width: 100%;height: auto;padding: 10px" ">'.$query.'</textarea>
            <input type="submit" name="" style="width: 75px;height: 25px; padding: 5px; margin: 10px 0px" value="execute">
        </div>
    </form>';
        if($error){
            echo '<span style="color: red">'.$error.'</span>';
            return;
        }
        if(!$res){
            return;
        }
        if($type != 'select' ){

            echo 'success';
        }else{
            $keys = array_keys($res[0]);
            echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb">
<head>
    <style>

        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        th, td {
  padding: 5px 10px;
}
    </style>
</head>
<body style="">
<table>
    <tr>
        ';
            foreach ($keys as $key){
                echo '<th><pre>' . $key . '</pre></th>';
            }
            echo '
    </tr>';
            foreach ($res as $row){
                echo '<tr><pre>';
                foreach ($keys as $key){
                    echo '<th><pre>' . $row[$key] . '</pre></th>';

                }
                echo '</pre></tr>';
            }
            echo '
</table>
</body>
</html>
    ';
        }
    }

}

class LECM_Connector_Action_Query extends LECM_Connector_Action
{
    function run()
    {
        $obj      = array();
        $error = array();
        $response = $this->getResponse();
        $cart     = $this->getCart();
        if ($cart) {
            $dbConnect = LECM_Db::getInstance($cart);
            if (isset($_REQUEST['query']) && !$dbConnect->getError()) {
                $queries = @json_decode($this->request_decode($_REQUEST['query']), true);
                if($queries !== false){
                    $dbConnect->processQuery('query', "SET SESSION SQL_MODE = ''");
                }
                if (isset($_REQUEST['serialize']) && $_REQUEST['serialize'] && $queries !== false) {
                    foreach ($queries as $key => $query) {
                        if (is_array($query) && isset($query['type'])) {
                            $params    = isset($query['params']) ? $query['params'] : null;
                            $processQuery = $dbConnect->processQuery($query['type'], $query['query'], $params);
                            $obj[$key] = $processQuery['data'];
                            $error[$key] = '';
                            if($processQuery['msg']){
                                $error[$key] = $processQuery['msg'];
                            }
                        } else {
                            $processQuery = $dbConnect->processQuery('select', $query);
                            $obj[$key] = $processQuery['data'];
                            $error[$key] = '';
                            if($processQuery['msg']){
                                $error[$key] = $processQuery['msg'];
                            }
                        }
                    }
                } elseif ($queries !== false) {
                    $query  = $queries;
                    $params = isset($query['params']) ? $query['params'] : null;
                    $processQuery    = $dbConnect->processQuery($query['type'], $query['query'], $params);
                    $obj = $processQuery['data'];
                    $error = '';

                    if($processQuery['msg']){
                        $error = $processQuery['msg'];
                    }
                } else {
                    $query = $this->request_decode($_REQUEST['query']);
                    $processQuery   = $dbConnect->processQuery('select', $query);
                    $obj = $processQuery['data'];
                    $error = '';

                    if($processQuery['msg']){
                        $error = $processQuery['msg'];
                    }
                }
                $response->success(null, $obj, $error);
                return;
            } else {
                $response->error('Can\'t connect to database or not run query! Error: ' . $dbConnect->getError(), null);
                return;
            }
        } else {
            $response->error('CMS Cart not found!', null);
            return;
        }
    }

}

class LECM_Connector_Action_Clearcache extends LECM_Connector_Action
{
    function run(){
        $response   = $this->getResponse();
        $cart       = $this->getCart();
        if($cart){
            $clear = $cart->clearCache();
            if($clear['result'] != 'success'){
                $response->error($clear['msg']);
            }else{
                $response->success();
            }
        }else{
            $response->error('Not detect cart');
        }
        return;
    }

}

abstract class LECM_Db
{
    static $instance = null;
    static $servers = array();
    var $server = 'localhost';
    var $user = 'root';
    var $password = '';
    var $database = '';
    var $charset = 'utf8';
    var $link = null;
    var $response = null;
    var $error = null;

    abstract function connect();

    abstract function query($query);

    abstract function select($query);

    abstract function disconnect();

    abstract function getLastInsertId();

    function insert($sql, $params = null)
    {
        $result = $this->query($sql);
        if ($result['data'] && isset($params['insert_id'])) {
            $result['data'] = $this->getLastInsertId();
        }
        return $result;
    }


    function defaultResponse(){
        return array(
            'result' => true,
            'msg' => '',
            'data' => ''
        );
    }

    function __construct($server, $user, $password, $database, $charset, $connect = true)
    {
        $this->server   = $server;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;

        if($charset){
            $this->charset  = $charset;
        }

        $this->response = new LECM_Connector_Response();

        if ($connect) {
            $this->connect();
        }
    }

    function __destruct()
    {
        if ($this->link) {
            $this->disconnect();
        }
    }

    static function getInstance($cart, $new = false)
    {
        if($new){
            self::$instance = null;
        }
        if (!self::$instance) {
            $class          = LECM_Db::getClass();
            self::$servers  = array('server' => $cart->host, 'user' => $cart->username, 'password' => $cart->password, 'database' => $cart->database, 'charset' => @$cart->charset);
            self::$instance = new $class(
                self::$servers['server'],
                self::$servers['user'],
                self::$servers['password'],
                self::$servers['database'],
                self::$servers['charset']
            );
        }
        return self::$instance;
    }

    static function getClass()
    {
        if (function_exists('mysql_connect')) {
            $class = 'LECM_MySQL';
        } elseif (class_exists('PDO') && extension_loaded('pdo_mysql')) {
            $class = 'LECM_Pdo';
        } else {
            $class = 'LECM_MySQLi';
        }
        return $class;
    }

    function getLink()
    {
        return $this->link;
    }

    function getError()
    {
        return $this->error;
    }

    function processQuery($type, $query, $params = null)
    {
        $result = null;
        switch (strtolower($type)) {
            case 'select':
                $result = $this->select($query);
                break;
            case 'insert':
                $result = $this->insert($query, $params);
                break;
            case 'query':
                $result = $this->query($query);
                break;
            default:
                $result = $this->query($query);
                break;
        }
        return $result;
    }
}

class LECM_MySQL extends LECM_Db
{
    function connect()
    {
        if (!$this->link = @mysql_connect($this->server, $this->user, $this->password)) {
            $this->error = 'Link to database cannot be established.';
            return;
        }
        if (!mysql_select_db($this->database, $this->link)) {
            $this->error = 'The database selection cannot be made.';
            return;
        }
        if ($this->charset) {
            mysql_set_charset($this->charset, $this->link);
        }
        /*
        if (!mysql_query('SET NAMES \'utf8\'', $this->link)) {
            $this->error = 'No utf-8 support. Please check your server configuration.';
            return;
        }*/
        return $this->link;
    }

    function disconnect()
    {
        mysql_close($this->link);
    }

    function query($sql)
    {
        $response = $this->defaultResponse();
        $res = mysql_query($sql, $this->link);
        if (mysql_errno($this->link)) {
            $response['msg'] = mysql_error($this->link);
            LECM_Connector::log($sql . ": " . mysql_error($this->link), 'mysql');
        }
        if(!$res){
            $response['result'] = false;
        }
        $response['data'] = $res;
        return $response;
    }



    function select($sql)
    {
        $data   = array();
        $result = $this->query($sql);
        if(!$result['result'] && mysql_errno($this->link)){
            $result['data'] = false;
            return $result;
        }
        while ($row = mysql_fetch_array($result['data'], MYSQL_ASSOC)) {
            $data[] = $row;
        }
        $result['data'] = $data;
        return $result;
    }
    function getLastInsertId(){
        return mysql_insert_id($this->link);
    }
}

class LECM_MySQLi extends LECM_Db
{
    function connect()
    {
        $socket = false;
        $port   = false;
        if (strpos($this->server, ':') !== false) {
            list($server, $port) = explode(':', $this->server);
            if (is_numeric($port) === false) {
                $socket = $port;
                $port   = false;
            }
        } elseif (strpos($this->server, '/') !== false) {
            $socket = $this->server;
        }
        if ($socket) {
            $this->link = @new mysqli(null, $this->user, $this->password, $this->database, null, $socket);
        } elseif ($port) {
            $this->link = @new mysqli($server, $this->user, $this->password, $this->database, $port);
        } else {
            $this->link = @new mysqli($this->server, $this->user, $this->password, $this->database);
        }
        if (mysqli_connect_error()) {
            $this->error = 'Link to database cannot be established: ' . mysqli_connect_error();
            return;
        }
        if ($this->charset) {
            $this->link->set_charset($this->charset);

        }
        /*
        if (!$this->link->query('SET NAMES \'utf8\'')) {
            $this->error = 'No utf-8 support. Please check your server configuration.';
            return;
        }*/
        return $this->link;
    }

    function disconnect()
    {
        @$this->link->close();
    }

    function query($sql)
    {
        $response = $this->defaultResponse();
        $res = $this->link->query($sql);
        if ($this->link->error) {
            $response['msg'] = $this->link->error;
            LECM_Connector::log($sql . ": " . $this->link->error, 'mysqli');
        }
        if(!$res){
            $response['result'] = false;
        }
        $response['data'] = $res;
        return $response;
    }

    function select($sql)
    {
        $data   = array();
        $result = $this->query($sql);
        if(!$result['result'] && $this->link->error){
            $result['data'] = false;
            return $result;
        }
        while ($row = mysqli_fetch_array($result['data'], MYSQLI_ASSOC)) {
            $data[] = $row;
        }
        $result['data'] = $data;
        return $result;
    }
    function getLastInsertId(){
        return $this->link->insert_id;
    }
}

class LECM_Pdo extends LECM_Db
{
    function connect()
    {
        $socket = false;
        $port   = false;
        if (strpos($this->server, ':') !== false) {
            list($server, $port) = explode(':', $this->server);
            $this->server = $server;
            if (is_numeric($port) === false) {
                $socket = $port;
                $port   = false;
            }
        } elseif (strpos($this->server, '/') !== false) {
            $socket = $this->server;
            $this->server = '';
        }
        $socket_cnx = ($socket ? ('unix_socket=' . $socket . ';') : '');
        $port_cnx = ($port ? ('port=' . $port . ';') : '');
        $dsn = "mysql:host=$this->server;$port_cnx$socket_cnx dbname=$this->database";
        if ($this->charset) {
            $dsn .= ";charset=" . $this->charset;
        } else {
            $dsn .= ";charset=utf8";
        }

        $retry = 3;
        while ($retry) {
            try {
                $this->link = new PDO($dsn,$this->user,$this->password, null);
                if ($this->charset) {
                    $charset = $this->charset;
                } else {
                    $charset = "utf8";
                }
                $this->link->exec('SET NAMES ' . $charset);
                $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                if (!empty($_REQUEST['disable_checks'])) {
                    $this->link->exec('SET SESSION FOREIGN_KEY_CHECKS=0, SESSION SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
                }
                return $this->link;

            } catch (PDOException $e) {
                if($e->getCode() == 2002 && $this->server == 'localhost'){
                    $this->server = '127.0.0.1';
                    return $this->connect();
                }
                $retry--;

                if ($retry == 1) {
                    $this->password = sprintf($this->password);
                }
                if(!$retry){
                    $this->error ='Link to database cannot be established: '. $e->getMessage();
                }
            }
        }
        return $this->link;
    }

    function disconnect()
    {
        $this->link = null;
    }

    function query($sql)
    {
        $response = $this->defaultResponse();
        try{
            $res = $this->link->query($sql);
        }catch (PDOException $e){
            $response['msg'] = $e->getMessage();
            LECM_Connector::log($sql . ": " . $e->getMessage(), 'pdo');
            $this->error = $e->getMessage();
            $res = false;
        }
        if(!$res){
            $response['result'] = false;
        }
        $response['data'] = $res;
        return $response;
    }

    function select($sql)
    {
        $data   = array();
        $result = $this->query($sql);
        if(!$result['result'] && $this->error){
            $result['data'] = false;
            return $result;
        }
        $res = $result['data'];
        $res->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($res as $row) {
            $data[] = $row;
        }
        $result['data'] = $data;
        return $result;
    }

    function getLastInsertId(){
        return $this->link->lastInsertId();
    }
}

class LECM_Connector_Adapter
{
    var $cart = null;
    var $host = 'localhost';
    var $username = 'root';
    var $password = '';
    var $database = '';
    var $tablePrefix = '';
    var $imageDir = '';
    var $imageDirCategory = '';
    var $imageDirProduct = '';
    var $imageDirManufacturer = '';
    var $version = '';
    var $charset = 'utf8';
    var $cookie_key = '';
    var $extend = '';
    var $check = false;
    var $type = false;

    function getType(){
        return $this->type;
    }

    public static function detectRootFolder(){
        $special_uri = array('litextension-data-migration-to-woocommerce');
        $module_connector = false;
        $cart_uri = '';
        foreach ($special_uri as $uri){
            if(stripos($_SERVER['REQUEST_URI'], $uri) !== false){
                $module_connector = true;
                $cart_uri = $uri;
                break;
            }
        }
        define('MODULE_CONNECTOR', $module_connector);
        if($module_connector){
            switch ($cart_uri){
                case 'litextension-data-migration-to-woocommerce':
                    define('LECM_STORE_BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR);
                    break;
                default:
                    define('LECM_STORE_BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

            }
        }else{
            define('LECM_STORE_BASE_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
        }
    }

    function getCart($check = false)
    {
        $cart = null;
        $cart_type = null;
        if  (file_exists(LECM_STORE_BASE_DIR . 'le_connector/db_custom.php'))
        {
            $cart_type = 'custom';
        }
        elseif(@$_REQUEST['cart_type']){
            $cart_type = $_REQUEST['cart_type'];
        }
        $list_cart_type  = $this->detectCartType();
        if($list_cart_type && in_array($cart_type, $list_cart_type)){
            $cart = $this->getCartType($cart_type, $check);
            if($cart){
                $this->cart = $cart;
                if($cart_type == 'custom'){
                    $db_custom = @require LECM_STORE_BASE_DIR . 'le_connector/db_custom.php';
                    $this->type = $db_custom['cart_type'];
                }else{
                    $this->type = $cart_type;
                }
                return $cart;
            }
        }
        foreach ($list_cart_type as $cart_type){
            $cart = $this->getCartType($cart_type, $check);
            if($cart){
                $this->cart = $cart;
                $this->type = $cart_type;
                return $cart;
            }
        }
        return $this->cart;
    }

    function getCartType($cart_type, $check)
    {
        $cart       = null;
        $cart_type  = strtolower($cart_type);
        $class_name = __CLASS__ . '_' . ucfirst($cart_type);
        if (class_exists($class_name)) {
            $cart = new $class_name();
            $cart->setCheck($check);
            $cart->setEnv();
        }
        return $cart;
    }

    function setCheck($check = false)
    {
        $this->check = $check;
    }

    function setEnv()
    {
        return $this;
    }

    function detectCartType()
    {
        $list_cart = array();
        //Custom cart
        if (file_exists(LECM_STORE_BASE_DIR . 'le_connector/db_custom.php'))
        {
            $db_custom = @require LECM_STORE_BASE_DIR . 'le_connector/db_custom.php';
            $list_cart[] = $db_custom['cart_type'];
            $list_cart[] = 'custom';
        }
        //Gambio
        if (@file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php')
            && @file_exists(LECM_STORE_BASE_DIR . 'gm' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'GMCat.php')
        ) {
            $list_cart[] = 'gambio';
        }

        //Shopware
        if ((@file_exists(LECM_STORE_BASE_DIR . '.env') || @file_exists(LECM_STORE_BASE_DIR . '../.env'))
            || (@file_exists(LECM_STORE_BASE_DIR . 'config.php') && @file_exists(LECM_STORE_BASE_DIR . 'engine/Shopware/'))
        ) {
            $list_cart[] = 'shopware';
        }

        //Squirrelcart
        if (@file_exists(LECM_STORE_BASE_DIR . 'squirrelcart/config.php')) {
            $list_cart[] = 'squirrelcart';
        }
        if (file_exists(LECM_STORE_BASE_DIR . 'sites/default/settings.php') && (is_dir(LECM_STORE_BASE_DIR . 'sites/all/modules/commerce') or is_dir(LECM_STORE_BASE_DIR . 'modules/commerce'))) {
            $list_cart[] = 'drupal';
        }
        if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php')) {

            // ZenCart
            if (file_exists(LECM_STORE_BASE_DIR . 'ipn_main_handler.php')) {
                $list_cart[] = 'zencart';
            }

            // XtCommerce v3
            if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.org.php')) {
                $list_cart[] = 'xtcommerce';
            }

            // Loaded Commerce v6
            if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure_dist.php')) {
                $list_cart[] = 'loaded';
            }

            // TomatoCart
            if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'toc_constants.php')) {
                $list_cart[] = 'tomatocart';
            }

            // OsCommerce
            $list_cart[] = 'oscommerce';
        }

        // VirtueMart
        if ((file_exists(LECM_STORE_BASE_DIR . 'configuration.php')) && (file_exists(LECM_STORE_BASE_DIR . '/components/com_virtuemart/virtuemart.php'))
        ) {
            $list_cart[] = 'virtuemart';
        }

        //Mijoshop
        if ((file_exists(LECM_STORE_BASE_DIR . 'configuration.php')) && (file_exists(LECM_STORE_BASE_DIR . 'components/com_mijoshop/opencart/config.php'))
        ) {
            $list_cart[] = 'mijoshop';
        }

        //joomshopping
        if ((file_exists(LECM_STORE_BASE_DIR . 'configuration.php')) && (file_exists(LECM_STORE_BASE_DIR . 'jshopping.xml'))
        ) {
            $list_cart[] = 'joomshopping';
        }

        // WordPress
        if (file_exists(LECM_STORE_BASE_DIR . 'wp-config.php') || file_exists(LECM_STORE_BASE_DIR . '../wp-config.php')) {
            // WooCommerce
            $wooCommerceDir = glob(LECM_STORE_BASE_DIR . 'wp-content/plugins/woocommerce*', GLOB_ONLYDIR);
            if (is_array($wooCommerceDir) && count($wooCommerceDir) > 0) {
                $list_cart[] = 'woocommerce';
            }
            //Jigoshop
            $JigoshopDir = glob(LECM_STORE_BASE_DIR . 'wp-content/plugins/jigoshop*', GLOB_ONLYDIR);
            if (is_array($JigoshopDir) && count($JigoshopDir) > 0) {
                $list_cart[] = 'jigoshop';
            }
            // Cart66
            $cart66Dir = glob(LECM_STORE_BASE_DIR . 'wp-content/plugins/cart66*', GLOB_ONLYDIR);
            if (is_array($cart66Dir) && count($cart66Dir) > 0) {
                $list_cart[] = 'cart66';
            }
            // Shopp
            $shopp = glob(LECM_STORE_BASE_DIR . 'wp-content/plugins/shopp*', GLOB_ONLYDIR);
            if (is_array($shopp) && count($shopp) > 0) {
                $list_cart[] = 'shopp';
            }
            // Marketpress
            $marketpress = glob(LECM_STORE_BASE_DIR . 'wp-content/plugins/wordpress-ecommerce*', GLOB_ONLYDIR);
            if (is_array($marketpress) && count($marketpress) > 0) {
                $list_cart[] = 'marketpress';
            }
            // WP eCommerce
            $list_cart[] = 'wpecommerce';
        }

        // XtCommerce v4
        if (file_exists(LECM_STORE_BASE_DIR . 'conf/config.php') || file_exists(LECM_STORE_BASE_DIR . 'core/config/configure.php')) {
            $list_cart[] = 'xtcommerce';
        }

        if (file_exists(LECM_STORE_BASE_DIR . 'config.php')) {

            // OpenCart
            if ((file_exists(LECM_STORE_BASE_DIR . 'system/startup.php') || (file_exists(LECM_STORE_BASE_DIR . 'common.php')) || (file_exists(LECM_STORE_BASE_DIR . 'library/locator.php')))
            ) {
                $list_cart[] = 'opencart';
            }

            //Cs-Cart
            if (file_exists(LECM_STORE_BASE_DIR . 'config.local.php') || file_exists(LECM_STORE_BASE_DIR . 'partner.php')
            ) {
                $list_cart[] = 'cscart';
            }

            // XCart
            $list_cart[] = 'xcart';
        }

        //Prestashop
        if (file_exists(LECM_STORE_BASE_DIR . 'config/settings.inc.php')) {
            $list_cart[] = 'prestashop';
        }

        // Loaded Commerce v7
        if (file_exists(LECM_STORE_BASE_DIR . 'includes/config.php')) {
            if (file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml')) {
                $list_cart[] = 'magento';
            }
            $list_cart[] = 'loaded';
        }

        // Cube Cart
        if (file_exists(LECM_STORE_BASE_DIR . 'includes/global.inc.php')) {
            $list_cart[] = 'cubecart';
        }

//        magento1
        if (file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml')) {
            $list_cart[] = 'magento';
        }

//        magento2
        if (file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'config.php')
            && file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'di.xml')
            && file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'env.php')) {
            $list_cart[] = 'magento';
        }

        if (file_exists(LECM_STORE_BASE_DIR . '/../app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'config.php')
            &&file_exists(LECM_STORE_BASE_DIR . '/../app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'di.xml')
            &&file_exists(LECM_STORE_BASE_DIR . '/../app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'env.php')) {
            $list_cart[] = 'magento';
        }

        // Interspire
        if (file_exists(LECM_STORE_BASE_DIR . 'config/config.php')) {
            $list_cart[] = 'interspire';
        }

        // XCart 5
        if (file_exists(LECM_STORE_BASE_DIR . 'etc/config.php')) {
            $list_cart[] = 'xcart';
        }

        //Oxid eShop
        if (file_exists(LECM_STORE_BASE_DIR . 'config.inc.php')) {
            $list_cart[] = 'oxideshop';
        }

        //Pinnacle
        if (@file_exists(LECM_STORE_BASE_DIR . 'content/engine/engine_config.php')) {
            $list_cart[] = 'pinnaclecart';
        }
        //
        if ((file_exists(LECM_STORE_BASE_DIR . 'configuration.php')) && (file_exists(LECM_STORE_BASE_DIR . '/components/com_hikashop/hikashop.php'))
        ) {
            $list_cart[] = 'hikashop';
        }
        if (file_exists(LECM_STORE_BASE_DIR . 'sites/default/settings.php') && is_dir(LECM_STORE_BASE_DIR . 'sites/all/modules/ubercart')) {
            $list_cart[] = 'ubercart';
        }
        if (file_exists(LECM_STORE_BASE_DIR . 'system/config.php')) {
            $list_cart[] = 'abantecart';
        }
        if (file_exists(LECM_STORE_BASE_DIR . 'conf/config.inc.php'))
        {
            $list_cart[] = 'randshop';
        }
        return array_unique($list_cart);
    }

    function setHostPort($source)
    {
        $this->host = $source;
    }

    function getHostPort(){
        return $this->host;
    }

    function getCartVersionFromDb($field, $tableName, $where)
    {
        $version = '';

        $sql = 'SELECT ' . $field . ' AS version FROM ' . $this->tablePrefix . $tableName . ' WHERE ' . $where;

        $dbConnect = LECM_Db::getInstance($this);
        if (!$dbConnect->getError()) {
            $result = $dbConnect->select($sql);
            if ($result && $result['data']) {
                $version = $result['data'][0]['version'];
            }
        }

        return $version;
    }

    function clearCache(){
        return array(
            'result' => 'success',
            'msg' => ''
        );
    }
}

class LECM_Connector_Adapter_Gambio extends LECM_Connector_Adapter
{
    public function __construct()
    {
        include(LECM_STORE_BASE_DIR . '/includes/configure.php');

        $this->setHostPort(DB_SERVER);
        $this->database   = DB_DATABASE;
        $this->username = DB_SERVER_USERNAME;
        $this->password = DB_SERVER_PASSWORD;

        if($this->check){
            $this->imageDir = DIR_WS_IMAGES;
            $this->imageDirCategory    = $this->imagesDir;
            $this->imageDirProduct      = DIR_WS_POPUP_IMAGES;
            $this->imageDirManufacturer = $this->imagesDir;
        }
    }
}

class LECM_Connector_Adapter_Shopware extends LECM_Connector_Adapter
{
    public function setEnv()
    {
        if (@file_exists(LECM_STORE_BASE_DIR .  '.env') || @file_exists(LECM_STORE_BASE_DIR .  '../.env')) {
            $base_dir = '';
            if (@file_exists(LECM_STORE_BASE_DIR .  '.env')) {
                $config = @file_get_contents(LECM_STORE_BASE_DIR . '.env');
                $base_dir = 'public';
            } else {
                $config = @file_get_contents(LECM_STORE_BASE_DIR . '../.env');
            }
            preg_match('/DATABASE_URL\=mysql\:\/\/.*?\n/', $config, $configMatch);
            $str_config = str_replace('DATABASE_URL=mysql://', '', trim($configMatch[0]));

            preg_match("/\/(.*)$/", $str_config, $match);
            $this->database = $match[1];
            preg_match("/^(.*?)\:/", $str_config, $match);
            $this->username = $match[1];
            preg_match("/\:(.*?)\@/", $str_config, $match);
            $this->password = urldecode((string)@$match[1]);
            preg_match("/\@(.*?)\:(.*?)\//", $str_config, $match);
            $host = $match[1];
            $this->setHostPort($host . ':' . @$match[2]);
            if($this->check){
                $this->imageDir = $base_dir . '/media/';
                $this->imageDirCategory = $this->imageDir;
                $this->imageDirProduct = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                $this->version = '6.0.0';
            }

        } elseif (@file_exists(LECM_STORE_BASE_DIR .  'config.php')) {
            $config = @require LECM_STORE_BASE_DIR . 'config.php';
            $this->database = @$config['db']['dbname'];
            $this->username = @$config['db']['username'];
            $this->password = @$config['db']['password'];
            $host = @$config['db']['host'];
            if(@$config['db']['port'] && @$config['db']['port'] != 3306){
                $host .= ':' . $config['db']['port'];
            }
            $this->setHostPort($host);
            if($this->check){

                if ($applicationphp = @file_get_contents(LECM_STORE_BASE_DIR . 'engine/Shopware/Application.php')) {
                    preg_match("/(const VERSION\s+= ')(.+)(';)/", $applicationphp, $match);
                    $version = $match[2];
                    if ($version != '') {
                        $this->version = $version;
                    }
                }
                $this->imageDir = 'media/image/';
                $this->imageDirCategory = $this->imagesDir;
                $this->imageDirProduct = $this->imagesDir;
                $this->imageDirManufacturer = $this->imagesDir;
                $this->tablePrefix = 's_';

            }

        }

    }
}

class LECM_Connector_Adapter_Randshop extends LECM_Connector_Adapter
{
    function setEnv()
    {
        @include_once LECM_STORE_BASE_DIR . 'conf/config.inc.php';
        $this->setHostPort($server);
        $this->username = $user;
        $this->password = $passwort;
        $this->database = $datenbankname;
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'conf/config.inc.php');
        preg_match('/tablePrefix\s*=\s*\'(.*)\'\s*;/', $config, $match);

        if ($this->check) {
            $this->tablePrefix = $match[1];

            $this->imageDir             = '';
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            $version = $this->getCartVersionFromDb('db_version', 'db_version', '1');
            if ($version != '') {
                $this->version = $version;
            }


        }
    }
}
class LECM_Connector_Adapter_Oscommerce extends LECM_Connector_Adapter
{
    function setEnv()
    {
        @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';
        $this->setHostPort(DB_SERVER);
        $this->username = DB_SERVER_USERNAME;
        $this->password = DB_SERVER_PASSWORD;
        $this->database = DB_DATABASE;
        if ($this->check) {
            $this->imageDir             = DIR_WS_IMAGES;
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            if (defined('DIR_WS_PRODUCT_IMAGES')) {
                $this->imageDirProduct = DIR_WS_PRODUCT_IMAGES;
            }
            if (defined('DIR_WS_ORIGINAL_IMAGES')) {
                $this->imageDirProduct = DIR_WS_ORIGINAL_IMAGES;
            }
        }
    }
}
class LECM_Connector_Adapter_Drupal extends LECM_Connector_Adapter
{

    function setEnv()
    {
        @include_once LECM_STORE_BASE_DIR . 'sites/default/settings.php';
        if (isset($databases)) {
            $_database = $databases['default']['default'];

            $this->setHostPort($_database['host']);

            $this->username = $_database['username'];

            $this->password = $_database['password'];

            $this->database = $_database['database'];

            $this->tablePrefix = $_database['prefix'];

        } else {
            $db_url = str_replace('mysql://', '', $db_url);
            $db_url = str_replace('mysqli://', '', $db_url);

            $info           = explode('/', $db_url);
            $this->database = $info[1];
            $info2          = explode('@', $info[0]);
            $this->setHostPort($info2[1]);
            $info3      = explode(':', $info2[0]);
            $this->user = $info3[0];

            if (isset($info3[1])) {
                $this->password = $info3[1];
            } else {
                $this->password = '';
            }

            $this->tablePrefix = $db_prefix;

        }

        $fileInfo = M1_STORE_BASE_DIR . '/sites/all/modules/commerce/commerce.info';
        if (@file_exists($fileInfo)) {
            $str = file_get_contents($fileInfo);
            if (preg_match('/version\s+=\s+".+-(.+)"/', $str, $match) != 0) {
                $this->version = $match[1];
                unset($match);
            }
        }

        $this->imageDir = '/sites/default/files/';

        $this->imageDirCategory     = $this->imageDir;
        $this->imageDirProduct      = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
    }
}
class LECM_Connector_Adapter_Custom extends LECM_Connector_Adapter
{
    function setEnv()
    {
        $db_custom = @require LECM_STORE_BASE_DIR . 'le_connector/db_custom.php';
        $this->setHostPort($db_custom['db_host']);
        $this->username = $db_custom['db_user'];
        $this->password = $db_custom['db_password'];
        $this->database = $db_custom['db_name'];
        if ($this->check) {
            $this->imageDir             = $db_custom['image_dir'];
            $this->imageDirCategory     = $db_custom['image_dir_category'];
            $this->imageDirProduct      = $db_custom['image_dir_product'];
            $this->imageDirManufacturer = $this->imageDir;
            $this->tablePrefix = $db_custom['db_prefix'];
            $this->version = $db_custom['version'];
        }
    }
}

class LECM_Connector_Adapter_Virtuemart extends LECM_Connector_Adapter
{
    function setEnv()
    {
        @require_once LECM_STORE_BASE_DIR . '/configuration.php';
        $config = new JConfig();
        $this->setHostPort($config->host);
        $this->username = $config->user;
        $this->password = $config->password;
        $this->database = $config->db;
        if ($this->check) {
            $this->tablePrefix = $config->dbprefix;

            $this->imageDir             = 'components/com_virtuemart/shop_image/';
            $this->imageDirCategory     = $this->imageDir . 'category/';
            $this->imageDirProduct      = $this->imageDir . 'product/';
            $this->imageDirManufacturer = $this->imageDir . 'manufacturer/';

            if (is_dir(LECM_STORE_BASE_DIR . 'images/stories/virtuemart/product')) {
                $this->imageDir             = 'images/stories/virtuemart/';
                $this->imageDirCategory     = $this->imageDir . 'category/';
                $this->imageDirProduct      = $this->imageDir . 'product/';
                $this->imageDirManufacturer = $this->imageDir . 'manufacturer/';
            }
            if (file_exists(LECM_STORE_BASE_DIR . '/administrator/components/com_virtuemart/version.php')) {
                $ver = file_get_contents(LECM_STORE_BASE_DIR . '/administrator/components/com_virtuemart/version.php');
                if (preg_match('/\$RELEASE.+\'(.+)\'/', $ver, $match) != 0) {
                    $this->version = (string)$match[1];
                }
            }
        }
    }
}

class LECM_Connector_Adapter_Zencart extends LECM_Connector_Adapter
{
    function setEnv()
    {
        @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';
        $this->username = DB_SERVER_USERNAME;
        $this->password = DB_SERVER_PASSWORD;
        $this->database = DB_DATABASE;
        $this->setHostPort(DB_SERVER);
        if ($this->check) {
            $this->tablePrefix          = DB_PREFIX;
            $this->imageDir             = defined('DIR_WS_IMAGES') ? DIR_WS_IMAGES : '/images';
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            if (defined('DIR_WS_PRODUCT_IMAGES')) {
                $this->imageDirProduct = DIR_WS_PRODUCT_IMAGES;
            }
            if (defined('DIR_WS_ORIGINAL_IMAGES')) {
                $this->imageDirProduct = DIR_WS_ORIGINAL_IMAGES;
            }
            if (file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php')) {
                @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'version.php';
                $major = PROJECT_VERSION_MAJOR;
                $minor = PROJECT_VERSION_MINOR;
                if (defined('EXPECTED_DATABASE_VERSION_MAJOR') && EXPECTED_DATABASE_VERSION_MAJOR != '') {
                    $major = EXPECTED_DATABASE_VERSION_MAJOR;
                }
                if (defined('EXPECTED_DATABASE_VERSION_MINOR') && EXPECTED_DATABASE_VERSION_MINOR != '') {
                    $minor = EXPECTED_DATABASE_VERSION_MINOR;
                }
                if ($major != '' && $minor != '') {
                    $this->version = $major . '.' . $minor;
                }
            }
            $this->charset = (defined('DB_CHARSET')) ? DB_CHARSET : "";
        }
    }
}
class LECM_Connector_Adapter_Squirrelcart extends LECM_Connector_Adapter
{
    function setEnv()
    {
        include_once(LECM_STORE_BASE_DIR . 'squirrelcart/config.php');

        $this->setHostPort($sql_host);
        $this->database = $db;
        $this->username = $sql_username;
        $this->password = $sql_password;
        if($this->check){
            $this->imageDir = $img_path;
            $this->imageDirCategory = $img_path . '/categories';
            $this->imageDirProduct = $img_path . '/products';
            $this->imageDirManufacturer = $img_path;

            $version = $this->getCartVersionFromDb('DB_Version', 'Store_Information', 'record_number = 1');
            if ($version != '') {
                $this->version = $version;
            }
        }

    }
}

class LECM_Connector_Adapter_Woocommerce extends LECM_Connector_Adapter
{
    var $site_id = null;
    function setEnv()
    {
        if(file_exists(LECM_STORE_BASE_DIR . 'wp-config.php')){
            $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');
        }else{
            $config = file_get_contents(LECM_STORE_BASE_DIR . '../wp-config.php');

        }

        preg_match('/^\s*define\s*\(\s*\'DB_NAME\',\s*(\'|\")(.+)(\'|\")\s*\)\s*;/m', $config, $match);
        $this->database = $match[2];
        preg_match('/^\s*define\s*\(\s*\'DB_USER\',\s*(\'|\")(.+)(\'|\")\s*\)\s*;/m', $config, $match);
        $this->username = $match[2];
        preg_match('/^\s*define\s*\(\s*\'DB_PASSWORD\',\s*(\'|\")(.*)(\'|\")\s*\)\s*;/m', $config, $match);
        $this->password = $match[2];
        preg_match('/^\s*define\s*\(\s*\'DB_HOST\',\s*(\'|\")(.+)(\'|\")\s*\)\s*;/m', $config, $match);
        $this->setHostPort($match[2]);
        preg_match('/^\s*define\s*\(\s*\'DB_CHARSET\',\s*(\'|\")(.+)(\'|\")\s*\)\s*;/m', $config, $match);
        if(@$match[2]){
            $this->charset = $match[2];
        }
        if ($this->check) {
            preg_match('/^\s*\$table_prefix\s*=\s*(\'|\")(.*)(\'|\")\s*;/m', $config, $match);
            $this->tablePrefix          = $match[2];
            preg_match('/^\s*define\s*\(\s*\'MULTISITE\',\s*(\'|\"|)(.+)(\'|\"|)\s*\)\s*;/m', $config, $match);
            if($match && (trim($match[2], ' ') == 'true' || $match[2] === true)){
                if($site_id = $this->getSiteId()){
                    $this->tablePrefix.= $site_id.'_';
                    $this->site_id = $site_id;
                }
            }
//            $upload_path = $this->getUploadPathFromDb();
//            if($upload_path){
//                $cwd = str_replace('/le_connector', '',getcwd());
//                $this->imageDir             = str_replace($cwd, '',$upload_path);
//            }else{
            $this->imageDir             = 'wp-content/uploads/';
            if(!is_dir(LECM_STORE_BASE_DIR . 'wp-content') && is_dir(LECM_STORE_BASE_DIR . 'content')){
                $this->imageDir             = 'content/uploads/';

            }
//            }

            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            $this->version              = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'woocommerce_db_version'");
        }
    }

    function getSiteId($port_domain = false){
        $domain = $_SERVER["SERVER_NAME"];
        if($port_domain && $_SERVER["SERVER_PORT"] != "80"){
            $domain = $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
        }
        $sql = 'SELECT * FROM ' . $this->tablePrefix . 'blogs' . ' WHERE `domain` = "'.$domain.'"';

        $dbConnect = LECM_Db::getInstance($this);
        if (!$dbConnect->getError()) {
            $result = $dbConnect->select($sql);
            if ($result && $result['data']) {
                $folder = 'le_connector';
                if(MODULE_CONNECTOR){
                    $folder = 'wp-content';
                }
                $find = explode($folder, dirname($_SERVER["REQUEST_URI"]));
                $site_path = trim($find[0], '/');
                foreach ($result['data'] as $row){
                    $path = trim($row['path'], '/ ');
                    if($site_path == $path){
                        $blog_id = $row['blog_id'];
                        $sql_post = 'SELECT * FROM ' . $this->tablePrefix.$blog_id . '_posts LIMIT 1';
                        $post = $dbConnect->select($sql_post);
                        if($post && $post['data']){
                            return $blog_id;
                        }
                        $dbConnect->error = null;
                        return false;
                    }
                }
            }elseif (!$port_domain){
                return $this->getSiteId(true);
            }
        }else{
            $dbConnect->error = null;
        }
        return false;
    }

    function getUploadPathFromDb()
    {
        $path = '';

        $sql = 'SELECT option_value FROM ' . $this->tablePrefix . 'options' . ' WHERE option_name = "upload_path"';

        $dbConnect = LECM_Db::getInstance($this);
        if (!$dbConnect->getError()) {
            $result = $dbConnect->select($sql);
            if ($result && $result['data']) {
                $path = $result['data'][0]['option_value'];
            }
        }

        return $path;
    }

    function clearCache()
    {
        $cleartransite_query = 'DELETE FROM ' . $this->tablePrefix . 'options WHERE option_name = "_transient_wc_attribute_taxonomies"';

        $dbConnect = LECM_Db::getInstance($this);
        if (!$dbConnect->getError()) {
            $dbConnect->processQuery('query',$cleartransite_query);
        }
        return array(
            'result' => 'success',
        );
    }
}
class LECM_Connector_Adapter_JoomShopping extends LECM_Connector_Adapter
{
    function setEnv()
    {

        @require_once LECM_STORE_BASE_DIR . '/configuration.php';
        $config = null;
        if (class_exists('JConfig')) {

            $config = new JConfig();

            $this->setHostPort($config->host);
            $this->database   = $config->db;
            $this->username = $config->user;
            $this->password = $config->password;

        } else {

            $this->setHostPort($mosConfig_host);
            $this->database   = $mosConfig_db;
            $this->username = $mosConfig_user;
            $this->password = $mosConfig_password;
        }
        if ($this->check) {
            if (@file_exists(LECM_STORE_BASE_DIR . 'components/com_jshopping/lib/functions.php')) {
                $data = file_get_contents(LECM_STORE_BASE_DIR . 'components/com_jshopping/lib/functions.php');
                if (preg_match('/\@version\s+(.+)\s+.+/', $data, $match) != 0) {
                    $version = explode(' ',$match[1]);
                    $this->version = $version[0];
                    unset($match);
                }
            }

            $this->imageDir = 'components/com_jshopping/files';
            $this->imageDirCategory    = $this->imageDir . '/img_categories/';
            $this->imageDirProduct      = $this->imageDir . '/img_products/';
            $this->imageDirManufacturer = $this->imageDir . '/img_manufs/';
            if($config){
                $this->tablePrefix = $config->dbprefix;
            }

        }

    }
}
class LECM_Connector_Adapter_Jigoshop extends LECM_Connector_Adapter
{

    function setEnv()
    {
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');

        preg_match('/define\s*\(\s*\'DB_NAME\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->database = $match[1];
        preg_match('/define\s*\(\s*\'DB_USER\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->username = $match[1];
        preg_match('/define\s*\(\s*\'DB_PASSWORD\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->password = $match[1];
        preg_match('/define\s*\(\s*\'DB_HOST\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->setHostPort($match[1]);
        if ($this->check) {
            preg_match('/define\s*\(\s*\'DB_CHARSET\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
            $this->charset = $match[1];
            preg_match('/\$table_prefix\s*=\s*\'(.*)\'\s*;/', $config, $match);
            $this->tablePrefix          = $match[1];
            $this->imageDir             = 'wp-content/uploads/';
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            $jigoshop_cart_id           = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'jigoshop_cart_id'");
            $this->version              = $jigoshop_cart_id ? '2.0' : '1.0';
        }
    }

}

class LECM_Connector_Adapter_Prestashop extends LECM_Connector_Adapter
{
    function setEnv()
    {
        if (file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'parameters.php') && file_exists(LECM_STORE_BASE_DIR . 'config' . DIRECTORY_SEPARATOR . 'autoload.php')) {

            $config = @require_once (LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'parameters.php');
//            preg_match("/'database_host' => '(.+)',/", $config, $match);
            $host = $config['parameters']['database_host'];
            if($config['parameters']['database_port']){
                $host .=':'.$config['parameters']['database_port'];
            }
            $this->setHostPort($host);
//            preg_match("/'database_user' => '(.+)',/", $config, $match);
            $this->username = $config['parameters']['database_user'];
//            preg_match("/'database_password' => '(.+)',/", $config, $match);
            $this->password = $config['parameters']['database_password'];
//            preg_match("/'database_name' => '(.+)',/", $config, $match);
            $this->database = $config['parameters']['database_name'];
//            preg_match("/'database_prefix' => '(.+)',/", $config, $match);
//            preg_match("/'cookie_key' => '(.+)',/", $config, $cookie);
            if ($this->check) {
                $this->tablePrefix          = $config['parameters']['database_prefix'];
                $this->imageDir             = '/img/';
                $this->imageDirCategory     = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                @require_once LECM_STORE_BASE_DIR.'vendor/autoload.php';

                if (class_exists('AppKernel') && AppKernel::VERSION) {
                    define('_PS_VERSION_', AppKernel::VERSION);
                }else{
                    define('_PS_VERSION_', $this->getCartVersionFromDb('value', 'configuration', 'name="PS_VERSION_DB"'));
                }
                $this->version              = _PS_VERSION_;
                $this->cookie_key           = $config['parameters']['cookie_key'];
            }
        } else {
            @require_once LECM_STORE_BASE_DIR . '/config/settings.inc.php';

            if (defined('_DB_SERVER_')) {
                $this->setHostPort(_DB_SERVER_);
            } else {
                $this->setHostPort(DB_HOSTNAME);
            }

            if (defined('_DB_USER_')) {
                $this->username = _DB_USER_;
            } else {
                $this->username = DB_USERNAME;
            }

            $this->password = _DB_PASSWD_;

            if (defined('_DB_NAME_')) {
                $this->database = _DB_NAME_;
            } else {
                $this->database = DB_DATABASE;
            }
            if ($this->check) {
                $this->tablePrefix          = _DB_PREFIX_;
                $this->imageDir             = '/img/';
                $this->imageDirCategory     = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                $this->version              = _PS_VERSION_;
                $this->cookie_key           = _COOKIE_KEY_;
            }
        }
    }
    function clearCache(){
        if (file_exists(LECM_STORE_BASE_DIR   . 'config' . DIRECTORY_SEPARATOR . 'config.inc.php') && file_exists(LECM_STORE_BASE_DIR . 'classes' . DIRECTORY_SEPARATOR . 'Category.php')) {
            require(LECM_STORE_BASE_DIR.'config/config.inc.php');
            require_once LECM_STORE_BASE_DIR.'classes/Category.php';
            if(class_exists('CategoryCore') && method_exists('CategoryCore', 'regenerateEntireNtree')){
                CategoryCore::regenerateEntireNtree();
            }

        }
        return array(
            'result' => 'success',
        );
    }
}

class LECM_Connector_Adapter_Magento extends LECM_Connector_Adapter
{
    function setEnv()
    {

        if (file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml')) {
            $config = simplexml_load_file(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml');
            $this->setHostPort((string)$config->global->resources->default_setup->connection->host);
            $this->username = (string)$config->global->resources->default_setup->connection->username;
            $this->database   = (string)$config->global->resources->default_setup->connection->dbname;
            $this->password = (string)$config->global->resources->default_setup->connection->password;
            $charSet = (string)$config->global->resources->default_setup->connection->initStatements;
            if($charSet != ''){
                $this->charset = str_replace('SET NAMES ', '', $charSet);
            }
            if ($this->check) {
                $this->tablePrefix = (string)$config->global->resources->db->table_prefix;
                $this->imageDir             = '/media/catalog/';
                $this->imageDirCategory     = $this->imageDir . 'category/';
                $this->imageDirProduct      = $this->imageDir . 'product/';
                $this->imageDirManufacturer = $this->imageDir;
                if (file_exists(LECM_STORE_BASE_DIR . 'app/Mage.php')) {
                    $ver = file_get_contents(LECM_STORE_BASE_DIR . 'app/Mage.php');
                    if (preg_match("/getVersionInfo[^}]+\'major\' *=> *\'(\d+)\'[^}]+\'minor\' *=> *\'(\d+)\'[^}]+\'revision\' *=> *\'(\d+)\'[^}]+\'patch\' *=> *\'(\d+)\'[^}]+}/s", $ver, $match) == 1) {
                        $mageVersion   = $match[1] . '.' . $match[2] . '.' . $match[3] . '.' . $match[4];
                        $this->version = $mageVersion;
                        unset($match);
                    }
                }
            }
        } else {
            $baseDir = LECM_STORE_BASE_DIR;
            $imagesDir = '/pub/media/';
            if (!file_exists($baseDir . 'app/etc/env.php') && file_exists($baseDir . '/../app/etc/env.php')) {
                $baseDir = LECM_STORE_BASE_DIR . '/../';
                $check = false;
                $imagesDir = 'media/';
            }
            $config = @include($baseDir . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'env.php');
            $db_config = array();
            foreach ($config['db']['connection'] as $connection) {
                if (@$connection['active'] == 1) {
                    $db_config = $connection;
                    break;
                }
            }
            if(!$db_config){
                foreach ($config['db']['connection'] as $key => $connection) {
                    if ($key == 'default') {
                        $db_config = $connection;
                        break;
                    }
                }
            }
            $this->setHostPort(isset($db_config['host'])?$db_config['host']:'');
            $this->username = isset($db_config['username'])?$db_config['username']:'';
            $this->password = isset($db_config['password'])?$db_config['password']:'';
            $this->database = isset($db_config['dbname'])?$db_config['dbname']:'';
            if (isset($db_config['initStatements']) && $db_config['initStatements'] != '') {
                $this->charset = str_replace('SET NAMES ', '', $db_config['initStatements']);
                $this->charset = str_replace(';', '', $this->charset);
            }
            if ($this->check) {
                $this->version = '2.2.0'; //default
                $this->view=$this->checkViewExist();
                $this->tablePrefix          = isset($config['db']['table_prefix'])?$config['db']['table_prefix']:'';
                $this->imageDir             = $imagesDir;
                $this->imageDirCategory     = $this->imageDir . 'catalog/category/';
                $this->imageDirProduct      = $this->imageDir . 'catalog/product/';
                $this->imageDirManufacturer = $this->imageDir;
                if (file_exists($baseDir . 'composer.json')) {
                    $ver = file_get_contents($baseDir . 'composer.json');
                    if (preg_match("/\"version\"\:[ ]*?\"([0-9\.]*)\"\,/", $ver, $match) == 1) {
                        $mageVersion = $match[1];
                        $this->version = $mageVersion;
                    }
                }
            }
        }
    }

    function checkViewExist(){
        $sql = "SHOW FULL TABLES IN `".$this->database."` WHERE TABLE_TYPE LIKE '%view%'";

        $dbConnect = LECM_Db::getInstance($this);
        if (!$dbConnect->getError()) {
            $result = $dbConnect->select($sql);
            if($result['result'] == 'success'){
                foreach ($result['data'] as $row){
                    if(strpos($row['Tables_in_'.$this->database], 'inventory_stock_1') !== false){
                        return true;
                    }
                }
            }

        }
        return false;

    }

    function clearCache(){
        chdir('../');
        $phpExecutable   = getPhpPath();
        if ($phpExecutable) {
            $memoryLimit = '-d memory_limit=1024M';
            if (file_exists(LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'env.php')) {
                $indexer      = "nohup $phpExecutable $memoryLimit bin/magento indexer:reindex;";
                $imagesResize = "nohup $phpExecutable $memoryLimit bin/magento catalog:images:resize;";
                $flushCache   = "nohup $phpExecutable $memoryLimit bin/magento cache:flush;";
                $cleanCache   = "nohup $phpExecutable $memoryLimit bin/magento cache:clean;";
                $rmCache      = "nohup rm -rf var/cache;";
                @exec("($indexer $imagesResize $flushCache $cleanCache $rmCache) &>/dev/null &");
            } else {
                @exec("nohup $phpExecutable shell/indexer.php --reindexall > /dev/null 2>/dev/null & echo $!");
            }
        } else {
            return array(
                'result' => 'error',
                'msg' => "Error: can not find PHP executable file."
            );
        }

        return array(
            'result' => 'success',
//            'msg' => "Error: can not find PHP executable file."
        );
    }
}
class LECM_Connector_Adapter_Mijoshop extends LECM_Connector_Adapter
{

    function setEnv()
    {

        @require_once LECM_STORE_BASE_DIR . 'configuration.php';
        $config = new JConfig();
        $this->setHostPort($config->host);
        $this->username = $config->user;
        $this->password = $config->password;
        $this->database = $config->db;
        if ($this->check) {
            //$this->tablePrefix = $config->dbprefix;
            $first_prefix = $config->dbprefix;

            $configFileContent = $baseFileContent = '';
            if (file_exists(LECM_STORE_BASE_DIR . '/components/com_mijoshop/opencart/config.php')) {
                $configFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/components/com_mijoshop/opencart/config.php');
            }
            if (file_exists(LECM_STORE_BASE_DIR . '/components/com_mijoshop/mijoshop/base.php')) {
                $baseFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/components/com_mijoshop/mijoshop/base.php');
            }

            preg_match("/define\(\"\DB_PREFIX\"\, \'(.+)\'\)/", $configFileContent, $match);
            $second_prefix = str_replace("#__", "", $match[1]);
            $this->tablePrefix = $first_prefix . $second_prefix;
            $this->imageDir = 'components/com_mijoshop/opencart/image/';
            $this->imageDirCategory = $this->imageDir;
            $this->imageDirProduct = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            preg_match('/\$version.+\'(.+)\';/', $baseFileContent, $match);
            $this->version = $match[1];
        }
    }
}
class LECM_Connector_Adapter_Opencart extends LECM_Connector_Adapter
{

    function setEnv()
    {

        if ((file_exists(LECM_STORE_BASE_DIR . 'configuration.php')) && (file_exists(LECM_STORE_BASE_DIR . '/components/com_mijoshop/opencart/config.php'))) {
            @require_once LECM_STORE_BASE_DIR . 'configuration.php';
            $config = new JConfig();
            $this->setHostPort($config->host);
            $this->username = $config->user;
            $this->password = $config->password;
            $this->database = $config->db;
            if ($this->check) {
                //$this->tablePrefix = $config->dbprefix;
                $first_prefix = $config->dbprefix;

                $configFileContent = $baseFileContent = '';
                if (file_exists(LECM_STORE_BASE_DIR . '/components/com_mijoshop/opencart/config.php')) {
                    $configFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/components/com_mijoshop/opencart/config.php');
                }
                if (file_exists(LECM_STORE_BASE_DIR . '/components/com_mijoshop/mijoshop/base.php')) {
                    $baseFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/components/com_mijoshop/mijoshop/base.php');
                }

                preg_match("/define\(\"\DB_PREFIX\"\, \'(.+)\'\)/", $configFileContent, $match);
                $second_prefix              = str_replace("#__", "", $match[1]);
                $this->tablePrefix          = $first_prefix . $second_prefix;
                $this->imageDir             = 'components/com_mijoshop/opencart/image/';
                $this->imageDirCategory     = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                preg_match('/\$version.+\'(.+)\';/', $baseFileContent, $match);
                $this->version = $match[1];
            }
        } else {
            @require_once LECM_STORE_BASE_DIR . 'config.php';

            if (defined('DB_HOST')) {
                $this->setHostPort(DB_HOST);
            } else {
                $this->setHostPort(DB_HOSTNAME);
            }

            if (defined('DB_USER')) {
                $this->username = DB_USER;
            } else {
                $this->username = DB_USERNAME;
            }

            $this->password = DB_PASSWORD;

            if (defined('DB_NAME')) {
                $this->database = DB_NAME;
            } else {
                $this->database = DB_DATABASE;
            }
            if ($this->check) {
                $this->tablePrefix          = DB_PREFIX;
                $this->imageDir             = 'image/';
                $this->imageDirCategory     = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;

                $indexFileContent   = '';
                $startupFileContent = '';

                if (file_exists(LECM_STORE_BASE_DIR . '/index.php')) {
                    $indexFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/index.php');
                }

                if (file_exists(LECM_STORE_BASE_DIR . '/system/startup.php')) {
                    $startupFileContent = file_get_contents(LECM_STORE_BASE_DIR . '/system/startup.php');
                }

                if (preg_match("/define\('\VERSION\'\, \'(.+)\'\)/", $indexFileContent, $match) == 0) {
                    preg_match("/define\('\VERSION\'\, \'(.+)\'\)/", $startupFileContent, $match);
                }

                if (count($match) > 0) {
                    $this->version = $match[1];
                }
            }
        }
    }
}

class LECM_Connector_Adapter_Interspire extends LECM_Connector_Adapter
{
    function setEnv()
    {
        @require_once LECM_STORE_BASE_DIR . 'config/config.php';
        $this->setHostPort($GLOBALS['ISC_CFG']['dbServer']);
        $this->username = $GLOBALS['ISC_CFG']['dbUser'];
        $this->password = $GLOBALS['ISC_CFG']['dbPass'];
        $this->database = $GLOBALS['ISC_CFG']['dbDatabase'];
        if ($this->check) {
            $this->tablePrefix          = $GLOBALS['ISC_CFG']['tablePrefix'];
            $this->imageDir             = $GLOBALS['ISC_CFG']['ImageDirectory'];
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;

            define('DEFAULT_LANGUAGE_ISO2', $GLOBALS['ISC_CFG']['Language']);

            $version = $this->getCartVersionFromDb('database_version', $GLOBALS['ISC_CFG']['tablePrefix'] . 'config', '1');
            if ($version != '') {
                $this->version = $version;
            }
        }
    }
}

class LECM_Connector_Adapter_Pinnaclecart extends LECM_Connector_Adapter
{

    function setEnv()
    {

        @require_once LECM_STORE_BASE_DIR . 'content/engine/engine_config.php';

        //$this->Host = DB_HOST;
        $this->setHostPort(DB_HOST);
        $this->database = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASSWORD;

        if ($this->check) {
            $this->imagesDir            = 'images/';
            $this->imageDirCategory     = $this->imagesDir;
            $this->imageDirProduct      = $this->imagesDir;
            $this->imageDirManufacturer = $this->imagesDir;
            $version                    = $this->getCartVersionFromDb('value', (defined('DB_PREFIX') ? DB_PREFIX : '') . 'settings', "name = 'AppVer'");
            if ($version != '') {
                $this->version = $version;
            }
        }
    }
}

class LECM_Connector_Adapter_Wpecommerce extends LECM_Connector_Adapter
{
    function setEnv()
    {
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');
        preg_match('/^\s*define\s*\(\s*\'DB_NAME\',\s*(\'|\")(.+)(\'|\")\s*\)\s*;/m', $config, $match);
        $this->database = $match[2];
        preg_match('/^\s*define\s*\(\s*\'DB_USER\',\s*(\'|\")(.+)(\'|\")\s*\)\s*;/m', $config, $match);
        $this->username = $match[2];
        preg_match('/^\s*define\s*\(\s*\'DB_PASSWORD\',\s*(\'|\")(.*)(\'|\")\s*\)\s*;/m', $config, $match);
        $this->password = $match[2];
        preg_match('/^\s*define\s*\(\s*\'DB_HOST\',\s*(\'|\")(.+)(\'|\")\s*\)\s*;/m', $config, $match);
        $this->setHostPort($match[2]);
        preg_match('/^\s*define\s*\(\s*\'DB_CHARSET\',\s*(\'|\")(.+)(\'|\")\s*\)\s*;/m', $config, $match);
        if(@$match[2]){
            $this->charset = $match[2];
        }
        if ($this->check) {
            preg_match("/(table_prefix)(.*)(')(.*)(')(.*)/", $config, $match);
            $this->tablePrefix = $match[4];

            $version = $this->getCartVersionFromDb('option_value', 'options', "option_name = 'wpsc_version'");
            if ($version != '') {
                $this->version = $version;
            } else {
                if (file_exists(LECM_STORE_BASE_DIR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-shopping-cart' . DIRECTORY_SEPARATOR . 'wp-shopping-cart.php')) {
                    $conf = file_get_contents(LECM_STORE_BASE_DIR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-shopping-cart' . DIRECTORY_SEPARATOR . 'wp-shopping-cart.php');
                    preg_match("/define\('WPSC_VERSION.*/", $conf, $match);
                    if (isset($match[0]) && !empty($match[0])) {
                        preg_match("/\d.*/", $match[0], $project);
                        if (isset($project[0]) && !empty($project[0])) {
                            $version = $project[0];
                            $version = str_replace(array(' ', '-', '_', "'", ');', ')', ';'), '', $version);
                            if ($version != '') {
                                $this->version = strtolower($version);
                            }
                        }
                    }
                }
            }

            if (file_exists(LECM_STORE_BASE_DIR . 'wp-content/plugins/shopp/Shopp.php') || file_exists(LECM_STORE_BASE_DIR . 'wp-content/plugins/wp-e-commerce/editor.php')
            ) {
                $this->imageDir             = 'wp-content/uploads/wpsc/';
                $this->imageDirCategory     = $this->imageDir . 'category_images/';
                $this->imageDirProduct      = $this->imageDir . 'product_images/';
                $this->imageDirManufacturer = $this->imageDir;
            } elseif (file_exists(LECM_STORE_BASE_DIR . 'wp-content/plugins/wp-e-commerce/wp-shopping-cart.php')) {
                $this->imageDir             = 'wp-content/uploads/';
                $this->imageDirCategory     = $this->imageDir . 'wpsc/category_images/';
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
            } else {
                $this->imageDir             = 'images/';
                $this->imageDirCategory     = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
            }
        }
    }
}

class LECM_Connector_Adapter_Shopp extends LECM_Connector_Adapter
{
    function setEnv()
    {
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');

        preg_match('/define\s*\(\s*\'DB_NAME\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->database = $match[1];
        preg_match('/define\s*\(\s*\'DB_USER\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->username = $match[1];
        preg_match('/define\s*\(\s*\'DB_PASSWORD\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->password = $match[1];
        preg_match('/define\s*\(\s*\'DB_HOST\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->setHostPort($match[1]);
        if ($this->check) {
            preg_match('/define\s*\(\s*\'DB_CHARSET\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
            $this->charset = $match[1];
            preg_match('/\$table_prefix\s*=\s*\'(.*)\'\s*;/', $config, $match);
            $this->tablePrefix          = $match[1];
            $this->imageDir             = 'wp-content/uploads/';
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            $this->version              = $this->getCartVersionFromDb('value', 'shopp_meta', "type = 'setting' AND name = 'version'");
        }
    }

    function clearCache()
    {
        $cleartransite_query = 'DELETE FROM ' . $this->tablePrefix . 'options WHERE option_name = "_transient_wc_attribute_taxonomies"';
        $dbConnect = LECM_Db::getInstance($this);
        if (!$dbConnect->getError()) {
            $dbConnect->processQuery($cleartransite_query);
        }
        return array(
            'result' => 'success',
        );
    }
}

class LECM_Connector_Adapter_Marketpress extends LECM_Connector_Adapter
{
    function setEnv()
    {
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');

        preg_match('/define\s*\(\s*\'DB_NAME\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->database = $match[1];
        preg_match('/define\s*\(\s*\'DB_USER\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->username = $match[1];
        preg_match('/define\s*\(\s*\'DB_PASSWORD\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->password = $match[1];
        preg_match('/define\s*\(\s*\'DB_HOST\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->setHostPort($match[1]);
        if ($this->check) {
            preg_match('/define\s*\(\s*\'DB_CHARSET\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
            $this->charset = $match[1];
            preg_match('/\$table_prefix\s*=\s*\'(.*)\'\s*;/', $config, $match);
            $this->tablePrefix          = $match[1];
            $this->imageDir             = 'wp-content/uploads/';
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            $this->version              = $this->getCartVersionFromDb('option_value', 'options', "option_name LIKE 'mp_version'");
        }
    }

    function clearCache()
    {
        $cleartransite_query = 'DELETE FROM ' . $this->tablePrefix . 'options WHERE option_name = "_transient_wc_attribute_taxonomies"';
        $dbConnect = LECM_Db::getInstance($this);
        if (!$dbConnect->getError()) {
            $dbConnect->processQuery($cleartransite_query);
        }
        return array(
            'result' => 'success',
        );
    }
}

class LECM_Connector_Adapter_Cubecart extends LECM_Connector_Adapter
{
    function setEnv()
    {
        $config = file_get_contents(LECM_STORE_BASE_DIR . '/includes/global.inc.php');
        preg_match("/glob\[\'dbhost\'\].+\'(.+)\';/", $config, $match);
        $this->setHostPort($match[1]);
        preg_match("/glob\[\'dbusername\'\].+\'(.+)\';/", $config, $match);
        $this->username = $match[1];
        preg_match("/glob\[\'dbpassword\'\].+\'(.*)\';/", $config, $match);
        $this->password = $match[1];
        preg_match("/glob\[\'dbdatabase\'\].+\'(.+)\';/", $config, $match);
        $this->database = $match[1];
        if ($this->check) {
            preg_match("/glob\[\'dbprefix\'\].+\'(.+)\';/", $config, $match);
            if ($match && $match[1]) {
                $this->tablePrefix = $match[1] . 'CubeCart_';
            } else {
                $this->tablePrefix = 'CubeCart_';
            }
            $this->imageDir = '/images/source/';
            if (file_exists(LECM_STORE_BASE_DIR . '/ini.inc.php')) {
                $config_local = file_get_contents(LECM_STORE_BASE_DIR . '/ini.inc.php');
            } else {
                $config_local = file_get_contents(LECM_STORE_BASE_DIR . '/includes/ini.inc.php');
            }
            preg_match("/define\(\'CC_VERSION\', \'(.+)\'\);/", $config_local, $match);
            if ($match) {
                $this->version = $match[1];
            } else {
                preg_match("/ini\[\'ver\'\].+\'(.*)\';/", $config_local, $match);
                $this->version  = $match[1];
                $this->imageDir = '/images/uploads/';
            }
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
        }
    }
}

class LECM_Connector_Adapter_Oxideshop extends LECM_Connector_Adapter
{

    function setEnv()
    {
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'config.inc.php');
        preg_match("/dbHost(.+)?=(.+)?\'(.*)\';/", $config, $match);
        $this->setHostPort($match[3]);
        preg_match("/dbUser(.+)?=(.+)?\'(.+)\';/", $config, $match);
        $this->username = $match[3];
        preg_match("/dbPwd(.+)?=(.+)?\'(.+)\';/", $config, $match);
        $this->password = isset($match[3]) ? $match[3] : '';
        preg_match("/dbName(.+)?=(.+)?\'(.+)\';/", $config, $match);
        $this->database = $match[3];
        if ($this->check) {
            $this->version = $this->getCartVersionFromDb('OXVERSION', 'oxshops', "OXACTIVE = 1");
            if (file_exists(LECM_STORE_BASE_DIR . 'bootstrap.php')) {
                @require_once LECM_STORE_BASE_DIR . 'bootstrap.php';
                $ox_lang      = new oxLang();
                $languages    = $ox_lang->getLanguageArray();
                $this->extend = $languages;

                $this->imageDir             = 'out/pictures/master/';
                $this->imageDirCategory     = $this->imageDir . 'category/thumb';
                $this->imageDirProduct      = $this->imageDir . 'product';
                $this->imageDirManufacturer = $this->imageDir . 'manufacturer';
            } else {
                $this->imageDir             = 'out/pictures';
                $this->imageDirCategory     = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
            }
        }
    }
}

class LECM_Connector_Adapter_Cscart extends LECM_Connector_Adapter
{
    function setEnv()
    {
        defined('IN_CSCART') || define('IN_CSCART', 1);
        defined('CSCART_DIR') || define('CSCART_DIR', LECM_STORE_BASE_DIR);
        defined('AREA') || define('AREA', 1);
        defined('BOOTSTRAP') || define('BOOTSTRAP', 1);
        defined('DIR_ROOT') || define('DIR_ROOT', LECM_STORE_BASE_DIR);
        defined('DIR_CSCART') || define('DIR_CSCART', LECM_STORE_BASE_DIR);
        defined('DS') || define('DS', DIRECTORY_SEPARATOR);
        if (@file_exists(LECM_STORE_BASE_DIR . 'config.local')) {
            require LECM_STORE_BASE_DIR . 'config.local';
        }
        if (@file_exists(LECM_STORE_BASE_DIR . 'config.local.php')) {
            require_once LECM_STORE_BASE_DIR . 'config.local.php';
        }

        //For CS CART 1.3.x
        if (isset($db_host, $db_name, $db_user, $db_password)) {
            $this->setHostPort($db_host);
            $this->database = $db_name;
            $this->username = $db_user;
            $this->password = $db_password;
        } else {
            $this->setHostPort($config['db_host']);
            $this->database = $config['db_name'];
            $this->username = $config['db_user'];
            $this->password = $config['db_password'];
        }


        if ($this->check) {

            if (isset($images_storage_dir)) {
                $imagesDir = $images_storage_dir;
            } elseif (defined('DIR_IMAGES')) {
                $imagesDir = DIR_IMAGES;
            } else {
                $imagesDir = $config['storage']['images']['dir'] . '/' . $config['storage']['images']['prefix'];
            }

            $this->imageDir = str_replace(LECM_STORE_BASE_DIR, '', $imagesDir);

            $this->imageDirCategory    = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;

            $this->tablePrefix = @$config['table_prefix']?$config['table_prefix']:"";

            if (defined('PRODUCT_VERSION')) {
                $this->version = PRODUCT_VERSION;
            }

        }
    }
}

class LECM_Connector_Adapter_Hikashop extends LECM_Connector_Adapter
{
    function setEnv()
    {

        @require_once LECM_STORE_BASE_DIR . '/configuration.php';
        $config = new JConfig();
        $this->setHostPort($config->host);
        $this->username = $config->user;
        $this->password = $config->password;
        $this->database = $config->db;
        if ($this->check) {
            $this->tablePrefix = $config->dbprefix;

            $this->imageDir             = 'images/com_hikashop/upload/';
            $this->imageDirCategory     = $this->imageDir;
            $this->imageDirProduct      = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;

            $this->version = $this->getCartVersionFromDb('config_value', 'hikashop_config', "config_namekey = 'version'");
        }
    }
}

class LECM_Connector_Adapter_Xcart extends LECM_Connector_Adapter
{
    function setEnv()
    {

        if (file_exists(LECM_STORE_BASE_DIR . 'config.php')) {
            $config = file_get_contents(LECM_STORE_BASE_DIR . 'config.php');

            preg_match('/\$sql_host.+\'(.+)\';/', $config, $match);
            $this->setHostPort($match[1]);
            preg_match('/\$sql_user.+\'(.+)\';/', $config, $match);
            $this->username = $match[1];
            preg_match('/\$sql_db.+\'(.+)\';/', $config, $match);
            $this->database = $match[1];
            preg_match('/\$sql_password.+\'(.*)\';/', $config, $match);
            $this->password = $match[1];
            if ($this->check) {
                $this->tablePrefix          = 'xcart_';
                $this->imageDir             = 'images/'; // xcart starting from 4.1.x hardcodes images location
                $this->imageDirCategory     = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                $this->version              = $this->getCartVersionFromDb('value', 'config', "name = 'version'");
                preg_match('/\$blowfish_key.+\'(.*)\';/', $config, $match);
                $this->cookie_key = $match[1];
            }
        } else {
            $config = file_get_contents(LECM_STORE_BASE_DIR . 'top.inc.php');
            @require_once LECM_STORE_BASE_DIR . 'top.inc.php';
            $config = XLite::getInstance()->getOptions(array('database_details'));
            $this->setHostPort($config['hostspec']);
            $this->username = $config['username'];
            $this->database = $config['database'];
            $this->password = $config['password'];
            if ($this->check) {
                $this->tablePrefix          = $config['table_prefix'];
                $this->imageDir             = 'images/'; // xcart v5
                $this->imageDirCategory     = $this->imageDir . 'category/';
                $this->imageDirProduct      = $this->imageDir . 'product/';
                $this->imageDirManufacturer = $this->imageDir;
                $this->version              = $this->getCartVersionFromDb('value', 'config', "name = 'version'");
            }
        }
    }
}

class LECM_Connector_Adapter_Cart66 extends LECM_Connector_Adapter{
    function setEnv(){
        $config = file_get_contents(LECM_STORE_BASE_DIR . 'wp-config.php');

        preg_match('/define\s*\(\s*\'DB_NAME\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->database = $match[1];
        preg_match('/define\s*\(\s*\'DB_USER\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->username = $match[1];
        preg_match('/define\s*\(\s*\'DB_PASSWORD\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->password = $match[1];
        preg_match('/define\s*\(\s*\'DB_HOST\',\s*\'(.+)\'\s*\)\s*;/', $config, $match);
        $this->setHostPort($match[1]);
        preg_match('/define\s*\(\s*\'DB_CHARSET\',\s*\'(.*)\'\s*\)\s*;/', $config, $match);
        $this->char_set = $match[1];
        preg_match('/\$table_prefix\s*=\s*\'(.*)\'\s*;/', $config, $match);
        $this->tablePrefix = $match[1];
        $this->imageDir = 'wp-content/uploads/';
        $this->imageDirCategory    = $this->imageDir;
        $this->imageDirProduct      = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
        $this->version = $this->getCartVersionFromDb('value', 'cart66_cart_settings', "`key` LIKE 'version'");
    }
}

class LECM_Connector_Adapter_Abantecart extends LECM_Connector_Adapter{

    function setEnv() {
        @require_once LECM_STORE_BASE_DIR . 'system/config.php';
        if (defined('DB_HOSTNAME')) {
            $this->setHostPort(DB_HOSTNAME);
        }
        $this->username = DB_USERNAME;
        $this->password = DB_PASSWORD;
        $this->database = DB_DATABASE;
        $this->tablePrefix = DB_PREFIX;
        $this->imageDir = 'resources/';
        $this->imageDirCategory = $this->imageDir;
        $this->imageDirProduct = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;

        $this->version = '1.2';
    }
}


class LECM_Connector_Adapter_Ubercart extends LECM_Connector_Adapter
{

    function setEnv()
    {
        @include_once LECM_STORE_BASE_DIR . 'sites/default/settings.php';
        if (isset($databases)) {
            $_database = $databases['default']['default'];

            $this->setHostPort($_database['host']);

            $this->username = $_database['username'];

            $this->password = $_database['password'];

            $this->database = $_database['database'];

            $this->tablePrefix = $_database['prefix'];
            $this->version     = '3.6';
        } else {
            $db_url = str_replace('mysql://', '', $db_url);
            $db_url = str_replace('mysqli://', '', $db_url);

            $info           = explode('/', $db_url);
            $this->database = $info[1];
            $info2          = explode('@', $info[0]);
            $this->setHostPort($info2[1]);
            $info3      = explode(':', $info2[0]);
            $this->user = $info3[0];

            if (isset($info3[1])) {
                $this->password = $info3[1];
            } else {
                $this->password = '';
            }

            $this->tablePrefix = $db_prefix;
            $this->version     = '2.13';
        }
        $this->imageDir = '/sites/default/files/';

        $this->imageDirCategory     = $this->imageDir;
        $this->imageDirProduct      = $this->imageDir;
        $this->imageDirManufacturer = $this->imageDir;
    }
}
class LECM_Connector_Adapter_Xtcommerce extends LECM_Connector_Adapter{

    function setEnv(){
        if(!file_exists(LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.org.php')){
            if(file_exists(LECM_STORE_BASE_DIR . 'core/config' . DIRECTORY_SEPARATOR . 'configure.php')){
                define('_VALID_CALL', 'TRUE');
                define('_SRV_WEBROOT', 'TRUE');
                @require_once LECM_STORE_BASE_DIR . 'core/config' . DIRECTORY_SEPARATOR . 'configure.php';
                @require_once LECM_STORE_BASE_DIR . 'core/config' . DIRECTORY_SEPARATOR . 'paths.php';

                $this->setHostPort(DB_SERVER);
                $this->username = DB_SERVER_USERNAME;
                $this->password = DB_SERVER_PASSWORD;
                $this->database = DB_DATABASE;
                $this->imageDir = DIR_WS_IMAGES;
                $this->imageDirCategory = $this->imageDir;
                $this->imageDirProduct = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                $this->imageDirCategory    = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                // if (defined('DIR_WS_PRODUCT_IMAGES')) {
                //     $this->imageDirProduct = DIR_WS_PRODUCT_IMAGES;
                // }
                // if (defined('DIR_WS_ORIGINAL_IMAGES')) {
                //     $this->imageDirProduct = DIR_WS_ORIGINAL_IMAGES;
                // }
                $this->version = '3.0.0';
            }else{
                define('_VALID_CALL', 'TRUE');
                define('_SRV_WEBROOT', 'TRUE');
                @require_once LECM_STORE_BASE_DIR . 'conf' . DIRECTORY_SEPARATOR . 'config.php';
                @require_once LECM_STORE_BASE_DIR . 'conf' . DIRECTORY_SEPARATOR . 'paths.php';

                $this->username = _SYSTEM_DATABASE_USER;
                $this->password = _SYSTEM_DATABASE_PWD;
                $this->database = _SYSTEM_DATABASE_DATABASE;
                $this->setHostPort(_SYSTEM_DATABASE_HOST);
                $this->tablePrefix = DB_PREFIX . '_';
                $this->imageDir = _SRV_WEB_IMAGES;

                $this->imageDirCategory    = $this->imageDir;
                $this->imageDirProduct      = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                $version = $this->getCartVersionFromDb('config_value', 'config', "config_key = '_SYSTEM_VERSION'");
                if ($version != '') {
                    $this->version = $version;
                } else {
                    $this->version = '5.0.0';
                }

            }

        }
        else {
            @require_once LECM_STORE_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'configure.php';
            $this->setHostPort(DB_SERVER);
            $this->username = DB_SERVER_USERNAME;
            $this->password = DB_SERVER_PASSWORD;
            $this->database = DB_DATABASE;
            $this->imageDir = DIR_WS_IMAGES;
            $this->imageDirCategory = $this->imageDir;
            $this->imageDirProduct = $this->imageDir;
            $this->imageDirManufacturer = $this->imageDir;
            if (defined('DIR_WS_PRODUCT_IMAGES')) {
                $this->imageDirProduct = DIR_WS_PRODUCT_IMAGES;
            }
            if (defined('DIR_WS_ORIGINAL_IMAGES')) {
                $this->imageDirProduct = DIR_WS_ORIGINAL_IMAGES;
            }
            $this->version = '3.0.0';
        }
    }
}

class LECM_Connector_Adapter_Loaded extends LECM_Connector_Adapter{

    function setEnv() {

        if (file_exists(LECM_STORE_BASE_DIR . 'includes/configure.php')) {
            @require_once LECM_STORE_BASE_DIR . 'includes/configure.php';
            $this->setHostPort(DB_SERVER);
            $this->username = DB_SERVER_USERNAME;
            $this->password = DB_SERVER_PASSWORD;
            $this->database = DB_DATABASE;

            if ($this->check) {
                $this->tablePrefix = '';
                $this->imageDir = DIR_WS_IMAGES;
                $this->imageDirCategory = $this->imageDir;
                $this->imageDirProduct = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                $this->version = '6.5';
            }
        } else {
            @require_once LECM_STORE_BASE_DIR . 'includes/config.php';
            $this->setHostPort(DB_SERVER);
            $this->username = DB_SERVER_USERNAME;
            $this->password = DB_SERVER_PASSWORD;
            $this->database = DB_DATABASE;

            if ($this->check) {
                $this->tablePrefix = DB_TABLE_PREFIX;
                $this->imageDir = DIR_WS_IMAGES;
                $this->imageDirCategory = $this->imageDir;
                $this->imageDirProduct = $this->imageDir;
                $this->imageDirManufacturer = $this->imageDir;
                $this->version = '7.0';
            }
            //LECM_STORE_BASE_DIR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'local.xml')
        }
    }
}

function getPhpPath()
{
    $paths   = explode(PATH_SEPARATOR, getenv('PATH'));
    $paths[] = PHP_BINDIR;
    foreach ($paths as $path) {
        if (isset($_SERVER["WINDIR"]) && strstr($path, 'php.exe') && @file_exists($path) && is_file($path)) {
            return $path;
        } else {
            $phpPath = $path . DIRECTORY_SEPARATOR . "php" . (isset($_SERVER["WINDIR"]) ? ".exe" : "");
            if (@file_exists($phpPath) && is_file($phpPath)) {
                return $phpPath;
            }
        }
    }

    return false;
}
error_reporting(1);

if (!isset($_SERVER)) {
    $_GET     = &$HTTP_GET_VARS;
    $_POST    = &$HTTP_POST_VARS;
    $_ENV     = &$HTTP_ENV_VARS;
    $_SERVER  = &$HTTP_SERVER_VARS;
    $_COOKIE  = &$HTTP_COOKIE_VARS;
    $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
}
foreach ($_SERVER as $name => $value)
{
    if (strtolower(substr($name, 0, 9)) == 'http_lecm')
    {
        $_REQUEST[strtolower(substr($name, 9))] = $value;
    }
}
define('LECM_ROOT_BASE_NAME', basename(getcwd()));
define('LECM_CONNECTOR_BASE_DIR', dirname(__FILE__));
LECM_Connector_Adapter::detectRootFolder();
@ini_set('display_errors', 1);
@ini_set('memory_limit', -1);
$connector = new LECM_Connector();
$connector->run();
