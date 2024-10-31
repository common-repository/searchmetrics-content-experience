<?php
include_once 'graphql.php';

//adding editor styles
function smce_admin_style(){
    $sm_screen = get_current_screen();
    if($sm_screen->base === 'post'){        
        wp_enqueue_style( 'admin_css', plugins_url( 'css/sm-admin.css', __DIR__ ), false, '1.2.5' );
        wp_enqueue_style( 'wpb-fa',  'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', false, '1.0.1');
        if(smce_is_gutenberg_active()){
            wp_enqueue_style( 'admin_css_modern', plugins_url( 'css/sm-admin-modern.css', __DIR__ ), false, '1.1.1' );
        }
    }
}
add_action( 'admin_enqueue_scripts', 'smce_admin_style' );

//adding editor javascript
add_action('admin_head', 'smce_post_id_script');
function smce_post_id_script(){    
    $sm_screen = get_current_screen();
    if($sm_screen->base === 'post'){
        $post_id = isset($_GET['post']) ? intval(sanitize_key($_GET['post'])) : 0;
        if($post_id){
            $currentBriefing = get_option('content_experience_current_briefing_'.$post_id);
        }else{
            global $post;
            $post_id = $post->ID;
            $currentBriefing = '';
        }
        ?>
    <script>
        var sm_force_load = 1;
        var sm_post_id = <?php echo $post_id?>;
        var sm_briefing_id = '<?php echo $currentBriefing ? esc_html($currentBriefing) : ''?>';
    </script>    
    <?php }
};

//Gutenberg editor mode
function smce_sidebar_plugin_register() {
    wp_register_script(
        'plugin-sidebar-js',
        plugins_url( 'js/sm-plugin-sidebar.js', __DIR__ ),
        array( 'wp-plugins', 'wp-edit-post', 'wp-element' )
    );
}
add_action( 'init', 'smce_sidebar_plugin_register' );

function smce_get_data_script(){
    $sm_screen = get_current_screen();
    if($sm_screen->base === 'post'){
        wp_register_script( 'sm-get-data-js', plugins_url( '/js/sm-get-data.js', __DIR__ ), array('jquery'), '1.1.2'  );
        $translation_array = array('messagekey_content_notice' => __('You have a content in the selected briefing. Do you want to add it to your post or replace your existing content with the briefing\'s one?', 'searchmetrics-content-experience'));
        wp_localize_script('sm-get-data-js', 'langvars', $translation_array);
        wp_enqueue_script( 'sm-get-data-js');            
    }
}
add_action( 'admin_enqueue_scripts', 'smce_get_data_script' );    

function smce_sidebar_plugin_script_enqueue() {
    $sm_screen = get_current_screen();
    if($sm_screen->base === 'post'){
        wp_enqueue_script( 'plugin-sidebar-js' );
    }    
}
add_action( 'enqueue_block_editor_assets', 'smce_sidebar_plugin_script_enqueue' );


//classic editor mode
function smce_add_custom_box(){
    $screens = ['post', 'page'];
    foreach ($screens as $screen) {
        add_meta_box(
            'sm_box_id',
            'Content Experience',
            'smce_custom_box_html',
            $screen,
            'side',
            'default',
            array(
                '__back_compat_meta_box' => true,
            )
        );
    }
}
add_action('add_meta_boxes', 'smce_add_custom_box');

function smce_custom_box_html(){
?>
<div class="sm-sidebar-content"><div class="sm-loader-container"><img src="<?php echo plugins_url( '/images/spinner.gif', __DIR__ )?>" class="sm-loader-img" alt="<?php esc_html_e('loading', 'searchmetrics-content-experience')?>"></div></div>
<?php 
}

//ajax
add_action( 'wp_ajax_content_experience', 'smce_get_sm_data' );
function smce_get_sm_data(){
    $forceLoad = intval(sanitize_key($_POST['force_load']));
    $post_id = isset($_POST['post_id']) ? intval(sanitize_key($_POST['post_id'])) : 0;
    header('Content-Type: application/json');
    if(isset($_POST['briefing_id'])){
        $currentBriefing = sanitize_text_field($_POST['briefing_id']);
        if(!empty($post_id)){
            update_option('content_experience_current_briefing_'.$post_id, $currentBriefing);
        }
    }else{
        if($post_id){
            $currentBriefing = get_option('content_experience_current_briefing_'.$post_id);
        }else{
            $currentBriefing = '';
        }
    }
    $searchmetrics_api_key = get_option('searchmetrics_api_key');
    $searchmetrics_api_secret = get_option('searchmetrics_api_secret');
    $aSMLicense = smce_getLicense($searchmetrics_api_key, $searchmetrics_api_secret);
    if($searchmetrics_api_key and $searchmetrics_api_secret and $aSMLicense['result']){
        $briefingContent = '';
        $output = '';            
        if($forceLoad){
            $aBriefings = smce_getBriefingsList($searchmetrics_api_key, $searchmetrics_api_secret);
            if($aBriefings['result']){
                $output.='<div class="sm-main-info first"><select id="smBriefingsSelector" class="sm-briefings-selector"><option value="">-'.__('Select briefing', 'searchmetrics-content-experience').'-</option>';
                foreach($aBriefings['response'] as $brief){
                    $output.='<option value="'.$brief['id'].'" '.($currentBriefing === $brief['id'] ? 'selected="selected"' : '').'>'.$brief['story'].'</option>';
                }
                $output.='</select></div>';

                //briefing
                if(!empty($currentBriefing)){
                    $aBriefing = smce_getBriefing($searchmetrics_api_key, $searchmetrics_api_secret, $currentBriefing);
                    if($aBriefing['result']){
                        $aBrief = $aBriefing['response'];

                        $briefingContent = $aBrief['content'];
                        if(!empty(strip_tags($briefingContent))){
                            $output.='<div class="sm-main-info last"><div class="sm-briefing-action">';
                            $output.='<button id="smAddBriefingContent" class="components-button is-secondary">'.__('Add Content', 'searchmetrics-content-experience').'</button> <button id="smReplaceWithBriefingContent" class="components-button is-secondary">'.__('Replace Content', 'searchmetrics-content-experience').'</button>';
                            $output.='</div></div>';  
                        }

                        //keywords block
                        $keywordsContent = '';
                        if(!empty($aBrief['topics_coverage'])){
                            $keywordsContent.='<select class="smTopicSelector sm-briefings-selector"><option value="">'.__('All Topics', 'searchmetrics-content-experience').'('.(count($aBrief['topics_coverage'])-1).')'.'</option>';
                            foreach($aBrief['topics_coverage'] as $topic){
                                if($topic['topic'] !== 'all_topics'){
                                    $keywordsContent.='<option value="'.$topic['topic'].'">'.$topic['topic'].'</option>';
                                }
                            }
                            $keywordsContent.='</select>';
                            $keywordsContent.='<div id="smKeywordContainer">';
                            $keywordsContent.= '<p><strong>'.__('Must Have Keywords', 'searchmetrics-content-experience').'</strong></p>';
                            foreach($aBrief['topics_coverage'] as $topic){
                                if(!empty($topic['keywords_coverage']) and $topic['topic'] !== 'all_topics'){
                                    foreach($topic['keywords_coverage'] as $kw){
                                        if($kw['keyword_type'] === 'MUST_HAVE'){
                                            $keywordsContent.= '<div class="kw" rel="'.$topic['topic'].'">'.($kw['current_frequency'] > 0 ? '<span class="kw-active"><i class="fa fa-check-circle"></i></span>' : '<span class="kw-inactive"><i class="fa fa-check-circle"></i></span>').' '.$kw['keyword'].' <div class="kw-stat"><div class="kw-scale">'.smce_scale($kw['current_frequency'], $kw['target_frequency']).'</div>'.$kw['current_frequency'].'/'.$kw['target_frequency'].'</div><div class="sm-clearfix"></div></div>';
                                        }
                                    }
                                }
                            }
                            $keywordsContent.= '<p><strong>'.__('Recommended Keywords', 'searchmetrics-content-experience').'</strong></p>';
                            foreach($aBrief['topics_coverage'] as $topic){
                                if(!empty($topic['keywords_coverage']) and $topic['topic'] !== 'all_topics'){
                                    foreach($topic['keywords_coverage'] as $kw){
                                        if($kw['keyword_type'] === 'RELEVANCE'){
                                            $keywordsContent.= '<div class="kw" rel="'.$topic['topic'].'">'.($kw['current_frequency'] > 0 ? '<span class="kw-active"><i class="fa fa-check-circle"></i></span>' : '<span class="kw-inactive"><i class="fa fa-check-circle"></i></span>').' '.$kw['keyword'].' <div class="kw-stat"><div class="kw-scale">'.smce_scale($kw['current_frequency'], $kw['target_frequency']).'</div>'.$kw['current_frequency'].'/'.$kw['target_frequency'].'</div><div class="sm-clearfix"></div></div>';
                                        }
                                    }
                                }
                            }
                            $keywordsContent.= '<p><strong>'.__('Additional Keywords', 'searchmetrics-content-experience').'</strong></p>';
                            foreach($aBrief['topics_coverage'] as $topic){
                                if(!empty($topic['keywords_coverage']) and $topic['topic'] !== 'all_topics'){
                                    foreach($topic['keywords_coverage'] as $kw){
                                        if($kw['keyword_type'] === 'ADDITIONAL'){
                                            $keywordsContent.= '<div class="kw" rel="'.$topic['topic'].'">'.($kw['current_frequency'] > 0 ? '<span class="kw-active"><i class="fa fa-check-circle"></i></span>' : '<span class="kw-inactive"><i class="fa fa-check-circle"></i></span>').' '.$kw['keyword'].' <div class="kw-stat"><div class="kw-scale">'.smce_scale($kw['current_frequency'], $kw['target_frequency']).'</div>'.$kw['current_frequency'].'/'.$kw['target_frequency'].'</div><div class="sm-clearfix"></div></div>';
                                        }
                                    }
                                }
                            }  
                            $keywordsContent.='</div>';
                        }

                        //content score block
                        $contenScoreContent = '<div id="smCoverageContainer">';
                        $contenScoreContent.= round($aBrief['content_score']*100). '% of '.$aBrief['infos']['content_score_goal']. '%';                        
                        $contenScoreContent.= smce_scale(round($aBrief['content_score']*100), 100);
                        $contenScoreContent.= '<p>'.__('Word Count', 'searchmetrics-content-experience').'</p>';
                        $contenScoreContent.= $aBrief['content_length']. ' of '.$aBrief['target_length'];                        
                        $contenScoreContent.= smce_scale($aBrief['content_length'], $aBrief['target_length']);
                        $contenScoreContent.= '<p>'.__('Sentence Structure', 'searchmetrics-content-experience').'</p>';
                        $contenScoreContent.= round($aBrief['validation']['contentScore']['natural_language_score']*100). '%';
                        $contenScoreContent.= smce_scale($aBrief['validation']['contentScore']['natural_language_score']*100, 100);                        
                        $contenScoreContent.= '<p>'.__('Repetitions', 'searchmetrics-content-experience').'</p>';
                        $contenScoreContent.= round($aBrief['validation']['contentScore']['repetition_score']*100). '%';
                        $contenScoreContent.= smce_scale($aBrief['validation']['contentScore']['repetition_score']*100, 100);                        
                        $contenScoreContent.= '<p>'.__('Keywords Coverage', 'searchmetrics-content-experience').'</p>';
                        $contenScoreContent.= round($aBrief['validation']['contentScore']['coverage_score']*100). '%';
                        $contenScoreContent.= smce_scale(round($aBrief['validation']['contentScore']['coverage_score']*100), 100);                       
                        $contenScoreContent.= '</div>';

                        $readabilityScoreContent = '';
                        $readabilityScoreContent.= round($aBrief['validation']['readability']*100). '%';
                        $readabilityScoreContent.= smce_scale($aBrief['validation']['readability']*100, 100);

                        //questions block
                        $questionsContent = '';
                        if(!empty($aBrief['questions'])){
                            $questionsContent.='<select class="smTopicSelector sm-briefings-selector"><option value="">'.__('All Topics', 'searchmetrics-content-experience').'('.(count($aBrief['questions'])).')'.'</option>';
                            foreach($aBrief['questions'] as $topic){
                                    $questionsContent.='<option value="'.$topic['topic'].'">'.$topic['topic'].'</option>';
                            }
                            $questionsContent.='</select>';
                            $countActive = 0;
                            $countTotal = 0;
                            foreach($aBrief['questions'] as $topic){
                                if(!empty($topic['data'])){
                                    foreach($topic['data'] as $quest){
                                        if($quest['active'] > 0){
                                            $questionsContent.= '<div class="kw" rel="'.$topic['topic'].'">'.$quest['question'].'</div>';
                                            $countActive++;
                                        }
                                        $countTotal++;
                                    }
                                }
                            }
                        }   

                        //Duplicate Check
                        $duplicateCheckContent = '';
                        $aAllDuplications = $aBrief['validation']['duplicationCheckResults'];
                        $aDuplications = [];
                        foreach($aAllDuplications as $dpl){
                            if($dpl['duplication_score'] > 5){
                                $aDuplications[] = $dpl;
                            }
                        }
                        $aDuplications = smce_sort_array($aDuplications,'duplication_score','DESC');
                        if(!empty($aDuplications)){
                            $duplicateCheckContent.= '<div class="sm_dpl_item"><div class="sm_dpl_error">'.__('Your text duplicates external content.', 'searchmetrics-content-experience').'</div></div>';
                            foreach($aDuplications as $dpl){
                                if($dpl['duplication_score'] >= 5 and $dpl['duplication_score'] < 30){
                                    $pill_color = '#f5944b';
                                }elseif($dpl['duplication_score'] >= 30){
                                    $pill_color = '#f53b31';
                                }else{
                                    $pill_color = '#4caf50';
                                }
                                $duplicateCheckContent.= '<div class="sm_dpl_item">';
                                $duplicateCheckContent.= '<div class="sm_dpl_url"><a href="'.$dpl['url'].'" target="_blank">'.$dpl['url'].'</a></div>';
                                $duplicateCheckContent.= '<span class="sm_pill" style="background-color:'.$pill_color.'">'.$dpl['duplication_score'].'%</span><div class="sm-clearfix"></div>';
                                $duplicateCheckContent.= '<p>'.$dpl['title'].'</p>';
                                $duplicateCheckContent.= '</div>';                                
                            }
                        }else{
                            $duplicateCheckContent.= '<div class="sm_dpl_item"><div class="sm_dpl_success">'.__('No duplicates found.', 'searchmetrics-content-experience').'</div></div>';
                        }


                        //searchmetrics info output
                        $output.= smce_block(__('Content Score', 'searchmetrics-content-experience'), $contenScoreContent, 1, 'score');
                        $output.= smce_block(__('Readability Score', 'searchmetrics-content-experience'), $readabilityScoreContent, 1, 'readability');
                        $output.= smce_block(__('Keywords', 'searchmetrics-content-experience'), $keywordsContent, 0, 'keywords');
                        $output.= smce_block(__('Questions', 'searchmetrics-content-experience'). ' <em class="sm_header_note">'.$countActive.' '.__('of').' '.$countTotal.'</em>', $questionsContent, 0, 'questions');
                        $output.= smce_block(__('External Duplicate Check', 'searchmetrics-content-experience'), $duplicateCheckContent, 0, 'duplicate_check');
                    }else{
                        $output.='<span class="error">'.__('Oops...It looks like the Searchmetrics server is overloaded or you have reached your API request limit. Please try again in a minute.', 'searchmetrics-content-experience').'<button type="button" class="components-button is-secondary smReloadPanel">'.__('Reload', 'searchmetrics-content-experience').'</button></span>';
                    }
                }else{
                    $output.= '<p class="sm_info">'.__('Please select a briefing in the dropdown menu above', 'searchmetrics-content-experience').'</p>';
                }
            }else{
                $output.='<span class="error">'.__('Oops...It looks like the Searchmetrics server is overloaded or you have reached your API request limit. Please try again in a minute.', 'searchmetrics-content-experience').'<button type="button" class="components-button is-secondary smReloadPanel">'.__('Reload', 'searchmetrics-content-experience').'</button></span>';
            }
            $updated = 1;
        }        
        $aResult = ['status'=>$updated, 'data'=>$output, 'briefing_content'=>$briefingContent];  
    }else{
        ob_start();
        ?>
        <p class="sm_info"><?php esc_html_e('Please add your Searchmetrics® credentials', 'searchmetrics-content-experience');echo ' <a href="'.esc_url(site_url()).'/wp-admin/options-general.php?page=content_experience_settings">'.__('here', 'searchmetrics-content-experience').'</a>'?></p>
        <?php
        $output = ob_get_clean();
        $aResult = ['status'=>1, 'data'=>$output, 'briefing_content'=>$briefingContent];        
    } 
    $jsonResult = json_encode($aResult);
    echo $jsonResult;
    die;
}

//local keywords calculation
add_action( 'wp_ajax_content_experience_keywords_calculation', 'smce_get_sm_keywords_calculation' );
function smce_get_sm_keywords_calculation(){
    $searchmetrics_api_key = get_option('searchmetrics_api_key');
    $searchmetrics_api_secret = get_option('searchmetrics_api_secret');
    header('Content-Type: application/json');
    $aBriefing = false;
    if(isset($_POST['briefing_id'])){
        $currentBriefing = sanitize_key($_POST['briefing_id']);
        $aBriefing = smce_getBriefing($searchmetrics_api_key, $searchmetrics_api_secret, $currentBriefing);
    }    
    if($aBriefing['result']){ 
        $aBrief = $aBriefing['response'];
        $rawContent = wp_kses($_POST['data'], []);
        $clearContent = stripslashes(str_replace( '>', '> ',$rawContent ));
        $clearContent = preg_replace('/<label.*?>(.*)?<\/label>/im', '', $clearContent);        
        $clearContent = strip_tags($clearContent);
        $clearContent = trim(preg_replace('/\s+/', ' ',$clearContent));
        $clearContent = mb_strtolower($clearContent);
        $wordsCount = count(preg_split('/\s+/', $clearContent));

        if(!empty($aBrief['topics_coverage'])){
            //keywords block
            $keywordsContent='';
            $keywordsContent.= '<p><strong>'.__('Must Have Keywords', 'searchmetrics-content-experience').'</strong></p>';
            foreach($aBrief['topics_coverage'] as $topic){
                if(!empty($topic['keywords_coverage']) and $topic['topic'] !== 'all_topics'){
                    foreach($topic['keywords_coverage'] as $kw){
                        if($kw['keyword_type'] === 'MUST_HAVE'){
                            $curr_frequency = substr_count ( $clearContent , mb_strtolower($kw['keyword']));
                            $keywordsContent.= '<div class="kw" rel="'.$topic['topic'].'">'.($curr_frequency > 0 ? '<span class="kw-active"><i class="fa fa-check-circle"></i></span>' : '<span class="kw-inactive"><i class="fa fa-check-circle"></i></span>').' '.$kw['keyword'].' <div class="kw-stat"><div class="kw-scale">'.smce_scale($curr_frequency, $kw['target_frequency']).'</div>'.$curr_frequency.'/'.$kw['target_frequency'].'</div><div class="sm-clearfix"></div></div>';
                        }
                    }
                }
            }
            $keywordsContent.= '<p><strong>'.__('Recommended Keywords', 'searchmetrics-content-experience').'</strong></p>';
            foreach($aBrief['topics_coverage'] as $topic){
                if(!empty($topic['keywords_coverage']) and $topic['topic'] !== 'all_topics'){
                    foreach($topic['keywords_coverage'] as $kw){
                        if($kw['keyword_type'] === 'RELEVANCE'){
                            $curr_frequency = substr_count ( $clearContent , mb_strtolower($kw['keyword']));
                            $keywordsContent.= '<div class="kw" rel="'.$topic['topic'].'">'.($curr_frequency > 0 ? '<span class="kw-active"><i class="fa fa-check-circle"></i></span>' : '<span class="kw-inactive"><i class="fa fa-check-circle"></i></span>').' '.$kw['keyword'].' <div class="kw-stat"><div class="kw-scale">'.smce_scale($curr_frequency, $kw['target_frequency']).'</div>'.$curr_frequency.'/'.$kw['target_frequency'].'</div><div class="sm-clearfix"></div></div>';
                        }
                    }
                }
            }
            $keywordsContent.= '<p><strong>'.__('Additional Keywords', 'searchmetrics-content-experience').'</strong></p>';
            foreach($aBrief['topics_coverage'] as $topic){
                if(!empty($topic['keywords_coverage']) and $topic['topic'] !== 'all_topics'){
                    foreach($topic['keywords_coverage'] as $kw){
                        if($kw['keyword_type'] === 'ADDITIONAL'){
                            $curr_frequency = substr_count ( $clearContent , mb_strtolower($kw['keyword']));
                            $keywordsContent.= '<div class="kw" rel="'.$topic['topic'].'">'.($curr_frequency > 0 ? '<span class="kw-active"><i class="fa fa-check-circle"></i></span>' : '<span class="kw-inactive"><i class="fa fa-check-circle"></i></span>').' '.$kw['keyword'].' <div class="kw-stat"><div class="kw-scale">'.smce_scale($curr_frequency, $kw['target_frequency']).'</div>'.$curr_frequency.'/'.$kw['target_frequency'].'</div><div class="sm-clearfix"></div></div>';
                        }
                    }
                }
            }
        }        
        //content coverage block
        $contenScoreContent = '';
        $contenScoreContent.= round($aBrief['content_score']*100). '% of '.$aBrief['infos']['content_score_goal']. '%';                        
        $contenScoreContent.= smce_scale(round($aBrief['content_score']*100), 100);
        $contenScoreContent.= '<p>'.__('Word Count', 'searchmetrics-content-experience').'</p>';
        $contenScoreContent.= $wordsCount. ' of '.$aBrief['target_length'];  
        $contenScoreContent.= smce_scale($wordsCount, $aBrief['target_length']);
        $contenScoreContent.= '<p>'.__('Sentence Structure', 'searchmetrics-content-experience').'</p>';
        $contenScoreContent.= round($aBrief['validation']['contentScore']['natural_language_score']*100). '%';
        $contenScoreContent.= smce_scale($aBrief['validation']['contentScore']['natural_language_score']*100, 100);
        $contenScoreContent.= '<p>'.__('Repetitions', 'searchmetrics-content-experience').'</p>';
        $contenScoreContent.= round($aBrief['validation']['contentScore']['repetition_score']*100). '%';
        $contenScoreContent.= smce_scale($aBrief['validation']['contentScore']['repetition_score']*100, 100);
        $contenScoreContent.= '<p>'.__('Keywords Coverage', 'searchmetrics-content-experience').'</p>';
        $contenScoreContent.= round($aBrief['validation']['contentScore']['coverage_score']*100). '%';
        $contenScoreContent.= smce_scale(round($aBrief['validation']['contentScore']['coverage_score']*100), 100);        

        $aResult = ['status'=>1, 'coverage_stat'=>$contenScoreContent, 'keywords_stat' => $keywordsContent, ];
    }else{
        $aResult = ['status'=>0, 'coverage_stat'=>'', 'keywords_stat' => ''];
    }
    $jsonResult = json_encode($aResult);
    echo $jsonResult;
    die;
}

//update briefing content
add_action( 'wp_ajax_content_experience_update_briefing', 'smce_update_briefing' );
function smce_update_briefing(){
    $searchmetrics_api_key = get_option('searchmetrics_api_key');
    $searchmetrics_api_secret = get_option('searchmetrics_api_secret');
    header('Content-Type: application/json');
    $aBriefing = false;
    if(isset($_POST['briefing_id']) && isset($_POST['data'])){
        $currentBriefing = sanitize_key($_POST['briefing_id']);
        $rawContent = wp_kses($_POST['data'], []);
        $clearContent = stripslashes(str_replace( '>', '> ',$rawContent ));
        $clearContent = preg_replace('/<label.*?>(.*)?<\/label>/im', '', $clearContent);        
        $clearContent = strip_tags($clearContent);
        $clearContent = trim(preg_replace('/\s+/', ' ',$clearContent));
        $clearContent = mb_strtolower($clearContent);
        smce_updateBriefingContent($searchmetrics_api_key, $searchmetrics_api_secret, $currentBriefing, $clearContent);
    }
}
