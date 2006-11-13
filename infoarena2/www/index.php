<?php

// common/log.php checks this.
define("IA_INSIDE_WWW", true);

require_once("config.php");
require_once(IA_ROOT . "common/log.php");
require_once(IA_ROOT . "common/common.php");
check_requirements();

require_once(IA_ROOT . "common/db/db.php");
require_once(IA_ROOT . "common/permissions.php");

require_once("utilities.php");
require_once("identity.php");
require_once("wiki/wiki.php");
require_once("textblock.php");

log_print("- -- --- ---- ----- Request: ".$_SERVER['QUERY_STRING']);

// restore identity (if such a session exists)
identity_restore();

// Do url validation.
// All urls that pass are valid, they can be missing wiki pages.
$page = request('page');

if (!preg_match('/^([a-z0-9_\-\/]*)$/i', $page)) {
    flash_error('invalid URL');
    redirect(url(''));
}


// Redirect to home if in /
if ($page == "") {
    $page = "home";
}

// split the page url
list($page_class, $page_id) = textblock_split_name($page);

// This is the first part of the url path
$urlstart = getattr(split('/', $page), 0, '');

// A lot of logic depends on this, so we try to keep the code nicer.
$action = request('action', 'view');

// Direct mapping list
// Note: array_flip() flips keys with values in a dictionary.
$directmaps = array_flip(array('register', 'profile', 'page_index',
                               'login', 'logout', 'reset_pass', 'json',
                               'job_detail', 'monitor', 'submit', 'userinfo'));
//
// Here comes the big url mapper.
// We include in the if statement to avoid an extra parsing load.
//

// Trivial direct mappings.
if (isset($directmaps[$urlstart])) {
    require_once("controllers/{$urlstart}.php");
    $fname = "controller_{$urlstart}";
    $fname($page_id);
}

// FIXME: convert to direct mapping.
else if ($urlstart == 'news_feed') {
    require_once("controllers/news.php");
    controller_news_view_feed();
}

// textblock controllers
//  - edit textblock
else if ($action == 'edit') {
    require_once('controllers/textblock.php');
    controller_textblock_edit($page);
}
//  - save textblock
else if ($action == 'save') {
    require_once('controllers/textblock.php');
    controller_textblock_save($page);
}
//  - delete textblock
else if ($action == 'delete') {
    require_once('controllers/textblock.php');
    controller_textblock_delete($page);
}
//  - view textblock history
else if ($action == 'history') {
    require_once('controllers/textblock.php');
    controller_textblock_history($page);
}
//  - restore textblock
else if ($action == 'restore') {
    require_once('controllers/textblock.php');
    controller_textblock_restore_revision($page, request('revision'));
}
//  - view textblock differences between revisions
else if ($action == 'diff') {
    require_once('controllers/textblock.php');
    controller_textblock_diff_revision($page, request('revision'));
}
//  - view textblock feed
else if ($action=='feed' && ($page_class!=TEXTBLOCK_NEWS || $page_id)) {
    require_once('controllers/textblock.php');
    controller_textblock_feed($page);
}

// task-specific controllers
//  - show details edit form
else if (TEXTBLOCK_TASK==$page_class && 'details'==$action) {
    require_once('controllers/task.php');
    controller_task_edit_details($page_id);
}
//  - save details
else if (TEXTBLOCK_TASK==$page_class && 'details-save'==$action) {
    require_once('controllers/task.php');
    controller_task_save_details($page_id);
}

// round-specific controllers
//  - edit round details
else if (TEXTBLOCK_ROUND==$page_class && $action == 'details') {
    require_once('controllers/round.php');
    controller_round_edit_details($page_id);
}
//  - save round details
else if ($urlstart=='round' && $action=='details-save') {
    require_once('controllers/round.php');
    controller_round_save_details($page_id);
}
//  - register for contest 
else if (TEXTBLOCK_ROUND==$page_class && 'register'==$action) {
    require_once('controllers/round.php');
    controller_round_register($page_id);
}
//  - register for contest (process input)
else if (TEXTBLOCK_ROUND==$page_class && 'register-process'==$action) {
    require_once('controllers/round.php');
    controller_round_register($page_id, true);
}

// attachment controllers
//  - create attachment
else if ($action == 'attach') {
    require_once('controllers/attachment.php');
    controller_attachment_create($page);
}
//  - save attachment
else if ($action == 'attach-submit') {
    require_once('controllers/attachment.php');
    controller_attachment_submit($page);
}
//  - print attachment list
else if ($action == 'attach-list') {
    require_once('controllers/attachment.php');
    controller_attachment_list($page);
}
//  - attachment delete
else if ($action == 'attach-del') {
    require_once('controllers/attachment.php');
    controller_attachment_delete($page);
}
//  - attachment download
else if ($action == 'download') {
    if (request('resize')) {
        require_once('controllers/image_attachment.php');
        // download resized image
        controller_attachment_resized_img($page, request('file'), request('resize'));
    }
    else {
        require_once('controllers/attachment.php');
        // regular file download
        controller_attachment_download($page, request('file'));
    }
}

// textblock view
else if ($action == 'view') {
    require_once('controllers/textblock.php');
    controller_textblock_view($page, request('revision'));
}

// invalid URL
else {
    flash_error('URL invalid');
    redirect(url(''));
}

?>
