<?php
class ControllerExtensionCaptchaHcaptcha extends Controller {
    public function index($error = array()) {
        $this->load->language('extension/captcha/hcaptcha');

        if (isset($error['captcha'])) {
			$data['error_captcha'] = $error['captcha'];
		} else {
			$data['error_captcha'] = '';
		}

		$data['site_key'] = $this->config->get('captcha_hcaptcha_key');

        $data['route'] = $this->request->get['route']; 

		return $this->load->view('extension/captcha/hcaptcha', $data);
    }

    public function validate() {
		// var_dump($this->session->data['hcaptcha']); die;
		if (empty($this->session->data['hcaptcha'])) {	
			$this->load->language('extension/captcha/hcaptcha');

			if (!isset($this->request->post['h-captcha-response'])) {
				return $this->language->get('error_captcha');
			}

			$hcaptcha = file_get_contents('https://hcaptcha.com/siteverify?secret=' . urlencode($this->config->get('captcha_hcaptcha_secret')) . '&response=' . $this->request->post['h-captcha-response'] . '&remoteip=' . $this->request->server['REMOTE_ADDR']);

			// var_dump($hcaptcha); die;

			$hcaptcha = json_decode($hcaptcha, true);

			if ($hcaptcha['success']) {
				$this->session->data['hcaptcha']	= true;
			} else {
				return $this->language->get('error_captcha');
			}
		}
    }
}


