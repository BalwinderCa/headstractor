<?php
class ControllerExtensionCaptchaGoogle extends Controller {

    public function index() {
        if (empty($this->config->get('captcha_google_key'))) {
            return '';
        }

        $data['site_key'] = $this->config->get('captcha_google_key');
        if (isset($this->request->get['route'])) {
            $route = $this->request->get['route'];
            $data['recaptcha_action'] = str_replace('/', '_', $route);
        } else {
            $data['recaptcha_action'] = 'homepage';
        }

        return $this->load->view('extension/captcha/google', $data);
    }

    public function validate() {
        // If reCAPTCHA is not configured, skip validation
        if (empty($this->config->get('captcha_google_secret'))) {
            return true;
        }

        // Check if token is provided
        if (!isset($this->request->post['g-recaptcha-response']) || empty($this->request->post['g-recaptcha-response'])) {
            return 'reCAPTCHA validation failed. Please try again.';
        }

        // Verify the token with Google
        $secret = $this->config->get('captcha_google_secret');
        $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secret) . '&response=' . urlencode($this->request->post['g-recaptcha-response']) . '&remoteip=' . $this->request->server['REMOTE_ADDR']);
        $response = json_decode($response, true);

        // Check if verification was successful
        if ($response['success']) {
    		// For v3, check the score
    		if (isset($response['score'])) {

        		// Lower threshold to 0.6 for more permissive validation
        		if ($response['score'] >= 0.6) {
            		return ''; // Passed validation
        		} else {
            		return 'reCAPTCHA score too low. Please try again.';
        		}
    		} else {
        		return ''; // Passed validation for v2
    		}
		}
    }
}
