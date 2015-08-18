<?php
function sqlite_chat_show_messages($quantity = 10) {
    global $sqlite_chat_cfg;
    global $sqlite_chat_db;

    $sqlite_chat_current_user = wp_get_current_user();
    
    $query = $sqlite_chat_db->query( "SELECT * FROM sq_chat ORDER BY date DESC LIMIT '{$quantity}'" );

    $messages = "<div class=\"sqlite_chat\" id=\"sqlite_chat\" style=\"width: max; overflow: auto;\">
                    <div id=\"sqlite_chat_messages\">
    ";

    while ( $row = $query->fetchArray() ) {
        if($sqlite_chat_current_user->user_level >= 10) {
            $admin_bar = ('
                <div align="right">
                    <img class="action" onclick="sqlite_chat_edit_message('.$row['id'].'); return false;" src="'.plugin_dir_url( __FILE__ ) . 'img/edit.png" alt="Редактировать"/>
                    <img class="action" onclick="sqlite_chat_delete('.$row['id'].'); return false;" src="'.plugin_dir_url( __FILE__ ) . 'img/delete.png" alt="Удалить" />
                </div>
            ');
        } else $admin_bar = '';
        
        $messages .= trim('
            <div class="sqlite_chat_message">
            
                <div class="avatar">
                    <img src="'.get_avatar_url(get_avatar($row['email'])).'" width="32" height="32" alt="'.iconv('cp1251', 'utf-8', $row['author']).'" />
                </div>

                <div class="info"><b>'.iconv('cp1251', 'utf-8', $row['author']).'</b><br />'.iconv('cp1251', 'utf-8', $row['date']).'</div>
                <hr />
                <div class="text">'.stripslashes(iconv('cp1251', 'utf-8', $row['message'])).'</div>
                '.$admin_bar.'
            </div>
        ');
    }
    
    $messages .= "</div></div>";
    
    echo $messages;
}

function sqlite_chat_clear_url($url) {
    $url = strip_tags( trim( stripslashes( $url ) ) );

    $url = str_replace( '\"', '"', $url );
    $url = str_replace( "'", "", $url );
    $url = str_replace( '"', "", $url );

    $url = str_ireplace( "document.cookie", "d&#111;cument.cookie", $url );
    $url = str_replace( " ", "%20", $url );
    $url = str_replace( "<", "&#60;", $url );
    $url = str_replace( ">", "&#62;", $url );
    $url = preg_replace( "/javascript:/i", "j&#097;vascript:", $url );
    $url = preg_replace( "/data:/i", "d&#097;ta:", $url );

    return $url;
}

function sqlite_chat_build_url($url = array()) {
    if( preg_match( "/([\.,\?]|&#33;)$/", $url['show'], $match ) ) {
        $url['end'] .= $match[1];
        $url['show'] = preg_replace( "/([\.,\?]|&#33;)$/", "", $url['show'] );
    }

    $url['html'] = sqlite_chat_clear_url( $url['html'] );
    $url['show'] = stripslashes( $url['show'] );

    $url['show'] = str_replace( "&nbsp;", " ", $url['show'] );
    
    if( ! preg_match( "#^(http|news|https|ed2k|ftp|aim|mms)://|(magnet:?)#", $url['html'] ) AND $url['html'][0] != "/" AND $url['html'][0] != "#") {
        $url['html'] = 'http://' . $url['html'];
    }

    $url['show'] = str_replace( "&amp;amp;", "&amp;", $url['show'] );
    $url['show'] = preg_replace( "/javascript:/i", "javascript&#58; ", $url['show'] );

    $target = "target=\"_blank\"";

    $url['html'] = home_url() . "/away/" . rawurlencode( base64_encode( $url['html'] ) );

    return "<!--wp_leech_begin--><a href=\"" . $url['html'] . "\" " . $target . ">" . $url['show'] . "</a><!--wp_leech_end-->" . @$url['end'];

}

function sqlite_chat_decode_leech($url = "", $show = "") {
    $show = stripslashes( $show );

    $url = explode( "away/", $url );
    $url = end( $url );
    $url = rawurldecode( $url );
    $url = base64_decode( $url );
    $url = str_replace("&amp;","&", $url );

    return "[leech=" . $url . "]" . $show . "[/leech]";
}

function sqlite_chat_decode_message($message) {
    global $sqlite_chat_cfg;

    $message = stripslashes( $message );
    $message = str_replace( "<b>", "[b]", str_replace( "</b>", "[/b]", $message ) );
    $message = str_replace( "<i>", "[i]", str_replace( "</i>", "[/i]", $message ) );
    $message = str_replace( "<u>", "[u]", str_replace( "</u>", "[/u]", $message ) );
    $message = str_replace( "<s>", "[s]", str_replace( "</s>", "[/s]", $message ) );

    $message = preg_replace( "#<!--wp_leech_begin--><a href=[\"'](http://|https://|ftp://|ed2k://|news://|magnet:)?(\S.+?)['\"].*?" . ">(.+?)</a><!--wp_leech_end-->#ie", "\sqlite_chat_decode_leech('\\1\\2', '\\3')", $message );

    $message = str_ireplace( "<br>", "\n", $message );
    $message = str_ireplace( "<br />", "\n", $message );

    $message = preg_replace( "#<!--smile:(.+?)-->(.+?)<!--/smile-->#is", ':\\1:', $message );

    $smilies = explode( ",", $sqlite_chat_cfg['smiles'] );
    $find[] = "'<!-- stop -->'"; $replace[] = "";
    foreach ( $smilies as $smile ) {
        $smile = trim( $smile );
        $replace[] = ":$smile:";
        $find[] = "#<img style=['\"]border: none;['\"] alt=['\"]" . $smile . "['\"] align=['\"]absmiddle['\"] src=['\"](.+?)" . $smile . ".gif['\"] />#is";
    }

    $message = preg_replace( $find, $replace, $message );
    
    return $message;
}

function sqlite_chat_encode_message($message) {
    global $sqlite_chat_cfg;
    global $sqlite_chat_db;
    
    $message = str_ireplace( "[b]", "<b>", str_ireplace( "[/b]", "</b>", $message ) );
    $message = str_ireplace( "[i]", "<i>", str_ireplace( "[/i]", "</i>", $message ) );
    $message = str_ireplace( "[u]", "<u>", str_ireplace( "[/u]", "</u>", $message ) );
    $message = str_ireplace( "[s]", "<s>", str_ireplace( "[/s]", "</s>", $message ) );        

    $find = array("'{'", "'}'", "'\['", "']'");
    $replace = array("{<!-- stop -->", "<!-- stop -->}", "[<!-- stop -->", "<!-- stop -->]");
    
    $find[] = "'\r'";
    $replace[] = "";
    $find[] = "'\n'";
    $replace[] = "<br />";

    $message = preg_replace( "#\[leech\](\S.+?)\[/leech\]#ie", "\sqlite_chat_build_url(array('html' => '\\1', 'show' => '\\1'))", $message );
    $message = preg_replace( "#\[leech\s*=\s*\&quot\;\s*(\S+?)\s*\&quot\;\s*\](.*?)\[\/leech\]#ie", "\sqlite_chat_build_url(array('html' => '\\1', 'show' => '\\2'))", $message );
    $message = preg_replace( "#\[leech\s*=\s*(\S.+?)\s*\](.*?)\[\/leech\]#ie", "\sqlite_chat_build_url(array('html' => '\\1', 'show' => '\\2'))", $message );
    
    $smilies = explode( ",", $sqlite_chat_cfg['smiles'] );
    
    foreach ( $smilies as $smile ) {
        $smile = trim( $smile );
        $find[] = "':$smile:'";
        $replace[] = "<!--smile:{$smile}--><img style=\"vertical-align: middle; border: none;\" alt=\"{$smile}\" src=\"" . includes_url() . "images/smilies/{$smile}.gif\" /><!--/smile-->";
    }

    $message = $sqlite_chat_db->escapeString(preg_replace( $find, $replace, $message ));
    
    return $message;
}

function sqlite_chat_add_message() {
    global $sqlite_chat_db;

    $message = iconv('utf-8', 'cp1251', trim($_POST['message']));

    if( function_exists( "get_magic_quotes_gpc" ) && get_magic_quotes_gpc() ) $message = stripslashes( $message ); 

    $message = htmlspecialchars( $message, ENT_QUOTES, 'cp1251' );

    $message = sqlite_chat_encode_message($message);
    
    $mode = @$_POST['mode'];
    $id = @$_POST['id'];

    if($mode == 'add') {
        $sqlite_chat_current_user = wp_get_current_user();

        if ($sqlite_chat_current_user->user_login != '') $user_login = $sqlite_chat_current_user->user_login;
        else $user_login = 'Guest';

        $user_avatar = get_avatar_url(get_avatar($sqlite_chat_current_user->user_email));

        $time = date( "Y-m-d H:i:s", time() );
        $ip_addr = getenv('REMOTE_ADDR');

        $sqlite_chat_db->query( "INSERT INTO sq_chat (date, foto, author, email, message, ip, user_group) values ('{$time}', '{$user_avatar}', '{$user_login}', '{$sqlite_chat_current_user->user_email}', '{$message}', '{$ip_addr}', '{$sqlite_chat_current_user->user_level}' )" );
    } elseif($mode == 'edit') {
        if(is_numeric($id)) {
            $sqlite_chat_db->query( "UPDATE sq_chat SET message = '{$message}' WHERE id = '{$id}'" );
        } else echo('<span style="color: red;"><b>Что-то не так с ID</b></span>');
    } else echo('<span style="color: red;"><b>Не верно указан режим создания сообщения</b></span>');

    sqlite_chat_show_messages();
    
    exit();
}
add_action('wp_ajax_sqlite_chat_add', 'sqlite_chat_add_message');
add_action('wp_ajax_nopriv_sqlite_chat_add', 'sqlite_chat_add_message');

function sqlite_chat_edit_message() {
    global $sqlite_chat_db;

    $sqlite_chat_current_user = wp_get_current_user();

    if($sqlite_chat_current_user->user_level < 10) {
        echo('<span style="color: red;"><b>Извините, не хватает прав для редактирования</b></span>');
        sqlite_chat_show_messages();
    
        exit();
    } else {
        $id = trim($_POST['id']);
        
        
        if(is_numeric($id)) {
            $query = $sqlite_chat_db->query( "SELECT * FROM sq_chat WHERE id = '{$id}'" );
            $row = $query->fetchArray();
            
            $message = iconv('cp1251', 'utf-8', trim($row['message']));

            if( function_exists( "get_magic_quotes_gpc" ) && get_magic_quotes_gpc() ) $message = stripslashes( $message ); 
            
            echo('
                <form method="post" name="sqlite_chat_edit_form" id="sqlite_chat_edit_form" class="sqlite_chat" action="/">
                    <div class="sqlite_chat_add_block">
                        <textarea name="sqlite_chat_new_message" id="sqlite_chat_new_message" rows="" cols="" style="height: 150px; width: 100%;">'.sqlite_chat_decode_message($message).'</textarea>
                    </div>
                    <div class="clr"></div>

                    <div style="padding-top: 5px;">
                        <input class="button ch_send" title="Отправить" onclick="sqlite_chat_add(\'edit\', \''.$id.'\'); return false;" type="button" value="Отправить" />
                    </div>
                </form>
            ');
        } else {
            echo('<span style="color: red;"><b>Что-то не так с ID</b></span>');

            sqlite_chat_show_messages();
        }
        
        exit();
    }
}
add_action('wp_ajax_sqlite_chat_edit_message', 'sqlite_chat_edit_message');
add_action('wp_ajax_nopriv_sqlite_chat_edit_message', 'sqlite_chat_edit_message');

function sqlite_chat_delete_message() {
    global $sqlite_chat_db;
    
    $sqlite_chat_current_user = wp_get_current_user();

    if($sqlite_chat_current_user->user_level < 10) {
        echo('<span style="color: red;"><b>Извините, не хватает прав для удаления</b></span>');
        sqlite_chat_show_messages();
    
        exit();
    } else {
        $id = trim($_POST['id']);

        if(is_numeric($id)) $sqlite_chat_db->query("DELETE FROM sq_chat WHERE id = '$id'");
        else echo('<span style="color: red;"><b>Что-то не так с ID</b></span>');

        sqlite_chat_show_messages();

        exit();
    }
}
add_action('wp_ajax_sqlite_chat_delete', 'sqlite_chat_delete_message');
add_action('wp_ajax_nopriv_sqlite_chat_delete', 'sqlite_chat_delete_message');

function sqlite_chat_show_smilies() {
    global $sqlite_chat_cfg;
    
    $output = "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\"><tr>";

    $smilies = explode(",", $sqlite_chat_cfg['smiles']);
    $count_smilies = count($smilies);
    $current_smile = 0;
    
    foreach($smilies as $smile) {
        $current_smile++; $smile = trim($smile);

        $output .= "
            <td style=\"padding: 2px;\" align=\"center\">
                <a href=\"#\" onclick=\"sqlite_chat_message_insert('smile', ':$smile:'); return false;\">
                    <img style=\"border: none;\" alt=\"$smile\" src=\"" . includes_url() . "images/smilies/{$smile}.gif\" />
                </a>
            </td>";

        if ($current_smile%4 == 0 AND $current_smile < $count_smilies) $output .= "</tr><tr>";
    }

    $output .= "</tr></table>";

    echo $output;

    exit();
}
add_action('wp_ajax_sqlite_chat_show_smilies', 'sqlite_chat_show_smilies');
add_action('wp_ajax_nopriv_sqlite_chat_show_smilies', 'sqlite_chat_show_smilies');

function sqlite_chat_show_history() {
    sqlite_chat_show_messages(1000);

    exit();
}
add_action('wp_ajax_sqlite_chat_show_history', 'sqlite_chat_show_history');
add_action('wp_ajax_nopriv_sqlite_chat_show_history', 'sqlite_chat_show_history');

function sqlite_chat_refresh() {
    sqlite_chat_show_messages();

    exit();
}
add_action('wp_ajax_sqlite_chat_refresh', 'sqlite_chat_refresh');
add_action('wp_ajax_nopriv_sqlite_chat_refresh', 'sqlite_chat_refresh');
?>