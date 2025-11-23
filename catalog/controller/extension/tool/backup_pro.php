<?php 
class ControllerExtensionToolBackupPro extends Controller { 
	private $error = array();
	private $database_backup_folder = 'backup_pro/database/';
	private $temp_folder = 'backup_pro/temp/';
	private $log_folder = 'backup_pro/log/';
	
	public function index() {
		
		$args = '';
		
		if(isset($this->request->get['test']) && $this->request->get['test'] == 'true') {
			$test = 1;
			$debug = 1;
			$args .= '&test=true';
		} else {
			$test = 0;
			$debug = 0;
		}

		if(isset($this->request->get['log']) && $this->request->get['log'] == 'true') {
			$debug = 1;
			$args .= '&log=true';
		} else {
			$debug = 0;
		}

		if($debug) {
			$this->resetDebug();
		}
		
		if($this->config->get('backup_scheduled_status')) {
			
			$this->optimiseResources();
			
			if(isset($this->request->get['backup'])) {
				$backup = $this->getData($this->request->get['backup']);
				
			} else {
				
				
				$this->load->model('extension/tool/backup_pro');
				$tables = $this->model_extension_tool_backup_pro->getTables();
				
				$tempfile = bin2hex(random_bytes(18));
				$args .= '&backup=' . $tempfile;

				$backup = array(
					'what'					=> 'db',
					'tables'				=> $tables,
					'backup_filename'		=> $this->config->get('backup_scheduled_filename'),
					'ignore_excludes'		=> false,
					'save_db2server'		=> $this->config->get('backup_scheduled_save_db2server'),
					'debug'					=> $debug,
					'tempfile'				=> $tempfile,
					'test'					=> $test
				);
				
				$this->saveData($backup);
			}
			
			$db_max_size = $this->config->get('backup_pro_config_max_sql_size');
			if($db_max_size == '' || $db_max_size == 'Not Set') {
				$db_max_size = 0;
			}
			
			if(isset($backup['db_backup_data'])) {
				$data = $backup['db_backup_data'];
				
				// Debug
				if($backup['debug']) {
					$this->debug('Redirecting to avoid timeout . . .');
				}
			} else {
				// Debug
				if($backup['debug']) {
					$this->resetDebug();
					if($backup['test']) {
						$this->debug('Testing Scheduled Database Backup');
					} else {
						$this->debug('Starting Scheduled Database Backup');
					}
				}
				
				$timestamp = date('Y-m-d_H-i-s');
				$data = array(
					'complete'				=> false,
					'filename'				=> 'database_' . $timestamp,
					'timestamp'				=> $timestamp,
					'consolidate_backups'	=> false,
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
				
			if($data['complete'] == false) {
				$this->load->model('extension/tool/backup_pro');
				$result = $this->model_extension_tool_backup_pro->backup($backup['tables'], $data);				
				$backup['db_backup_data'] = $result;
				$this->saveData($backup);
				
				// Debug
				if($result['complete'] && $backup['debug']) {
					$sql_filename = $this->database_backup_folder . $data['filename'] . '.sql';
					$sql_filesize = $this->formatSize(filesize($sql_filename));
					$this->debug('SQL Backup File Complete', $sql_filename . '  >  ' . $sql_filesize);
				}

				$this->response->redirect($this->url->link('extension/tool/backup_pro', $args, true));
			
			} else {
	
				if($this->config->get('backup_pro_config_zip')) {
					
					// Debug
					if($backup['debug']) {
						$this->debug('Creating Zip Archive');
					}
					
					$zip_archive = $backup['backup_filename'] . '_db_' . $data['timestamp'] . '.sql.zip';
					$destination = realpath($this->database_backup_folder) . '/' . $zip_archive;
					$zip_filename = $destination;
					$filename = $data['filename'] . '.sql';
					$sql_filename = $this->database_backup_folder . $filename;
					
					if($this->isShellZipEnabled()) { 
					
						$cmd = 'cd ' . realpath($this->database_backup_folder) . '; zip -v ' . $zip_archive . ' ' . $filename . ' 2>&1';
						
						//Debug
						if($backup['debug']) {
							$this->debug('Zipping via Shell . . .', $cmd);
						}
						
						$output = shell_exec($cmd);
						
						// Debug
						if($backup['debug']) {
							$this->debug($output);
						}
					
					} else {
						$sql_filename = $this->database_backup_folder . $filename;
						
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
						
						$zip->addFile($sql_filename, 'database.sql');
						$zip->close();

						//Debug
						if($backup['debug']) {
							$zip_filesize = $this->formatSize(filesize($destination));
							$this->debug('Zip Archive Created Successfully', $zip_filename . '  >  ' . $zip_filesize);
						}
					}
					
					
				} else {
					//Debug
					if($backup['debug']) {
						$this->debug('Zip is switched off.');
					}
					$sql_filename = $this->database_backup_folder . $data['filename'] . '.zip';
				}
			
			}
			
			
			if($backup['test'] && $this->config->get('backup_scheduled_email_backup')) {
				$this->testEmail($destination);
				$this->debug('Sending First Test Email.');
			} elseif($this->config->get('backup_scheduled_email_backup')) {
				
				if($backup['test']) {
					$this->debug('Sending Second Test Email.');
				} elseif ($backup['debug']) {
					$this->debug('Sending Backup Email.');
				}
				
				$config_mail = $this->getConfigMail();

				$status = $this->config->get('backup_scheduled_status');
				$zip_data = $this->config->get('backup_pro_config_zip');
				$email = $this->config->get('backup_scheduled_email');
				$subject = $this->config->get('backup_scheduled_email_subject');
				$text = $this->config->get('backup_scheduled_email_message');
			
				$mail = new Mail(); 
				$mail->protocol = $config_mail['protocol'];
				$mail->parameter = $config_mail['parameter'];
				$mail->smtp_hostname = $config_mail['smtp_hostname'];
				$mail->smtp_username = $config_mail['smtp_username'];
				$mail->smtp_password = $config_mail['smtp_password'];
				$mail->smtp_port = $config_mail['smtp_port'];
				$mail->smtp_timeout = $config_mail['smtp_timeout'];			
				$mail->setTo($email);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender($this->config->get('config_email'));
				$mail->setSubject($subject);
				$mail->setText($text);
				$mail->addAttachment($zip_data ? $destination : $this->database_backup_folder . $sql_name);
				$mail->send();
			}
			
			// Update next backup due date
			if ($this->config->get('backup_scheduled_cron') == 0 && $test == 0) {
				$backup_scheduled_date = $this->config->get('backup_scheduled_date');
				if($backup_scheduled_date <= date('Y-m-d')) {
					$frequency = $this->config->get('backup_scheduled_frequency');
					if ($frequency == 'Daily') {
						$new_date = date('Y-m-d', strtotime(date('Y-m-d H:i:s') . ' + 1 day'));
					} elseif ($frequency == 'Weekly') {
						$new_date = date('Y-m-d', strtotime(date('Y-m-d H:i:s') . ' + 1 week'));
					} else {
						$new_date = date('Y-m-d', strtotime(date('Y-m-d H:i:s') . ' + 1 month'));
					}
					$this->load->model('extension/tool/backup_pro');
					$this->model_extension_tool_backup_pro->reset_next_backup($new_date);
					$this->config->set('backup_scheduled_date', $new_date);
				}
			}
			
			// Housekeeping
			if(!$this->config->get('backup_scheduled_save_db2server')) {
				
				if(isset($sql_filename) && is_file($sql_filename)) {	
					//Debug
					if($backup['debug']) {
						$this->debug('Deleting SQL file: ' . $sql_filename);
					}
					unlink($sql_filename);
				}
				if(isset($zip_filename) && is_file($zip_filename)) {	
					//Debug
					if($backup['debug']) {
						$this->debug('Deleting Zip Archive: ' . $zip_filename);
					}
					unlink($zip_filename);
				}
			} else {
				if(isset($zip_filename) && is_file($zip_filename)) {	
					//Debug
					if($backup['debug']) {
						$this->debug('Deleting SQL File: ' . $sql_filename);
					}
					unlink($sql_filename);
				}
			}
			if($backup['test']) {
				echo 'Success. Scheduled Backup Completed. Please check your email for 2 test messages. If you don\'t receive both, refresh the page and check the log.';
			} else {
				echo 'Success. Scheduled Backup Completed. Please check your email.';
			}

			//Debug
			if($backup['debug']) {
				$this->debug('Success. Scheduled Backup Completed.');
			}
			
			unlink($this->temp_folder . $backup['tempfile']);
			
		} else {

			//Debug
			if($debug) {
				$this->debug('Testing Scheduled Database Backup', 'Automatic backups are not switched on.');
			}
			
			echo 'Automatic backups are not switched on.';

		}
		
	}
		
	private function getData($filename) {
		return json_decode(file_get_contents($this->temp_folder . $filename), true);
	}
	
	private function saveData($data) {
		$json = json_encode($data);
		$fp = fopen($this->temp_folder . $data['tempfile'], 'w');
		fwrite($fp, $json);
		fclose($fp);
	}
	
	private function getConfigMail() {
		
		$config_mail = $this->config->get('config_mail');
		if(!is_array($config_mail)) {
			$config_mail = array();
			$config_mail['protocol'] = $this->config->get('config_mail_protocol');
			$config_mail['parameter'] = $this->config->get('config_mail_parameter');
			$config_mail['smtp_hostname'] = $this->config->get('config_mail_smtp_hostname');
			$config_mail['smtp_username'] = $this->config->get('config_mail_smtp_username');
			$config_mail['smtp_password'] = $this->config->get('config_mail_smtp_password');
			$config_mail['smtp_port'] = $this->config->get('config_mail_smtp_port');
			$config_mail['smtp_timeout'] = $this->config->get('config_mail_smtp_timeout');
		}
		
		return $config_mail;
	}
	
	public function testEmail($archive) {		
			$status = $this->config->get('backup_scheduled_status');
			$zip_data = $this->config->get('backup_pro_config_zip');
			$email = $this->config->get('backup_scheduled_email');
			$subject = $this->config->get('backup_scheduled_email_subject');
			$text = $this->config->get('backup_scheduled_email_message');

			// Send Blank email to test that email system works.
			if ($email) {
				$config_mail = $this->getConfigMail();
				$text2 = "This is the first of 2 test emails. This email checks that emails are received by the specified address.\n\nThe second email will include the database backup. If this is not received, then there is a problem with the database backup being created or there is a problem with attaching and sending the file.\n\nIn the event of problems, please send an email to me at justcurious@ocmodz.co.uk.";
				
				$mail = new Mail(); 
				$mail->protocol = $config_mail['protocol'];
				$mail->parameter = $config_mail['parameter'];
				$mail->smtp_hostname = $config_mail['smtp_hostname'];
				$mail->smtp_username = $config_mail['smtp_username'];
				$mail->smtp_password = $config_mail['smtp_password'];
				$mail->smtp_port = $config_mail['smtp_port'];
				$mail->smtp_timeout = $config_mail['smtp_timeout'];			
				$mail->setTo($email);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender($this->config->get('config_email'));
				$mail->setSubject('Backup Pro Test email 1 of 2');
				$mail->setText($text2);
				$mail->send();

			}

			// Send actual email with database backup to test backup works and is actually sent as an attachment
			if ($email) {
				$config_mail = $this->getConfigMail();
			
				$mail = new Mail(); 
				$mail->protocol = $config_mail['protocol'];
				$mail->parameter = $config_mail['parameter'];
				$mail->smtp_hostname = $config_mail['smtp_hostname'];
				$mail->smtp_username = $config_mail['smtp_username'];
				$mail->smtp_password = $config_mail['smtp_password'];
				$mail->smtp_port = $config_mail['smtp_port'];
				$mail->smtp_timeout = $config_mail['smtp_timeout'];			
				$mail->setTo($email);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender($this->config->get('config_email'));
				$mail->setSubject($subject . ' - Backup Pro Test email 2 of 2');
				$mail->setText($text);
				$mail->addAttachment($archive);
				$mail->send();

			}
			
			if (file_exists($archive)) {
				unlink($archive);
			}
			
	}
	
	private function getSSL() {
		if($this->config->get('config_secure') == '1') {
			return true;
		} else {
			return false;
		}
	}
	
	public function housekeep404s() {
		$this->load->model('extension/module/backup_pro');
		$this->model_extension_module_backup_pro->housekeep404s();
	}
	
	public function housekeepBotsReport() {
		$this->load->model('extension/module/backup_pro');
		$this->model_extension_module_backup_pro->housekeepBotsReport();
	}
	
	public function housekeepSession() {
		$this->load->model('extension/module/backup_pro');
		$this->model_extension_module_backup_pro->housekeepSession();
	}
	
	private function isExecEnabled() {
		return is_callable('exec') && false === stripos(ini_get('disable_functions'), 'exec');
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
	
	private function optimiseResources(){
	
		error_reporting(E_ALL); 
		ini_set('display_errors', 1);
		
		// memory_limit
		$memory_limit = $this->config->get('backup_pro_config_memory_limit') . 'M';
		ini_set('memory_limit', $memory_limit);
		
		// max_execution_time
		ini_set('max_execution_time', $this->config->get('backup_pro_config_max_time'));
		
	}

}
?>