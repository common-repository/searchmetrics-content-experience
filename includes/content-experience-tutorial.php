<?php
//tutorial
add_action('admin_menu', 'smce_register_content_experience_tutorial_page');
function smce_register_content_experience_tutorial_page(){
    add_submenu_page(null, '', '', 'edit_posts', 'content-experience-tutorial', 'smce_content_experience_tutorial');
}

//add tutorial javascript variable
add_action('admin_head', 'smce_tutorial_js_data');
function smce_tutorial_js_data(){   
    echo '<script>var smAdminURL = "'.admin_url(),'"</script> ';
    if(isset($_GET['page'])){
        $page = sanitize_key($_GET['page']);
    }else{
        $page = '';
    }
    if($page === 'content-experience-tutorial'){
        $step = isset($_GET['step']) ? intval(sanitize_key($_GET['step'])) : 0;
        if(!$step){
           $step = 0;
        }?>
    <script>
        var sm_tutorial_step = <?php echo esc_html($step)?>;
    </script>    
    <?php }else{
        ?>
    <script>
        var sm_tutorial_step = 0;
    </script>
        <?php
    }
};

//adding tutorial styles
add_action('admin_head', 'smce_content_experience_admin_tutorial_styles');
function smce_content_experience_admin_tutorial_styles() {
    if(isset($_GET['page'])){
        $page = sanitize_key($_GET['page']);
    }else{
        $page = '';
    }
    if($page === 'content-experience-tutorial'){
    ?>
<style>  
    .sm-tutorial-intro{
        background-image: url('<?php echo plugins_url('/images/intro.svg', __DIR__)?>');       
    }
</style>
    <?php
    }
};

function smce_admin_tutorial_style(){
    if(isset($_GET['page'])){
        $page = sanitize_key($_GET['page']);
    }else{
        $page = '';
    }
    if($page === 'content-experience-tutorial'){      
        wp_enqueue_style( 'admin_css', plugins_url( 'css/sm-tutorial.css', __DIR__ ), false, '1.2.5' );
    }
}
add_action( 'admin_enqueue_scripts', 'smce_admin_tutorial_style' );

//adding tutorial javascript
function smce_tutorial_script(){
    wp_enqueue_script( 'sm-tutorial-js', plugins_url( '/js/sm-tutorial.js', __DIR__ ), array('jquery'), '1.1.2' );
}
add_action( 'admin_enqueue_scripts', 'smce_tutorial_script' );

//set tutorial inactive
add_action( 'wp_ajax_content_experience_hide_tutorial', 'smce_hide_tutorial' );
function smce_hide_tutorial(){
    update_option( 'content_experience_tutorial_active', 0);
    die;
}

//tutorial output
function smce_content_experience_tutorial(){
    $searchmetrics_api_key = get_option('searchmetrics_api_key');
    $searchmetrics_api_secret = get_option('searchmetrics_api_secret');
    ?>
<div class="sm-tables wrap center">
    <div class="sm-steps sm-step1">        
        <div class="sm-tutorial-intro">
            <div class="sm-close">X</div>
            <h2><?php esc_html_e('Welcome', 'searchmetrics-content-experience')?></h2>            
            <div class="sm-intro-text">
                <p><?php esc_html_e('Thank you for downloading the Searchmetics Content Experience plugin.', 'searchmetrics-content-experience')?></p>
                <p><?php esc_html_e('From now on you can display your content briefings directly in the WordPress Editor where you write and optimize your posts.', 'searchmetrics-content-experience')?></p>
            </div>            
            <p class="actionBtn"><input type="button" class="button button-primary sm-next" id="Weiter" value="<?php esc_html_e('CONTINUE', 'searchmetrics-content-experience')?>" /></p>
        </div>        
    </div>

    <div class="sm-steps sm-step3">
        <div class="sm-tutorial-intro">
            <div class="sm-close">X</div>
            <h2>Searchmetrics API</h2>
            <div class="sm-intro-text">
                <p><?php esc_html_e('To get the necessary data in your WordPress editor, you need a Searchmetrics API. You can get this in the Searchmetrics Suite.', 'searchmetrics-content-experience')?></p>
                <p><a href="https://developers.searchmetrics.com/api-key" target="_blank">https://developers.searchmetrics.com/api-key</a></p>
                <div class="wrap-form">
                    <?php if(isset($_GET['credentials_status']) and sanitize_key($_GET['credentials_status']) === 'success'){?>
                    <p class="sm-success"><?php esc_html_e('Your Searchmetrics API Key and Secret have been accepted.', 'searchmetrics-content-experience')?></p>
                    <?php }elseif(isset($_GET['credentials_status']) and sanitize_key($_GET['credentials_status']) === 'error'){?>
                    <p class="sm-error"><?php esc_html_e('Searchmetrics API key or secret are not correct.', 'searchmetrics-content-experience')?></p>
                    <?php }?>

                    <form method="post" action="<?php echo esc_url(admin_url( 'admin-post.php' )); ?>">
                        <input type="hidden" name="action" value="submit_content_experience_tutorial_credentials"/>
                        <?php wp_nonce_field( 'submit_content_experience_tutorial_credentials', 'submit_content_experience_tutorial_credentials_nonce' ); ?>
                        <table class="form-table sm-table">
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Searchmetrics API Key', 'searchmetrics-content-experience')?></label></th>
                                <td class="sm-input">
                                    <input type="text" name="data[api_key]" value="<?php echo esc_html($searchmetrics_api_key)?>" class="credentials"/>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Searchmetrics API Secret', 'searchmetrics-content-experience')?></label></th>
                                <td class="sm-input">
                                    <input type="password" name="data[api_secret]" value="<?php echo esc_html($searchmetrics_api_secret)?>" class="credentials"/>
                                </td>
                            </tr>
                        </table>
                        <p style="margin-top:0">
                            <input type="submit" class="button button-primary sm-next filled" value="<?php esc_html_e('Check API Data', 'searchmetrics-content-experience')?>" />
                            <input id="nextFinish" type="button" class="button button-primary sm-next<?php if(!isset($_GET['credentials_status']) or sanitize_key($_GET['credentials_status']) !== 'success') echo " disabled"?>" value="<?php esc_html_e('CONTINUE', 'searchmetrics-content-experience')?>" />                            
                        </p>
                    </form>
                </div> 
            </div>
            
        </div>
    </div>
    
    <div class="sm-steps sm-step4">
        <div class="sm-tutorial-intro">
            <div class="sm-close">X</div>
            <h2><?php esc_html_e('Thank you, you have successfully set up the plugin. We hope you\'ll enjoy writing and creating content.', 'searchmetrics-content-experience')?></h2>
            <div class="sm-intro-text">
                <p><?php esc_html_e('If you have any questions or suggestions, you can reach out any time using our support.', 'searchmetrics-content-experience')?></p>
            </div>
            <p class="actionBtn"><input id="Finish" type="submit" class="button button-primary sm-next" value="<?php esc_html_e('DONE', 'searchmetrics-content-experience')?>" onClick="smHideTutorialNotice('<?php echo esc_url(admin_url('options-general.php?page=content_experience_setup&tab=searchmetrics_api'))?>');"/></p>
        </div>
    </div>
</div>
    <?php
}

//saving searchmetrics credentials
add_action( 'admin_post_submit_content_experience_tutorial_credentials', 'smce_process_content_experience_tutorial_credentials_form_data' );
function smce_process_content_experience_tutorial_credentials_form_data(){
    $nonce = isset($_POST['submit_content_experience_tutorial_credentials_nonce']) ? esc_attr($_POST['submit_content_experience_tutorial_credentials_nonce']) : '';
    if(wp_verify_nonce($nonce, 'submit_content_experience_tutorial_credentials')){
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
                    add_query_arg( 'credentials_status', 'error', admin_url('admin.php?page=content-experience-tutorial&step=3') )
                )
            );
        }else{
            wp_safe_redirect(
                esc_url_raw(
                    add_query_arg( 'credentials_status', 'success', admin_url('admin.php?page=content-experience-tutorial&step=3') )
                )
            ); 
        }
    }
}