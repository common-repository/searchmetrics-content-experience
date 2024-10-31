<?php

function smce_apiRequest($key, $secret, $request){
    $headers = [
        'sm-api-key'   => $key,
        'sm-api-secret'=> $secret
    ];
    $args = array(
        'method'      => 'POST',
        'data_format' => 'body',
        'body'        => $request,
        'timeout'     => '5',
        'redirection' => '5',
        'httpversion' => '1.1',
        'blocking'    => true,
        'headers'     => $headers,
        'cookies'     => array(),
        'sslverify'   => false
    );
    $response = wp_remote_request( SM_SM_API_URL, $args );
    /* If WP_Error, throw exception */
    if (is_wp_error($response)) {
        var_dump($response);
        throw new Exception('Request failed. ' . implode("|",$response->get_error_messages()));
    }
    // echo "<pre>";
    // print_r($request);
    // print_r($response);
    // echo "</pre>";
    if($response['body']){ 
        $aResponse = json_decode($response['body'], true); 
        if(isset($aResponse['message'])){
            $aResponse['errors'][0]['message'] = $aResponse['message'];
        }
    }else{        
        $aResponse['errors'][0]['message'] = 'API request error';
    }
    return $aResponse;    
}

function smce_getLicense($key, $secret, $forced = false){
    $license = get_option('content_experience_sm_license');
    if(!$license or $forced){
        $query = '{"query": "{ admin { license { license_id parent_license_id } } }"}';
        $result = smce_apiRequest($key, $secret, $query); 
        if(!empty($result)){
            if(!empty($result['data']['admin']['license'])){
                $license = $result['data']['admin']['license'];
                update_option('content_experience_sm_license', serialize($license));
                $res = ['result'=>1, 'response'=>$license];
            }else{
                $res = ['result'=>0, 'response'=>$result['errors'][0]['message']];
            }
        }else{
            $res = ['result'=>0, 'response'=>'Request error'];
        }
    }else{
        $res = ['result'=>1, 'response'=> unserialize($license)];
    }
    return $res;
}

function smce_getBriefingsList($key, $secret){
    $sBriefingsContainer = get_option('content_experience_sm_briefings');
    $aBriefingsContainer = unserialize($sBriefingsContainer);
    if(empty($aBriefingsContainer['data']) or (isset($aBriefingsContainer['timestamp']) and $aBriefingsContainer['timestamp'] < time()-3600)){
        $aLicense = smce_getLicense($key, $secret);
        if($aLicense['result']){
            $query = '{"query": "{ content_experience { briefings_list(filter:{ account_id:'.$aLicense['response']['license_id'].' }, license_id:'.$aLicense['response']['parent_license_id'].', limit:100, offset:0) { briefings { id story } count } } }"}';
            $result = smce_apiRequest($key, $secret, $query);
            if(!empty($result)){
                if(!empty($result['data']['content_experience']['briefings_list']['briefings'])){
                    $briefings = $result['data']['content_experience']['briefings_list']['briefings'];
                    $briefsContainer = ['timestamp' => time(), 'data' => $briefings];
                    update_option('content_experience_sm_briefings', serialize($briefsContainer));
                    $res = ['result'=>1, 'response'=>$briefings];
                }else{
                    $res = ['result'=>0, 'response'=>'Briefings API request: '.$result['errors'][0]['message']];
                }
            }else{
                $res = ['result'=>0, 'response'=>'Briefings request error'];
            }
        }else{
            $res = ['result'=>0, 'response'=>'License request: '.$aLicense['response']];
        }
    }else{
        $res = ['result'=>1, 'response'=>$aBriefingsContainer['data']];
    }
    return $res;
}

function smce_getBriefing($key, $secret, $id){
    $id = addslashes($id);
    $sBriefingContainer = get_option('content_experience_sm_briefing_'.$id);
    $aBriefingContainer = unserialize($sBriefingContainer);
    if(empty($aBriefingContainer['data']) or (isset($aBriefingContainer['timestamp']) and $aBriefingContainer['timestamp'] < time()-3600)){
        $query = '{"query": "{ content_experience { briefing(id:\"'.$id.'\") { owner_id assignee_id name title content content_score target_score content_length target_length topics { state type value } topics_coverage { topic keywords_coverage { keyword current_frequency target_frequency keyword_type } } questions { id topic data { active group id origin question local_rank global_rank } } infos{ average_median_num_words content_score_goal readability_target seo_value seo_value_potential status traffic_index traffic_index_potential } content_optimization { docStats { customerReadability readability } } validation { overallScore readability contentScore { content_score coverage_score natural_language_score repetition_score length_score } duplicationCheckResults{ duplication_score level title url } } } } }"}'; 
        $result = smce_apiRequest($key, $secret, $query);
        if(!empty($result)){
            if(!empty($result['data']['content_experience']['briefing'])){
                $briefing = $result['data']['content_experience']['briefing'];
                $briefContainer = ['timestamp' => time(), 'data' => $briefing];
                update_option('content_experience_sm_briefing_'.$id, serialize($briefContainer));
                $res = ['result'=>1, 'response'=>$briefing];
            }else{
                $res = ['result'=>0, 'response'=>'Briefing "'.$id.'" API request: '.$result['errors'][0]['message']];
            }
        }else{
            $res = ['result'=>0, 'response'=>'Briefing "'.$id.'" request error'];

        }
    }else{
        $res = ['result'=>1, 'response'=>$aBriefingContainer['data']];
    }
    return $res;
}

function smce_updateBriefingContent($key, $secret, $id, $content){
    $id = addslashes($id);
    $aBriefing = smce_getBriefing($key, $secret, $id);
    if($aBriefing['result']){
        $mutation = '{"query": "mutation { update_briefing_text(input: { author_id: \"'.$aBriefing['response']['assignee_id'].'\", briefing_id: \"'.$id.'\", content: \"\"\"'.addslashes(str_replace(array("\n", "\r"), '', $content)).'\"\"\", title: \"'.$aBriefing['response']['title'].'\" }){ name } }"}';
        $result = smce_apiRequest($key, $secret, $mutation);
        if(!empty($result['errors'][0]['message'])){
            $res = ['result'=>0, 'response'=>'Briefing "'.$id.'" API update request: '.$result['errors'][0]['message']];
        }else{
            $res = ['result'=>1, 'response'=>'OK'];
        }
        update_option('content_experience_sm_briefing_'.$id, '');        
    }else{
        $res = ['result'=>0, 'response'=>'Briefing "'.$id.'" request error'];
    }
    return $res;
}