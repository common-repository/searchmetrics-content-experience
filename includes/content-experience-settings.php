<?php
if(!defined('ABSPATH')) {
  die('You are not allowed to call this page directly.');
}

//adding items to the Settings menu
add_action('admin_menu', 'smce_register_content_experience_setup_page');
function smce_register_content_experience_setup_page(){
    add_submenu_page('options-general.php', 'Content Experience', 'Content Experience', 'edit_posts', 'content_experience_setup', 'smce_content_experience_setup');
}

//adding setup page styles
add_action('admin_head', 'smce_content_experience_admin_styles');
function smce_content_experience_admin_styles() {
    if(isset($_GET['page'])){
        $page = sanitize_key($_GET['page']);
    }else{
        $page = '';
    }
    if($page === 'content_experience_setup'){
    ?>
<style>
    #wpcontent{
        padding-left:0;
    }
    .sm-tables.wrap{
        margin:0;        
    }
    .sm-tables.wrap .wrap{
        margin-bottom:40px;
    }
    .sm-tables.wrap h3{
        margin-bottom: 5px;
    }
    .sm-content-wrapper{
        margin: 0 20px 0 20px;
    }
    .sm-table input[type="text"],.sm-table input[type="password"]{
        width: 480px
    }
    .sm-next{
        text-align: center;
        margin: 80px auto 30px auto;
    }
    .sm-next .button{
        width: 150px;
    }
    @media screen and (max-width: 782px){
        .auto-fold #wpcontent {
            padding-left: 0px;
        }
    }
</style>
    <?php
    }
};

//plugin settings
function smce_content_experience_setup(){
    if(isset($_GET['tab'])){
        $tab = sanitize_key($_GET['tab']);
    }else{
        $tab = '';
    }
    $active_tab = !empty( $tab ) ? $tab : 'searchmetrics_api';
    $pluginData = get_plugin_data(plugin_dir_path(__DIR__).'/searchmetrics-content-experience.php');
    $pluginVersion = $pluginData['Version'];
    $searchmetrics_api_key = get_option('searchmetrics_api_key');
    $searchmetrics_api_secret = get_option('searchmetrics_api_secret');
    $tutorial_active = get_option('content_experience_tutorial_active');
    
    ?>
    <div class="sm-tables wrap">        
        <div class="sm-content-wrapper">
            <h2 class="nav-tab-wrapper">
                <a href="?page=content_experience_setup&tab=searchmetrics_api" class="nav-tab<?php echo $active_tab === 'searchmetrics_api' ? ' nav-tab-active' : ''; ?>">Searchmetrics API</a>
                <?php /*temporary hidden
                <a href="?page=content_experience_setup&tab=documentation" class="nav-tab<?php echo $active_tab === 'documentation' ? ' nav-tab-active' : ''; ?>"><?php esc_html_e('Documentation', 'searchmetrics-content-experience')?></a>
                */?>
                <a href="?page=content_experience_setup&tab=support" class="nav-tab<?php echo $active_tab === 'support' ? ' nav-tab-active' : ''; ?>"><?php esc_html_e('Support', 'searchmetrics-content-experience')?></a>                
                
            </h2>
    
    <?php if($active_tab === 'searchmetrics_api'){?>
            <h3><?php esc_html_e('Searchmetrics API', 'searchmetrics-content-experience')?></h3>
            <div class="wrap">
                <form method="post" action="<?php echo esc_url(admin_url( 'admin-post.php' )); ?>">
                    <input type="hidden" name="action" value="submit_content_experience_credentials"/>
                    <?php wp_nonce_field( 'submit_content_experience_credentials', 'submit_content_experience_credentials_nonce' ); ?>
                    <table class="form-table sm-table">
                        <tr>
                            <th scope="row"><label><?php esc_html_e('Searchmetrics API Key', 'searchmetrics-content-experience')?></label></th>
                            <td>
                                <input type="text" name="data[api_key]" value="<?php echo esc_html($searchmetrics_api_key)?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php esc_html_e('Searchmetrics API Secret', 'searchmetrics-content-experience')?></label></th>
                            <td>
                                <input type="password" name="data[api_secret]" value="<?php echo esc_html($searchmetrics_api_secret)?>" />
                            </td>
                        </tr>
                    </table>       
                    <p><input type="submit" class="button button-primary" value="<?php esc_html_e('Submit', 'searchmetrics-content-experience')?>" /></p>
                </form>
            </div>
    <?php }elseif($active_tab === 'documentation'){?>
            <h3><?php esc_html_e('Documentation', 'searchmetrics-content-experience')?></h3>
            <div class="wrap">
                <p><?php esc_html_e('Our documentation is the first place to go for questions about our plugin, which we might have already answered.', 'searchmetrics-content-experience')?></p>                
            </div>
    <?php }elseif($active_tab === 'support'){?>
            <h3><?php esc_html_e('Support', 'searchmetrics-content-experience')?></h3>
            <div class="wrap">
                <p>
                    <?php esc_html_e('Contact our excellent support.', 'searchmetrics-content-experience')?>
                </p>
                <p>
                    <?php esc_html_e('Please contact us', 'searchmetrics-content-experience')?>:<br />
                    <a href="mailto:support@searchmetrics.com">support@searchmetrics.com</a>
                </p>
            </div>
    <?php }?>
        </div>
    </div>
    <?php
}

//saving searchmetrics credentials
add_action( 'admin_post_submit_content_experience_credentials', 'smce_process_content_experience_credentials_form_data' );
function smce_process_content_experience_credentials_form_data(){
    $nonce = isset($_POST['submit_content_experience_credentials_nonce']) ? esc_attr($_POST['submit_content_experience_credentials_nonce']) : '';
    if(wp_verify_nonce($nonce, 'submit_content_experience_credentials')){
        $apikey = $apisecret = '';
        if(isset($_POST['data']['api_key'])){
            $apikey = sanitize_text_field($_POST['data']['api_key']);
            update_option('searchmetrics_api_key', $apikey); 
            
        }
        if(isset($_POST['data']['api_secret'])){
            $apisecret = sanitize_text_field($_POST['data']['api_secret']);
            update_option('searchmetrics_api_secret', $apisecret);     
        }
        smce_delete_options('content_experience_sm');
        smce_delete_options('content_experience_current');
        smce_delete_options('content_experience_recent');
        sleep(1);
        $aLicense = smce_getLicense($apikey, $apisecret, true);
        if(!$aLicense['result']){
            $licenseError = true;
        }else{
            $licenseError = false;
        }
    
        if($licenseError){
            wp_safe_redirect(
                esc_url_raw(
                    add_query_arg( 'searchmetrics_status', 'error', admin_url('options-general.php?page=content_experience_setup&tab=searchmetrics_api') )
                )
            );
        }else{
            wp_safe_redirect(
                esc_url_raw(
                    add_query_arg( 'searchmetrics_status', 'success', admin_url('options-general.php?page=content_experience_setup&tab=searchmetrics_api') )
                )
            ); 
        }
    }
}

//notifications output
if(isset($_GET['searchmetrics_status'])){    
    $searchmetrics_status = sanitize_key($_GET['searchmetrics_status']);
    if($searchmetrics_status === 'error'){
        function smce_admin_sm_notice(){
            echo '<div class="notice notice-error is-dismissible">
                     <p>'.__('Searchmetrics API key or secret are not correct.', 'searchmetrics-content-experience').'</p>
                 </div>';
        }
        add_action('admin_notices', 'smce_admin_sm_notice');
    }
    if($searchmetrics_status === 'success'){
        function smce_admin_sm_notice(){
            echo '<div class="notice notice-success is-dismissible">
                     <p>'.__('Your Searchmetrics API Key and Secret have been accepted.', 'searchmetrics-content-experience').'</p>
                 </div>';
        }
        add_action('admin_notices', 'smce_admin_sm_notice');
    }
}