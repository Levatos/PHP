<?php
/**                     Valerii Kulykov                      **/
/** Social Networks Library written with using of native API **/
/** Library is taken from working project and some variables **/
/**   in code are connected with DB and $_POST environment   **/
/**                      Version: 1.0                        **/
/**      TODO: OOP structure, separately cURL function       **/

/** Configuration variables, all needed data can be obtained from API of listed below social networks **/

$vk_pid = ''; // VK Application ID
$vk_aid = ''; // VK album ID to post photos
$vk_token = ''; // VK Token (need to be updated by cron once in 24 hours (vk_update_token.php))

$fb_uid = ''; // FaceBook UID
$fb_token_pid = ''; // FaceBook token PID
$fb_token_uid = ''; // FaceBook token UID

/** Twitter keys and tokens **/

$tw_consumer_key = "";
$tw_consumer_secret = "";
$tw_oauth_token = "";
$tw_oauth_token_secret = "";

/** ----------------------- **/

/** ------------------------------------------------------------------------------------------------- **/

// Let's make short link for each twit that contain link (because we have only 140 characters and space saving is very important for us)
function curlGetShortLink($url){
    $link = 'http://clck.ru/--?url='.$url;
 
    $ci = curl_init();

    curl_setopt($ci, CURLOPT_URL, $link);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
 
    while(true){
        $returned_link = curl_exec($ci);
        $status = curl_getinfo($ci, CURLINFO_HTTP_CODE);

        if($status == "200"){
            break;
        }
        sleep(2);
    }
    return $returned_link;
}

// Post to Twitter
function tw_post($title, $short_text, $full_text, $link, $tw_consumer_key, $tw_consumer_secret, $tw_oauth_token, $tw_oauth_token_secret){
    $nonce = md5(microtime().mt_rand());
    $time = time();
    
    // Parse images from text
    if(@preg_match_all("/<(img|image)[\s]+[^>]*src=['\"]?([^'\"\s>]+)['\"]?[^>]*>/is", stripslashes($full_text), $image)){
        $picture = $image[2][0];
    } elseif(@preg_match_all("/<(img|image)[\s]+[^>]*src=['\"]?([^'\"\s>]+)['\"]?[^>]*>/is", stripslashes($short_text), $image)){
        $picture = $image[2][0];
    } else
        $picture = '';

    // Clear HTML tags from text, prepare pure message
    $full_text = preg_replace('|\s+|', ' ', preg_replace('/<br\\s*?\/??>/i', '
        ', preg_replace('/\s*(?:<br\s*\/?>\s*)*$/i', '', preg_replace('/^\s*(?:<br\s*\/?>\s*)*/i', '', preg_replace('|(<br />)|', ' ', strip_tags(nl2br($full_text), '<br>'))))));
        
    $short_text = preg_replace('|\s+|', ' ', preg_replace('/<br\\s*?\/??>/i', '
        ', preg_replace('/\s*(?:<br\s*\/?>\s*)*$/i', '', preg_replace('/^\s*(?:<br\s*\/?>\s*)*/i', '', preg_replace('|(<br />)|', ' ', strip_tags(nl2br($short_text), '<br>'))))));
    
    // Make short link
    $link = curlGetShortLink($link);
    
    // Cut message to allowed size
    if(!empty($full_text)){
        $title = substr(stripslashes($title), 0, 38);
        $text = $link." ".strip_tags(stripslashes($title)).": ".substr(stripslashes($full_text), 0, 50)."";
        $text = stripslashes($text);
        $text = iconv( 'windows-1251', 'utf-8', $text);
    } 
    elseif(!empty($short_text)){
        $title = substr(stripslashes($title), 0, 38);
        $text = $link." ".strip_tags(stripslashes($title)).": ".substr(stripslashes($short_text), 0, 50)."";
        $text = stripslashes($text);
        $text = iconv( 'windows-1251', 'utf-8', $text);
    } else {
        $title = substr(stripslashes($title), 0, 119);
        $text = $link." ".strip_tags(stripslashes($title));
        $text = stripslashes($text);
        $text = iconv( 'windows-1251', 'utf-8', $text);
    }

    /** Prepare query for Twitter API using cURL library (OAuth authorisation according to OAuth standard) **/

    // If message contains a picture - use "update_with_media" API method
    if(!empty($picture)){
        $url = "https://upload.twitter.com/1/statuses/update_with_media.json";
        $photo = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][0].'');
        
        $txt = 'POST&'.rawurlencode($url).'&';
        $data = array(
                'oauth_consumer_key' => $tw_consumer_key,
                'oauth_nonce' => $nonce,
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => $time,
                'oauth_token' => $tw_oauth_token,
                'oauth_version' => '1.0'
            );
        $tmp = array(); foreach($data as $key => $value) $tmp[] = rawurlencode($key)."%3D".rawurlencode($value);
        $txt .= implode("%26", $tmp); $key = $tw_consumer_secret.'&'.$tw_oauth_token_secret; $signature_key = base64_encode(hash_hmac('sha1', $txt, $key, true));
        
        $post_data = array(
            'media[]' => $photo,
            'status' => $text
        );
    }
    // For messages without images - use "update" API method
    else {
        $url = "https://api.twitter.com/1/statuses/update.json";
        $txt = 'POST&'.rawurlencode($url).'&';
        $data = array(
                'oauth_consumer_key' => $tw_consumer_key,
                'oauth_nonce' => $nonce,
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => $time,
                'oauth_token' => $tw_oauth_token,
                'oauth_version' => '1.0',
                'status' => rawurlencode($text)
            );
        $tmp = array(); foreach($data as $key => $value) $tmp[] = rawurlencode($key)."%3D".rawurlencode($value);
        $txt .= implode("%26", $tmp); $key = $tw_consumer_secret.'&'.$tw_oauth_token_secret; $signature_key = base64_encode(hash_hmac('sha1', $txt, $key, true));
        
        $post_data = array(
            'status' => $text
        );
        $post_data = http_build_query($post_data);
    }
    // Prepare headers for OAuth
    $header = 'OAuth oauth_consumer_key="'.rawurlencode($tw_consumer_key).'", ';
    $header .= 'oauth_nonce="'.rawurlencode($nonce).'", ';
    $header .= 'oauth_signature="'.rawurlencode($signature_key).'", ';
    $header .= 'oauth_signature_method="HMAC-SHA1", ';
    $header .= 'oauth_timestamp="'.rawurlencode($time).'", ';
    $header .= 'oauth_token="'.rawurlencode($tw_oauth_token).'", ';
    $header .= 'oauth_version="1.0"';
    
    // Send request using cURL
    $ci = curl_init();
    curl_setopt($ci, CURLOPT_VERBOSE, true);
    curl_setopt($ci, CURLOPT_URL, $url);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Authorization: '.$header.''));
    curl_setopt($ci, CURLOPT_POST, true);
    curl_setopt($ci, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ci, CURLOPT_HEADER, true);

    // Result is here
    $twit_post_res = curl_exec($ci);
}

// Post to FaceBook
function fb_post($title, $short_text, $full_text, $link, $fb_uid, $fb_token_pid, $fb_token_uid){
    // Parse image from text
    if(@preg_match_all("/<(img|image)[\s]+[^>]*src=['\"]?([^'\"\s>]+)['\"]?[^>]*>/is", stripslashes($full_text), $image)){
        $picture = $image[2][0];
    } elseif(@preg_match_all("/<(img|image)[\s]+[^>]*src=['\"]?([^'\"\s>]+)['\"]?[^>]*>/is", stripslashes($short_text), $image)){
        $picture = $image[2][0];
    } else
        $picture = '';

    // Clear HTML tags from text, prepare pure message
    $full_text = preg_replace('/<br\\s*?\/??>/i', '
        ', preg_replace('/\s*(?:<br\s*\/?>\s*)*$/i', '', preg_replace('/^\s*(?:<br\s*\/?>\s*)*/i', '', preg_replace('|(<br />){2,}|', '<br /><br />', strip_tags(nl2br($full_text), '<br>')))));
        
    $short_text = preg_replace('/<br\\s*?\/??>/i', '
        ', preg_replace('/\s*(?:<br\s*\/?>\s*)*$/i', '', preg_replace('/^\s*(?:<br\s*\/?>\s*)*/i', '', preg_replace('|(<br />){2,}|', '<br /><br />', strip_tags(nl2br($short_text), '<br>')))));

    // Add read more line to the end of the message, convert charsets
    if(!empty($full_text)){
        $text = strip_tags(stripslashes($title))."

            ".substr(stripslashes($full_text), 0, 500)." ......

            Подробнее - у нас на сайте, ".$link."";
        $text = stripslashes($text);
        $text = iconv( 'windows-1251', 'utf-8' , $text);
    } 
    elseif(!empty($short_text)){
        $text = strip_tags(stripslashes($title))."

            ".substr(stripslashes($short_text), 0, 500)." ......

            Подробнее - у нас на сайте, ".$link."";
        $text = stripslashes($text);
        $text = iconv( 'windows-1251', 'utf-8' , $text);
    } else {
        $text = strip_tags(stripslashes($title))."

            Подробнее - у нас на сайте, ".$link."";
        $text = stripslashes($text);
        $text = iconv( 'windows-1251', 'utf-8' , $text);
    }

    // Prepare title
    $title = iconv( 'windows-1251', 'utf-8' , $title);

    // Prepare POST data
    $post_data = array(
        'access_token' => $fb_token_pid,
        'link' => $link,
        'picture' => $picture,
        'name' => $title,
        'caption' => $title,
        'description' => $link,
        'message' => $text
    );

    // Send message to FaceBook
    $ch = curl_init('https://graph.facebook.com/'.$fb_uid.'/feed');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $pub_post_res = curl_exec($ch);
    
    curl_close($ch);

    // Send image to FaceBook
    if(!empty($picture)){
        $begin_mess = iconv( 'windows-1251', 'utf-8' , 'Подробнее - у нас на сайте, ');
        $photo['message'] = ''.$begin_mess.''.$link.'
        
        '.$text;
        
        $photo['source'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][0].'');
    
        $ch = curl_init('https://graph.facebook.com/'.$fb_uid.'/photos?access_token='.$fb_token_uid.'');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $photo);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $data = curl_exec($ch);
        
        curl_close($ch);
    }

    return $pub_post_res;
}

// Post to VK
function vk_post($title, $short_text, $full_text, $link, $pid, $aid, $token, $m_url = 'https://api.vkontakte.ru/method/'){
    // Clear mesage from garbage images
    $full_text = preg_replace("/<(img|image)[\s]+[^>]*src=['\"]?\{THEME\}['\"]?[^>]*>/is", "", stripslashes($full_text));
    $short_text = preg_replace("/<(img|image)[\s]+[^>]*src=['\"]?\{THEME\}['\"]?[^>]*>/is", "", stripslashes($short_text));

    // If message contains pictures - prepare them to upload into VK public page (up to 4 images according to VK API)
    if(@preg_match_all("/<(img|image)[\s]+[^>]*src=['\"]?([^'\"\s>]+)['\"]?[^>]*>/is", stripslashes($full_text), $image)){
        $x = 0;
        // while($x < count($image[2])){
            $upload_server = json_decode(file_get_contents($m_url."photos.getUploadServer?aid=".$aid."&access_token=".$token.""));

            if(!empty($image[2][$x])) $photos['file1'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x].'');
            if(!empty($image[2][$x+1])) $photos['file2'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x+1].'');
            if(!empty($image[2][$x+2])) $photos['file3'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x+2].'');
            if(!empty($image[2][$x+3])) $photos['file4'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x+3].'');
            // if(!empty($image[2][$x+4])) $photos['file5'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x+4].'');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $upload_server->response->upload_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $photos);
            $curl_result = curl_exec($ch);
            curl_close($ch);

            $upload_photo = json_decode($curl_result);

            $save_server = $upload_photo->server; $save_photos_list = $upload_photo->photos_list; $save_hash = $upload_photo->hash;

            $save_photo = json_decode(file_get_contents($m_url."photos.save?aid=".$aid."&server=".$save_server."&photos_list=".$save_photos_list."&hash=".$save_hash."&access_token=".$token.""));
            
            $i = 0;
            while($i < count($save_photo->response)){
                $photo_id .= $save_photo->response[$i]->id.',';
                $i++;
            }

            // $x = $x+5;
        // }
    } elseif(@preg_match_all("/<(img|image)[\s]+[^>]*src=['\"]?([^'\"\s>]+)['\"]?[^>]*>/is", stripslashes($short_text), $image)){
        $x = 0;
        // while($x < count($image[2])){
            $upload_server = json_decode(file_get_contents($m_url."photos.getUploadServer?aid=".$aid."&access_token=".$token.""));
                
            if(!empty($image[2][$x])) $photos['file1'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x].'');
            if(!empty($image[2][$x+1])) $photos['file2'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x+1].'');
            if(!empty($image[2][$x+2])) $photos['file3'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x+2].'');
            if(!empty($image[2][$x+3])) $photos['file4'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x+3].'');
            // if(!empty($image[2][$x+4])) $photos['file5'] = str_replace('http://altfast.ru/', '', '@'.''.$_SERVER['DOCUMENT_ROOT'].'/'.$image[2][$x+4].'');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $upload_server->response->upload_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $photos);
            $curl_result = curl_exec($ch);
            curl_close($ch);

            $upload_photo = json_decode($curl_result);

            $save_server = $upload_photo->server; $save_photos_list = $upload_photo->photos_list; $save_hash = $upload_photo->hash;

            $save_photo = json_decode(file_get_contents($m_url."photos.save?aid=".$aid."&server=".$save_server."&photos_list=".$save_photos_list."&hash=".$save_hash."&access_token=".$token.""));

            $i = 0;
            while($i < count($save_photo->response)){
                $photo_id .= $save_photo->response[$i]->id.',';
                $i++;
            }

            // $x = $x+5;
        // }
    } else
        $photo_id = '';

    // Clear message from HTML tags
    $full_text = preg_replace('/<br\\s*?\/??>/i', '
        ', preg_replace('/\s*(?:<br\s*\/?>\s*)*$/i', '', preg_replace('/^\s*(?:<br\s*\/?>\s*)*/i', '', preg_replace('|(<br />){2,}|', '<br /><br />', strip_tags(nl2br($full_text), '<br>')))));
        
    $short_text = preg_replace('/<br\\s*?\/??>/i', '
        ', preg_replace('/\s*(?:<br\s*\/?>\s*)*$/i', '', preg_replace('/^\s*(?:<br\s*\/?>\s*)*/i', '', preg_replace('|(<br />){2,}|', '<br /><br />', strip_tags(nl2br($short_text), '<br>')))));

    // Cut message to 500 characters and add "read more" link
    if(!empty($full_text)){
        $text = strip_tags(stripslashes($title))."

            ".substr(stripslashes($full_text), 0, 500)." ......

            Подробнее - у нас на сайте, ".$link."";
        $text = stripslashes($text);
        $text = urlencode(iconv( 'windows-1251', 'utf-8' , $text));
    }
    elseif(!empty($short_text)){
        $text = strip_tags(stripslashes($title))."

            ".substr(stripslashes($short_text), 0, 500)." ......

            Подробнее - у нас на сайте, ".$link."";
        $text = stripslashes($text);
        $text = urlencode(iconv( 'windows-1251', 'utf-8' , $text));
    } else {
        $text = strip_tags(stripslashes($title))."

            Подробнее - у нас на сайте, ".$link."";
        $text = stripslashes($text);
        $text = urlencode(iconv( 'windows-1251', 'utf-8' , $text));
    }

    // Call "wall.post" API method with sending to it all needed data
    $wall_post = json_decode(file_get_contents($m_url."wall.post?owner_id=".$pid."&access_token=".$token."&message=".$text."&attachment=".$photo_id.$link.""));

    return $wall_post;
}
?>