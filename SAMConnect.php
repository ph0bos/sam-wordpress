<?php
/*
Plugin Name: SAM Connect
Plugin URI: http://www.samdesk.io
Description: <a href="http://www.samdesk.io">SAM Connect</a> connects your WordPress blog with your SAM Social Asset Management system.
Version: 1.0
Author: SAM
http://www.samdesk.io
*/

#} Hooks

    #} Install/uninstall
    register_activation_hook(__FILE__,'SAMConnect__install');
    register_deactivation_hook(__FILE__,'SAMConnect__uninstall');
    
    #} General
	add_action('init', 'SAMConnect__init');
    add_action('admin_menu', 'SAMConnect__admin_menu'); 
    add_action('admin_head', 'SAMConnect__adminheader_includes'); 
    add_action('wp_head', 'SAMConnect__publicheader_includes'); 
    
#=======================================================================================================================
					# GLOBALS
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================

#} Globals 

	#} Initial Vars
	global $SAMConnect_db_version,$SAMConnect_version;
	$SAMConnect_db_version 			= "0.0";
	$SAMConnect_version 				= "1.0";
	
	#} Urls
	global $SAMConnect_urls;
	$SAMConnect_urls['home'] 		= 'http://www.samdesk.io';
	$SAMConnect_urls['app']			= 'http://app.samdesk.io';
	$SAMConnect_urls['support']		= 'mailto:support@samdesk.io';
	//$SAMConnect_urls['apiroot']		= 'http://app.samdesk.io/json/0.1/'; #} HTTP here over https? Curl doesn't like s
	$SAMConnect_urls['apiroot']		= 'http://localhost:3000/api/v1/';
		
	#} Page slugs
	global $SAMConnect_slugs;
	$SAMConnect_slugs['home'] 			= "sam-plugin";
	$SAMConnect_slugs['settings'] 		= "sam-plugin-settings";
	
	#} Extras (Dirs..)
	define( 'SAMConnectPLUGIN_PATH', plugin_dir_path(__FILE__) );
	define( 'SAMConnectPLUGIN_URL', plugin_dir_url(__FILE__) );
	
	#} For use on-page
	global $SAMConnect_assetIndex;
	$SAMConnect_assetIndex = 1;
		
	#} Settings Model. Total req. > v0.1 
	if(!class_exists('SAMConnectSettings')) 
		require_once(SAMConnectPLUGIN_PATH . 'includes/SAMConnect.Options.php');

	#} Init settings model
	global $SAMSettings; $SAMSettings = new SAMSettings();	

	#} Init SAM API library
	require_once(SAMConnectPLUGIN_PATH . 'includes/sam-php/lib/SAM.php');
	SAM::setApiKey($SAMSettings->get('api_key'));
	SAM::setApiSecret($SAMSettings->get('api_secret'));

#=======================================================================================================================
					# INSTALL/UNINSTALL
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================


#} Install function
function SAMConnect__install(){
	
	global $SAMSettings, $SAMConnect_version, $SAMConnect_db_version;	#} Req

	#} Initialising settings no happens via Settings Class
	if (!is_array($SAMSettings->getAll())){
		
		add_action('admin_notices','SAMConnect__settingsfail');function SAMConnect__settingsfail(){echo '<div class="error"><p>SAM Connect Could not create its options object!</p></div>';}
		
	}
	
}

#} Uninstall
function SAMConnect__uninstall(){
	
	#} Removes initial settings, leaves config intact for upgrades.
    #delete_option("SAMConnect_db_version");
    #delete_option("SAMConnect_version"); 
	
	#} This should: back up options then delete main model
	#global $SAMSettings;	#$SAMSettings->uninstall(); 
    
}

#=======================================================================================================================
function SAM_________INIT(){}				# INIT + MENUS
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================

#} Initialisation - enqueueing scripts/styles
function SAMConnect__init(){
  
	global $SAMSettings, $SAMConnect_slugs; #} Req
		
	#} Admin & Public
	wp_enqueue_script("jquery");
	
	#} Public 	
	wp_enqueue_style('SAMConnectPluginCSS', plugins_url('/css/SAMConnect.css',__FILE__) );

	#} Admin only
	if (is_admin()) {
		
		#} Admin CSS
		wp_enqueue_style('SAMConnectPluginCSSADM', plugins_url('/css/SAMConnectAdmin.css',__FILE__) );
		
		#} Admin JS
		wp_register_script('SAMConnectPluginJSADM', plugins_url('/js/SAMConnectAdmin.js',__FILE__), array('jquery') );
		wp_enqueue_script('SAMConnectPluginJSADM');
		
		#} Register Query Var
		add_filter('query_vars','SAMConnect_query_vars');
		
		#} Add Visual aid to editor
		SAMConnect_WYSIWYG_add();
		
	}	
	
}

#} Hook in isotope where needed
function SAMConnect_enqueue_via_hook($hook_suffix) {
    
			if ($hook_suffix == 'post.php' || $hook_suffix == 'post-new.php'){
				
				#} Isotope!
				wp_register_script('SAMConnectPluginJSIsotope', plugins_url('/js/jquery.isotope.min.js',__FILE__), array('jquery') );
				wp_enqueue_script('SAMConnectPluginJSIsotope');
				
			}
}
add_action('admin_enqueue_scripts', 'SAMConnect_enqueue_via_hook');

#} Add the appropriate SAM "Add Social Asset" button where needed
function SAMConnect__adminheader_includes(){
	
	#} Add SAM Button
	add_action('media_buttons_context',  'SAMConnect_add_socialasset_button');
	
	#} Add Social Asset Picker HTML	
	add_action( 'admin_footer',  'SAMConnect_add_social_asset_picker' );
	
	#} Absolute Refs
	SAMConnect_headerRefs();
	
}

#} Include any absolute refs in public facing
function SAMConnect__publicheader_includes() {
	
	#} Absolute Refs
	# These aren't needed publically! SAMConnect_headerRefs();
	
}

#} Absolute references
function SAMConnect_headerRefs(){
	
	#} Abs ref'd stuff
	echo '<!--SAMConnect V1.0-->';
	echo '<script>var SAMCU = \''.SAMConnectPLUGIN_URL.'\';</script>';
	echo '<style type="text/css">.tweetFull .tags .tagsWrap .tag:hover {background: #f7a8a8 url('.SAMConnectPLUGIN_URL.'i/37-circle-x-red.png) 4px 6px no-repeat}.tweet .tweetMeta div.tweetAt {background: url('.SAMConnectPLUGIN_URL.'i/19-clock-18.png) 0 1px no-repeat; background-size:12px; }</style>';

}

#} Declare SAM Button
function SAMConnect_add_socialasset_button() {
	
	#} req.
	global $SAMSettings;
	
	#} Verified API Key?
	$scConfig = $SAMSettings->getAll();
	if (!empty($scConfig['api_key']))	
		if ($scConfig['api_key_verified'] == 1) {	
			#} Adapted straight from WP button
			?><a href='#' class="button add_asset" data-editor="content" title="Add Social Asset" id="SAMStoryPicker"><span class="wp-media-buttons-icon" style="width:18px;background: url('<?php echo plugins_url('i/sam-grey-1812.png',__FILE__); ?>') no-repeat top left;"></span> Add Social Asset</a><?php
		}
	
}

#} Add le admin menu
function SAMConnect__admin_menu() {

	global $SAMConnect_slugs; #} Req
	
	add_menu_page( 'SAM Connect', 'SAM Connect', 'manage_options', $SAMConnect_slugs['home'], 'SAMConnect_pages_home', plugins_url('i/icon.png',__FILE__));
    add_submenu_page( $SAMConnect_slugs['home'], 'Settings', 'Settings', 'manage_options', $SAMConnect_slugs['settings'], 'SAMConnect_pages_settings' );
		
}

#} Run at all page points of load in admin.
add_action('admin_notices','SAMConnect_adminGeneralCheck');
function SAMConnect_adminGeneralCheck(){

	if (is_admin()){
				
		#} Check for updates
		#SAMConnect_checkForUpdatesHeader();	
	
	}
}


#} Custom AJAX Handler 
add_action('wp_ajax_SAMConnect', 'SAMConnect_XML_Response');
function SAMConnect_XML_Response(){

	if (is_admin()){
		
		global $SAMSettings;
		#} Verified api key?
		$verifiedAPIKey = $SAMSettings->get('api_key_verified');
		$apiKey = $SAMSettings->get('api_key');
		if ($verifiedAPIKey == 1 && !empty($apiKey)){
	
			if (isset($_POST['SAMRequestVar']['t'])){
					
					$acceptable = array('stories','story'); #,'go'
					if (in_array($_POST['SAMRequestVar']['t'],$acceptable)){
						
						switch ($_POST['SAMRequestVar']['t']){
							
							case 'stories':
								echo json_encode(SAMConnect_retrieveStoriesList());
								break;	
								
							case 'story':
								$sid = $_POST['SAMRequestVar']['sid'];					
								echo json_encode(SAMConnect_retrieveStoryAssets($sid));
								break;	
								
							default:
								echo json_encode(array('error'=>true));
								break;
						}
						
					}
					
					
				}
			}
		
		} # End if is admin

	exit();	
	
}


#} Register Query Vars
function SAMConnect_query_vars($vars){
	
	global $SAMSettings;
			
	#} Request Var for AJAX load of stories through CURL		
	array_push($vars, 'SAMRequestVar'); 
	
	return $vars;
}



#} Shortcode in WYSIWYG
function SAMConnect_WYSIWYG_add_plugin( $plugin_array ) {
   $plugin_array['samconnect'] = plugins_url('/sam-wordpress/js/SAMConnectAdmin.mce.js'); 
   return $plugin_array;
}
function SAMConnect_WYSIWYG_add() {

   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) return;

   if ( get_user_option('rich_editing') == 'true' ) {
      #add_filter( 'mce_external_plugins', 'mobileSliderPro_WYSIWYG_add_plugin' );
      add_filter( 'mce_external_plugins', 'SAMConnect_WYSIWYG_add_plugin' );
   }

}


#=======================================================================================================================
function SAM__________PAGES(){}					# Pages
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#} Home page Catcher
function SAMConnect_pages_home() {
	
	global $SAMConnectWizardPrompt, $wpdb, $SAMConnect_urls, $SAMConnect_version, $SAMSettings;	#} Req
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
		
	#} Header
	SAMConnect_pagecomponents_header();
		
	#} Identify what's to do
	$toSave = false; if (isset($_GET['save'])) if ($_GET['save'] == "1") $toSave = true; 

	 if ($toSave && !$toCrawl){
	 
			SAMConnect_save_settings(); 
	 
	} else { 
	
			SAMConnect_pages_home_html(); 
		
	}	
		
	?></div><?php 
	
}


#} ROOT page
function SAMConnect_pages_home_html(){
    	
	global $wpdb, $SAMConnect_db_version, $SAMConnect_version, $SAMConnect_urls, $SAMConnect_slugs, $SAMSettings;	#} Req 

	#} Get this
	$scConfig = $SAMSettings->getAll();
	
	#} Work out status
	$pluginStatus = 'API Key Needed';
	if (!empty($scConfig['api_key'])){
		
		if ($scConfig['api_key_verified'] == 1)
			$pluginStatus = 'API Key Entered & Verified';
		else
			$pluginStatus = 'API Key Invalid';
			
	}
	
	#} Work out action
	$pluginStatusAction = '';
	if ($pluginStatus != 'API Key Entered & Verified') $pluginStatusAction = '<a href="?page='.$SAMConnect_slugs['settings'].'" class="button button-primary button-large">Enter your API Key</a>';
	
	?>
	<div class="samConnectPage">
        <p class="samConnectPageIntro">Welcome to SAM Connect, using this WordPress plugin you can connect your WordPress blog to your SAM account and utilise Social Assets from your Stories in your WordPress posts and pages.</p>
        <p class="samPluginStatus">Plugin Status: <span class="<?php if ($pluginStatus == 'API Key Entered & Verified') echo 'green'; else echo 'red'; ?>"><?php echo $pluginStatus; ?></span></p>
        <p class="samPluginStatusAction"><?php echo $pluginStatusAction; ?></p>
        <div class="samCopy">&copy; Copyright Social Asset Management Ltd. 2013-<?php echo date('Y'); ?></div>
    </div>
   <?php
}

#} Settings Page Catcher
function SAMConnect_pages_settings(){
	
		$toSave = false; if (isset($_GET['save'])) if ($_GET['save'] == "1") $toSave = true; 
		
		if ($toSave){
		
			SAMConnect_save_settings(); 
		
		} else { 
		
			SAMConnect_pages_settings_html(); 
			
		}	
				
}

#} Settings Page Proper
function SAMConnect_pages_settings_html(){
	
	global $wpdb, $SAMConnect_db_version, $SAMConnect_version, $SAMConnect_urls, $SAMConnect_slugs, $SAMSettings;	#} Req
    
	#} Lazy retrieval of all
	$sgConfig = $SAMSettings->getAll();
	
	SAMConnect_pagecomponents_header();

    global $SAMConnectSavedSettingsFlag; if (isset($SAMConnectSavedSettingsFlag)) if ($SAMConnectSavedSettingsFlag) SAMConnect_html_msg(0,"Saved options");
	
	$tabToShow = 'General';
	if (isset($_POST['loadTab'])) $tabToShow = sanitize_text_field($_POST['loadTab']);
	
		?>
        <p id="sgpDesc">Here you can set the configuration options for your SAM Connect Plugin.</p>
        <form action="?page=<?php echo $SAMConnect_slugs['settings']; ?>&save=1" id="settingsForm" method="post">
        <input type="hidden" value="<?php echo $tabToShow ?>" name="loadTab" id="loadTab" />
        <div id="SAMConnectSettings">
            <div id="SAMConnectMenu">
                <div<?php if ($tabToShow == "General") echo ' class="SAMConnectPageActive"'; ?> id="General">General</div>
                <?php if (isset($usethistoaddpages)){ ?>
                <div<?php if ($tabToShow == "Style") echo ' class="SAMConnectPageActive"'; ?> id="Style">Style</div>
                <?php } ?>
            </div>
            <div id="SAMConnectSettingsPage">         
            
                <div id="SAMConnectPageGeneral" class="SAMConnectPage<?php if ($tabToShow == "General") echo ' SAMConnectPageActive'; ?>">
           			<h3>General Settings</h3>
                    <table width="715" border="0" cellpadding="0" cellspacing="0" class="sgpSettingsTable">
                    
                    <?php SAMConnect_trhd('General Settings',0); ?>                    
                    <tr>
                        <td class="sgFieldLabel">API Key</td>
                        <td class="sgField">
                            <input type="text" name="SAMConnect_api_key" id="SAMConnect_api_key" value="<?php if (!empty($sgConfig['api_key'])) echo $sgConfig['api_key']; ?>"  />
                            <?php
							
								if (!empty($sgConfig['api_key'])){
									
									if ($sgConfig['api_key_verified'] == 1){
										
										?><div id="samVerified">Verified!</div><?php
										
									} else {
										
										?><div id="samUnVerified">Invalid!</div><?php
											
									}
									
								} 
							
							?>
                        </td>
                    </tr>
                    <tr>
                        <td class="sgFieldLabel">API Secret</td>
                        <td class="sgField">
                            <input type="text" name="SAMConnect_api_secret" id="SAMConnect_api_secret" value="<?php if (!empty($sgConfig['api_secret'])) echo $sgConfig['api_secret']; ?>"  />
                            <br />You can find your API key/secret in SAM->Account->API Keys
                        </td>
                    </tr>
                    <!-- This Feature is not present in this version
                    <tr>
                        <td class="sgFieldLabel sgFieldLabelCB">Share Usage</td>
                        <td class="sgField">
                            <div class="sgFieldCB<?php if ($sgConfig['share_usage'] == "1") echo ' on'; ?>">
                                <span class="thumb"></span>
                                 <input type="checkbox" name="SAMConnect_share_usage" id="SAMConnect_share_usage" value="1" <?php if ($sgConfig['share_usage'] == "1") echo ' checked="checked"'; ?> />
                            </div>    
                            Enabling this feature discretly shares your usage of this plugin with SAM, this will help us improve the plugin for you!
                        </td>
                    </tr> 
                    -->
           			</table>
                </div>
                <div id="SAMConnectSaveButton"><input type="submit" value="Save All Settings" class="bButton" /></div>
                
            </div>
                      	
        </div>
        </form>
        <script type="text/javascript">jQuery(document).ready(function(ex) {            	
				jQuery('#SAMConnectMenu div').unbind('click').click(function(e) {
						var page = jQuery(this).attr('id');
						jQuery(this).removeClass('SAMConnectPageActive');
						jQuery('.SAMConnectPageActive').removeClass('SAMConnectPageActive');
						jQuery('#SAMConnectPage' + page).addClass('SAMConnectPageActive').slideDown(400);
						jQuery('#' + page).addClass('SAMConnectPageActive');	
						jQuery('#loadTab').val(page);					
						window.location.hash = '#settings_' + page;
						e.preventDefault();				
            	});   				
				
				if (typeof window.location.hash != "undefined")
					jQuery('#' + window.location.hash.substr(10)).click();
									
            });</script><?php
		
}




#=======================================================================================================================
function SAM_________________COGS(){}					# Cogs
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================

#} Save options changes 
function SAMConnect_save_settings(){
    
	global $wpdb, $SAMConnect_db_version, $SAMConnect_t, $SAMConnect_urls, $SAMConnect_slugs, $SAMSettings;	#} Req
	
	$sgConfig = array();
	$sgConfigOptions = array(	
								'api_key' => '',
								'api_secret' => '',
								'share_usage' => 1,
								'api_key_verified' => 0
																
								);
	#} Retrieve
	foreach ($sgConfigOptions as $option => $default)
		if (isset($_POST['SAMConnect_'.$option])) 
			$sgConfig[$option] = $_POST['SAMConnect_'.$option]; 
		else 
			$sgConfig[$option] = ''; #$default; - Don't specifically overwrite blanks with default as blanks will be turning off checkboxes. 
	
	#} Validate
	$intAbles = array('share_usage');
	foreach ($intAbles as $i) if ($sgConfig[$i] == '') $sgConfig[$i] = 0; else $sgConfig[$i] = (int)$sgConfig[$i]; 
	
	#} Verify API Key
	$verified = false; 
	if ( ! empty( $sgConfig['api_key'] ) && ! empty( $sgConfig['api_secret'] ) ) {

		if ( SAMConnect_verifyAPIKey( $sgConfig['api_key'], $sgConfig['api_secret'] ) ) {
			$sgConfig['api_key_verified'] = 1;
		} else {
			$sgConfig['api_key_verified'] = 0;
		}
			
		#} Save it
		$SAMSettings->update('api_key_verified', $sgConfig['api_key_verified']);
	}
	
    #} Save down  
	$SAMSettings->update('api_key', $sgConfig['api_key']);
	$SAMSettings->update('api_secret', $sgConfig['api_secret']);
	$SAMSettings->update('share_usage', $sgConfig['share_usage']);
	
    #} Msg(s)
    global $SAMConnectSavedSettingsFlag; $SAMConnectSavedSettingsFlag = true; 
		
    #} Run standard page
    SAMConnect_pages_settings_html();
    
}


#} Outputs HTML message
function SAMConnect_html_msg($flag,$msg,$includeExclaim=false){
	
    if ($includeExclaim){ $msg = '<div id="sgExclaim">!</div>'.$msg.''; }
    
    if ($flag == -1){
		echo '<div class="sgfail wrap sgM">'.$msg.'</div>';
	} 
	if ($flag == 0){
		echo '<div class="sgsuccess wrap sgM">'.$msg.'</div>';	
	}
	if ($flag == 1){
		echo '<div class="sgwarn wrap sgM">'.$msg.'</div>';	
	}
    if ($flag == 2){
        echo '<div class="sginfo wrap sgM">'.$msg.'</div>';
    }
}


#} Determines if this is our admin page
function SAMConnect_isAdminPage(){
	
	global $SAMConnect_slugs;
	
	$isOurPage = false;	
	if (isset($_GET['page'])) if (in_array($_GET['page'],$SAMConnect_slugs)) $isOurPage = true; 
	
	return $isOurPage;
	
}

#} Add's the story picker
function SAMConnect_add_social_asset_picker() {
?>
<div id="samWrap" style="display:none;">
	<div id="samStoryWrap">
    	<div id="samStoryList"></div>
        <div id="samStoryContent"></div>
        <div id="samLoading">
        	<img src="<?php echo plugins_url('i/sam-connect.png',__FILE__); ?>" /><br />
            <div>Retrieving your stories...</div>
        </div>
    </div>
</div>
<script type="text/javascript">
jQuery('document').ready(function(e) {
    initialiseStoryPicker();
});
</script>
<?php
}

#} Verify an API Key/Secret
function SAMConnect_verifyAPIKey($apiKey, $apiSecret){
	
	global $SAMConnect_urls;

	SAM::setApiKey($apiKey);
	SAM::setApiSecret($apiSecret);

	#} Try retrieval
	try {
		
		SAM_Account::retrieve();
		return true;
	
	} catch (Exception $e) {
		
		#{ /:o
		
	}
	
	return false;
	
}

#} Retrieve Stories List
function SAMConnect_retrieveStoriesList(){
	
	#} Try retrieval
	try {

		$_stories = array();
		$stories = SAM_Story::all();
		for ($i = 0; $i < count($stories); $i++) {
			array_push($_stories, $stories[$i]->__toArray(true));
		}
		return $_stories;
	
	} catch (Exception $e) {
		
		#{ /:o
		
	}
	
	return false;	
	
}

#} Retrieve Assets in a story
function SAMConnect_retrieveStoryAssets($sid){
	
	global $SAMConnect_urls,$SAMSettings;
	
	if (!empty($sid)){
	
		#} Try retrieval
		try {
			
			return SAM_Story::retrieve($sid)->__toArray(true);
		
		} catch (Exception $e) {
			
			#{ /:o
			
		}

	}
	
	return false;
	
}

#} Shortcode output
function SAMConnect_shortcode( $atts ){
	
	#} Create unique ID
	global $SAMConnect_assetIndex; $thisSliderID = $SAMConnect_assetIndex; $SAMConnect_assetIndex++;
	
	#} Set up return
	$returnHTML = '';
	
	#} Retrieve Settings
	extract($atts);
	
	#} Validate and build (only if id present)
	if (isset($atts['embedid']) && isset($atts['socialtype'])){
		$embedId = $atts['embedid'];
		$socialType = $atts['socialtype'];

		$returnHTML = '<div class="samAsset">'.SAMConnect_createEmbedHTML($embedId,$socialType).'</div>';
	}

	return $returnHTML;		
}

#} Define shortcode.
add_shortcode( 'SAMASSET', 'SAMConnect_shortcode' );

#} Create Tweet Embed HTML
function SAMConnect_createEmbedHTML($embedId,$socialType){
	
	$retHTML = '';
	switch($socialType) {
		case 'twitter':
			$retHTML = json_decode(file_get_contents('https://api.twitter.com/1/statuses/oembed.json?id='.$embedId))->html;
			break;
		case 'instagram':
			$retHTML = '<iframe src="//instagram.com/p/'.$embedId.'/embed/" width="612" height="710" frameborder="0" scrolling="no" allowtransparency="true"></iframe>';
			break;
		case 'youtube':
			$retHTML = '<iframe width="420" height="315" src="//www.youtube.com/embed/'.$embedId.'" frameborder="0" allowfullscreen></iframe>';
			break;
	}
	return $retHTML;
}

#=======================================================================================================================
function SAM________PAGECOMPONENTS(){}					# Page Components
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#=======================================================================================================================
#} Global page header
function SAMConnect_pagecomponents_header(){

	
	global $wpdb, $SAMConnect_urls, $SAMConnect_version, $SAMSettings,$SAMConnect_slugs;	#} Req
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
    
?>
<div id="sgpBody">
    <div class="wrap"> 
	    <h2 id="SAMConnectLogo" style="background:url(<?php echo plugins_url('/i/sam-connect.png',__FILE__); ?>) top left no-repeat"><span style="display:none">SAM Connect Plugin</span></h2> 
    </div>
    <div id="sgpHeader">
		<a href="<?php echo $SAMConnect_urls['home']; ?>" title="SAM Homepage" target="_blank">SAM Home</a> | 
		<a href="<?php echo $SAMConnect_urls['app']; ?>" title="SAM App" target="_blank">SAM App</a> | 
		<a href="<?php echo $SAMConnect_urls['support']; ?>" title="SAM Support" target="_blank">Support</a> | Version <?php echo $SAMConnect_version; ?>
    </div>
    <?php 	
	
	
	#} Deal with any hide msgs
	global $SAMConnectMessagehidden;
	if ($SAMConnectMessagehidden)			
		SAMConnect_html_msg(0,'Message hidden');
	
	
}


#} HTML Helper for settings pages
function SAMConnect_trhd($m,$paddingtop=-1){
	
	?><tr><td class="sgFieldLabelHD" colspan="2"<?php if ($paddingtop > -1) echo ' style="padding-top:'.$paddingtop.'"'; ?>><?php echo $m; ?></td></tr><tr><?php

} ?>