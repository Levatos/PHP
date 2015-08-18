<?php
// Auth info
$login = ""; // VK user E-Mail
$password = ""; // VK user password

$auth_url = "https://oauth.vk.com/authorize";
$u_id = ""; // User ID
$scope = "8204";
$r_url = "http://oauth.vk.com/blank.html";

// Parse cookies from cURL result
function get_cookies($result){
    $cookie_array = explode("Set-Cookie:", $result);
    $cookies = ""; $count = 1;

    while ($count < count($cookie_array)){
        $cookies .= substr($cookie_array[$count].";", 0, strpos($cookie_array[$count].";", ";")+1);
        $count++;
    }

    return $cookies;
}

// Parse location from cURL result
function get_location($result) {
    $location = explode("Location:", $result);
    $location = trim(substr($location[1], 0, strpos($location[1], "\n")+1));

    return $location;
}

// cURL connect function
function curl_connect($url, $referer, $send_headers, $post, $cookies, $header, $timeout){
    // Start connection
    $curl_connect = curl_init();

    // Tell site that we use Opera client
    curl_setopt($curl_connect, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.14');

    // cURL options
    curl_setopt($curl_connect, CURLOPT_URL, trim($url));

    curl_setopt($curl_connect, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($curl_connect, CURLOPT_ENCODING, 1);
    curl_setopt($curl_connect, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_connect, CURLOPT_TIMEOUT, $timeout);

    // Turn off headers if we don't need to use them
    if($header == false) curl_setopt($curl_connect, CURLOPT_HEADER, false);
    else curl_setopt($curl_connect, CURLOPT_HEADER, true);

    curl_setopt($curl_connect, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl_connect, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($curl_connect, CURLOPT_REFERER, trim($referer));

    if ($send_headers != false) curl_setopt($curl_connect, CURLOPT_HTTPHEADER, $send_headers);
    
    if ($cookies != false) curl_setopt($curl_connect, CURLOPT_HTTPHEADER, array("Cookie: ".$cookies.""));
    
    if ($post != false) {
        curl_setopt($curl_connect, CURLOPT_POST, true);
        curl_setopt($curl_connect, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $result = curl_exec($curl_connect);
 
    curl_close($curl_connect);

    return $result;
}

// VK auth & token get function
function get_vk_token($login, $password, $auth_url, $u_id, $scope, $r_url){
    $headers = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*;q=0.8',
        'Accept-Language: ru,en-us;q=0.7,en;q=0.3',
        'Accept-Encoding: gzip,deflate',
        'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7'
    );

    // Collect auth parameters and hash from mini VK, construct headers
    $get_hash_result = curl_connect('http://m.vk.com/login', '', $headers, false, false, true, '30');
    $get_hash_cookies = get_cookies($get_hash_result);
    
    preg_match('/ip_h=([^&]*)/', $get_hash_result, $ip_hash);
    preg_match('/lg_h=([^&]*)/', $get_hash_result, $lg_hash);

    // Construct POST request with auth data
    $post = array(
        'email' => $login,
        'pass' => $password
    );

    // Obtain first part of cookies with auth
    $auth_result_step_1 = curl_connect('https://login.vk.com/?act=login&_origin=http://m.vk.com&ip_h='.$ip_hash[1].'&lg_h='.$lg_hash[1].'&role=pda&utf8=1', 'http://m.vk.com/login', $headers, $post, $get_hash_cookies, true, '30');

    $auth_result_step_1_cookies = get_cookies($auth_result_step_1);
    $auth_result_step_1_location = get_location($auth_result_step_1);

    // Obtain second part of cookies with auth
    $auth_result_step_2 = curl_connect($auth_result_step_1_location, 'http://m.vk.com/login', $headers, false, $auth_result_step_1_cookies, true, '30');
    $auth_result_step_2_cookies = get_cookies($auth_result_step_2);

    // Obtain location from first request to VK API
    $get_token_result_step_1 = curl_connect($auth_url.'?client_id='.$u_id.'&scope='.$scope.'&redirect_uri='.$r_url.'&display=page&response_type=token', 'http://altfast.ru/driver/modules/vk/connect.php', false, false, $auth_result_step_1_cookies.$auth_result_step_2_cookies, true, '30');
    $get_token_result_step_1_location = get_location($get_token_result_step_1);

    // Obtain token
    $get_token_result_step_2 = curl_connect(rawurldecode($get_token_result_step_1_location), 'your_referer', false, false, $auth_result_step_1_cookies.$auth_result_step_2_cookies, true, '30');

    $vk_token = explode("access_token=", $get_token_result_step_2);
    $vk_token = trim(substr($vk_token[1], 0, strpos($vk_token[1], "&")));

    // Below is ready token, we can save it anywhere in DB or config file
    return $vk_token;
}


// Here is implementation for WordPress, saving token to "options" table in DB using "update_option" method
@parse_str($_GET['q'], $get_query);

if (@$get_query['get_token'] == '1') {
    // Authorizing
    $vk_token = get_vk_token($login, $password, $auth_url, $u_id, $scope, $r_url);

    update_option( 'vk_token', $vk_token, 'yes' );
} else echo('Nothing to do here');

?>