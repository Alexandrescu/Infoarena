<?php 

require_once(IA_ROOT_DIR."common/textblock.php");
require_once(IA_ROOT_DIR."common/db/textblock.php");
require_once(IA_ROOT_DIR."common/db/user.php");

// View user profile (personal page, rating evolution, statistics)
// $action is one of (view | rating | stats)
function controller_user_view($username, $action, $revision = null) {
    // validate username
    $user = user_get_by_username($username);
    if (!$user) {
        flash_error("Utilizator inexistent");
        redirect(url_home());
    }

    // Build view.
    $page_name = IA_USER_TEXTBLOCK_PREFIX.$user['username'];
    $view = array(
        'title' => $user['full_name'].' ('.$user['username'].')',
        'page_name' => $page_name,
	    'revision' => $revision,
        'revision_count' => textblock_get_revision_count($page_name),
        'action' => $action,
        'user' => $user,
        'topnav_select' => 'profile',
        'template_userheader' => 'template/userheader',
    );

    switch ($action) {
        case 'view':
            // view personal page
            $textblock = textblock_get_revision($page_name, $revision);
            log_assert($textblock);
            $view['textblock'] = $textblock;
            $view['title'] = $textblock['title'];
            break;

        case 'rating':
            // view rating evolution
            $view['template'] = 'template/userrating';
            $view['title'] = 'Rating '.$view['title'];
            break;

        case 'stats':
            // view user statistics
            $view['template'] = 'template/userstats';
            $view['title'] = 'Statistici '.$view['title'];
            break;

        default:
            log_error('Invalid user profile action: '.$action);
    }

    // View
    execute_view_die('views/user.php', $view);
}

?>
