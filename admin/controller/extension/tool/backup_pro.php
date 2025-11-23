<?php 
class ControllerExtensionToolBackupPro extends Controller { 
	
	private $version = '3.3.0.10';
	
	private $error = array();
	private $backup_folder = '../backup_pro/';
	private $database_backup_folder = '../backup_pro/database/';
	private $excludes_folder = '../backup_pro/excludes/';
	private $log_folder = '../backup_pro/log/';
	private $temp_folder = '../backup_pro/temp/';
	private $storage_folder = '../backup_pro/_storage/';
	private $backup_filetype = '.tgz';
	private $large_table = 50;
	private $max_rows = 50000;
	private $max_files = 30000;
	private $restore_time_limit = 30;
	private $max_packet = 0;
	private $memory_limit = 256;
	private $php_memory_limit = 0;
	private $max_execution_time = 300;
	
	
	public function index() {	

		unset($this->session->data['db_backup_data']);
		unset($this->session->data['destination']);
		
		$this->checkStatus();
		
		$this->load->model('extension/tool/backup_pro');
		$this->load->language('extension/tool/backup_pro');
		
		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/backup_pro.css');

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			
			// Database Backup
			if (isset($this->request->post['what']) && $this->request->post['what'] == 'db') {
				// Debug
				if($this->request->post['debug']) {
					$this->resetDebug();
					$this->debug('Database Backup');
					$this->debug('Tables To Be Backed Up:', $this->request->post['backup']);
				}
				$this->session->data['backup'] = array(
					'what'					=> $this->request->post['what'],
					'tables'				=> $this->request->post['backup'],
					'backup_filename'		=> $this->request->post['backup_filename'],
					'ignore_excludes'		=> $this->request->post['backup_ignore_excludes'],
					'save_db2server'		=> $this->request->post['save_db2server'],
					'debug'					=> $this->request->post['debug']
				);
				$this->response->redirect($this->url->link('extension/tool/backup_pro/backupDatabase', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));
			}
			
			
			// Wholestore Backup
			if (isset($this->request->post['what']) && $this->request->post['what'] == 'ws') {
				$tables = $this->model_extension_tool_backup_pro->getTables();
				if($this->request->post['debug']) {
					$this->resetDebug();
					$this->debug('Whole Store Backup');
					$this->debug('Tables To Be Backed Up:', $tables);
				}
				$this->session->data['backup'] = array(
					'what'					=> $this->request->post['what'],
					'step'					=> 'database',
					'tables'				=> $tables,
					'backup_filename'		=> $this->request->post['backup_filename'],
					'ignore_excludes'		=> $this->request->post['backup_ignore_excludes'],
					'save_db2server'		=> true,
					'debug'					=> $this->request->post['debug']
				);
				
				// Delete Previous database_clone backups
				
				$files = scandir($this->database_backup_folder);
				foreach($files as $file) {
					if(strpos($file, 'database_clone') === 0) {
						unlink($this->database_backup_folder . $file);
					}
				}

				$this->response->redirect($this->url->link('extension/tool/backup_pro/backupDatabase', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));
			}
			
			
			// Scheduled Backup
			if (isset($this->request->post['backup_scheduled_status'])) {
				if ($this->user->hasPermission('modify', 'extension/tool/backup_pro')) {
					$this->load->model('setting/setting');
					$this->model_setting_setting->editSetting('backup_scheduled', $this->request->post);
									
					$this->session->data['success'] = $this->language->get('text_success');
				} else {
					$this->session->data['error_warning'] = $this->language->get('error_permission');
					$this->response->redirect($this->url->link('extension/tool/backup_pro', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));			
				}
				
				// Check to see if the email needs testing
				if($this->request->post['test_email'] == '1') {
					
					$this->session->data['backup_filename']	= $this->request->post['backup_scheduled_filename'];
					
					$this->response->redirect($this->url->link('extension/tool/backup_pro/backupDatabase', 'rt=extension/tool/backup_pro/testEmail&user_token=' . $this->session->data['user_token'], $this->getSSL()));							
				}
			}
			
			// Configuration Settings
			if (isset($this->request->post['backup_pro_config_max_rows'])) {				
				$this->load->model('setting/setting');
				$this->model_setting_setting->editSetting('backup_pro_config', $this->request->post);
				$fp = fopen($this->excludes_folder . 'excludes.txt', 'w');
				fwrite($fp, $this->request->post['backup_pro_config_wholestore_excludes']);
				fclose($fp);

			}
		}

		// Check if a backup has been made & if so, set the trigger for download
		$data['download_available'] = FALSE;
		if(isset($this->session->data['download_file'])) {
			$data['download_available'] = TRUE;
		} 
		
		$data['wholeStoreTar'] = false;
		if(isset($this->session->data['wholeStoreTar'])) {
			$data['wholeStoreTar'] = $this->session->data['wholeStoreTar'];
			unset($this->session->data['wholeStoreTar']);
		}
		
		
		$data['goto_utilities'] = false;
		$data['goto'] = false;
		if(isset($this->request->get['goto'])) {
			$data['goto'] = '#' . $this->request->get['goto'];
		}			
				
		$memory_limit = substr(ini_get('memory_limit'), 0, -1);
		
		$query = $this->db->query("show variables like 'max_allowed_packet'");
		if($query->num_rows) {
			$system_max_allowed_packet = (int)($query->row['Value'] / 1024 / 1024);
		}
		
		if($this->config->get('backup_pro_config_filename') == '' && !isset($this->request->post['backup_pro_config_filename'])) {
		
			$this->max_packet = (int)($memory_limit / 2);
			if($this->max_packet > 10) {
				$this->max_packet = 10;
			}
			if($this->max_packet >= $system_max_allowed_packet) {
				$this->max_packet = $system_max_allowed_packet - 1;
			}
			if($this->max_packet <= 1) {
				$this->max_packet = 0.7;
			}
			
			if($memory_limit < 256) {
				$this->memory_limit = 256;
			} else {
				$this->memory_limit = $memory_limit;
			}
			
			$backup_pro_config = array(
				'backup_pro_config_filename' 			=> $this->convert_text($this->config->get('config_name')) . '-' . $this->language->get('text_backup_filename'),
				'backup_pro_config_404_report' 			=> 0,
				'backup_pro_config_bots_report' 		=> 0,
				'backup_pro_config_session' 			=> 0,
				'backup_pro_config_large_table'			=> $this->large_table,
				'backup_pro_config_max_rows'			=> $this->max_rows,
				'backup_pro_config_max_packet'			=> $this->max_packet,
				'backup_pro_config_max_time'			=> $this->max_execution_time,
				'backup_pro_config_memory_limit' 		=> $this->memory_limit,
				'backup_pro_config_zip' 				=> 1,
				'backup_pro_config_consolidate_zip' 	=> 1,
				'backup_pro_config_download' 			=> 1,
				'backup_pro_config_save_db2server' 		=> 0,
				'backup_pro_config_combine_download' 	=> 1,
				'backup_pro_config_limit' 				=> 'All Files',
				'backup_pro_config_db_limit' 			=> 'None',
				'backup_pro_config_group_size' 			=> 1073741824,
				'backup_pro_config_max_files'			=> $this->max_files,
				'backup_pro_config_restore_time_limit'	=> $this->restore_time_limit,
				'backup_pro_config_max_sql_size'		=> 'Not Set',
				'backup_pro_config_exclude_tables' 		=> ''
			);
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('backup_pro_config', $backup_pro_config);

			$this->response->redirect($this->url->link('extension/tool/backup_pro', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));						
		}
		
		$database_tables = $this->model_extension_tool_backup_pro->getTables();
		
		if(!$this->config->get('tool_backup_pro_status')) {
			$status = array('tool_backup_pro_status' => 1);
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('tool_backup_pro', $status);
		}

		$this->max_rows = $this->config->get('backup_pro_config_max_rows');
		$this->max_packet = $this->config->get('backup_pro_config_max_packet');
		$this->memory_limit = $this->config->get('backup_pro_config_memory_limit');
		$this->max_execution_time = $this->config->get('backup_pro_config_max_time');
				
		$data['storage_folder_exists'] = is_dir($this->storage_folder);
		$data['b_limit'] = array('All Files', '1000', '500', '450', '400', '350', '300', '250', '200', '150', '125', '100', '75');
		$data['max_sql_size'] = array('Not Set', '100', '80', '60', '40');
		$data['exec'] = $this->isExecEnabled();
		$data['tar'] = $this->isTarEnabled();
		$data['clone_exists'] = file_exists($this->database_backup_folder . 'database_clone.sql');
		$data['version'] = $this->version;
		$data['configuration_alert'] = '';
		$data['hidden'] = '';
		
		if(isset($this->request->post['backup_pro_config_filename'])) {
			$data['backup_pro_config_filename'] = $this->request->post['backup_pro_config_filename'];
		} elseif ($this->config->get('backup_pro_config_filename') != '') {
			$data['backup_pro_config_filename'] = $this->config->get('backup_pro_config_filename');
		} else {
			$data['backup_pro_config_filename'] = $this->convert_text($this->config->get('config_name')) . '-' . $this->language->get('text_backup_filename');
		}

		if(isset($this->request->post['backup_pro_config_limit'])) {
			$data['backup_pro_config_limit'] = $this->request->post['backup_pro_config_limit'];
		} elseif ($this->config->get('backup_pro_config_limit') != '') {
			$data['backup_pro_config_limit'] = $this->config->get('backup_pro_config_limit');
		} else {
			$data['backup_pro_config_limit'] = 'All Files';
		}
		
		if(isset($this->request->post['backup_scheduled_status'])) {
			$data['backup_scheduled_status'] = $this->request->post['backup_scheduled_status'];
		} elseif ($this->config->get('backup_scheduled_status') != '') {
			$data['backup_scheduled_status'] = $this->config->get('backup_scheduled_status');
		} else {
			$data['backup_scheduled_status'] = '0';
		}

		if(isset($this->request->post['backup_scheduled_housekeeping'])) {
			$data['backup_scheduled_housekeeping'] = $this->request->post['backup_scheduled_housekeeping'];
		} elseif ($this->config->get('backup_scheduled_housekeeping') != '') {
			$data['backup_scheduled_housekeeping'] = $this->config->get('backup_scheduled_housekeeping');
		} else {
			$data['backup_scheduled_housekeeping'] = '0';
		}

		if(isset($this->request->post['backup_scheduled_filename'])) {
			$data['backup_scheduled_filename'] = $this->request->post['backup_scheduled_filename'];
		} elseif ($this->config->get('backup_scheduled_filename') != '') {
			$data['backup_scheduled_filename'] = $this->config->get('backup_scheduled_filename');
		} else {
			$data['backup_scheduled_filename'] = $this->convert_text($this->config->get('config_name')) . '-' . $this->language->get('text_backup_filename');
		}
		
		$data['backup_scheduled_test_button'] = '';
		if($this->config->get('backup_scheduled_filename') == '' && !isset($this->request->post['backup_scheduled_filename'])) {
			$data['backup_scheduled_test_button'] = ' disabled';
		}
		

		if(isset($this->request->post['backup_scheduled_zip'])) {
			$data['backup_scheduled_zip'] = $this->request->post['backup_scheduled_zip'];
		} elseif ($this->config->get('backup_scheduled_zip') != '') {
			$data['backup_scheduled_zip'] = $this->config->get('backup_scheduled_zip');
		} else {
			$data['backup_scheduled_zip'] = 1;
		}

		if(isset($this->request->post['backup_scheduled_save_db2server'])) {
			$data['backup_scheduled_save_db2server'] = $this->request->post['backup_scheduled_save_db2server'];
		} elseif ($this->config->get('backup_scheduled_save_db2server') != '') {
			$data['backup_scheduled_save_db2server'] = $this->config->get('backup_scheduled_save_db2server');
		} else {
			$data['backup_scheduled_save_db2server'] = 0;
		}

		if(isset($this->request->post['backup_scheduled_email_backup'])) {
			$data['backup_scheduled_email_backup'] = $this->request->post['backup_scheduled_email_backup'];
		} elseif ($this->config->get('backup_scheduled_email_backup') != '') {
			$data['backup_scheduled_email_backup'] = $this->config->get('backup_scheduled_email_backup');
		} else {
			$data['backup_scheduled_email_backup'] = 1;
		}

		if(isset($this->request->post['backup_scheduled_email'])) {
			$data['backup_scheduled_email'] = $this->request->post['backup_scheduled_email'];
		} elseif ($this->config->get('backup_scheduled_email') != '') {
			$data['backup_scheduled_email'] = $this->config->get('backup_scheduled_email');
		} else {
			$data['backup_scheduled_email'] = $this->config->get('config_email');
		}

		if(isset($this->request->post['backup_scheduled_email_subject'])) {
			$data['backup_scheduled_email_subject'] = $this->request->post['backup_scheduled_email_subject'];
		} elseif ($this->config->get('backup_scheduled_email') != '') {
			$data['backup_scheduled_email_subject'] = $this->config->get('backup_scheduled_email_subject');
		} else {
			$data['backup_scheduled_email_subject'] = $this->config->get('config_name') . ' - ' . $this->language->get('text_backup_email_subject');
		}

		if(isset($this->request->post['backup_scheduled_email_message'])) {
			$data['backup_scheduled_email_message'] = $this->request->post['backup_scheduled_email_message'];
		} elseif ($this->config->get('backup_scheduled_email') != '') {
			$data['backup_scheduled_email_message'] = $this->config->get('backup_scheduled_email_message');
		} else {
			$data['backup_scheduled_email_message'] = $this->language->get('text_backup_email_message');
		}
		
		$data['frequencies'] = array('Daily', 'Weekly', 'Monthly');
		
		if(isset($this->request->post['backup_scheduled_frequency'])) {
			$data['backup_scheduled_frequency'] = $this->request->post['backup_scheduled_frequency'];
		} elseif ($this->config->get('backup_scheduled_frequency') != '') {
			$data['backup_scheduled_frequency'] = $this->config->get('backup_scheduled_frequency');
		} else {
			$data['backup_scheduled_frequency'] = 'Daily';
		}

		$data['backup_scheduled_cron_command'] = 'curl --silent --location ' . ($this->getSSL() ? HTTPS_CATALOG: HTTP_CATALOG) . 'index.php?route=extension/tool/backup_pro > /dev/null';
//		$data['backup_scheduled_cron_command'] = 'wget ' . ($this->getSSL() ? HTTPS_CATALOG: HTTP_CATALOG) . 'index.php?route=extension/tool/backup_pro > /dev/null';
		
		if(isset($this->request->post['backup_scheduled_cron'])) {
			$data['backup_scheduled_cron'] = $this->request->post['backup_scheduled_cron'];
		} elseif ($this->config->get('backup_scheduled_email') != '') {
			$data['backup_scheduled_cron'] = $this->config->get('backup_scheduled_cron');
		} else {
			$data['backup_scheduled_cron'] = 0;
		}

		if(isset($this->request->post['backup_pro_config_404_report'])) {
			$data['backup_pro_config_404_report'] = $this->request->post['backup_pro_config_404_report'];
		} elseif ($this->config->get('backup_pro_config_404_report') != '') {
			$data['backup_pro_config_404_report'] = $this->config->get('backup_pro_config_404_report');
		} else {
			$data['backup_pro_config_404_report'] = '';
			$data['configuration_alert'] = $this->language->get('text_configuration_alert');
			$data['hidden'] = ' hidden';
		}

		if(isset($this->request->post['backup_pro_config_bots_report'])) {
			$data['backup_pro_config_bots_report'] = $this->request->post['backup_pro_config_bots_report'];
		} elseif ($this->config->get('backup_pro_config_bots_report') != '') {
			$data['backup_pro_config_bots_report'] = $this->config->get('backup_pro_config_bots_report');
		} else {
			$data['backup_pro_config_bots_report'] = '';
			$data['configuration_alert'] = $this->language->get('text_configuration_alert');
			$data['hidden'] = ' hidden';
		}

		if(isset($this->request->post['backup_pro_config_session'])) {
			$data['backup_pro_config_session'] = $this->request->post['backup_pro_config_session'];
		} elseif ($this->config->get('backup_pro_config_session') != '') {
			$data['backup_pro_config_session'] = $this->config->get('backup_pro_config_session');
		} else {
			$data['backup_pro_config_session'] = '';
			$data['configuration_alert'] = $this->language->get('text_configuration_alert');
			$data['hidden'] = ' hidden';
		}

		if(isset($this->request->post['backup_pro_config_max_rows'])) {
			$data['backup_pro_config_max_rows'] = $this->request->post['backup_pro_config_max_rows'];
		} elseif ($this->config->get('backup_pro_config_max_rows') != '') {
			$data['backup_pro_config_max_rows'] = $this->config->get('backup_pro_config_max_rows');
		} else {
			$data['backup_pro_config_max_rows'] = $this->max_rows;
		}

		if(isset($this->request->post['backup_pro_config_save_db2server'])) {
			$data['backup_pro_config_save_db2server'] = $this->request->post['backup_pro_config_save_db2server'];
		} elseif ($this->config->get('backup_pro_config_save_db2server') != '') {
			$data['backup_pro_config_save_db2server'] = $this->config->get('backup_pro_config_save_db2server');
		} else {
			$data['backup_pro_config_save_db2server'] = 0;
		}

		if(isset($this->request->post['backup_pro_config_download'])) {
			$data['backup_pro_config_download'] = $this->request->post['backup_pro_config_download'];
		} elseif ($this->config->get('backup_pro_config_download') != '') {
			$data['backup_pro_config_download'] = $this->config->get('backup_pro_config_download');
		} else {
			
			$data['backup_pro_config_download'] = 1;
		}

		if(isset($this->request->post['backup_pro_config_memory_limit'])) {
			$data['backup_pro_config_memory_limit'] = $this->request->post['backup_pro_config_memory_limit'];
		} elseif ($this->config->get('backup_pro_config_memory_limit') != '') {
			$data['backup_pro_config_memory_limit'] = $this->config->get('backup_pro_config_memory_limit');
		} else {
			$data['backup_pro_config_memory_limit'] = $this->memory_limit;
		}

		if(isset($this->request->post['backup_pro_config_max_files'])) {
			$data['backup_pro_config_max_files'] = $this->request->post['backup_pro_config_max_files'];
		} elseif ($this->config->get('backup_pro_config_max_files') != '') {
			$data['backup_pro_config_max_files'] = $this->config->get('backup_pro_config_max_files');
		} else {
			$data['backup_pro_config_max_files'] = $this->max_files;
		}

		if(isset($this->request->post['backup_pro_config_restore_time_limit'])) {
			$data['backup_pro_config_restore_time_limit'] = $this->request->post['backup_pro_config_restore_time_limit'];
		} elseif ($this->config->get('backup_pro_config_restore_time_limit') != '') {
			$data['backup_pro_config_restore_time_limit'] = $this->config->get('backup_pro_config_restore_time_limit');
		} else {
			$data['backup_pro_config_restore_time_limit'] = $this->restore_time_limit;
		}

		if(isset($this->request->post['backup_pro_config_limit'])) {
			$data['backup_pro_config_limit'] = $this->request->post['backup_pro_config_limit'];
		} elseif ($this->config->get('backup_pro_config_limit') != '') {
			$data['backup_pro_config_limit'] = $this->config->get('backup_pro_config_limit');
		} else {
			$data['backup_pro_config_limit'] = 'All Files';
		}
		
		if(isset($this->request->post['backup_pro_config_zip'])) {
			$data['backup_pro_config_zip'] = $this->request->post['backup_pro_config_zip'];
		} elseif ($this->config->get('backup_pro_config_zip') != '') {
			$data['backup_pro_config_zip'] = $this->config->get('backup_pro_config_zip');
		} else {
			$data['backup_pro_config_zip'] = 1;
		}
		
		if(isset($this->request->post['backup_pro_config_consolidate_zip'])) {
			$data['backup_pro_config_consolidate_zip'] = $this->request->post['backup_pro_config_consolidate_zip'];
		} elseif ($this->config->get('backup_pro_config_consolidate_zip') != '') {
			$data['backup_pro_config_consolidate_zip'] = $this->config->get('backup_pro_config_consolidate_zip');
		} else {
			$data['backup_pro_config_consolidate_zip'] = 1;
		}
		
		if(isset($this->request->post['backup_pro_config_db_limit'])) {
			$data['backup_pro_config_db_limit'] = $this->request->post['backup_pro_config_db_limit'];
		} elseif ($this->config->get('backup_pro_config_db_limit') != '') {
			$data['backup_pro_config_db_limit'] = $this->config->get('backup_pro_config_db_limit');
		} else {
			$data['backup_pro_config_db_limit'] = 'None';
		}
		
		if(isset($this->request->post['backup_pro_config_max_sql_size'])) {
			$data['backup_pro_config_max_sql_size'] = $this->request->post['backup_pro_config_max_sql_size'];
		} elseif ($this->config->get('backup_pro_config_max_sql_size') != '') {
			$data['backup_pro_config_max_sql_size'] = $this->config->get('backup_pro_config_max_sql_size');
		} else {
			$data['backup_pro_config_max_sql_size'] = 'Not Set';
		}
		
		if(isset($this->request->post['backup_pro_config_combine_download'])) {
			$data['backup_pro_config_combine_download'] = $this->request->post['backup_pro_config_combine_download'];
		} elseif ($this->config->get('backup_pro_config_combine_download') != '') {
			$data['backup_pro_config_combine_download'] = $this->config->get('backup_pro_config_combine_download');
		} else {
			$data['backup_pro_config_combine_download'] = '1';
		}

		if(isset($this->request->post['backup_pro_config_max_packet'])) {
			$data['backup_pro_config_max_packet'] = $this->request->post['backup_pro_config_max_packet'];
		} elseif ($this->config->get('backup_pro_config_max_packet') != '') {
			$data['backup_pro_config_max_packet'] = $this->config->get('backup_pro_config_max_packet');
		} else {
			$max_allowed_packet = (int)($data['backup_pro_config_memory_limit'] / 2);
			if($max_allowed_packet >= $system_max_allowed_packet) {
				$max_allowed_packet = $system_max_allowed_packet -1;
			}
			$data['backup_pro_config_max_packet'] = $max_allowed_packet;
		}
		

		$data['system_max_packet_size'] = '(' . $system_max_allowed_packet . ' Mb)';
		

		if(isset($this->request->post['backup_pro_config_max_time'])) {
			$data['backup_pro_config_max_time'] = $this->request->post['backup_pro_config_max_time'];
		} elseif ($this->config->get('backup_pro_config_max_time') != '') {
			$data['backup_pro_config_max_time'] = $this->config->get('backup_pro_config_max_time');
		} else {
			$data['backup_pro_config_max_time'] = $this->max_execution_time;
		}

		if(isset($this->request->post['backup_pro_config_large_table'])) {
			$data['backup_pro_config_large_table'] = $this->request->post['backup_pro_config_large_table'];
		} elseif ($this->config->get('backup_pro_config_large_table') != '') {
			$data['backup_pro_config_large_table'] = $this->config->get('backup_pro_config_large_table');
		} else {
			$data['backup_pro_config_large_table'] = $this->large_table;
		}

		$data['backup_pro_config_wholestore_excludes'] = file_get_contents('../backup_pro/excludes/excludes.txt');
		
		if(isset($this->request->post['backup_pro_config_exclude_tables'])) {
			$data['backup_pro_config_exclude_tables'] = $this->request->post['backup_pro_config_exclude_tables'];
		} elseif ($this->config->get('backup_pro_config_exclude_tables') != '') {
			$data['backup_pro_config_exclude_tables'] = $this->config->get('backup_pro_config_exclude_tables');
		} else {
			$data['backup_pro_config_exclude_tables'] = '';
		}
		
		$data['exclude_tables'] = explode("\n", str_replace("\r","\n", $data['backup_pro_config_exclude_tables']));
		$db_max_size = $this->config->get('backup_pro_config_max_sql_size');
		$db_data = array();
		$large_tables = array();
		$query = $this->db->query("SHOW TABLE STATUS FROM `" . DB_DATABASE . "`");
		if($query->num_rows) {
			$database_size = 0;
			foreach($query->rows as $row) {
				$database_size = $database_size + ($row['Data_length'] + $row['Index_length']);
				$db_data[] = array(
					'table'				=> $row['Name'],
					'rows'				=> $row['Rows'],
					'data_length'		=> $this->formatSize($row['Data_length']),
					'index_length'		=> $this->formatSize($row['Index_length']),
					'size'				=> $this->formatSize($row['Data_length'] + $row['Index_length']),
					'total_size'		=> $row['Data_length'] + $row['Index_length']
				);
				
				if(($row['Data_length'] + $row['Index_length']) / 1024 / 1024 >= $data['backup_pro_config_large_table']) {
					if($db_max_size == 'Not Set') {
						$buttons = 1;
					} else {
						$buttons = ((int)($row['Data_length'] + $row['Index_length']) / ($db_max_size * 1024 * 1024)) + 1;
					}
					
					$large_tables[] = array(
						'table'				=> $row['Name'],
						'size'				=> $this->formatSize($row['Data_length'] + $row['Index_length']),
						'total_size'		=> $row['Data_length'] + $row['Index_length'],
						'buttons'			=> $buttons
					);
				}
			}
		}

		$data['database_tables'] = $this->array_msort($db_data, array('total_size'=>SORT_DESC));
		$data['large_tables'] = $this->array_msort($large_tables, array('total_size'=>SORT_DESC));
		
		$data['database_size'] = $this->formatSize($database_size);
		
		
		if (isset($this->session->data['error_warning']) && !isset($data['error_warning'])) {
    		$data['error_warning'] = $this->session->data['error_warning'];
    
			unset($this->session->data['error_warning']);
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
		
  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], $this->getSSL()),     		
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/tool/backup_pro', 'user_token=' . $this->session->data['user_token'], $this->getSSL()),
      		'separator' => ' :: '
   		);
		
		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], $this->getSSL());
		$data['restore'] = $this->url->link('extension/tool/backup_pro/restore', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL());

		$data['restore_clone'] = $this->url->link('extension/tool/backup_pro/restore_clone', 'user_token=' . $this->session->data['user_token'], $this->getSSL());
		$data['delete_clone'] = $this->url->link('extension/tool/backup_pro/delete_clone', 'user_token=' . $this->session->data['user_token'], $this->getSSL());
		$data['test'] = HTTPS_CATALOG . 'index.php?route=extension/tool/backup_pro&test=true';
		
		$data['restore_storage_folder'] = html_entity_decode($this->url->link('extension/tool/backup_pro/restoreStorageFolder', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));
		$data['delete_storage_folder'] = html_entity_decode($this->url->link('extension/tool/backup_pro/deleteCopyStorageFolder', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));

		$data['backup'] = $this->url->link('extension/tool/backup_pro', 'user_token=' . $this->session->data['user_token'], $this->getSSL());
		$data['backup_download'] = HTTP_SERVER . 'index.php?route=extension/tool/backup_pro/download&user_token=' . $this->session->data['user_token'];
		$data['backup_scheduled'] = $this->url->link('extension/tool/backup_pro', 'goto=scheduled&user_token=' . $this->session->data['user_token'], $this->getSSL());
		$data['backup_configuration'] = $this->url->link('extension/tool/backup_pro', 'goto=config&user_token=' . $this->session->data['user_token'], $this->getSSL());

		
		$data['max_ex_time_test'] = $this->url->link('extension/tool/backup_pro/maxExTime', 'user_token=' . $this->session->data['user_token'], $this->getSSL());
		$data['memory_limit_test'] = $this->url->link('extension/tool/backup_pro/memTest', 'user_token=' . $this->session->data['user_token'], $this->getSSL());
		
		$data['max_execution_time'] = ini_get('max_execution_time');
		$data['upload_max_filesize'] = ini_get('upload_max_filesize');		
		$data['post_max_size'] = ini_get('post_max_size');		
		$data['curl'] = ($this->isCurlEnabled() ? '<i class="fa fa-2x fa-thumbs-o-up text-success"></i> Enabled' : '<i class="fa fa-2x fa-thumbs-o-down text-danger"></i> Not Enabled');
		$data['zip'] = ($this->isZipEnabled() ? '<i class="fa fa-2x fa-thumbs-o-up text-success"></i> Enabled' : '<i class="fa fa-2x fa-thumbs-o-down text-danger"></i> Not Enabled');
		$data['exec'] = ($this->isExecEnabled() ? '<i class="fa fa-2x fa-thumbs-o-up text-success"></i> Enabled' : '<i class="fa fa-2x fa-thumbs-o-down text-danger"></i> Not Enabled');
		$data['tar'] = ($this->isTarEnabled() ? '<i class="fa fa-2x fa-thumbs-o-up text-success"></i> Enabled' : '<i class="fa fa-2x fa-thumbs-o-down text-danger"></i> Not Enabled');
		
		$data['memory_limit'] = $memory_limit . 'M';
		
		$data['tarIsEnabled'] = $this->isTarEnabled();		
		
		
		// Get files data
		$root = str_replace('\\', '/', substr(DIR_SYSTEM, 0, -7));
		$source = array($root);
		$wholestore = $this->getFiles($source);
		$data['total_data']['folder'] = 'Total';
		$data['total_data']['no'] = $wholestore['no_of_files'];
		$data['total_data']['bytes'] = $wholestore['size_of_files'];
		$data['total_data']['size'] = $this->formatSize($wholestore['size_of_files']);
		
		$folders = array();
		$root_data['no'] = 0;
		$root_data['bytes'] = 0;
		$files = scandir($root);
		
		foreach($files as $file) {
			if($file == '.' || $file == '..') {
				continue;
			} elseif(is_dir($root . $file)) {
				$folders[] = $file;
			} elseif(is_file($root . $file)) {
				$root_data['no']++;
				$root_data['bytes'] += filesize($root . $file);
			}
		}
		$root_data['size'] = $this->formatSize($root_data['bytes']);
		
		
		$folder_data = array();
		$folder_data[] = array(
			'folder'	=> '/',
			'no'		=> $root_data['no'],
			'bytes'		=> $root_data['bytes'],
			'size'		=> $root_data['size'],
		);
		
		foreach($folders as $folder) {
			$source = array($root . $folder);
					
			$filedata = $this->getFiles($source);
			$folder_data[] = array(
				'folder'	=> $folder,
				'no'		=> $filedata['no_of_files'],
				'bytes'		=> $filedata['size_of_files'],
				'size'		=> $this->formatSize($filedata['size_of_files'])
			);
		}
		
		$columns = array_column($folder_data, 'bytes');
		array_multisort($columns, SORT_DESC, $folder_data);
		
		$data['folder_data'] = $folder_data;
		
		$data['tables'] = $this->model_extension_tool_backup_pro->getTables();

		// Database Backups
		$db_backup_files = scandir($this->database_backup_folder);
		$db_backups = array();
		foreach($db_backup_files as $db_backup_file) {
			if(substr($db_backup_file, -4) == '.sql' || substr($db_backup_file, -4) == '.zip') {
				$db_backups[] = array(
					'filename' 		=> $db_backup_file,
					'size'			=> $this->formatSize(filesize($this->database_backup_folder . $db_backup_file)),
					'file_date'		=> preg_match('#\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])_[0-2][0-9]-[0-5][0-9]-[0-5][0-9]#', $db_backup_file, $matches) ? $matches[0] : date('Y-m-d_H-i-s', filemtime($this->database_backup_folder . $db_backup_file)),
					'part'			=> preg_match('#part_[0-9]{2}#', $db_backup_file, $matches) ? $matches[0] : ''
				);
			}
		}

		$filename = array();
		$size = array();
		$file_date = array();
		$part = array();
		$db_backups_list = $this->language->get('text_no_backups');
		if($db_backups) {
			foreach($db_backups as $key => $row) {
				$filename[$key] = $row['filename'];
				$size[$key] = $row['size'];
				$file_date[$key] = $row['file_date'];
				$part[$key] = $row['part'];
			}
			if(count($db_backups) > 1) {
				array_multisort($file_date, SORT_DESC, $part, SORT_ASC, $filename, $size, $db_backups);
			}
		
			$db_backups_list = '';
			foreach($db_backups as $backup) {
				$db_backups_list .= '<div style="width: 55%; float: left;"><a href="' . $this->database_backup_folder . $backup['filename'] . '">' . $backup['filename'] . '</a></div>';
				$db_backups_list .= '<div style="width: 15%; float: left; text-align: right;">' . $backup['size'] . '</div>';

				$db_backups_list .= '<div style="width: 15%; float: left; text-align: center;"><a href="' . $this->url->link('extension/tool/backup_pro/deleteBackup', '&del=' . $this->database_backup_folder . $backup['filename'] . '&user_token=' . $this->session->data['user_token'], $this->getSSL()) . '">delete</a></div>';
				$db_backups_list .= '<div style="clear: both;"></div>';
			}
		}

		// Whole Store Backups
		$ws_backup_files = scandir($this->backup_folder);
		$ws_backups = array();
		foreach($ws_backup_files as $ws_backup_file) {
			if(substr($ws_backup_file, -4) == '.tar' || substr($ws_backup_file, -4) == '.tgz' || substr($ws_backup_file, -4) == '.zip') {
				$ws_backups[] = array(
					'filename' 		=> $ws_backup_file,
					'size'			=> $this->formatSize(filesize($this->backup_folder . $ws_backup_file)),
					'file_date'		=> preg_match('#\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])_[0-2][0-9]-[0-5][0-9]-[0-5][0-9]#', $ws_backup_file, $matches) ? $matches[0] : date('Y-m-d_H-i-s', filemtime($this->database_backup_folder . $ws_backup_file)),
					'part'			=> preg_match('#part_[0-9]{2}#', $ws_backup_file, $matches) ? $matches[0] : 'part_01'
				);
			}
		}

		$filename = array();
		$size = array();
		$file_date = array();
		$part = array();		
		$ws_backups_list = $this->language->get('text_no_backups');
		if($ws_backups) {
			foreach($ws_backups as $key => $row) {
				$filename[$key] = $row['filename'];
				$size[$key] = $row['size'];
				$file_date[$key] = $row['file_date'];
				$part[$key] = $row['part'];
			}

			if(count($ws_backups) > 1) {
				array_multisort($file_date, SORT_DESC, $part, SORT_ASC, $filename, $size, $ws_backups);
			}
		
			$ws_backups_list = '';
			foreach($ws_backups as $backup) {
				$ws_backups_list .= '<div style="width: 55%; float: left;"><a href="' . $this->backup_folder . $backup['filename'] . '">' . $backup['filename'] . '</a></div>';
				$ws_backups_list .= '<div style="width: 15%; float: left; text-align: right;">' . $backup['size'] . '</div>';
				$ws_backups_list .= '<div style="width: 15%; float: left; text-align: center;">' . substr($backup['file_date'], 0, 10) . '</div>';
				$ws_backups_list .= '<div style="width: 15%; float: left; text-align: center;"><a href="' . $this->url->link('extension/tool/backup_pro/deleteBackup', '&del=' . realpath($this->backup_folder . $backup['filename']) . '&user_token=' . $this->session->data['user_token'], $this->getSSL()) . '">delete</a></div>';
				$ws_backups_list .= '<div style="clear: both;"></div>';
			}
		}

		$data['db_backups_list'] = $db_backups_list;
		$data['ws_backups_list'] = $ws_backups_list;
		$data['user_token'] = $this->session->data['user_token'];
		$data['session_id'] = $this->session->getId();
		$data['e404s'] = in_array(DB_PREFIX . '404s_report', $database_tables);
		$data['bots'] = in_array(DB_PREFIX . 'bots_report', $database_tables);
		
		if(is_file($this->log_folder . 'backup_pro.log')) {
			$data['log'] = $this->formatLog(file_get_contents($this->log_folder . 'backup_pro.log'));
		} else {
			$data['log'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/tool/backup_pro', $data));
	}



	
	public function backupDatabase() {

		$this->optimiseResources();
		
		$backup = $this->session->data['backup'];
		
		$db_max_size = $this->config->get('backup_pro_config_max_sql_size');
		if($db_max_size == '' || $db_max_size == 'Not Set') {
			$db_max_size = 0;
		}
		
		if(isset($this->session->data['db_backup_data'])) {
			$data = $this->session->data['db_backup_data'];
			
			// Debug
			if($backup['debug']) {
				$this->debug('Redirecting to avoid timeout . . .');
			}
		} else {
			// Debug
			if($backup['debug']) {
				$this->debug('Starting Database Backup');
			}
			
			$timestamp = date('Y-m-d_H-i-s');
			if($backup['what'] == 'db') {
				$filename = 'database_' . $timestamp;
			} else {
				$filename = 'database_clone';
			}
			
			$data = array(
				'complete'				=> false,
				'filename'				=> $filename,
				'timestamp'				=> $timestamp,
				'consolidate_backups'	=> $this->config->get('backup_pro_config_consolidate_zip'),
				'table'					=> '',
				'row'					=> 0,
				'file_size'				=> 0,
				'db_max_size'			=> $db_max_size,
				'part'					=> 1,
				'packet'				=> 1,
				'append'				=> 0,
				'ignore_excludes'		=> $backup['ignore_excludes'],
				'debug'					=> $backup['debug']
			);
		}
			
		if(!$data['complete']) {
			$this->load->model('extension/tool/backup_pro');
			$result = $this->model_extension_tool_backup_pro->backup($backup['tables'], $data);				
			$this->session->data['db_backup_data'] = $result;
			
			$this->response->redirect($this->url->link('extension/tool/backup_pro/backupDatabase', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));
		
		} else {
		
			if($db_max_size == 0) {
				$sql_filename = $this->database_backup_folder . $data['filename'] . '.sql';
				$sql_filesize = $this->formatSize(filesize($sql_filename));

				// Debug
				if(['debug']) {
					$this->debug('SQL Backup File Complete', $sql_filename . '  >  ' . $sql_filesize);
				}
				
				// Zip Files
				if($this->config->get('backup_pro_config_zip') && $backup['what'] == 'db') {
				
				// Zip Single database file
					
					// Debug
					if($backup['debug']) {
						$this->debug('Creating Zip Archive');
					}
					
					$archive = $backup['backup_filename'] . '_db_' . $data['timestamp'] . '.sql.zip';
					$destination = realpath($this->database_backup_folder) . '/' . $archive;
					$filename = $data['filename'] . '.sql';
						
					if($this->isShellZipEnabled()) { 
					
						$cmd = 'cd ' . realpath($this->database_backup_folder) . '; zip -v ' . $archive . ' ' . $filename . ' 2>&1';
						
						//Debug
						if($backup['debug']) {
							$this->debug('Zipping via Shell . . .', $cmd);
						}
						
						$output = shell_exec($cmd);
						
						// Debug
						if($backup['debug']) {
							$this->debug($output);
						}
						
						unlink($this->database_backup_folder . $filename);
					
					} else {
						$filename = $this->database_backup_folder . $filename;
						
						//Debug
						if($backup['debug']) {
							$this->debug('Zipping via PHP . . .');
						}

						$zip = new ZipArchive();
						if(!$zip->open($destination, ZIPARCHIVE::CREATE)) {

							//Debug
							if($backup['debug']) {
								$this->debug('Unable to create the ziparchive . . .');
							}
							die('Unable to create the ziparchive . . .');
							return false;
						}
						
						$zip->addFile($filename, 'database.sql');
						$zip->close();
						
						unlink($filename);
					}
					
					$this->session->data['download_file'] = $destination;
					$this->session->data['archive'] = $archive;
					
					// Debug
					if($backup['debug']) {
						$archive_filesize = $this->formatSize(filesize($destination));
						$this->debug('Zip Archive:' . $destination . '  >  ' . $archive_filesize, 'Backup Completed Successfully !');
					}
					
				}
				
			// Multiple database files
			} else {
				
				// Zip Files
				if($this->config->get('backup_pro_config_zip') && $backup['what'] == 'db') {
					
					if(!isset($data['archives'])) {
					
						$files = scandir($this->database_backup_folder);
						$archives = array();
						foreach($files as $file) {
							if(substr($file, -4) == '.sql' && strpos($file, $data['timestamp']) !== false && $backup['what'] == 'db') {
								$archives[] = $file;
							}
						}
						
						$data['archives'] = $archives;
						$this->session->data['db_backup_data'] = $data;
					}
					
					foreach($data['archives'] as $file) {
						$ar = $this->database_backup_folder . $file . '.zip';
						if(!is_file($ar)) {						
							
							// Debug
							if($backup['debug']) {
								$this->debug('Zipping ' . $file);
							}

							$zip = new ZipArchive();
							if(!$zip->open($ar, ZIPARCHIVE::CREATE)) {
								die('Unable to create the ziparchive . . .');
								return false;
							}
											
							$zip->addFile($this->database_backup_folder . $file, 'database.sql');
							$zip->close();
							$this->response->redirect($this->url->link('extension/tool/backup_pro/backupDatabase', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));
						}
					}
					foreach($data['archives'] as $file) {
						unlink($this->database_backup_folder . $file);
					}


					if($data['consolidate_backups']) {
						
						$archive = $this->database_backup_folder . $this->config->get('backup_pro_config_filename') . '_db_' . $data['timestamp'] . '.sql.zip';
						if(!is_file($archive)) {	

							// Debug
							if($backup['debug']) {
								$this->debug('Consolidating multiple backups: ' . $archive);
							}
						
							$zip = new ZipArchive();
							if(!$zip->open($archive, ZIPARCHIVE::CREATE)) {
								die('Unable to create the ziparchive . . .');
								return false;
							}
							foreach($data['archives'] as $file) {
								$zip->addFile($this->database_backup_folder . $file . '.zip', $file . '.zip');
							}		
							$zip->close();
							
							$this->session->data['download_file'] = $archive;
							$this->session->data['archive'] = $archive;
							
							// Debug
							if($backup['debug']) {
								$this->debug('Removing temporary files');
							}
							
							// Remove Temporary files
							foreach($data['archives'] as $file) {								
								unlink($this->database_backup_folder . $file . '.zip');
							}
						}
					}
				}
			}				// End of Multiple Files
			
			if($backup['what'] == 'db') {
				$this->load->language('extension/tool/backup_pro');
				$this->session->data['success'] = $this->language->get('text_success_database');

				// Debug
				if($backup['debug']) {
					$this->debug('Backup Completed Successfully');
				}
				

				
				$this->response->redirect($this->url->link('extension/tool/backup_pro', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));
			} else {

				// Debug
				if($backup['debug']) {
					$this->debug('Database Backup Completed Successfully. Moving on to Whole Store Files');
				}
				$this->response->redirect($this->url->link('extension/tool/backup_pro/backupWholeStore', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));
			}
		}		
	}
		
	
	public function backupWholeStore() {
		
		$this->optimiseResources();
		$backup = $this->session->data['backup'];
		$excludes_file = $this->excludes_folder . 'excludes.txt';
		
		if($backup['step'] == 'database') {
				
			// If storage folder is OUTSIDE of store folder
			$store_folder = substr(DIR_SYSTEM, 0, -7);
			if(strpos(DIR_STORAGE, $store_folder) === false) {

				// Debug
				if($backup['debug']) {
					$this->debug('Making temporary backup of storage folder');
				}
				
				$this->deleteStorageFolderCopy();
				mkdir($this->storage_folder, 0755);

				$this->copyStorageFolder(substr(DIR_STORAGE, 0, -1), substr($this->storage_folder, 0, -1));
			}
			
			// Move database_clone.sql to backup_pro folder
			$files = scandir($this->database_backup_folder);
			foreach($files as $file) {
				if(strpos($file, 'database_clone') === 0) {
					rename($this->database_backup_folder . $file, $this->backup_folder . $file);
				}
			}
		
		}

		if($this->isTarEnabled()) {
			
			// TAR backup
			
			$filename = $this->config->get('backup_pro_config_filename');
					
			if($this->backup_filetype == '.tgz') {
				$operations = ' czf ';
			} else {
				$operations = ' cf ';
			}
			
			$tar = exec("command -v tar");
			
			$archive = $this->backup_folder . $filename . '_all_' . date('Y-m-d_H-i-s', time()) . $this->backup_filetype;
			$cmd = $tar . $operations . $archive . " -X " . $excludes_file . " ../* > tar.log 2>&1";
			
			// Debug
			if($backup['debug']) {
				$this->debug('Creating Whole Store archive.', $cmd);
			}

			copy('../config.php', '../_config.php');
			copy('config.php', '_config.php');
			if(is_file('../.htaccess')) {
				copy('../.htaccess', '../htaccess.txt');
			}
			
			$this->session->data['wholeStoreTar'] = array(
				'url'		=> 'index.php?route=extension/tool/backup_pro/wholeStoreTar&user_token=' . $this->session->data['user_token'],
				'cmd'		=> $cmd,
				'debug'		=> $backup['debug']
			);
			
			
			$this->load->language('extension/tool/backup_pro');
			$this->session->data['success'] = $this->language->get('text_success_backup');
			
			$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=ws&user_token=' . $this->session->data['user_token'], true));			
		} else {
			
			// Zipped Backup

			if(!isset($backup['excludes'])) {
				$starts_with = array();
				$folders = array();
				$matches = array();
				$excluded_files = file_get_contents($excludes_file);
				$filetypes = array();
				$excludes = explode("\n", $excluded_files);
				foreach($excludes as $exclude) {
					if(strpos(trim($exclude), '../') === 0) {
						$starts_with[] = substr(trim($exclude), 3);
					} elseif(substr(trim($exclude), -1) == '/' || substr(trim($exclude), -2) == '/*') {
						$matches[] = trim(str_replace('*', '', $exclude));
						$folders[] = substr(trim(str_replace('*', '', $exclude)), 0, -1);
					} elseif(strpos(trim($exclude), '*.') === 0) {
						$filetypes[] = trim(str_replace('*', '', $exclude));
					} else {
						$matches[] = trim($exclude);
					}
				}
				$backup['excludes'] = array(
					'starts_with'		=> $starts_with,
					'folders'			=> $folders,
					'filetypes'			=> $filetypes,
					'matches'			=> $matches
				);
			} 
			
			if($backup['step'] == 'database') {
			
				// Debug
				if($backup['debug']) {
					$this->debug('Listing files to be archived.');
				}

				// List Files (including database)
				$filename = $this->config->get('backup_pro_config_filename') . '_all';
				
				$this->session->data['create_zip_folders'] = array();
				$archive = $this->database_backup_folder . $filename;
				$root = str_replace('\\', '/', substr(DIR_SYSTEM, 0, -7));
				$sources = array($root);
				
				$this->listFiles($sources, $backup['excludes']);
				$this->load->model('extension/tool/backup_pro');

				$backup['step'] = 'zip';				
				$backup['filename'] = $filename;
				$backup['archive'] = $archive;
				$backup['part'] = 1;
				$backup['parts'] = $this->model_extension_tool_backup_pro->getNoOfParts();
				$backup['timestamp'] = date('Y-m-d_H-i-s', time());
				$backup['create_zip_folders'] = $this->session->data['create_zip_folders'];
				$backup['skipped_files'] = array();
				$backup['archives'] = array();
				$this->session->data['backup'] = $backup;
				
				// Debug
				if($backup['debug']) {
					$this->debug('Starting zip archive - ' . $backup['parts'] . ' parts.');
				}

				$this->response->redirect($this->url->link('extension/tool/backup_pro/backupWholeStore', 'user_token=' . $this->session->data['user_token'], true));			
			} elseif($backup['step'] == 'zip') {
				
				$part = $backup['part'];
				$skipped = false;
				$skipped_files = $backup['skipped_files'];
				
				// Zip Files
				if($backup['parts'] == 1) {
					$archive = $backup['filename'] . '_' . $backup['timestamp'] . '.zip';
				} elseif($backup['part'] <= 9) {
					$archive = $backup['filename'] . '_' . $backup['timestamp'] . '_part_0' . trim($backup['part']) . '.zip';
				} else {
					$archive = $backup['filename'] . '_' . $backup['timestamp'] . '_part_' . trim($backup['part']) . '.zip';
				}
				$root = str_replace('\\', '/', substr(DIR_SYSTEM, 0, -7));
				$destination = realpath($this->backup_folder) . '/' . $archive;
				

				
				$zip = new ZipArchive();
				if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
					return false;
				}
				$backup['archives'][] = $archive;
				
				if ($part == 1) {
					$query = $this->db->query("SELECT `file_name` FROM " . DB_PREFIX . "backup WHERE `type` = 'DIR'");
					
					if ($query->rows) {
						foreach ($query->rows as $result) {
							$zip->addEmptyDir($result['file_name']);			
						}
					}
				}
				
				$query = $this->db->query("SELECT `file_name` FROM " . DB_PREFIX . "backup WHERE `type` = 'FILE' AND `part` = '" . $part . "'");
				foreach ($query->rows as $result) {
					if (is_file($root . $result['file_name']) && is_readable($root . $result['file_name'])) {
					
						// Rename config files
						if ($result['file_name'] == 'config.php') {
							$zip->addFile($root . $result['file_name'], '_config.php');
						} elseif ($result['file_name'] == 'admin/config.php') {
							$zip->addFile($root . $result['file_name'], 'admin/_config.php');
						} else {
							$zip->addFile($root . $result['file_name'], $result['file_name']);
						} 
					} else {
						// File has been skipped and needs to be logged
						$skipped = TRUE;
						$skipped_files[] = $root . $result['file_name'];
						$this->log->write('BACKUP PRO WARNING - ' . $result['file_name'] . ' was not readable and was not included in the backup archive!');
					}
				}
				
				$zip->close();
				
				// Debug
				if($backup['debug']) {
					$this->debug('Successfully zipped part ' . $backup['part']);
				}
				
				
				if ($backup['part'] < $backup['parts']) {
					$backup['part'] = $backup['part'] + 1;
					if ($skipped) {
						$backup['skipped_files'] = $skipped_files;
					}
					$this->session->data['backup'] = $backup;
					$this->response->redirect($this->url->link('extension/tool/backup_pro/backupWholeStore', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));			
				} else {
					$backup['step'] = 'consolidate';
					$this->session->data['backup'] = $backup;
					$list = '';

					$this->response->redirect($this->url->link('extension/tool/backup_pro/backupWholeStore', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));			
				}				
						
			} else {
				// consolidate backup files
				
				if($backup['parts'] > 1 && $this->config->get('backup_pro_config_combine_download')) {
					if($backup['debug']) {
						$this->debug('Consolidating backup archives into a single file');
					}
				
					$root = str_replace('\\', '/', substr(DIR_SYSTEM, 0, -7));
					$archive = 	$backup['filename'] . '_' . $backup['timestamp'] . '.zip';			
					$destination = realpath($this->backup_folder) . '/' . $archive;
					// Debug
					if($backup['debug']) {
						$this->debug($destination);					
					}
					$zip = new ZipArchive();
					if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
					// Debug
						if($backup['debug']) {
							$this->debug('Creating Zip Archive Failed');											
						}
						return false;
					}
					
					foreach($backup['archives'] as $ziparchive) {
					// Debug
						if($backup['debug']) {
							$this->debug(realpath($this->backup_folder) . '/' . $ziparchive);					
						}
						$zip->addFile(realpath($this->backup_folder) . '/' . $ziparchive, $ziparchive);
					}
					
					$zip->close();

					// Debug
					if($backup['debug']) {
						$this->debug('Successfully consolidated archives');
					}
				}
				
				
				// Prepare Skipped files message and final housekeeping
				
				$this->load->language('extension/tool/backup_pro');
				if (count($backup['skipped_files'])) {

				// Format then render the message page
					
					$list = '<ul>';
					foreach ($skipped_files as $skipped_file) {
						$list .= '<li>' . $skipped_file . '</li>';
					}
					$list .= '</ul>';
					$this->session->data['error_warning'] = $this->language->get('error_warning')  . $list; 
				}
				if (!empty($this->session->data['zipfiles'])) {
					$list .= $this->language->get('error_warning_zipfiles');
					$list .= '<ul>';
					foreach ($this->session->data['zipfiles'] as $zipfile) {
						$list .= '<li>' . $zipfile . '</li>';			
					}
					$list .= '</ul>';
					$this->session->data['error_warning'] = $list; 
				}
				
				
				// Tidy Up Temporary Files
				
				if(file_exists($this->database_backup_folder . 'database.sql')) {
					unlink($this->database_backup_folder . 'database.sql');
				}
				if(file_exists($this->database_backup_folder . 'database_clone.sql')) {
					unlink($this->database_backup_folder . 'database_clone.sql');
				}
				unset($this->session->data['part']);
				unset($this->session->data['parts']);
				unset($this->session->data['filename']);
				unset($this->session->data['limit']);
				unset($this->session->data['timestamp']); 
				unset($this->session->data['zipfiles']);
				
				$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "backup`");
				
				$this->deleteStorageFolderCopy();
				$this->session->data['success'] = $this->language->get('text_success_wholestore');
					
				$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=ws&user_token=' . $this->session->data['user_token'], $this->getSSL()));
			}
			
		}
	}


	public function wholeStoreTar() {
		
//		ignore_user_abort(true); //if connect is close, we continue php script in background up to script will be end
		
		if(isset($this->request->post['debug']) && isset($this->request->post['cmd'])) {
			
			$cmd = html_entity_decode($this->request->post['cmd']);			
			exec($cmd);
			
			$files = scandir($this->database_backup_folder);
			foreach($files as $file) {
				if(substr($file, -4) == '.sql' && strpos($file, 'database_clone') !== false) {
					unlink($this->backup_folder . $file);
				}
			}
			
			$this->deleteStorageFolderCopy();
			
			// Debug
			if($this->request->get['debug']) {
				$this->debug('Whole Store Backup Completed Successfully');
			}
		}
		echo 'done !';
	}
	

	public function download() {
			
		$this->optimiseResources();

		if(isset($this->session->data['backup'])) {
			$data = $this->session->data['backup'];
		}

		$destination = $this->session->data['download_file'];
		$archive = $this->session->data['archive'];
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"".$archive."\"");
		header("Content-Transfer-Encoding: binary");
		// make sure the file size isn't cached
		clearstatcache();
		header("Content-Length: ".filesize($destination));
		// output the file
		readfile($destination);
		
		unset($this->session->data['archive']);
		unset($this->session->data['download_file']);
		unset($this->session->data['backup']);
		unset($this->session->data['db_backup_data']);

		if(!$data['save_db2server']) {
			unlink($destination);
			// Debug
			if($data['debug']) {
				$this->debug('$archive = ' . $archive);
				$this->debug('$destination = ' . $destination);
				$this->debug('Deleting Backup File');
			}
		}
	
	}

	
	public function restore() {
	
		$this->optimiseResources();

		ignore_user_abort(true);
		$start_time = microtime(true);
		
		$row = 0;
		
		if($this->config->get('backup_pro_config_restore_time_limit') != '') {
			$max_ex_time = $this->config->get('backup_pro_config_restore_time_limit');
		} else {
			$max_ex_time = $this->restore_time_limit;
		}
		
		if(isset($this->request->get['start'])) {
			$start = $this->request->get['start'];
		} else {
			$start = 0;	
		}
		
		if(isset($this->request->get['debug'])) {
			$debug = $this->request->get['debug'];
		} else {
			$debug = 0;	
		}
		
		if(isset($this->request->get['sql'])) {
			$sql_file = $this->request->get['sql'];
		} else {
			$sql_file = false;
		}
	
	
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['debug'])) {
			if ($this->user->hasPermission('modify', 'extension/tool/backup_pro')) {
				
				$debug = $this->request->post['debug'];
				// Debug
				if($debug) {
					$this->resetDebug();
					$this->debug('Starting Restore . . .');
				}
				
				if($this->request->files['import']['error'] == 4) {
					$this->language->load('extension/tool/backup_pro');
					$this->session->data['error_warning'] = $this->language->get('error_nofile');
					$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
				}
				
				if (is_uploaded_file($this->request->files['import']['tmp_name'])) {
				
					if ($this->request->files['import']['type'] == 'application/x-zip-compressed' || $this->request->files['import']['type'] == 'application/zip') {
						$sql_file = $this->unzip($this->request->files['import']['tmp_name']);

						// Debug
						if($debug) {
							$this->debug($sql_file);
						}
						
					} elseif (substr($this->request->files['import']['name'], -4) == '.sql' && $this->request->files['import']['type'] == 'application/octet-stream') {
						$sql_file = $this->request->files['import']['tmp_name'];				

						// Debug
						if($debug) {
							$this->debug($sql_file);
						}
					}				
				} else {
					$this->language->load('extension/tool/backup_pro');
					$this->session->data['error_warning'] = $this->language->get('error_empty');
					$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
				}
				
			} else {
				$this->language->load('extension/tool/backup_pro');
				$this->session->data['error_warning'] = $this->language->get('error_permission');
				$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
			}
		}
		
		if ($sql_file) {
		
			$this->load->model('extension/tool/backup_pro');
			
			$fp = fopen($sql_file, 'r');
			$sql = '';
			while (!feof($fp)) {
				$line = fgets($fp);
				$row++;
				
				if($row >= $start) {
					
					// Debug
					if($debug && substr($line, 0, 10) == 'DROP TABLE') {
						$first = strpos($line, '`') + 1;
						$last = strpos($line, '`', $first);
						$this->debug('Restoring table: ' . substr($line, $first, $last - $first));
					}
					
					$sql .= $line;
					if (substr(trim($line), -1) == ';') {
						$this->model_extension_tool_backup_pro->restore($sql);
						
						if(microtime(true) - $start_time > $max_ex_time) {
							$row++;
							fclose($fp);
							
							// Debug
							if($debug) {
								$this->debug('Redirecting to avoid timeout . . .');
							}
							$this->response->redirect($this->url->link('extension/tool/backup_pro/restore', 'start=' . $row . '&debug=' . (int)$debug . '&sql=' . realpath($sql_file) . '&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
						}
					
						$sql = '';
					}
				}
			}
			fclose($fp);
			// Debug
			if($debug) {
				$this->debug('Successfully Restored Database');
			}
			
			unlink($sql_file);
			
			$this->language->load('extension/tool/backup_pro');
			$this->session->data['success'] = $this->language->get('text_success_restore');
			
			$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
		} else {
			$this->language->load('extension/tool/backup_pro');
			$this->session->data['error_warning'] = $this->language->get('error_empty');
			$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
		}
		
	
	}


	public function restore_clone() {
	
		ignore_user_abort(1);
		$start_time = microtime(true);

		if ($this->user->hasPermission('modify', 'extension/tool/backup_pro')) {

			$this->optimiseResources();
			$row = 0;
			$max_ex_time = $this->config->get('backup_pro_config_max_time');
			
			if(isset($this->request->get['start'])) {
				$start = $this->request->get['start'];
			} else {
				$start = 0;	
			}
		
			$sql_file = $this->database_backup_folder . 'database_clone.sql';
					
			if ($sql_file) {
		
				$this->load->model('extension/tool/backup_pro');
				
				$fp = fopen($sql_file, 'r');
				$sql = '';
				while (!feof($fp)) {
					$line = fgets($fp);
					
					if($row >= $start) {
						if (strpos(trim($line), '--') === 0) {
							$this->model_extension_tool_backup_pro->restore($sql);
							
							if(microtime(true) - $start_time > $max_ex_time) {
								$row++;
								fclose($fp);
								
								$this->response->redirect($this->url->link('extension/tool/backup_pro/restore_clone', 'start=' . $row . '&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
							}
						
							$sql = '';
						} else {
							$sql .= $line;
						}
					}
					$row++;
				}
				fclose($fp);
				
				if($sql) {
					$this->model_extension_tool_backup_pro->restore($sql);
				}
				unlink($sql_file);
				
				$this->language->load('extension/tool/backup_pro');
				$this->session->data['success'] = $this->language->get('text_success_restore');
				
				$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
			} else {
				$this->language->load('extension/tool/backup_pro');
				$this->error['warning'] = $this->language->get('error_empty');
			}
			
		} else {
			$this->language->load('extension/tool/backup_pro');
			$this->session->data['error_warning'] = $this->language->get('error_permission');
			
			$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
		}
	
	}
	
	
	public function delete_clone() {
		
		if ($this->user->hasPermission('modify', 'extension/tool/backup_pro')) {
			
			unlink($this->database_backup_folder . 'database_clone.sql');
			
			$this->language->load('extension/tool/backup_pro');
			$this->session->data['success'] = $this->language->get('text_success_delete_clone');
			
				$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
								
		} else {
			$this->language->load('extension/tool/backup_pro');
			$this->session->data['error_warning'] = $this->language->get('error_permission');
			
			$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=restore&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
		}
	
	}


	public function testEmail() {
		
		$archive = $this->zipDatabase();
		
		$this->load->model('extension/tool/backup_pro');
		$this->model_extension_tool_backup_pro->testEmail($archive);
					
		$this->load->language('extension/tool/backup_pro');
		$this->session->data['success'] = $this->language->get('text_check_email');
		
		unset($this->session->data['database_tables']);
				
		$this->response->redirect($this->url->link('extension/tool/backup_pro', 'goto=scheduled&user_token=' . $this->session->data['user_token'], $this->getSSL()));			
	}


	public function unzip($filename) {
	
		$this->optimiseResources();
	
		$zip = new ZipArchive;
		if ($zip->open($filename) === true) {
			for($i = 0; $i < $zip->numFiles; $i++) { 
				$entry = $zip->getNameIndex($i);
				if(preg_match('#\.(sql)$#i', $entry)) {

					$zip->extractTo($this->database_backup_folder, array($zip->getNameIndex($i)));
					$sql_file = $this->database_backup_folder . $zip->getNameIndex($i);
					break;
				} 
			}  
			$zip->close();
			return $sql_file;
				 
		} else{
			return false;
		}	
	}

	
	public function deleteBackup() {

		if(isset($this->request->get['del']) && $this->user->hasPermission('modify', 'extension/tool/backup_pro')) {
			
			if(is_file($this->request->get['del'])) {
				unlink($this->request->get['del']);
			}
		} 
		
		$this->response->redirect($this->url->link('extension/tool/backup_pro', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));			
		
	}

	
	public function housekeeping() {

		if(isset($this->request->post['session_id'])) {
			$session_id = $this->request->post['session_id'];
			$this->load->model('extension/tool/backup_pro');
			
			if(isset($this->request->post['nf_404_report']) && $this->request->post['nf_404_report'] == '1') {
				$this->model_extension_tool_backup_pro->housekeep404s();
			}
		
			if(isset($this->request->post['bots_report']) && $this->request->post['bots_report'] == '1') {
				$this->model_extension_tool_backup_pro->housekeepBotsReport();
			}
		
			if(isset($this->request->post['session']) && $this->request->post['session'] == '1') {
				$this->model_extension_tool_backup_pro->housekeepSession($session_id);
			}
		}
		
		$html = 'abcde';
		$this->response->setOutput($html);		
		
	}
	
	
	public function dbAnalysis() {
		if(isset($this->request->post['file'])) {
			$sql_file = $this->request->post['file'];
		} else {
			if(is_file('../backup_pro/database/database.sql')) {
				$sql_file = '../backup_pro/database/database.sql';
			} elseif(is_file('../backup_pro/database/database_clone.sql')) {
				$sql_file = '../backup_pro/database/database_clone.sql';
			} else {
				$sql_file = '';
			}
		}
		
		if(is_file($sql_file)) {
		
			$sort = 'size';
			if(isset($_GET['sort'])) {
				$sort = $_GET['sort'];
			}
			
			$sort_order = SORT_DESC;
			if($sort == 'size') {
				$size_class = ' bold';
				$max_packet_class = '';
				$name_class = '';
			} elseif($sort == 'table') {
				$size_class = '';
				$max_packet_class = '';
				$name_class = ' bold';
				$sort_order = SORT_ASC;
			} else {
				$size_class = '';
				$max_packet_class = ' bold';
				$name_class = '';
			}
			$url_m = 'db_sql_analysis.php?sort=max_packet&f=' . $sql_file;
			$url_s = 'db_sql_analysis.php?sort=size&f=' . $sql_file;
			$url_n = 'db_sql_analysis.php?sort=table&f=' . $sql_file;

			$data = array();

			$fp = fopen($sql_file, 'r');
			$sql = '';
			$table = '';
			$last_table = '';
			$sql = '';
			$size = 0;
			$queries = 0;
			$rows = 0;
			$max_packet = 0;
			$packet = 0;
			$complete = false;
			
			$ret = "\n";
			$html = '';
			
			while (!feof($fp)) {
				$line = fgets($fp);
				$line = trim($line);
				$size += strlen($line);
				$packet += strlen($line);
				if(strpos($line, 'DROP TABLE') === 0) {
					$table = $this->getTableName($line);
					if($table != $last_table) {
	//					fwrite($fo, $last_table . ',' . $queries . ',' . $rows . ',' . $max_packet . ',' . $size . "\n");
						if($last_table != '') {
							$data[] = array (
								'table'				=> $last_table,
								'queries'			=> $queries,
								'rows'				=> $rows,
								'max_packet'		=> $max_packet,
								'size'				=> $size,
								'str_max_packet'	=> $this->formatSize($max_packet),
								'str_size'			=> $this->formatSize($size)
							);
						}
						$last_table = $table;
						$size = 0;
						$queries = 0;
						$rows = 0;
						$max_packet = 0;
					}
						
				}
				if(substr($line, -1) === ';') {
					$queries++;
					if($max_packet < $packet) {
						$max_packet = $packet;
					}
					$packet = 0;
					
				} 
				
				if(substr($line, 0, 1) == '(') {
					$rows ++;
				}
				
				if(strpos($line, 'SET FOREIGN_KEY_CHECKS=1') === 0) {
					$complete = true;
				}
				
			}
			fclose($fp);

			if($last_table != '') {
				$data[] = array (
					'table'				=> $last_table,
					'queries'			=> $queries,
					'rows'				=> $rows,
					'max_packet'		=> $max_packet,
					'size'				=> $size,
					'str_max_packet'	=> $this->formatSize($max_packet),
					'str_size'			=> $this->formatSize($size)
				);
			}
			
//			$columns = array_column($data, $sort);
//			array_multisort($columns, $sort_order, $data);
			
			$no_of_tables = count($data);
			$filesize = $this->formatSize(filesize(realpath($sql_file)));
			$date = date ("d F Y H:i:s.", filemtime(realpath($sql_file)));
			$completed = $complete ? '<i class="fa fa-thumbs-o-up text-success"></i>' : '<i class="fa fa-thumbs-o-down text-danger"></i>';
			
			
			$html .= '	<button class="btn btn-warning pull-right" onclick="clearDbAnalysis()">Clear</button>' . $ret;
			$html .= '	<h2>Database Analysis</h2>' . $ret;
			$html .= '	<strong>File:</strong> ' . $sql_file . '<br>' . $ret;
			$html .= '	<strong>Size:</strong> ' . $filesize . '<br>' . $ret;
			$html .= '	<strong>Date:</strong> ' . $date . '<br>' . $ret;
			$html .= '	<strong>Tables:</strong> ' . $no_of_tables . '<br>' . $ret;
			$html .= '	<strong>Last Table Backed Up:</strong> ' . $last_table . '<br>' . $ret;
			$html .= '	<strong>Completed:</strong> ' . $completed . '<br><br><br>' . $ret;

/*		
			$html .= '	<h3>Sorted By <?php echo $sort; ?></h3>' . $ret;
			$html .= '	<a href="<?php echo $url_n; ?>">Sort By Table Name</a><br>' . $ret;
			$html .= '	<a href="<?php echo $url_m; ?>">Sort By Max Packet</a><br>' . $ret;
			$html .= '	<a href="<?php echo $url_s; ?>">Sort By Size</a><br>' . $ret;
*/

			$html .= '	<table class="table table-striped">' . $ret;
			$html .= '	  <thead>' . $ret;
			$html .= '		<tr>' . $ret;
			$html .= '		  <td>Table Name</td>' . $ret;
			$html .= '		  <td class="text-right">Queries</td>' . $ret;
			$html .= '		  <td class="text-right">Rows</td>' . $ret;
			$html .= '		  <td class="text-right">Max Packet</td>' . $ret;
			$html .= '		  <td class="text-right">Size</td>' . $ret;
			$html .= '		</tr>' . $ret;
			$html .= '	  </thead>' . $ret;
			$html .= '	  <tbody>' . $ret;
			foreach($data as $row) {
				$html .= '		<tr>' . $ret;
				$html .= '		<td class="text-left' . $name_class . '">' . $row['table'] . '</td>' . $ret;
				$html .= '		  <td class="text-right">' . $row['queries'] . '</td>' . $ret;
				$html .= '		  <td class="text-right">' . $row['rows'] . '</td>' . $ret;
				$html .= '		  <td class="text-right' . $max_packet_class . '">' . $row['str_max_packet'] . '</td>' . $ret;
				$html .= '		  <td class="text-right' . $size_class . '">' . $row['str_size'] . '</td>' . $ret;
				$html .= '		</tr>' . $ret;
			}
			$html .= '	  </tbody>' . $ret;
			$html .= '	</table>' . $ret;

		} else {
			$ret = "\n";
			$html = '';
			
			$html .= '	<h2>Database Analysis</h2>' . $ret;
			$html .= '	Database File not found.<br>' . $ret;
		}
		
		$this->response->setOutput($html);
//		echo $html;
	}

	
	public function memTest() {

		error_reporting(E_ALL); 
		ini_set('display_errors', 1);

 		ini_set('memory_limit', '1024M');
		
		$v = '';
		for($i = 0; $i< (1024 * 1024); $i++) {
			$v .= '################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################################';
		}

		$a = array();
		
		echo strlen($v) . '<br>';

		for($i = 0; $i< 40960; $i++) {
		//echo $i;
			$a[] = $v;
		}
		echo count($a) . '<br>';
		echo 'You can set the memory limit to 1024M. This is sufficient for Backup Pro.';

	}

	
	public function maxExTime() {
	
		error_reporting(E_ALL); 
		ini_set('display_errors', 1);


		ini_set('max_execution_time', 300);

		$start = microtime(true);

		$ctr = 0;
		while ($ctr <= 100000) {
		   for($i=1; $i<=1000000; $i++) {
			$a = 1;
			$b = 2;
			$c = $a;
			$a = $b;   
		   }
		   $end = microtime(true);
		   $total_time = (int)($end - $start);
		   $fp = fopen('../system/storage/logs/max_ex_time.txt', 'w');
		   fwrite($fp, 'Elapsed time:  ' . $total_time . ' seconds');
		   fclose($fp);
		   $ctr++;
		}
	
	}
	
			
	private function listFiles($sources = array(), $excludes = array()) {
	
		$this->optimiseResources();

		if($this->config->get('backup_pro_config_limit') == 'All Files') {
			$limit = 10000;
		} else {
			$limit = $this->config->get('backup_pro_config_limit');
		}
		$max_files = $this->config->get('backup_pro_config_max_files');
		$this->session->data['zipfiles'] = array();
		
		$part = 1;
		$backup_size = 0;
		$ctr = 0;
		$folders = array();
		
	// Put the file data into the backup table
	
		$this->db->query("TRUNCATE TABLE " . DB_PREFIX . "backup");
		$root = str_replace('\\', '/', substr(DIR_SYSTEM, 0, -7));

		foreach ($sources as $source) {
			$source = str_replace('\\', '/', realpath($source));

			if (is_dir($source) === true) {
				if($source != substr($root, 0, -1)) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "backup (`file_name`, `type`, `size`, `part`) VALUES ('" . str_replace($root, '', $source) . "', 'DIR', '0', '1')");
				}
					
				$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
				
				foreach ($files as $file) {
					$path_info = pathinfo($file);
					$folder = $path_info['dirname'];
					if(!in_array($folder, $folders)) {
						$folders[] = $folder;
					}
					
					if($file->isDir()) {
						continue;
					}
					$file = str_replace('\\', '/', $file);


					if(is_file($file) === true && $this->includeFile($file, $excludes)) {
						
						$ctr++;
						if ($limit) {
							if(($backup_size + filesize($file) >= $limit * 1048576 && $ctr >= 1) || $ctr >= $max_files) {
								$part++;
								$ctr = 0;
								$backup_size = 0;
							}	
							$backup_size = $backup_size + filesize($file);
						}
						$this->db->query("INSERT INTO " . DB_PREFIX . "backup (`file_name`, `type`, `size`, `part`) VALUES ('" . str_replace($root, '', $this->db->escape($file)) . "', 'FILE', '" . filesize($file) . "', '" . $part . "')");
					}
					
				}
				
				foreach($folders as $folder) {
					foreach($excludes['folders'] as $find) {
						if(substr($folder, -strlen($find)) == $find) {
							$this->session->data['create_zip_folders'][] = str_replace($root, '', $folder);
						}
					}
				}
				
			} else if (is_file($source) === true) {
				if ($limit) {
					$backup_size = $backup_size + filesize($source);
					if ($backup_size >= $limit * 1048576) {
						$part++;
						$backup_size = 0;
					}	
				}
				$this->db->query("INSERT INTO " . DB_PREFIX . "backup (`file_name`, `type`, `size`, `part`) VALUES ('" . str_replace($root, '', $this->db->escape($source)) . "', 'FILE', '" . filesize($source) . "', '" . $part . "')");
			}
		}
		$this->session->data['parts'] = $part;
	}


	private function includeFile($file, $excludes) {
	
		$root = str_replace('\\', '/', substr(DIR_SYSTEM, 0, -7));
		$test_string = str_replace($root, '', $file);

		foreach($excludes as $key => $list) {
			if($key == 'starts_with') {
				foreach($list as $exclude) {
					if(strpos($test_string, $exclude) === 0) {
						return false;
					}
				}
			} elseif($key == 'folders') {
				foreach($list as $exclude) {
					if(strpos($test_string, $exclude) !== false) {
						if(is_dir($file)) {
							$this->session->data['create_zip_folders'][] = $file;
						}
					}
				}
			} elseif($key == 'filetypes') {
				foreach($list as $exclude) {
					if(substr($test_string, -strlen($exclude)) == $exclude) {
						return false;
					}
				}
			} elseif($key == 'matches') {
				foreach($list as $exclude) {
					if(strpos($test_string, $exclude) !== false) {
						return false;
					}
				}
			}
		}
		return true;
	}


	private function getFiles($sources = array()) {
	
		$root = str_replace('\\', '/', substr(DIR_SYSTEM, 0, -7));
		$ctr = 0;
		$size = 0;

		foreach ($sources as $source) {
			$source = str_replace('\\', '/', realpath($source));

			if (is_dir($source) === true) {
					
				$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
				
				foreach ($files as $file) {
					$file = str_replace('\\', '/', $file);

					// Ignore "." and ".." folders
					if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ) {
						continue;
					}
					$file = realpath($file);
					$file = str_replace('\\', '/', $file);
				
					if (is_dir($file) === true && !strpos($file, '/cache/')) {
 
					} elseif (is_file($file) === true && !strpos($file, '/cache/')) {
					
						if(substr($file, -4) == '.zip' || substr($file, -3) == '.gz' || substr($file, -4) == '.tgz') {
							continue;
						} else {
							$ctr++;
							$size = $size + filesize($file);
						}
					}
					
				}
			}
		}
		$wholestore = array(
			'no_of_files'		=> $ctr,
			'size_of_files'		=> $size
		);
		return $wholestore;
	}


	private function copyStorageFolder($source, $dest) {
		if(is_dir($source)) {
			$dir_handle=opendir($source);
			while($file=readdir($dir_handle)){
				if($file!="." && $file!=".."){
					if(is_dir($source."/".$file)){
						if(!is_dir($dest."/".$file)){
							mkdir($dest."/".$file, 0755);
						}
						$this->copyStorageFolder($source."/".$file, $dest."/".$file);
					} else {
						copy($source."/".$file, $dest."/".$file);
					}
				}
			}
			closedir($dir_handle);
		} else {
			copy($source, $dest);
		}
	}
	
	
	private function deleteStorageFolderCopy() {
		$this->rrmdir(realpath($this->storage_folder));
	}
	
	
	private function deleteCopyStorageFolder() {
		$this->rrmdir(realpath($this->storage_folder));
		$this->session->data['success'] = 'Success! The backup version of the Storage folder has been successfully deleted.';
		$this->response->redirect($this->url->link('extension/tool/backup_pro', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));					
	}
	
	
	private function restoreStorageFolder() {
		$this->copyStorageFolder($this->storage_folder, DIR_STORAGE);
		$this->deleteStorageFolderCopy();

		$this->session->data['success'] = 'Success! The Storage folder has been successfully restored.';
		$this->response->redirect($this->url->link('extension/tool/backup_pro', 'user_token=' . $this->session->data['user_token'], $this->getSSL()));					
	}
	

	private function formatSize($size) {
		if($size >= 1048576 * 1024) {
			$resize = number_format(($size / 1048576 / 1024), 1) . ' Gb';
		} elseif($size >= 1048576) {
			$resize = number_format(($size / 1048576), 1) . ' Mb';
		} elseif($size >= 1024) {
			$resize = number_format(($size / 1024), 1) . ' Kb';
		} else {
			$resize = $size . ' bytes';
		}
		return $resize;
	}

	
	private function convert_text($text) {
		return str_replace(' ', '-', strtolower($text));

	}
	
	
	private function isExecEnabled() {
    	$disabled_functions = explode(',', ini_get('disable_functions'));
    	return function_exists('exec') && !in_array('exec', $disabled_functions);
	}

	private function isTarEnabled() {
	
		$result = $this->isExecEnabled();
		
		$tar = null;
		
		if ($result) {
			$tar = exec("command -v tar");
		}
		
		if(!empty($tar)) {
			return true;
		} else {
			return false;
		}

	}

	
	private function isShellZipEnabled() {
	
		$result = $this->isExecEnabled();
		
		$zip = null;
		
		if ($result) {
			$zip = exec("command -v zip");
		}
		
		if(!empty($zip)) {
			return true;
		} else {
			return false;
		}

	}

	
	private function isZipEnabled() {
			if (class_exists('ZipArchive')) {
				return true;	
			} else {
				return false;
			}

	}


	private function isCurlEnabled(){
		return function_exists('curl_version');
	}


	private function optimiseResources(){
	
		error_reporting(E_ALL); 
		ini_set('display_errors', 1);
		
		// memory_limit
		$memory_limit = $this->config->get('backup_pro_config_memory_limit') . 'M';
		ini_set('memory_limit', $memory_limit);
		
		// max_execution_time
		ini_set('max_execution_time', $this->config->get('backup_pro_config_max_time'));
		
	}

	
	private function array_msort($array, $cols) {
		$colarr = array();
		foreach ($cols as $col => $order) {
			$colarr[$col] = array();
			foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
		}
		$eval = 'array_multisort(';
		foreach ($cols as $col => $order) {
			$eval .= '$colarr[\''.$col.'\'],'.$order.',';
		}
		$eval = substr($eval,0,-1).');';
		eval($eval);
		$ret = array();
		foreach ($colarr as $col => $arr) {
			foreach ($arr as $k => $v) {
				$k = substr($k,1);
				if (!isset($ret[$k])) $ret[$k] = $array[$k];
				$ret[$k][$col] = $array[$k][$col];
			}
		}
		return $ret;
	}	

	
   	private function rrmdir($dir) {
	  if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
		  if ($object != "." && $object != "..") {
			if (filetype($dir."/".$object) == "dir") {
			   $this->rrmdir($dir."/".$object); 
			} else {
				unlink   ($dir."/".$object);
			}
		  }
		}
		reset($objects);
		rmdir($dir);
  	  }
	}

	
	private function getTableName($string) {
		$start = strpos($string, '`');
		$end = strpos($string, '`', $start + 1);
		if(strpos($string, 'DROP TABLE') === 0) {
//			echo substr($string, $start + 1, $end - $start - 1) . '<br>';
		}
		return substr($string, $start + 1, $end - $start - 1);
	}


	private function getSSL() {
		if($this->config->get('config_secure') == '1') {
			return true;
		} else {
			return false;
		}
	}

	
	private function debug($output1, $output2 = '!"£$%^&*') {
		$file = $this->log_folder . 'backup_pro.log';
		
		$handle = fopen($file, 'a+'); 
		
		if(is_array($output1)) {
			fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($output1, true) . "\n");
		} else {
			if($output1 === false) {
				$output1 = '(boolean FALSE)';
			} elseif($output1 === true) {
				$output1 = '(boolean TRUE)';
			} elseif($output1 === null) {
				$output1 = '(NULL)';
			} elseif($output1 === '') {
				$output1 = '(\'\')';
			}
			fwrite($handle, date('Y-m-d H:i:s') . ' - ' . $output1 . "\n");
		}
		
		if($output2 != '!"£$%^&*') {
			$this->debug($output2);
		}
			
		fclose($handle); 
	}

	
	private function resetDebug() {
		if(file_exists($this->log_folder . 'backup_pro.log')) {
			unlink($this->log_folder . 'backup_pro.log');
		}
	}

	
	private function checkStatus() {
		
		if(!is_dir('../backup_pro')) {
			mkdir('../backup_pro', 0755);
		}
		
		if(!is_dir('../backup_pro/database')) {
			mkdir('../backup_pro/database', 0755);
		}
		
		if(!is_dir('../backup_pro/excludes')){
			mkdir('../backup_pro/excludes', 0755);
		}
		
		if(!is_dir('../backup_pro/log')){
			mkdir('../backup_pro/log', 0755);
		}
		
		if(!is_dir('../backup_pro/temp')){
			mkdir('../backup_pro/temp', 0755);
		}
		
		if(!is_file('../backup_pro/database/index.html')) {
			$fp = fopen('../backup_pro/database/index.html', 'w');
			fclose($fp);
		}
		
		if(!is_file('../backup_pro/excludes/excludes.txt')) {
			$fp = fopen('../backup_pro/excludes/excludes.txt', 'w');
			fwrite($fp, 'image/cache/*
storage/cache/*
backup_pro/database/*
backup_pro/log/*
temp/*
*.tar
*.tgz
*.gz
*.zip
../config.php
./config.php');
			fclose($fp);
		}
		
		// Check if the backup table exists and create if necessary
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "backup'");
		
		if(!$query->num_rows) {
			$this->db->query("CREATE TABLE `" . DB_PREFIX . "backup` (
				 `id` int(11) NOT NULL AUTO_INCREMENT,
				 `file_name` varchar(1024) NOT NULL,
				 `type` varchar(6) NOT NULL,
				 `size` int(11) NOT NULL,
				 `part` int(2) NOT NULL,
				 PRIMARY KEY (`id`)
				) ENGINE=MyISAM AUTO_INCREMENT=1");
		}

	
	}


	
	private function formatLog($string) {
		return str_replace("    ", '&nbsp;&nbsp;&nbsp;&nbsp;', nl2br($string));
	}
	
}
?>