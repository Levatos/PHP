<?php
/*
Plugin Name: SQLite Chat
Plugin URI: http://altfast.ru
Description: Sidebar minichat based on SQLite 3
Version: 1.0
Author: Valerii Kulykov
Author URI: http://altfast.ru
*/

class SQLite_Chat extends WP_Widget
{
    public function __construct() {
        parent::__construct("sqlite_chat", "SQLite Chat",
            array("description" => "Sidebar minichat based on SQLite 3"));
    }
    
    public function form($instance) {
        $title = "";
        
        if (!empty($instance)) {
            $title = $instance["title"];
        }
 
        $tableId = $this->get_field_id("title");
        $tableName = $this->get_field_name("title");
        echo '<label for="' . $tableId . '">Title</label><br>';
        echo '<input id="' . $tableId . '" type="text" name="' .
        $tableName . '" value="' . $title . '"><br>';
    }
    
    public function update($newInstance, $oldInstance) {
        $values = array();
        $values["title"] = htmlentities($newInstance["title"]);

        return $values;
    }

    public function widget($args, $instance) {
        $title = $instance["title"];

        echo $args['before_widget'];

        echo $args['before_title'];
        echo apply_filters( 'widget_title', $title);
        echo $args['after_title'];

        wp_enqueue_script( 'sqlite_chat_main_js', plugins_url( 'js/action.js', __FILE__ ) );
        wp_enqueue_style( 'sqlite_chat_main_style', plugins_url( 'css/chat.css', __FILE__), array(), '1.0' );
?>
<script type="text/javascript">
    <!--
        jQuery( document ).ready(function() {
            setInterval(sqlite_chat_refresh, 15000);
        });


        jQuery(function () {
            jQuery(document).on("keydown", "#sqlite_chat_message", function(e) {
                if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey) {
                    sqlite_chat_add('add', ''); return false;
                }
            });
        });
    //-->
</script>


<?php sqlite_chat_show_messages(); ?>

<br />

<div id="loading-layer" style="display: none;">
    <div id="loading-layer-text">Loading</div>
</div>

<div class="sqlite_chat_add_block">
    <div class="sqlite_chat_bbcode_bar">
        <span onclick="sqlite_chat_message_insert('tagname', 'b');">
            <img title="Полужирный" src="<?php echo(plugin_dir_url( __FILE__ )); ?>img/bbcode/b.png" alt="" />
        </span>
        <span onclick="sqlite_chat_message_insert('tagname', 'i');">
            <img title="Наклонный текст" src="<?php echo(plugin_dir_url( __FILE__ )); ?>img/bbcode/i.png" alt="" />
        </span>
        <span onclick="sqlite_chat_message_insert('tagname', 'u');">
            <img title="Подчеркнутый текст" src="<?php echo(plugin_dir_url( __FILE__ )); ?>img/bbcode/u.png" alt="" />
        </span>

        <img class="bbspacer" src="<?php echo(plugin_dir_url( __FILE__ )); ?>img/bbcode/brkspace.png" alt="" />

        <span onclick="sqlite_chat_ins_emo(this);">
            <img title="Вставка смайликов" src="<?php echo(plugin_dir_url( __FILE__ )); ?>img/bbcode/emo.png" alt="" />
        </span>

        <span onclick="sqlite_chat_tag_leech();">
            <img title="Вставка защищенной ссылки" src="<?php echo(plugin_dir_url( __FILE__ )); ?>img/bbcode/link.png" alt="" />
        </span>

        <div class="clr"></div>
    </div>

    <form method="post" name="sqlite_chat_form" id="sqlite_chat_form" class="sqlite_chat" action="/">
        <textarea name="sqlite_chat_message" id="sqlite_chat_message" rows="" cols=""></textarea>

        <div class="clr"></div>

        <div style="padding-top: 5px;">
            <input class="button ch_send" title="Отправить" onclick="sqlite_chat_add('add', ''); return false;" type="button" value="Отправить" />
            <input class="button ch_refresh" title="Обновить" onclick="sqlite_chat_refresh(); return false;" type="button" value="Обновить" />
            <input class="button ch_history" title="История сообщений" onclick="sqlite_chat_show_history(); return false;" type="button" value="История" />
        </div>
    </form>
</div>
<?php

        echo $args['after_widget'];
    }
}

add_action("widgets_init", function () {
    register_widget("SQLite_Chat");
});

$sqlite_chat_db = new SQLite3($_SERVER['DOCUMENT_ROOT'].'/wp-content/plugins/sqlite_chat/data/sqlite_chat.db');

include_once('config.php');
include_once('functions.php');
?>