jQuery(document).ready(function(){
       
    if(typeof sm_tutorial_step != 'undefined'){
        if(sm_tutorial_step){
            jQuery('.sm-step'+sm_tutorial_step).css('display', 'block');
        }else{
            jQuery('.sm-steps').eq(0).css('display', 'block');
        }
    }else{
        jQuery('.sm-steps').eq(0).css('display', 'block');
    }
    
    jQuery('body').on('click', '.sm-tutorial-notice .skip', function(){
        smHideTutorialNotice('');
        return false;
    });
    
    jQuery('body').on('click', '.sm-close', function(){
        parent.location = smAdminURL;
    });
    
    jQuery('body').on('click', '#Weiter', function(){
        if(jQuery(this).hasClass('disabled')){
            return false;
        }
        jQuery('.sm-steps').css('display', 'none');
        jQuery('.sm-steps').eq(1).css('display', 'block');
        return false;
    });
    
    jQuery('body').on('click', '#nextSmAPI', function(){
        if(jQuery(this).hasClass('disabled')){
            return false;
        }
    });
    
    jQuery('body').on('click', '#nextFinish', function(){
        if(jQuery(this).hasClass('disabled')){
            return false;
        }
        jQuery('.sm-steps').css('display', 'none');
        jQuery('.sm-steps').eq(2).css('display', 'block');
        return false;
    });
    
    jQuery('body').on('change', '.credentials', function(){
        jQuery('#nextFinish').addClass('disabled');
    });  
    
    
    
});

function smHideTutorialNotice(redirecrt_url){
    var requestData = {
        action: 'content_experience_hide_tutorial'
    };
    jQuery.post(ajaxurl, requestData, function(){
        jQuery('.sm-tutorial-notice').remove();
        if(redirecrt_url !== ''){
            parent.location = redirecrt_url;
        }
    });
}