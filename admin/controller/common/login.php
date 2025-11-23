<?php
class ControllerCommonLogin extends Controller {
	private $error = array();

	public function index() {
		// DEBUG: Log everything
		error_log("=== LOGIN DEBUG START ===");
		error_log("REQUEST_METHOD: " . $this->request->server['REQUEST_METHOD']);
		error_log("POST data: " . print_r($this->request->post, true));
		error_log("GET data: " . print_r($this->request->get, true));
		error_log("Session data: " . print_r($this->session->data, true));
		error_log("User logged status: " . ($this->user->isLogged() ? 'YES' : 'NO'));
		
		$this->load->language('common/login');

		$this->document->setTitle($this->language->get('heading_title'));

		// DEBUG: Check user session persistence
		if (isset($this->session->data['user_token'])) {
			error_log("User token in session: " . $this->session->data['user_token']);
			if (isset($this->request->get['user_token'])) {
				error_log("User token in GET: " . $this->request->get['user_token']);
				error_log("Tokens match: " . ($this->request->get['user_token'] == $this->session->data['user_token'] ? 'YES' : 'NO'));
			}
		}
		
		// Check if user_id is in session
		if (isset($this->session->data['user_id'])) {
			error_log("User ID in session: " . $this->session->data['user_id']);
		} else {
			error_log("No user_id in session data");
		}

		// DEBUG: Check if user is already logged in
		if ($this->user->isLogged() && isset($this->request->get['user_token']) && ($this->request->get['user_token'] == $this->session->data['user_token'])) {
			error_log("User already logged in, redirecting to dashboard");
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		}

		// DEBUG: Check POST request and validation
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			error_log("POST validation successful, creating user token");
			$this->session->data['user_token'] = token(32);
			error_log("Generated user_token: " . $this->session->data['user_token']);

			// Force redirect to dashboard to avoid loops
			$redirect_url = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
			// Fix HTML entity encoding in URL
			$redirect_url = html_entity_decode($redirect_url, ENT_QUOTES, 'UTF-8');
			error_log("Forcing redirect to dashboard: " . $redirect_url);
			$this->response->redirect($redirect_url);
		} elseif ($this->request->server['REQUEST_METHOD'] == 'POST') {
			error_log("POST validation FAILED");
			error_log("Validation errors: " . print_r($this->error, true));
		}

		// DEBUG: Token validation check
		if ((isset($this->session->data['user_token']) && !isset($this->request->get['user_token'])) || ((isset($this->request->get['user_token']) && (isset($this->session->data['user_token']) && ($this->request->get['user_token'] != $this->session->data['user_token']))))) {
			error_log("Token validation failed");
			$this->error['warning'] = $this->language->get('error_token');
		}

		if (isset($this->error['error_attempts'])) {
			$data['error_warning'] = $this->error['error_attempts'];
		} elseif (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['action'] = $this->url->link('common/login', '', true);

		if (isset($this->request->post['username'])) {
			$data['username'] = $this->request->post['username'];
		} else {
			$data['username'] = '';
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->get['route'])) {
			$route = $this->request->get['route'];

			unset($this->request->get['route']);
			unset($this->request->get['user_token']);

			$url = '';

			if ($this->request->get) {
				$url .= http_build_query($this->request->get);
			}

			$data['redirect'] = $this->url->link($route, $url, true);
		} else {
			$data['redirect'] = '';
		}

		if ($this->config->get('config_password')) {
			$data['forgotten'] = $this->url->link('common/forgotten', '', true);
		} else {
			$data['forgotten'] = '';
		}

		error_log("=== LOGIN DEBUG END ===");

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('common/login', $data));
	}

	protected function validate() {
		error_log("=== VALIDATE DEBUG START ===");
		
		if (!isset($this->request->post['username']) || !isset($this->request->post['password']) || !$this->request->post['username'] || !$this->request->post['password']) {
			error_log("Username or password missing");
			$this->error['warning'] = $this->language->get('error_login');
		} else {
			error_log("Username: " . $this->request->post['username']);
			$this->load->model('user/user');

			// Check how many login attempts have been made.
			$login_info = $this->model_user_user->getLoginAttempts($this->request->post['username']);

			if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
				error_log("Too many login attempts");
				$this->error['error_attempts'] = $this->language->get('error_attempts');
			}
		}

		if (!$this->error) {
			error_log("Attempting user login...");
			if (!$this->user->login($this->request->post['username'], html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8'))) {
				error_log("User login FAILED");
				$this->error['warning'] = $this->language->get('error_login');

				$this->model_user_user->addLoginAttempt($this->request->post['username']);

				unset($this->session->data['user_token']);
			} else {
				error_log("User login SUCCESS");
				error_log("Session data after login: " . print_r($this->session->data, true));
				error_log("User ID after login: " . $this->user->getId());
				error_log("User logged status after login: " . ($this->user->isLogged() ? 'YES' : 'NO'));
				
				// FORCE user session data if not set properly
				if (!$this->user->isLogged()) {
					error_log("User not logged in after successful login - forcing session data");
					// Get user data from database
					$user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE username = '" . $this->db->escape($this->request->post['username']) . "' AND status = '1'");
					if ($user_query->num_rows) {
						$user_info = $user_query->row;
						$this->session->data['user_id'] = $user_info['user_id'];
						$this->session->data['username'] = $user_info['username'];
						$this->session->data['user_group_id'] = $user_info['user_group_id'];
						error_log("Forced session data: user_id=" . $user_info['user_id']);
					}
				}
				
				$this->model_user_user->deleteLoginAttempts($this->request->post['username']);
			}
		}

		error_log("Validation result: " . (!$this->error ? 'SUCCESS' : 'FAILED'));
		error_log("=== VALIDATE DEBUG END ===");

		return !$this->error;
	}
}