<?php
// Heading
$_['heading_title']    					= 'Backup Pro';
	
//Tab
$_['tab_db_backup']      				= 'Database Backup';
$_['tab_ws_backup']      				= 'Whole Store Backup';
$_['tab_scheduled']  					= 'Scheduled Backup';
$_['tab_restore']    	  				= 'Restore';
$_['tab_config']						= 'Configuration';
$_['tab_info']      					= 'Additional Information';
$_['tab_utilities']      				= 'Utilities';
$_['tab_troubleshooting']      			= 'Troubleshooting';
$_['tab_log']      						= 'Log';
	
// Text
$_['text_edit']      					= 'Backup, Schedule a Backup or Restore your Database';
$_['text_yes']      					= 'Yes';
$_['text_no']      						= 'No';
$_['text_enabled']      				= 'Enabled';
$_['text_disabled']      				= 'Disabled';
$_['text_database_tables']      		= 'Database Tables';
$_['text_backup']      					= 'Download Backup';
$_['text_backup_filename']      		= 'backup';
$_['text_backup_settings']      		= '<h1 style="color: #ff8000">Backup Settings:</h1>';
$_['text_backup_db']      				= '<h1 style="color: #ff8000">Backup Database:</h1>';
$_['text_backup_ws']      				= '<h1 style="color: #ff8000">Backup Whole Store:</h1>';
$_['text_backup2']      				= '<h1 style="color: #ff8000">Scheduled Backups:</h1>';
$_['text_log']      					= '<h1 style="color: #ff8000">Backup Log:</h1>';
$_['text_restore']      				= '<h1 style="color: #ff8000">Restore:</h1>';
$_['text_success']     					= 'Success: You have successfully saved your scheduled backup settings!';
$_['text_configuration_alert']  		= 'Please Update and Save your Configuration Settings before continuing.';
$_['text_success_backup']     			= 'Success: Your backup is currently being created as a background process and can be found in the folder <strong>backup_pro/</strong>.<br /><br />This process may take up to an hour or more if there are a significant number of files, so please do not try to download it before the process has finished !';
$_['text_success_database']     		= 'Success: Your database backup has been created and should download automatically.';
$_['text_success_wholestore']     		= 'Success: Your whole store backup has been created.';
$_['text_success_backup_ready'] 		= 'Success: Your backup is ready to be downloaded by ftp from the folder: <strong>system/logs</strong>';
$_['text_success_restore']     			= 'Success: You have successfully restored your database!';
$_['text_success_delete_clone']			= 'Success: You have successfully deleted the cloned database!';
$_['text_instructions']   				= 'Use this section to set up an automatic and regular backup of your database. The backup will be emailed to you.';
$_['text_backup_scheduled_filename']    = 'db-backup';
$_['text_backup_email_subject']     	= 'Database Backup';
$_['text_backup_email_message']     	= 'Here is the database backup';
$_['text_further_information']      	= '<h1 style="color: #ff8000">Further Information:</h1>';
$_['text_further_information2']   		= '<strong>To "clone" a store take the following steps:</strong><ol>
												<li>Make a "Whole Store" backup of your store</li>
												<li>Create a brand new installation of Opencart making sure you use the same version as the store you\'ve backed up. <span style="color: #ff0000">You must also make sure you use the same database prefix as in the cloned store.</span></li>
												<li>Using ftp, upload the backup file to the <strong>root</strong> of your new store</li>
												<li>Log in to your server cPanel and open up the file manager</li>
												<li>Find the backup file in the root of your store, select it then click on the extract files / unzip button. Unzip the files to the root of your store<br /><strong>Note:</strong> if you have had to limit the maximum number of files per zip, you may find additional .zip files in the root of your store. These will also need to be unzipped.</li>
												<li>Delete the .zip file(s) you\'ve just unziped</li>
												<li>Log in to the administration area of your new store, click on <strong>System > Maintenance > Backup Pro</strong> (you may have to set the user permissions again).<br />In the Restore section, you will have an option to <strong>Restore Backup from a "Cloned" Store</strong>. Click the Restore button.</li>
												<li>Job done!</li></ol><br /><br />To see a video of this process <a href="http://www.showmeademo.co.uk/opencart/backup-pro/" target="_blank"><strong>click here</strong></a><br /><br />';
$_['text_download_ready']   			= 'Your backup is ready to download. Please press the <strong>Continue</strong> button.'; 
$_['text_wait']      					= '<img src="view/image/loading.gif" alt="preparing backup . . ." /> Preparing your backup - please wait. <br />Please do not refresh or browse away from this page until the process has completed.';
$_['text_restore_wait']      			= '<img src="view/image/loading.gif" alt="restoring backup . . ." /> Restoring your backup - please wait. <br />Please do not refresh or browse away from this page until the process has completed.';
$_['text_no_backups']      				= 'No backups are currently available.';
$_['text_check_email']      			= 'A test backup email has been sent to the email address provided.';
$_['text_database_configuration']      	= '<!-- <h3>Database Configuration Settings</h3> --> The following settings can be adjusted if you are having difficulties saving or restoring a very large database. Do not change these values unless you are having problems.';
$_['text_wholestore_configuration']     = '<h3>Wholestore Backup Configuration Settings</h3>';
$_['text_general']      				= '<h3>General Settings</h3>';
$_['text_db']      						= '<h3>Database Backup Settings</h3>';
$_['text_ws']      						= '<h3>Whole Store Backup Settings</h3>';
$_['text_restore']      				= '<h3>Restore Settings</h3>';
$_['text_housekeeping']      			= '<h3>Database Housekeeping</h3>';
$_['text_housekeeping_1']      			= 'There are some database tables that over time collect a huge amount of data which can result in database backups failing simply because of the size of the backup.<br><br><ul>';
$_['text_housekeeping_2']      			= 'Backup Pro includes a tool that performs a houskeeping task on certain database tables that are prone to \'bloat\'. This clears out old and expired data. The housekeeping task can be performed manually and / or immediately prior to database backups. Indicate your preferences below';
$_['text_housekeeping_session'] 		= '<li>The table <strong>oc_session</strong> holds \'cookie\' data about users preferences. There is an expiry date for these, but Opencart provides no mechanism to automatically clear out expired information.</li>';
$_['text_404_report']      				= 'Records the url of any \'Page Not Found\'. This can happen if you change the seo url of an item or if an item is deleted. Often entries are duplicated, bulking out the content of the table. Also, there is no mechanism within Opencart to make use of or analyse this data, so a third party extension would be required if you wished to do so.<br><br>This housekeeping task removes entries that are more than three months old.';
$_['text_bots_report']      			= 'This is simply a record of all the web bots that have crawled your site and which page they examined. There is no utility built in to Opencart that allows you to make use of this data, so to do so you would need a third party extension.<br><br> This housekeeping task clears any data that is more than a month old.';
$_['text_session']      				= 'This table holds \'cookie\' data about users preferences. There is an expiry date for these, but Opencart provides no mechanism to automatically clear out expired information.<br><br>This housekeeping task removes expired session data.';
$_['text_table_excludes_placeholder']   = 'See database tables at the bottom of the page.';
$_['text_capacity_test']				= '<span style="color: #808080">It is possible to increase the capacities allowed by your server, but it is not always allowed. The following tests allow you to establish the absolute limits of your server, so that you can maximise the effectiveness of Backup Pro (and other scripts).<br /><br />In order to find these limits, we have to force the script to "crash", and this means that the limits can\'t be neatly displayed on the page. Both of the following tests open up a new window, so you can easily find your way back to this page. You must read the instructions for each test to find out how to establish the limit for each of the two capacities.</span><br />&nbsp;';
$_['text_memory_limit']     			= '<span style="color: #808080">This test sets the Memory Limit to 1000M then attempts to add over 4Gb of data to a variable forcing the script to fail. You should see an error message along the lines of: <br /><br /><span style="font-family: monospace; color: blue">Fatal error: Out of memory (allocated 336068608) (tried to allocate 72 bytes) in /home/xxxx/public_html/vqmod/vqcache/vq2-admin_controller_tool_backup.php on line 164</span><br /><br />Simply divide the "allocated" figure (336068608 in this case) by 1048576 to get the value in Mb. This is the real Memory Limit available to you.<br /><br />Be aware, though that the maximum memory available to you may fluctuate, particularly if you are on a shared server.</span>';
$_['text_max_ex_time']     				= '<span style="color: #808080">This test sets the Maximum Execution Time to 300 seconds (5 minutes) then keeps the server busy on unproductive activities, occasionally writing the elapsed time to a file. When the "real" maximum execution time has elapsed, the script will fail giving a "Timeout" error. Remember - this could be as long as 5 minutes!.<br /><br />Simpy open the file <strong>system/storage/logs/max_ex_time.txt</strong> to find the "real" Maximum Execution Time available to you.</span>';
$_['text_files_data']     				= 'Files Data';
$_['text_utilities']     				= 'The ability to pack up your store into a single convenient archive depends on many things, including:<ul><li>Size and No of files.</li><li>Available server space and internal memory.</li><li>CPU speed.</li><li>Limits on script processing time.</li><li>Database server limitations.</li></ul>In most cases it will be possible to configure <strong>Backup Pro</strong> to generate a store backup in a single cycle.<br><br>In a small number of cases, the limitations on the server will make this impossible to achieve.<br><br>The utilities on this page are therefore provided in order to still be able to create a full backup of your store by performing multiple operations.<br><br>See the <strong>Troubleshooting</strong> tab for more information.';
$_['text_troubleshooting']     			= 'Sometimes the Server will stop responding and send an error message. This may take the form of a \'<strong>Timeout</strong>\', \'<strong>Out of Memory</strong>\' or simply \'<strong>The Server Has Stopped Responding</strong>\' message.<br><br>The cause will either be as a result of the database being too large, or because of a problem with compressing the backup archive.<br><br>Go through the checklist below <strong>in strict order</strong> to find the solution to the problem.';
	
// Entry
$_['entry_restore']    					= 'Restore Backup from File:';
$_['entry_backup']     					= 'Select Database Tables:';
$_['entry_debug']     					= 'Debug Mode:';
$_['entry_log']     					= 'Log File:';
$_['entry_backup_filename']     		= 'Backup Filename:';
$_['entry_backup_zip']     				= 'Zip backup SQL file (recommended):';
$_['entry_consolidate_zip']    			= 'Consolidate Multiple Database Files:';
$_['entry_save_db2server']     			= 'Retain Backup on Server:';
$_['entry_download']     				= 'Automatically Download / Email Backup:';
$_['entry_email_backup']     			= 'Email Backup File:';
$_['entry_backup_ignore_excludes']     	= 'Ignore Excluded Tables List?';
$_['entry_backup_limit']     			= 'Limit Size of Zip:';
$_['entry_backup_db_limit']     		= 'Limit Size of Database Backups:';
$_['entry_available_backups']  			= 'Available Backups:';
$_['entry_backup_status']     			= 'Schedule Automatic Backups:';
$_['entry_backup_housekeeping'] 		= 'Schedule Automatic Housekeeping:';
$_['entry_backup_email']     			= 'Email Address to Send Backup:';
$_['entry_backup_email_subject']     	= 'Email Subject:';
$_['entry_backup_email_message']     	= 'Email Message:';
$_['entry_backup_email_status'] 		= 'Email Backup:';
$_['entry_backup_save_to_server']     	= 'Save Backups to the Server:';
$_['entry_backup_no_of_backups']     	= 'How many Backups to Keep:';
$_['entry_backup_cron']     			= 'Backup Using Cron Job:';
$_['entry_backup_cron_command'] 		= 'Cron Job Command:';
$_['entry_backup_frequency']    		= 'Backup Frequency:';
$_['entry_config_large_table']    		= 'Large Table Size (Mb):';
$_['entry_config_max_rows']    			= 'Max Table Rows:';
$_['entry_config_max_packet']    		= 'Max Chunk Size (Mb):';
$_['entry_config_max_time']    			= 'Max Execution Time (seconds):';
$_['entry_config_max_files']    		= 'Max No of Files in Zip Archives:';
$_['entry_config_max_sql_size']    		= 'Split Database Backup File:';
$_['entry_config_memory_limit']    		= 'Memory Limit (Mb):';
$_['entry_combine_download']    		= 'Combine Zip Archives:';
$_['entry_config_excludes']    			= 'Exclude Database Tables From Backups:';
$_['entry_config_wholestore_excludes']  = 'Exclude Files / Folders From Backups:';
$_['entry_config_restore_time_limit']  	= 'Restore Time Limit:';
$_['entry_clone']     					= 'Restore Cloned Store Backup:';
$_['entry_storage_folder']     			= 'Restore Storage Folder:';
$_['entry_404_report']     				= 'Housekeep 404 Report Table Before Backup:';
$_['entry_bots_report']     			= 'Housekeep Bots Report Table Before Backup:';
$_['entry_session']     				= 'Housekeep Session Table Before Backup:';
$_['entry_folder']     					= 'Folder';

$_['entry_backup_pro_compatibility']	= '<h1 style="color: #ff9900">Backup Pro Status</h1><p>These are the values set by php.ini';
$_['entry_max_memory']     				= '<strong>Backup Pro Memory Limit:</strong>';
$_['entry_memory_limit']     			= '<strong>PHP Memory Limit:</strong><br />128M or higher is recommended.';
$_['entry_max_execution_time']     		= '<strong>Maximum Execution Time:</strong><br />Minimum 120 seconds recommended.';
$_['entry_max_packets']     			= '<strong>Max Packets:</strong><br />This is the maximum size that the system will accept for database queries.';
$_['entry_curl']     					= '<strong>CURL:</strong><br />This is required for automated database backups.';
$_['entry_zip']     					= '<strong>ZIP:</strong><br />Required for backing up Wholestore if TAR is not available.';
$_['entry_exec']     					= '<strong>Shell Access:</strong><br />Required for access to TAR.';
$_['entry_tar']     					= '<strong>TAR:</strong><br />Essential if you have very large backups (ie larger than 2Gb).';
$_['entry_no_of_files']     			= '<strong>Number of Files</strong>';
$_['entry_size_of_files']     			= '<strong>Size</strong>';
$_['entry_size_of_database']     		= '<strong>Size of Database:</strong>';
$_['entry_capacity_test']				= '<h2 style="color: #ff8000">Capacity Test</h2>';
$_['entry_max_ex_time']     			= '<strong>Maximum Execution Time:</strong>';
$_['entry_post_max_size']     			= '<strong>PHP post_max_size:</strong><br>This needs to be higher than the database restore file size. Set in php.ini or .htaccess.<br><br>See Troubleshooting section.';
$_['entry_upload_max_filesize']     	= '<strong>PHP upload_max_filesize:</strong><br>This needs to be higher than the database restore file size. Set in php.ini or .htaccess.<br><br>See Troubleshooting section.';
$_['entry_diagnostics']     			= '<strong>Download Diagnostics Page</strong>:';
$_['entry_db_analysis']     			= '<strong>Analyse Database Backup</strong>:';
$_['entry_large_tables']     			= '<strong>Backup Large Tables</strong>:';

	
// Help
$_['help_backup_filename']     			= 'To change the backup filename, click on the<br>\'<strong>Configuration Settings</strong>\'<br>tab.';
$_['help_save_db2server']     			= 'Database backups are automatically downloaded. Choose whether you wish to also keep a copy on your server.';
$_['help_scheduled_save_db2server']     = 'Scheduled database backups are usually deleted after being emailed. You can choose to keep them on the server.';
$_['help_email_backup']     			= 'If you don\'t want to receive email copies of the backup or the backups are too large to be emailed, set this to <strong>No</strong>.<br><br>If set to <strong>No</strong>, make sure that <strong>Retain Backup on Server</strong> is set to <strong>Yes</strong>.';
$_['help_download']     				= 'Automatically download a manual database backup / email a scheduled database backup';
$_['help_debug']     					= 'Switch this on if you are having problems with backing up or restoring.<br><br>The results can be seen in the \'Log\' tab <strong>AFTER</strong> the backup or restore script has completed.';
$_['help_backup_limit']     			= 'If you are getting problems with Error 500, timeouts, or exceeding maximum memory, try reducing the maximum size of each zip. This will mean that your backup will consist of 2 or more zip files each of which will also need to be unzipped when restoring your data.';
$_['help_consolidate_zip']    			= 'If you set the backup to split into several smaller, zipped files, this will attempt to create a single archive containing the smaller zipped files.';
$_['help_backup_db_limit']     			= 'If you are having problems restoring database backups, try setting a lower limit for database backups.<br><br>This <strong>WILL</strong> lead to multiple database backups.';
$_['help_backup_cron']     				= 'Scheduled backups can either be triggered by a Cron Job (recommended) or by visitors to your store (admin or customers).<br /><br />You will need to set up a Cron Job separately through your server\'s cPanel using the command shown below if you select Yes here.<br /><br />If you are not familiar with setting up Cron Jobs, you should select No.';
$_['help_backup_housekeeping']  		= 'This will only work for the tables selected in Housekeeping section of the Configuration tab.';
$_['help_backup_cron_command']  		= 'Use this command if you set up a CRON job.';
$_['help_backup_frequency']     		= 'If you set up a Cron Job, this field will be ignored.';
$_['help_backup_ignore_excludes']     	= 'In other words, do you want to include those tables that are usually left out ?<br><br>(See the \'Configuration\' tab).';
$_['help_clone']     					= 'Backup Pro has detected a database backup that seems to have been restored from a Whole Store backup. <br /><br />Click the <strong>No Thanks</strong> button if you would like to delete this file.';
$_['help_restore']    					= 'You can only restore your database here. This should either be a .sql file or a .zip file that contains a .sql file.<br /><br />If you would like to restore a whole store - see the <strong>Additional Information</strong> tab.';
$_['help_available_backups']    		= 'Here is a list of available backups. They can be downloaded by clicking the filename or via ftp from the folder <strong>backup_pro/</strong>.';
$_['help_config_large_table']    		= "Set the size for a database table to be classed as 'large'.";
$_['help_config_max_rows']    			= "Maximum number of database table rows to be included in restore 'chunks':";
$_['help_config_max_packet']    		= "Maximum size of database restore 'chunks' or MYSQL queries (max_allowed_packet). You should set this to a value lower than the system 'Max Allowed Packet' value and normally not more than 10Mb.";
$_['help_config_max_time']    			= 'Maximum time (in seconds) before the script times out';
$_['help_config_max_files']    			= 'Limit the number of files allowed in zip archives';
$_['help_config_memory_limit']  		= 'Maximum memory available to the script';
$_['help_combine_download']  			= 'Sometimes multiple zip archives are required in order to successfully backup the whole store. Set this to <strong>Yes</Strong> to combine the partial backups into a single archive.<br><br>If the script fails on combining the partial archives, set this to <strong>No</strong>.';
$_['help_config_excludes']    			= 'Sometimes it is necessary to exclude some non-essential database tables from the backup in order for the backup to not \'time out\'.<br><br>List database tables to be excluded from backups. Include the database prefix (often \'oc_\'). Put each table on a separate line.';
$_['help_config_wholestore_excludes']   = 'Exclude files or folders from wholestore backup.<br><br>Take care to ensure that you don\'t accidentally exclude files and folders you will need to be able to operate the store.<br><br>Put each file/folder path on a separate line.';
$_['help_config_restore_time_limit']  	= 'Maximum time (in seconds) for restore operation to run before redirecting to avoid timeout.<br><br>If you are experiencing timeouts when restoring database archives, try reducing this setting.';
$_['help_storage_folder']    			= 'You appear to have an archived copy of the \'storage\' folder. If this is from a recently restored backup, you should restore this folder now.';
$_['help_diagnostics']     				= 'Sometimes a backup fails to complete. There are many potential reasons for this.<br><br>If you are having problems with backups failing, click the button to download a diagnostic summary which can help to troubleshoot the problem and assist with finding a suitable fix.';
$_['help_db_analysis']     				= 'Sometimes a database backup fails to complete. There are two possible reasons for this. The actual backup failed or the archive compression failed to complete<br><br>Clicking the button will check to see if a database backup was completed, and if not, at which point the backup failed.<br><br>If the database backup was successfully created, then the problem is likely that the ziparchive failed to complete (see troubleshooting).';
$_['help_large_tables']     			= 'Select a large table from the dropdown and click the button(s) to backup the table.';
$_['help_max_sql_size']     			= 'Only set this if database backups are failing and you have followed the steps at section 2 of the Troubleshooting tab.<br><br>Choose the maximum size of each segment.';
	
// Button
$_['button_save']     					= 'Save Settings';
$_['button_no']     					= 'No Thanks, Delete It';
$_['button_continue']     				= 'Continue';
$_['button_test_email']     			= 'Test Email';
$_['button_table_status']     			= 'Show Table Sizes';
$_['button_backup']						= 'Backup Now';
$_['button_restore']					= 'Restore';
$_['button_housekeeping']				= 'Perform Housekeeping On Selected Tables Now';
$_['button_diagnostics']				= 'Download Diagnostics';
$_['button_db_analysis']				= 'Database Analysis';
	
// Error
$_['error_permission'] 					= 'Warning: You do not have permission to modify backups!';
$_['error_backup']     					= 'Warning: You must select at least one table to backup!';
$_['error_empty']      					= 'Warning: The file you uploaded was empty!';
$_['error_nofile']      				= 'Warning: You must select a file to upload!';
$_['error_warning']    					= '<strong>Warning: The following file(s) were not readable and have not been included in your backup!</strong>';
$_['error_warning_zipfiles']    		= '<strong>Warning: Ziparchives are no longer included in backups. The following file(s) have been excluded!</strong>';


?>