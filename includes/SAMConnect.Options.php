<?php 
 /*!
 * SAM Connect
 * http://www.samdesk.io
 * V1.0
 *
 * Copyright 2013, Social Asset Management Ltd., Woody Hayday & StormGate Ltd. (Options model created by)
 *
 * Date: 28/08/2013
 */
	class SAMSettings {
		
		#} Main settings storage
		private $settings;
		private $settingsKey = 'samsettings';
		private $settingsVer = 'v0.1//21.08.13';
		private $settingsDefault = array( #} Defaults
		
										'plugin_ver' => 			0.1,
										'settingsID' => 			'',
										'api_key' =>				'',
										'api_secret' =>				'',
										'api_key_verified' =>		0,
										'share_usage' => 			1
		
		);
		
		
		#} Constructor
		function SAMSettings() {
			
			#} Load direct
			$this->loadFromDB();
			
			#} Fill any missing vars
			$this->validateAndUpdate();
			
			#} If empty it's first run so init from defaults
			if (empty($this->settings)) $this->initCreate();
			
		}
		
		#} Checks through defaults + existing and adds defaults where unset
		function validateAndUpdate(){
			$defaultsAdded = 0;
			foreach ($this->settingsDefault as $key => $val) 
				if (!isset($this->settings[$key])) {
					$this->settings[$key] = $val;
					$defaultsAdded++;
				}
			
			if ($defaultsAdded > 0) $this->saveToDB();
		}
		
		#} Initial Create
		function initCreate(){
			
			global $socialGallery_db_version, $socialGallery_version;
			
			#} Create + save initial from default
			$defaultOptions = $this->settingsDefault;
			$defaultOptions['settingsID'] = $this->settingsVer; 		#} Has to be set out of props
			$defaultOptions['plugin_ver'] = $socialGallery_version; 	#} Has to be set out of props
			$this->settings = $defaultOptions;
			$this->saveToDB();
			
		}
		
		#} Get all options as object
		function getAll(){
			
			return $this->settings;
			
		}
		
		#} Get single option
		function get($key){
			
			if (empty($key) === true) return false;
			
			if (isset($this->settings[$key]))
				return $this->settings[$key];
			else
				return false;
			
		}
		
		#} Add/Update *brutally
		function update($key,$val=''){
			
			if (empty($key) === true) return false;
			
			#} Don't even check existence as I guess it doesn't matter?
			$this->settings[$key] = $val;		
			
			#} Save down
			$this->saveToDB();
		}		
		
		#} Delete option
		function delete($key){
			
			if (empty($key) === true) return false;
			
			$newSettings = array();
			foreach($this->settings as $k => $v)
				if ($k != $key) $newSettings[$k] = $v;
				
			#} Brutal
			$this->settings = $newSettings;
						
		}
		
		#} Save back to db
		function saveToDB(){
		
			return update_option($this->settingsKey, $this->settings);				
			
		}
		
		#} Load/Reload from db 
		function loadFromDB(){
			
			$this->settings = get_option($this->settingsKey);
			return $this->settings;
			
		}		
		
		#} Uninstall func - effectively creates a bk then removes its main setting
		function uninstall(){
			
			#} Set uninstall flag
			$this->settings['uninstall'] = time();
			
			#} Backup
			$this->createBackup('Pre-UnInstall Backup');
			
			#} Blank it out
			$this->settings = NULL;
			
			#} Return the delete
			return delete_option($this->settingsKey);
			
		}
		
		#} Backup existing settings obj (ripped from sgv2.0)
		function createBackup($backupLabel=''){
			
			$existingBK = get_option($this->settingsKey.'_bk'); if (!is_array($existingBK)) $existingBK = array();
			$existingBK[time()] = $this->settings; 
			if (!empty($backupLabel)) $existingBK[time()]['backupLabel'] = sanitize_text_field($backupLabel); #} For named settings bk
			update_option($this->settingsKey.'_bk',$existingBK);
			return $existingBK[time()];
			
		}
		
		#} Kills all bks
		function killBackups(){
		
			return delete_option($this->settingsKey.'_bk');
			
		}
		
		#} Retrieve BKs
		function getBKs(){
			
			$x = get_option($this->settingsKey.'_bk');
			
			if (is_array($x)) return $x; else return array();
			
		}
		
		#} Reload from BK (bkkey will be a timestamp, use getBKs to list these keys)
		function reloadFromBK($bkkey){
		
			$backups = get_option($this->settingsKey.'_bk');
			
			if (isset($backups[$bkkey])) if (is_array($backups[$bkkey])) {
				
				#} kill existing settings and use backed up ones
				$this->settings = $backups[$bkkey];
				
				#} Save 
				$this->saveToDB();
			
				return true;	
				
			} 
			
			return false;
				
			
		}
		
		
		
	}
	
?>