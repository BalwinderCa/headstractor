<?php
class ControllerInformationContact extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('information/contact');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $mail = new Mail($this->config->get('config_mail_engine'));
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username = $this->config->get('config_mail_smtp_username');
            $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $mail->setTo($this->config->get('config_email'));
            $mail->setFrom($this->config->get('config_email'));
    		$mail->setReplyTo($this->request->post['email']);
    		$mail->setSender(html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8'));
    		$mail->setSubject(html_entity_decode(sprintf($this->language->get('email_subject'), $this->request->post['name']), ENT_QUOTES, 'UTF-8'));
            $mail->setHtml(
    			"<h2>New Contact Form Submission</h2><br>" .
    			"<strong>Name:</strong> " . $this->request->post['name'] . "<br><br>" .
    			"<strong>Email:</strong> " . $this->request->post['email'] . "<br><br>" .
    			"<strong>Message:</strong> " . nl2br($this->request->post['enquiry'])
			);
            
    		$mail->send();

    		$this->response->redirect($this->url->link('information/contact/success'));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/contact')
		);

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['enquiry'])) {
			$data['error_enquiry'] = $this->error['enquiry'];
		} else {
			$data['error_enquiry'] = '';
		}
        
        // Add captcha error to view data
        if (isset($this->error['captcha'])) {
            $data['error_captcha'] = $this->error['captcha'];
        } else {
            $data['error_captcha'] = '';
        }

		$data['button_submit'] = $this->language->get('button_submit');

		$data['action'] = $this->url->link('information/contact', '', true);

		$this->load->model('tool/image');

		if ($this->config->get('config_image')) {
			$data['image'] = $this->model_tool_image->resize($this->config->get('config_image'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
		} else {
			$data['image'] = false;
		}

		$data['store'] = $this->config->get('config_name');
		$data['address'] = nl2br($this->config->get('config_address'));
		$data['geocode'] = $this->config->get('config_geocode');
		$data['geocode_hl'] = $this->config->get('config_language');
		$data['telephone'] = $this->config->get('config_telephone');
		$data['fax'] = $this->config->get('config_fax');
		$data['open'] = nl2br($this->config->get('config_open'));
		$data['comment'] = $this->config->get('config_comment');

		$data['locations'] = array();

		$this->load->model('localisation/location');

		foreach((array)$this->config->get('config_location') as $location_id) {
			$location_info = $this->model_localisation_location->getLocation($location_id);

			if ($location_info) {
				if ($location_info['image']) {
					$image = $this->model_tool_image->resize($location_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
				} else {
					$image = false;
				}

				$data['locations'][] = array(
					'location_id' => $location_info['location_id'],
					'name'        => $location_info['name'],
					'address'     => nl2br($location_info['address']),
					'geocode'     => $location_info['geocode'],
					'telephone'   => $location_info['telephone'],
					'fax'         => $location_info['fax'],
					'image'       => $image,
					'open'        => nl2br($location_info['open']),
					'comment'     => $location_info['comment']
				);
			}
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} else {
			$data['name'] = $this->customer->getFirstName();
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = $this->customer->getEmail();
		}

		if (isset($this->request->post['enquiry'])) {
			$data['enquiry'] = $this->request->post['enquiry'];
		} else {
			$data['enquiry'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/contact', $data));
	}

	protected function validate() {
		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 32)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ((utf8_strlen($this->request->post['enquiry']) < 10) || (utf8_strlen($this->request->post['enquiry']) > 3000)) {
			$this->error['enquiry'] = $this->language->get('error_enquiry');
		}
    
    	// Google reCAPTCHA v3 verification
    	if (isset($this->request->post['g-recaptcha-response'])) {
			$recaptcha_response = $this->request->post['g-recaptcha-response'];

			if ($recaptcha_response) {
				$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
				// Get secret key from configuration instead of hardcoding
				$secret_key = $this->config->get('config_recaptcha_secret_key');
                
                if (!$secret_key) {
                    // Fallback to your key if not in config (but you should move this to config)
                    $secret_key = '6Lek3CcrAAAAALFwWx4nbDD8cG0VxIyv5ueHCNW-';
                }

				$recaptcha = file_get_contents($verify_url . '?secret=' . $secret_key . '&response=' . $recaptcha_response);
				$recaptcha = json_decode($recaptcha);
                

				if (!$recaptcha->success || $recaptcha->score < 0.6) {
  
					$this->error['captcha'] = 'reCAPTCHA verification failed. Please try again.';
				}
			} else {
				$this->error['captcha'] = 'reCAPTCHA token missing. Please refresh and try again.';
			}
		} else {
        	// Fall back to the default captcha system if reCAPTCHA not used
        	if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && 
                in_array('contact', (array)$this->config->get('config_captcha_page'))) {
            	
                $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');
            	
                if ($captcha) {
                	$this->error['captcha'] = $captcha;
            	}
       		}
        }

		return !$this->error;
	}

	/**
     * Get client IP address - works with proxies and supports both IPv4 and IPv6
     * 
     * @return string The IP address
     */
    private function getIpAddress() {
        // Check for shared internet/ISP IP
        if (!empty($this->request->server['HTTP_CLIENT_IP']) && $this->validateIp($this->request->server['HTTP_CLIENT_IP'])) {
            return $this->request->server['HTTP_CLIENT_IP'];
        }
        
        // Check for IPs passing through proxies
        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            // Can include multiple IPs, with the last one being the client IP
            $ipList = explode(',', $this->request->server['HTTP_X_FORWARDED_FOR']);
            foreach ($ipList as $ip) {
                $ip = trim($ip);
                if ($this->validateIp($ip)) {
                    return $ip;
                }
            }
        }
        
        // Check for CloudFlare IP
        if (!empty($this->request->server['HTTP_CF_CONNECTING_IP']) && $this->validateIp($this->request->server['HTTP_CF_CONNECTING_IP'])) {
            return $this->request->server['HTTP_CF_CONNECTING_IP'];
        }
        
        // Default: use REMOTE_ADDR
        return $this->request->server['REMOTE_ADDR'];
    }
    
    /**
     * Validate if an IP address is valid (supports both IPv4 and IPv6)
     * 
     * @param string $ip The IP address to validate
     * @return bool True if valid, false if not
     */
    private function validateIp($ip) {
        // First, check if this is a valid IPv4 address
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }
        
        // Then check if it's a valid IPv6 address
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }
        
        return false;
    }

	public function success() {
		$this->load->language('information/contact');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/contact')
		);

 		$data['text_message'] = $this->language->get('text_message'); 

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}