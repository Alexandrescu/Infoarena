<?php

require_once("../config.php");
require_once(IA_ROOT."www/config.php");
require_once(IA_ROOT."common/log.php");
require_once(IA_ROOT."common/common.php");
log_print("- -- --- ---- ----- Request: ".$_SERVER['QUERY_STRING']);
check_requirements();
require_once(IA_ROOT."common/security.php");
require_once(IA_ROOT."www/url.php");
require_once(IA_ROOT."www/utilities.php");
require_once(IA_ROOT."www/identity.php");

// restore identity (if such a session exists)
identity_restore();

// Do url validation.
// All urls that pass are valid, they can be missing wiki pages.
$page = request('page');

// Redirect to home if in /
if ($page == "") {
    $page = "home";
}

// Check page name.
if (!is_page_name($page)) {
    flash_error('invalid URL');
    redirect(url_home());
}

// Prepare some vars for url handler.
// Filter empty path elements. Strips extra '/'s
$page = normalize_page_name($page);
$pagepath = explode('/', $page);

$urlstart = getattr($pagepath, 0, '');
$page_id = implode('/', array_slice($pagepath, 1));
$action = request('action', 'view');

// Direct mapping list
// Note: array_flip() flips keys with values in a dictionary.
$directmaps = array_flip(array('register', 'account', 'news_feed', 'changes',
                               'login', 'logout', 'json', 'job_detail',
                               'monitor', 'submit', 'userinfo', 'plot',
                               'unsubscribe', 'resetpass'
                               ));
//
// Here comes the big url mapper.
// We include in the if statement to avoid an extra parsing load.
//

// Trivial direct mappings.
if (isset($directmaps[$urlstart])) {
    require_once(IA_ROOT."www/controllers/{$urlstart}.php");
    $fname = "controller_{$urlstart}";
    $fname($page_id);
}

else if ($page == 'admin/new-task') {
    require_once(IA_ROOT.'www/controllers/task.php');
    controller_task_create();
}

// Task detail editor.
else if ($urlstart == 'admin' && getattr($pagepath, 1) == 'task') {
    $obj_id = implode("/", array_slice($pagepath, 2));
    require_once(IA_ROOT.'www/controllers/task.php');
    controller_task_details($obj_id);
}

// Round detail editor.
// FIXME: doesn't work, fix later.
/*else if ($urlstart == 'admin' && getattr($pagepath, 1) == 'round') {
    $obj_id = implode("/", array_slice($pagepath, 2));
    if (getattr($pagepath, 1) == 'round') {
        require_once(IA_ROOT.'www/controllers/round.php');
        if ($action == 'save') {
            controller_round_edit_details($obj_id);
        } else {
            controller_round_save_details($obj_id);
        }
    }
}*/

// textblock controllers
// FIXME: quick array of sorts?
//  - edit textblock
else if ($action == 'edit') {
    require_once(IA_ROOT.'www/controllers/textblock_edit.php');
    controller_textblock_edit($page);
}
//  - delete textblock
else if ($action == 'delete') {
    require_once(IA_ROOT.'www/controllers/textblock.php');
    controller_textblock_delete($page);
}
//  - view textblock history
else if ($action == 'history') {
    require_once(IA_ROOT.'www/controllers/textblock.php');
    controller_textblock_history($page);
}
//  - move textblock
else if ($action == 'move') {
    require_once(IA_ROOT.'www/controllers/textblock_move.php');
    controller_textblock_move($page);
}
//  - restore textblock
else if ($action == 'restore') {
    require_once(IA_ROOT.'www/controllers/textblock.php');
    controller_textblock_restore($page, request('revision'));
}
//  - view textblock differences between revisions
else if ($action == 'diff') {
    require_once(IA_ROOT.'www/controllers/textblock.php');
    controller_textblock_diff($page);
}

// attachment controllers
//  - create attachment
else if ($action == 'attach') {
    require_once(IA_ROOT.'www/controllers/attachment.php');
    controller_attachment_create($page);
}
//  - print attachment list
else if ($action == 'attach-list') {
    require_once(IA_ROOT.'www/controllers/attachment.php');
    controller_attachment_list($page);
}
//  - attachment delete
else if ($action == 'attach-del') {
    require_once(IA_ROOT.'www/controllers/attachment.php');
    controller_attachment_delete($page);
}
//  - attachment download
else if ($action == 'download') {
    if (request('resize')) {
        require_once(IA_ROOT.'www/controllers/image_attachment.php');
        // download resized image
        controller_attachment_resized_img($page, request('file'), request('resize'));
    }
    else {
        require_once(IA_ROOT.'www/controllers/attachment.php');
        // regular file download
        controller_attachment_download($page, request('file'));
    }
}

// reset password
else if ('confirm' == $urlstart) {
    // confirm reset password
    require_once(IA_ROOT.'www/controllers/resetpass.php');
    controller_resetpass_confirm($page_id);
}

// user profile, view personal page / statistics / rating evolution
else if (TB_USER_PREFIX==$urlstart.'/' &&
        ('view' == $action || 'rating' == $action || 'stats' == $action )) {
    require_once(IA_ROOT.'www/controllers/user.php');
    controller_user_view($page_id, $action, request('revision'));
}

// general textblock view
else if ($action == 'view') {
    require_once(IA_ROOT.'www/controllers/textblock.php');
    controller_textblock_view($page, request('revision'));
}

// invalid URL
else {
    flash_error('URL invalid');
    redirect(url_home());
}

?>
