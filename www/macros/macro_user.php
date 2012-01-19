<?php

require_once(IA_ROOT_DIR . 'www/format/format.php');
require_once(IA_ROOT_DIR . 'common/db/user.php');
require_once(IA_ROOT_DIR . 'www/xhp/ui/user.php');

// Display a link to an user.
// Includes avatar, etc.
//
// Args:
//      user(required): user id.
//      type: link(default), tiny, normal, etc.
function macro_user($args) {
    $user = getattr($args, 'user', '');
    if ($user === '') {
        return macro_error("User parameter required.");
    }

    $dbuser = user_get_by_username($user);
    if (!$dbuser) {
        return macro_error("User inexistent.");
    }

    $type = getattr($args, 'type', 'link');
    if ($type == 'link') {
        return <ui:user:link user={$dbuser} />;
    } else if ($type == 'tiny') {
        return <ui:user:tiny user={$dbuser} />;
    } else if ($type == 'normal') {
        return <ui:user:normal user={$dbuser} />;
    } else {
        return macro_error("Unknown userlink type \"$type\"");
    }
}

?>
