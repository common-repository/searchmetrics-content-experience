<?php
/*
 * Plugin Name:         Searchmetrics Content Experience
 * Plugin URI: 	        https://wordpress.org/plugins/searchmetrics-content-experience/
 * Description:         The "Searchmetrics Content Experience WordPress Plugin" builds the bridge between WordPress and Searchmetrics.
 * Version:             2.2
 * Requires at least:   4.6
 * Requires PHP:        5.2.4
 * Author:              Searchmetrics GmbH
 * Author URI: 	        https://www.searchmetrics.com/suite/content-experience/
 * Domain Path:	        /languages
 * Text Domain:         searchmetrics-content-experience
 */
include_once 'includes/config.php';

include_once 'includes/content-experience-settings.php';

include_once 'includes/content-analyzer.php';

include_once 'includes/func.php';

include_once 'includes/content-experience-tutorial.php';

//tutorial notice output
add_action( 'admin_notices', 'smce_plugin_activate_notice' );
function smce_plugin_activate_notice(){
    if( get_option( 'content_experience_tutorial_active' ) ){
        ?>
        <div class="notice notice-success sm-tutorial-notice">
            <p>
                <?php esc_html_e('Welcome to Searchmetrics Content Experience – You\'re almost ready to start.', 'searchmetrics-content-experience')?>
            </p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=content-experience-tutorial')?>" class="button-primary"><?php echo __('Run the Tutorial', 'searchmetrics-content-experience')?></a>
                <a class="button-secondary skip" href="#"><?php esc_html_e('Skip Tutorial', 'searchmetrics-content-experience')?></a>
            </p>
        </div>
        <?php
    }
}

//language support
function smce_plugin_load_plugin_textdomain() {
    load_plugin_textdomain( 'searchmetrics-content-experience', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'smce_plugin_load_plugin_textdomain' );

//adding Settings link to the Plugins page
function smce_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=content_experience_setup&tab=searchmetrics_api">Settings</a>'; 
  array_push($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'smce_plugin_settings_link' );

//activate
register_activation_hook( __FILE__, 'smce_content_experience_activate' );
function smce_content_experience_activate() {
    update_option( 'content_experience_tutorial_active', 1);
}

function smce_activation_redirect( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect( admin_url( 'admin.php?page=content-experience-tutorial' ) ) );
    }
}
add_action( 'activated_plugin', 'smce_activation_redirect' );

//uninstall
register_uninstall_hook( __FILE__, 'smce_content_experience_uninstall' );
function smce_content_experience_uninstall() {
    delete_option('searchmetrics_api_key');
    delete_option('searchmetrics_api_secret');
    smce_delete_options('content_experience_');
}
