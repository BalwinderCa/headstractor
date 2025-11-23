<?php
class ModelExtensionToolBackupPro extends Model {

	private $max_rows = 50000;
	private $database_backup_folder = 'backup_pro/database/';
	private $log_folder = 'backup_pro/log/';
	
	public function getTables() {
		$table_data = array();
		
		$query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "`");
		
		foreach ($query->rows as $result) {
			if (utf8_substr($result['Tables_in_' . DB_DATABASE], 0, strlen(DB_PREFIX)) == DB_PREFIX) {
				if (isset($result['Tables_in_' . DB_DATABASE])) {
					$table_data[] = $result['Tables_in_' . DB_DATABASE];
				}
			}
		}
		
		return $table_data;
	}
	
	public function backup($tables, $data) {
		
		$start_time = microtime(true);
		
		$last_table = $data['table'];
		$next_row = $data['row'];
		$packet = $data['packet'];
		$file_size = $data['file_size'];
		$db_max_size = $data['db_max_size'];
		$part = substr('0' . $data['part'], -2);
		$packet = $data['part'];
		$max_packet = $this->config->get('backup_pro_config_max_packet');
		$max_time = $this->config->get('backup_pro_config_max_time') -5;
		$mem_limit = (int)($this->config->get('backup_pro_config_memory_limit') / 4);
		if($db_max_size == 0) {
			$filename = $data['filename'] . '.sql';
		} else {
			$filename = $data['filename'] . '_part_' . $part . '.sql';
		}

		if(!$data['ignore_excludes']) {
			$excludes = $this->convert2Array($this->config->get('backup_pro_config_exclude_tables'));
		} else {
			$excludes = array();
		}
		
		if($data['append'] == 0) {
			$fp = fopen($this->database_backup_folder . $filename, 'w');
			fwrite($fp, "-- Backup PRO " . date('Y-m-d  H:i:s'));
			fwrite($fp, "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n");
		} else {
			$fp = fopen($this->database_backup_folder . $filename, 'a');
		}	
		
		foreach ($tables as $table) {
			
			$limit = $this->config->get('backup_pro_config_max_rows');

			if (DB_PREFIX) {
				if (strpos($table, DB_PREFIX) === false) {
					$status = false;
				} else {
					$status = true;
				}
			} else {
				$status = true;
			}
			
			if ($status) {
				
				// Check if current table has already been written
				if($last_table > $table) {
					continue;
				}
				
				// Current table has been written, but the next table has not
				if($last_table == $table && $next_row == 0) {
					continue;
				}
				
				// If this is true, the current table has not been written at all yet. 
				// If it is false, then we are part way through the current table
				if($last_table < $table) {
					$next_row = 0;
				}
				
				$sql_size = 0;
				
				if($next_row == 0) {
					
					// Debug
					if($data['debug']) {
						$this->debug('Processing: ' . $table);
					}
					
					$query = $this->db->query('SHOW CREATE TABLE `' . $table . '`');
					$result = $query->row;
					if(in_array($table, $excludes)) {
						
						$string = $result['Create Table'].";\n\n";
						$output = str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $string);
						fwrite($fp, $output);
						$file_size += strlen($output);
					} else {
						$output = 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n";
						
						fwrite($fp, $output);
						$file_size += strlen($output);
						
						$output = $result['Create Table'].";\n\n";
						fwrite($fp, $output);
						$file_size += strlen($output);
					}
				}
				
				if(in_array($table, $excludes)) {
					continue;
				}
				
				$query = $this->db->query("SELECT COUNT(*) as nbr FROM `" . $table . "`");
				$total_rows = $query->row['nbr'];
				
				$result = $total_rows - $next_row;
				
				if($result > $limit) {
					$iterations = (int)($result/$limit) + ($result % $limit ? 1 : 0);
				} else {
					$iterations = 1;
				}
				
				for($i = 1; $i <= $iterations; $i++) {
					
					$start = (($i - 1) * $limit) + $next_row;
					$query = $this->db->query("SELECT * FROM `" . $table . "` LIMIT " . $start . ", " . $limit);

					if ($query->num_rows) {
						$num_rows = $query->num_rows;
						$row = $start;
						
						
						$result = $query->row;
						$fields = '';
						
						foreach (array_keys($result) as $value) {
							$fields .= '`' . $value . '`, ';
						}
						
						$insert_into = 'INSERT INTO `' . $table . '` (' . preg_replace('/, $/', '', $fields) . ') VALUES' . "\n";
						fwrite($fp, $insert_into);
						$file_size += strlen($insert_into);
						
						$ctr = 0;
						foreach ($query->rows as $result) {
							
							$values = '';
							
							foreach (array_values($result) as $value) {
/*								$value = str_replace(array("\x00", "\x0a", "\x0d", "\x1a"), array('\0', '\n', '\r', '\Z'), $value);
							$value = str_replace(array("\n", "\r", "\t"), array('\n', '\r', '\t'), $value);
							$value = str_replace('\\', '\\\\',	$value);
							$value = str_replace('\'', '\\\'',	$value);
							$value = str_replace('\\\n', '\n',	$value);
							$value = str_replace('\\\r', '\r',	$value);
							$value = str_replace('\\\t', '\t',	$value);		*/	
							
							$values .= '\'' . $this->db->escape($value) . '\', ';
							}
															
							$output = '(' . preg_replace('/, $/', '', $values) . ')';
							
							// Time limit reached
							if (microtime(true) - $start_time > $max_time) {
								$output .= ";\n\n-- [$packet]\n"; 
								$packet++;
								fwrite($fp, $output);
								$file_size += strlen($output);
								$row++;
								$data['table'] = $table;
								$data['row'] = $row;
								$data['packet'] = $packet;
								$data['append'] = 1;
								$data['file_size'] = $file_size;

								fclose($fp);
								return $data;
							}
							
							// End of table reached
							if ($ctr == $num_rows-1) {
								$output .= ";\n";
								
							// File size limit reached
							} elseif($db_max_size > 0 && $file_size + strlen($output) >= $db_max_size * 1048576) {
								$output .= ";\n-- [$packet]\n"; 
								$packet++;
								fwrite($fp, $output);
								fwrite($fp, "\n");
								fclose($fp);

								$data['part']++;
								$part = substr('0' . $data['part'], -2);
								$filename = $data['filename'] . '_part_' . $part . '.sql';

								$fp = fopen($this->database_backup_folder . $filename, 'w');
//								fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");
								fwrite($fp, $insert_into);

								$sql_size = 0;
								$file_size = strlen($insert_into) + 29;
								$output = '';
								$row++;
							
							// Max Packet reached
							} elseif ($sql_size > $max_packet * 1048576) {
								$output .= ";\n\n-- [$packet] Max Packet\n"; 
								$packet++;
								$output .= 'INSERT INTO `' . $table . '` (' . preg_replace('/, $/', '', $fields) . ') VALUES' . "\n";
								$sql_size = strlen($insert_into);
							
							// Regular line							
							} else {
								$output .= ",\n";
								$sql_size += strlen($output);
							}
							fwrite($fp, $output);
							$file_size += strlen($output);
							$ctr ++;
							$row++;
						}
					}
					if($i<$iterations) {
						$output = "\n-- [$packet]\n";
						$packet++;
						fwrite($fp, $output);
						$file_size += strlen($output);
					}
				}
				
				$output = "\n-- [$packet]\n";
				$packet++;
				fwrite($fp, $output);
				$file_size += strlen($output);
												
			}
		}
		fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n\n\n");
		fwrite($fp, "-- Backup PRO " . date('Y-m-d  H:i:s'));
		fclose($fp);
		
		$data['complete'] = true;
		return $data;
	}
	
	public function reset_next_backup($new_date) {
		$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $new_date . "' WHERE `key` = 'backup_scheduled_date'");		
	}
	
	public function housekeep404s() {
		$query = $this->db->query("SELECT `link`, MAX(`date`) AS date, COUNT(*) AS total FROM `" . DB_PREFIX . "404s_report` GROUP BY `link`");
		if($query->num_rows) {
			foreach($query->rows as $row) {
				if($row['total'] > 1) {
					$this->db->query("DELETE FROM `" . DB_PREFIX . "404s_report` WHERE `link` = '". $row['link'] . "' AND `date` != '" . $row['date'] . "'");
				}
			}
		}
		$this->db->query("OPTIMIZE TABLE `" . DB_PREFIX . "404s_report`");
	}
	
	public function housekeepBotsReport($limit = 'month') {
		if($limit == 'week') {
			$cutoff = date('Y-m-d H:i:s', strtotime('-1 week'));
		} else {
			$cutoff = date('Y-m-d H:i:s', strtotime('-1 month'));
		}
		$this->db->query("DELETE FROM `" . DB_PREFIX . "bots_report` WHERE `date` < '" . $cutoff . "'");
		$this->db->query("OPTIMIZE TABLE `" . DB_PREFIX . "bots_report`");
	}
	
	public function housekeepSession($session_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "session` WHERE `expire` <= NOW()");
		$this->db->query("OPTIMIZE TABLE `" . DB_PREFIX . "session`");
	}

	private function convert2Array($string) {
		$data = array();
		if($string != "") {
			$excludes = explode("\n",$string);
			foreach($excludes as $exclude) {
				$data[] = trim($exclude);
			}
		}
		return $data;
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
	
}
?>