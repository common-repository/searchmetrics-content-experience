var sm_loaded = false;
var sm_remote_briefing_content = '';
var sm_keywords_calculation_started = false;
var sm_briefing_selected = false;
var sm_notice_displayed = false;
var sm_briefing_content_update_complete = true;

jQuery(window).load(function(){
    
    setInterval(function(){
        refreshSMData();
    }, 3000);

    setInterval(function() {
        smUpdateBriefingContent();
    }, 30000);
    
    jQuery('.sm-icon').click(function(){
        sm_force_load = 1;
    });
    
    jQuery('body').on('click', '.smReloadPanel', function(){
        jQuery('.sm-sidebar-content').html('<div class="sm-loader-container"><img src="images/spinner.gif" class="sm-loader-img" alt=""></div>');
        sm_force_load = 1;
        return false;
    });
    
    jQuery('body').on('change', '#smBriefingsSelector', function(){
        selectSMBriefing(jQuery(this));
        refreshSMData();
        return false;
    });
    
    jQuery('body').on('change', '.smTopicSelector', function(){
        var selector = jQuery(this);
        selector.parent().find('.kw').each(function(){
            jQuery(this).css('display', 'block');
        });
        var currTopic = selector.val();
        if(currTopic != ''){
            selector.parent().find('.kw').each(function(){
                if(jQuery(this).attr('rel') !== currTopic){
                    jQuery(this).css('display', 'none');
                }
            });
        }
    });
    
    jQuery('body').on('click', '.sm-content-block>h2', function(){
        openSMBlock(jQuery(this));
        return false;
    });
    
    jQuery('body').on('click', '#smAddBriefingContent', function(){
        smAddBriefingContent(sm_remote_briefing_content);
        return false;
    });
    
    jQuery('body').on('click', '#smReplaceWithBriefingContent', function(){
        smReplaceWithBriefingContent(sm_remote_briefing_content);
        return false;
    });
    
});

function refreshSMData(){
    if(jQuery('.sm-sidebar-content').length > 0){
        var requestData = {
            action: 'content_experience',
            post_id: sm_post_id,
            briefing_id: sm_briefing_id,
            force_load: sm_force_load
        };
        if(sm_force_load){
            jQuery.post(ajaxurl, requestData, function(response){
                if(response.status || !sm_loaded){
                    jQuery('.sm-sidebar-content').html(response.data);
                    sm_remote_briefing_content = response.briefing_content;
                    sm_loaded = true;
                    sm_force_load = 0;                   
                    if(sm_briefing_id && !sm_remote_briefing_content){
                        smStartKeywordsCalculation();                        
                    }else{
                        if(sm_briefing_selected && !sm_notice_displayed){
                            if(jQuery('.mce-edit-area iframe').length > 0){
                                alert(langvars.messagekey_content_notice);
                            }else{                                
                                ( function( wp ) {
                                    wp.data.dispatch( 'core/notices' ).createNotice(
                                        'info', //success, info, warning, error.
                                        langvars.messagekey_content_notice,
                                        {
                                            isDismissible: true
                                        }
                                    );
                                } )( window.wp ); 
                                sm_notice_displayed = true;
                            }
                        }
                    }
                }
            });
        }
    }
}

function selectSMBriefing(obj){
    jQuery('.sm-sidebar-content').html('<div class="sm-loader-container"><img src="images/spinner.gif" class="sm-loader-img" alt=""></div>');
    sm_briefing_id = obj.val();
    sm_force_load = 1;
    sm_briefing_selected = true;
}

function openSMBlock(obj){
    if(obj.parent().hasClass('closed')){
        obj.parent().removeClass('closed');
    }else{
        obj.parent().addClass('closed');
    }
}

function smAddBriefingContent(brief_content){
    if(jQuery('.sm-sidebar-content').length > 0){
        var content_editor = 'gutenberg';
        if(jQuery('.mce-edit-area iframe').length > 0){
            content_editor = 'tinymce';
        }
        if(content_editor === 'gutenberg'){
            let block = wp.blocks.createBlock( 'core/paragraph', { content: brief_content } );
            wp.data.dispatch( 'core/editor' ).insertBlocks( block );
        }else{
            var currText = tinymce.get("content").getContent();
            tinymce.get("content").setContent(currText + brief_content);
        }
    }
    smStartKeywordsCalculation();
}

function smReplaceWithBriefingContent(brief_content){
    if(jQuery('.sm-sidebar-content').length > 0){
        var content_editor = 'gutenberg';
        if(jQuery('.mce-edit-area iframe').length > 0){
            content_editor = 'tinymce';
        }
        if(content_editor === 'gutenberg'){
            let block = wp.blocks.createBlock( 'core/paragraph', { content: smRemoveTags(brief_content) } );
            wp.data.dispatch( 'core/editor' ).resetBlocks([]);
            wp.data.dispatch( 'core/editor' ).insertBlocks( block );
            //convert to native gutenberg blocks
            wp.data.select("core/editor").getBlocks().forEach(function(block, blockIndex){
                wp.data.dispatch( 'core/editor' ).replaceBlocks(block.clientId, wp.blocks.rawHandler( 
                  { HTML: wp.blocks.getBlockContent( block ) }
                ));
            });
        }else{
            tinymce.get("content").setContent(brief_content);
        }
    }
    smStartKeywordsCalculation();
}

function smStartKeywordsCalculation(){
    if(!sm_keywords_calculation_started){
        setInterval(function(){
            smKeywordsCalculation();
        }, 5000);
        sm_keywords_calculation_started = true;
    }
}

function smKeywordsCalculation(){
    var content = '';
    if(jQuery('.editor-writing-flow').length > 0){
        content =  jQuery('.editor-writing-flow').html();
    }else if(jQuery('.block-editor-writing-flow').length > 0){
        content =  jQuery('.block-editor-writing-flow').html();
    }else if(jQuery('.mce-edit-area iframe').length > 0){
        content = tinymce.get("content").getContent();
    }
    if(content){
        var requestData = {
            action: 'content_experience_keywords_calculation',
            data: content,
            briefing_id: sm_briefing_id,
        };
        jQuery.post(ajaxurl, requestData, function(response){
            if(response.status){
                jQuery('#smCoverageContainer').html(response.coverage_stat);
                jQuery('#smKeywordContainer').html(response.keywords_stat);
            }
        });
    }
}

function smRemoveTags(html){
   var htmlNew = html.replace(/<h/g,"[[h").replace(/<\/h/,"]]h").replace(/<p/g,"[[p").replace(/<\/p/g,"]]p");  
   var tmp = document.createElement("DIV");
   tmp.innerHTML = htmlNew;
   htmlNew = tmp.textContent||tmp.innerText;
   return htmlNew.replace(/\[\[h/,"<h").replace(/\]\]h/,"</h").replace(/\[\[p/g,"<p").replace(/\]\]p/g,"</p");
}

function smUpdateBriefingContent(){
    if (sm_briefing_content_update_complete) {
        sm_briefing_content_update_complete = false;
        var content = '';
        if(jQuery('.editor-writing-flow').length > 0){
            content =  jQuery('.editor-writing-flow').html();
        }else if(jQuery('.block-editor-writing-flow').length > 0){
            content =  jQuery('.block-editor-writing-flow').html();
        }else if(jQuery('.mce-edit-area iframe').length > 0){
            content = tinymce.get("content").getContent();
        }
        if(content){
            var requestData = {
                action: 'content_experience_update_briefing',
                data: content,
                briefing_id: sm_briefing_id,
            };
            jQuery.post(ajaxurl, requestData)
                .always(function() {
                    sm_briefing_content_update_complete = true;
                });
        }
    }
}
