function sqlite_chat_add(mode, id) {
    if(mode == 'add') {
        if (jQuery.trim(document.sqlite_chat_form.sqlite_chat_message.value) == '') { 
            alert('Emty text area');

            return false;
        };
        var message = document.getElementById('sqlite_chat_message').value;
    }
    if(mode == 'edit') var message = document.getElementById('sqlite_chat_new_message').value;

    iShowLoading('');

    jQuery.post("/wp-admin/admin-ajax.php", { action: 'sqlite_chat_add', mode: mode, id: id, message: message }, function(data) {

        iHideLoading('');

        jQuery("#sqlite_chat_messages").fadeOut(500, function() {
            jQuery(this).html(jQuery.trim(data));
            jQuery(this).fadeIn(500);
        });

    });
    
    if(mode == 'edit') jQuery('#sqlite_chat_edit_message_dialog').dialog("close");
    
    document.getElementById('sqlite_chat_form').sqlite_chat_message.value = '';
};

function sqlite_chat_delete(id) {
    iShowLoading('');

    jQuery.post("/wp-admin/admin-ajax.php", { action: 'sqlite_chat_delete', id: id }, function(data) {
        iHideLoading('');
        
        jQuery("#sqlite_chat_messages").fadeOut(500, function() {
            jQuery(this).html(jQuery.trim(data));
            jQuery(this).fadeIn(500);
        });

    });
};

function sqlite_chat_refresh() {
    jQuery.post("/wp-admin/admin-ajax.php", { action: 'sqlite_chat_refresh' }, function(data) {

        if (jQuery.trim(data) != 'no need refresh') {
            jQuery("#sqlite_chat_messages").html(jQuery.trim(data));
        };

    });

    return false;
};

function iShowLoading(message) {

    if (message) {
        jQuery("#loading-layer-text").html(message);
    }
        
    var setX = ( jQuery(window).width()  - jQuery("#loading-layer").width()  ) / 2;
    var setY = ( jQuery(window).height() - jQuery("#loading-layer").height() ) / 2;
            
    jQuery("#loading-layer").css( {
        left : setX + "px",
        top : setY + "px",
        position : 'fixed',
        zIndex : '99'
    });
        
    jQuery("#loading-layer").fadeTo('slow', 0.6);
};

function iHideLoading(message) {
    jQuery("#loading-layer").fadeOut('slow');
};

function sqlite_chat_ins_emo() {
    jQuery.post("/wp-admin/admin-ajax.php", { action: 'sqlite_chat_show_smilies' }, function(data) {
        jQuery("body").append("<div id='sqlite_chat_emoticons_dialog' title='Смайлы' style='display: none; height: 100%;'>" + jQuery.trim(data) + "</div>");
        
        jQuery('#sqlite_chat_emoticons_dialog').dialog({
            autoOpen: true,
            show: 'slide',
            hide: 'explode',
            width: '300',
            height: '400',
            position: {my: "right-70% center", at: "center bottom-10%", of: jQuery("#sqlite_chat")}}
        );
    });
};

function sqlite_chat_show_history() {
    jQuery('#sqlite_chat_history_dialog').remove();
    jQuery.post("/wp-admin/admin-ajax.php", { action: 'sqlite_chat_show_history' }, function(data) {
        jQuery("body").append("<div id='sqlite_chat_history_dialog' title='История сообщений' style='display: none; height: 100%;'>" + jQuery.trim(data) + "</div>");
        
        jQuery('#sqlite_chat_history_dialog').dialog({
            autoOpen: true,
            show: 'slide',
            hide: 'explode',
            width: '300',
            height: '400',
            position: {my: "right-70% center", at: "center bottom-10%", of: jQuery("#sqlite_chat")}}
        );
    });
};

function sqlite_chat_edit_message(id) {
    jQuery('#sqlite_chat_edit_message_dialog').remove();
    jQuery.post("/wp-admin/admin-ajax.php", { action: 'sqlite_chat_edit_message', id: id }, function(data) {
        jQuery("body").append("<div id='sqlite_chat_edit_message_dialog' title='Изменение сообщения' style='display: none; height: 100%;'>" + jQuery.trim(data) + "</div>");
        
        jQuery('#sqlite_chat_edit_message_dialog').dialog({
            autoOpen: true,
            show: 'slide',
            hide: 'explode',
            width: '300',
            height: '270',
            position: {my: "right-70% center", at: "center bottom-10%", of: jQuery("#sqlite_chat")}
        });
    });
};

function sqlite_chat_tag_leech() {
    var site_name = prompt('Ведите название сайта', 'My Webpage');
    var site_url = prompt('Ведите URL сайта', 'http://');

    sqlite_chat_message_insert('url', '[leech=' + site_url + ']' + site_name + '[/leech]');
};


function sqlite_chat_message_insert(mode, text) {
    document.getElementById('sqlite_chat_message').focus();

    if(mode == 'smile') {
        jQuery('#sqlite_chat_message').val(jQuery('#sqlite_chat_message').val() + ' ' + text + ' ');
        jQuery('#sqlite_chat_emoticons_dialog').dialog("close");

        document.getElementById('sqlite_chat_message').focus();
    }

    if(mode == 'url') jQuery('#sqlite_chat_message').val(jQuery('#sqlite_chat_message').val() + ' ' + text + ' ');

    if(mode == 'tagname') jQuery('#sqlite_chat_message').val(jQuery('#sqlite_chat_message').val()+' [' + text + '][/' + text + '] ');
};