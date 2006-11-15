<?php

// View a plain textblock.
// That textblock can be owned by something else.
function controller_textblock_view($page_name, $rev_num = null) {
    // Get actual page.
    $crpage = textblock_get_revision($page_name);

    // If the page is missing jump to the edit/create controller.
    if ($crpage) {
        if ($rev_num) {
            identity_require("wiki-history", $crpage);
            $page = textblock_get_revision($page_name, $rev_num);
        } else {
            identity_require("wiki-view", $crpage);
            $page = $crpage;
        }
    } else {
        // Missing page.
        // FIXME: what if the user can't create the page?
        flash_error("Nu am gasit pagina, te trimit sa editezi");
        redirect(url($page_name, array('action' => 'edit')));
    }

    // Build view.
    $view = array();
    $view['title'] = $page['title'];
    $view['revision'] = $rev_num;
    $view['page_name'] = $page_name;
    $view['textblock'] = $page;
    execute_view_die('views/textblock_view.php', $view);
}

// Show a textblock diff.
// FIXME: two revisions.
function controller_textblock_diff_revision($page_name, $rev_num) {
    global $identity_user;
    $page = textblock_get_revision($page_name);
    $rev = textblock_get_revision($page_name, $rev_num);
    if ($page) {
        identity_require('wiki-history', $page);
    } else {
        flash_error("Pagina nu exista");
        redirect(url(''));
    }

    if (is_null($rev_num)) {
        flash_error("Nu ati specificat revizia");
        redirect(url($page_name));
    }
    if (!$rev) {
        flash_error("Revizia nu exista!");
        redirect(url($page_name));
    }

    $diff_title = string_diff($rev['title'], $page['title']);
    $diff_content = string_diff($rev['text'], $page['text']);

    $view = array();
    $view['page_name'] = $page_name;
    $view['title'] = 'Diferente '.$page_name;
    $view['diff_title'] = explode("\n", $diff_title);
    $view['diff_content'] = explode("\n", $diff_content);
    execute_view_die('views/textblock_diff.php', $view);
}

// Restore a certain revision
function controller_textblock_restore_revision($page_name, $rev_num) {
    global $identity_user;
    $page = textblock_get_revision($page_name);
    $rev = textblock_get_revision($page_name, $rev_num);

    if ($page) {
        identity_require('wiki-restore', $page);
    } else {
        flash_error("Pagina nu exista");
        redirect(url(''));
    }

    if (is_null($rev_num)) {
        flash_error("Nu ati specificat revizia");
        redirect(url($page_name));
    }
    if (!$rev) {
        flash_error("Revizia nu exista!");
        redirect(url($page_name));
    }
    
    textblock_add_revision($rev['name'], $rev['title'], $rev['text'],
                           getattr($identity_user, 'id'));
    redirect(url($page_name));
}

// Display revisions
function controller_textblock_history($page_name) {
    $page = textblock_get_revision($page_name, null, false, false);
    if ($page) {
        identity_require('wiki-history', $page);
    } else {
        flash_error("Pagina nu exista");
        redirect(url(''));
    }

    $view = array();
    $view['page_name'] = $page_name;
    $view['title'] = 'Istoria paginii '.$page_name;
    //$view['count'] = textblock_get_revision_count($page_name);
    $view['page_list'] = textblock_get_revisions($page_name);
    $view['count'] = count($view['page_list']);
    $view['current'] = $page;
    $view['feed_link'] = url($view['page_name'], array('action' => 'feed'));
    execute_view_die('views/textblock_history.php', $view);
}

// give a RSS feed with the history of a textblock
function controller_textblock_feed($page_name) {
    $page = textblock_get_revision($page_name);
    if (!$page) {
        flash_error("Pagina nu exista");
        redirect(url(''));
    }
    identity_require('wiki-history', $page);

    $page_list = textblock_get_revisions($page_name, true, true);
    $count = count($page_list);

    $view = array();
    $view['channel']['title'] = 'info-arena: '.$page['title'];
    $view['channel']['link'] = url($page_name, array(), true);
    $view['channel']['description'] = $view['channel']['title'];
    $view['channel']['description'] .= ' ('.$count.' revizii)';
    $view['channel']['language'] = 'ro-ro';
    $view['channel']['copyright'] = '&copy; 2006 - info-arena';

    $i = 0;
    $view['item'][$i]['title'] = 'Revizia curenta: '.
                                 (getattr($page, 'title') ? $page['title'] :
                                  'FARA TITLU');
    $view['item'][$i]['description'] = wiki_process_text($page['text']);
    $view['item'][$i]['pubDate'] = date('r', strtotime($page['timestamp']));
    $view['item'][$i]['guid'] = sha1($page['name'].$page['timestamp']);
    $view['item'][$i]['link'] = url($page['name'], array(), true).
                               '#'.$view['item'][$i]['guid'];
    $view['item'][$i]['author'] = $page['username'];

    $i = 1; 
    for($rev_num = $count-1; $rev_num >= 0; $rev_num--, $i++) {
        $v = $page_list[$rev_num];
        $view['item'][$i]['title'] = 'Revizia #'.($rev_num+1).': '.
                                     (getattr($v, 'title') ? $v['title'] :
                                     'FARA TITLU');
        $view['item'][$i]['description'] = wiki_process_text($v['text']);
        $view['item'][$i]['pubDate'] = date('r', strtotime($v['timestamp']));
        $view['item'][$i]['guid'] = sha1($v['name'].$v['timestamp']);
        $view['item'][$i]['link'] = url($v['name'],
                                    array('revision' => $rev_num), true).
                                    '#'.$view['item'][$i]['guid'];
        $view['item'][$i]['author'] = $v['username'];
        if ($i == IA_MAX_FEED_ITEMS) {
            break;
        }
    }    

    execute_view_die('views/rss.php', $view);
}

// Edit a textblock
function controller_textblock_edit($page_name) {
    $page = textblock_get_revision($page_name);

    // permission check
    if ($page) {
        identity_require('wiki-edit', $page);
    } else {
        identity_require('wiki-create', $page);
    }

    $view = array();
    $form_errors = array();

    if (!$page) {
        $page_title = $page_name;
        $page_content = "Scrie aici despre " . $page_name;
        $page_security = "public";
        $view['title'] = "Creare " . $page_name;
    } else {
        $page_title = $page['title'];
        $page_content = $page['text'];
        $page_security = $page['security'];
        $view['title'] = "Editare " . $page_name;
    }

    // This is the creation action.
    $view['page_name'] = $page_name;
    $view['action'] = url($page_name, array('action' => 'save'));
    $view['form_values'] = array('content'=> $page_content,
                                 'title' => $page_title,
                                 'security' => $page_security);
    $view['form_errors'] = $form_errors;
    execute_view_die("views/textblock_edit.php", $view);
}

// Save changes controller
function controller_textblock_save($page_name) {
    $page = textblock_get_revision($page_name);
    global $identity_user;

    // permission check
    if ($page) {
        identity_require('wiki-edit', $page);
    } else {
        identity_require('wiki-create', $page);
    }

    // Validate data here and place stuff in errors.
    $form_errors = array();
    $view = array();

    $page_content = getattr($_POST, 'content', "");
    $page_title = getattr($_POST, 'title', "");
    $page_security = getattr($_POST, 'security', "");
    if (strlen($page_content) < 1) {
        $form_errors['content'] = "Continutul paginii este prea scurt.";
    }
    if (strlen($page_title) < 1) {
        $form_errors['title'] = "Titlul este prea scurt.";
    }
    // FIXME: proper check for security changes.
    if (!$form_errors) {
        textblock_add_revision($page_name, $page_title, $page_content, 
                               getattr($identity_user, 'id'),
                               $page_security);
        flash('Am actualizat continutul');
        redirect(url($page_name));
    }
    else {
        $view['title'] = "Editare " . $page_name;
        $view['page_name'] = $page_name;
        $view['page_class'] = 
        $view['action'] = url($page_name, array('action' => 'save'));
        $form_values['content'] = $page_content;
        $view['form_values'] = array('content'=> $page_content,
                                     'title' => $page_title,
                                     'security' => $page_security);
        $view['form_errors'] = $form_errors;
        execute_view_die("views/textblock_edit.php", $view);
    }
}

// Delete a certain textblock.
function controller_textblock_delete($page_name)
{
    // Get actual page.
    $page = textblock_get_revision($page_name);

    // If the page is missing jump to the edit/create controller.
    if ($page) {
        identity_require('wiki-delete', $page);
    } else {
        // Missing page.
        flash_error("Pagina inexistenta.");
    }
    textblock_delete($page_name);
    flash("Pagina a fost stearsa.");
    redirect(url('home'));
}

?>
