<?php
//progress bar generation
function smce_scale($value, $value_max, $reverse = false){
    if ($value_max == 0) {
        return '';
    }
    $percent = round($value/$value_max*100);
    if($value > $value_max){
        $percent = 100;
    }
    if($reverse){
        if($percent >= 0 and $percent < 33){
            $color = '#4caf50';
        }elseif($percent >= 33 and $percent < 66){
            $color = '#f5944b';
        }else{
            $color = '#f53b31';
        }
    }else{
        if($percent >= 0 and $percent < 33){
            $color = '#f53b31';
        }elseif($percent >= 33 and $percent < 66){
            $color = '#f5944b';
        }else{
            $color = '#4caf50';
        }   
    }
    $html = '<div class="sm-scale-wrapper"><div class="sm-scale-value" style="background-color:'.$color.';width:'.$percent.'%;"></div></div>';       
    return $html;
}

//parameters block generation
function smce_block($header, $content, $default_open=1, $additional_css_class=''){
    ob_start();
    if(!$default_open){
        $closed_class = 'closed';
    }else{
        $closed_class = '';
    }
    ?>
    <div class="components-panel__body sm-content-block <?php echo $closed_class?> <?php echo esc_html($additional_css_class)?>">
        <h2 class="components-panel__body-title"><?php echo $header?><span class="sm_display_action"><span class="sm-arrow-up"><svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="components-panel__arrow" role="img" aria-hidden="true" focusable="false"><path d="M12,8l-6,6l1.41,1.41L12,10.83l4.59,4.58L18,14L12,8z"></path></svg></span><span class="sm-arrow-down"><svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="components-panel__arrow" role="img" aria-hidden="true" focusable="false"><path d="M7.41,8.59L12,13.17l4.59-4.58L18,10l-6,6l-6-6L7.41,8.59z"></path></svg></span></span></h2>
        <div class="sm-content-block-content">
            <?php echo $content?>
        </div>
    </div>
    <?php
    $output = ob_get_clean();
    return $output;
}

function smce_sort_array($array,$key,$type='ASC'){
    $sorted_array = array();
    if(@is_array($array) and count($array)>0){
        foreach($array as $k=>$row){
            @$key_values[$k] = $row[$key];
        }
        if($type == 'ASC' ){
            asort($key_values, SORT_NATURAL | SORT_FLAG_CASE);
        }else{
            arsort($key_values, SORT_NATURAL | SORT_FLAG_CASE);
        }
        foreach($key_values as $k=>$v){
           $sorted_array[] = $array[$k];
        }
        return $sorted_array;
    }else{
        return false;
    }

}

function smce_delete_options( $prefix ) {
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$prefix}%'" );
}

function smce_is_gutenberg_active() {
    // Gutenberg plugin is installed and activated.
    $gutenberg = ! ( false === has_filter( 'replace_editor', 'gutenberg_init' ) );

    // Block editor since 5.0.
    $block_editor = version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' );

    if ( ! $gutenberg && ! $block_editor ) {
        return false;
    }

    if ( smce_is_classic_editor_plugin_active() ) {
        $editor_option       = get_option( 'classic-editor-replace' );
        $block_editor_active = array( 'no-replace', 'block' );

        return in_array( $editor_option, $block_editor_active, true );
    }

    return true;
}

function smce_is_classic_editor_plugin_active() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if ( is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
        return true;
    }

    return false;
}
