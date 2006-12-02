<?php

function request($param, $default = null) {
    return getattr($_REQUEST, $param, $default);
}

// Returns boolean whether current request method is POST
function request_is_post() {
    $post = ('post' == strtolower(getattr($_SERVER, 'REQUEST_METHOD')));
    return $post;
}

// Call this function for a http-level redirect.
// NOTE: this function DOES NOT RETURN.
// 
// NOTE: this must be called before any other output.
// If output started before issuing a redirect means you're either
// printing stuff too early or you're trying to redirect too late (view?).
// Either way, it is a bug and it must be solved rather than handled gracefully
//
// FIXME: bool to se ia_redirect to REQUEST_URI? might be usefull.
function redirect($absolute_url) {
    log_print("HTTP Redirect to $absolute_url from {$_SERVER['QUERY_STRING']}");
    header("Location: {$absolute_url}\n\n");
    session_write_close();
    die();
}

// Die with a http error.
function die_http_error($code = 404, $msg = "File not found") {
    log_print("HTTP ERROR $code $msg");
    header("HTTP/1.0 $code");
    echo '<h1>'.$msg.'</h1>';
    echo '<p><a href="'.IA_URL.'">Inapoi la prima pagina</a></p>';
    die();
}

// Compute url.
// The params array contains http get parameter,
// it's formatted in the end result as a series
// of key1=value1&key2=value2.
//
// NOTE: Only use this function for urls.
// NOTE: don't add ?x=y stuff in document.
//
// If $absolute is true(default false) then the server will be
// included in the url.
function url($document = '', $args = array(), $absolute = false) {
    log_assert(false === strpos($document, '?'), 'Page name contains ?');
    log_assert(is_array($args), "Argument list must be an array");
    log_assert(!array_key_exists("page", $args), "Argument list contains page");

    $args['page'] = $document;
    return url_from_args($args, $absolute);
}

// Construct an URL from an argument list.
// These are the exact $args you will receive in $_GET
function url_from_args($args, $absolute = false)
{
    // First part.
    if ($absolute) {
        $url = IA_URL;
    } else {
        $url = IA_URL_PREFIX;
    }
    $url .= getattr($args, "page", "home");

    // Actual args.
    $first = true;
    foreach ($args as $k => $v) {
        if ($k != 'page') {
            $url .= ($first ? "?" : "&");
            $first = false;
            $url .= $k . '=' . urlencode($v);
        }
    }

    return $url;
}

// Get an url for an attachement
function url_attachment($page, $file, $absolute = false) {
    log_assert(is_page_name($page));
    log_assert(is_attachment_name($file));
    return url($page, array('action' => 'download', 'file' => $file), $absolute);
}

// Get an url for a resized image.
function url_image_resize($page, $file, $resize, $absolute = false)
{
    if ($resize) {
        return url($page, array(
                'action' => 'download',
                'file' => $file,
                'resize' => $resize,
        ), $absolute);
    } else {
        return url_attachment($page, $file, $absolute);
    }
}

// Url to the login page
function url_login($absolute = false) {
    return url("login", array(), $absolute);
}

// Url to the submit page
function url_submit($absolute = false) {
    return url("submit", array(), $absolute);
}

function url_textblock($page_name, $absolute = false) {
    return url($page_name, array(), $absolute);
}

function url_textblock_edit($page_name, $absolute = false) {
    return url($page_name, array('action' => 'edit'), $absolute);
}

function url_textblock_history($page_name, $absolute = false) {
    return url($page_name, array('action' => 'history'), $absolute);
}

function url_textblock_diff($page_name, $revfrom, $revto, $absolute = false) {
    $args = array(
            'action' => 'diff',
            'rev_from' => $revfrom,
            'rev_to' => $revto
    );
    return url($page_name, $args, $absolute);
}

function url_textblock_revision($page_name, $rev, $absolute = false) {
    return url($page_name, array('revision' => $rev), $absolute);
}

function url_textblock_restore($page_name, $rev, $absolute = false) {
    $args = array(
            'action' => 'restore',
            'revision' => $rev,
    );
    return url($page_name, $args, $absolute);
}

// Url to user profile page
function url_user_info($username, $absolute = false) {
    return url('userinfo/' . $username, array(), $absolute);
}

// Url to user profile page
function url_user_profile($username, $absolute = false) {
    return url(TB_USER_PREFIX . $username, array(), $absolute);
}

function url_user_avatar($username, $resize = "50x50", $absolute = false) {
    return url_image_resize(TB_USER_PREFIX . $username, 'avatar', $resize, $absolute);
}

// Url to job detail page
function url_job_detail($job_id, $absolute = false) {
    return url("job_detail", array('id' => $job_id), $absolute);
}

// Use flash() to display a message right after redirecting the user.
// Message is displayed only once.
function flash($message, $style_class = null) {
    global $_SESSION;
    $_SESSION['_ia_flash'] = $message;
    $_SESSION['_ia_flash_class'] = $style_class;
}

// This is a simple binding for flash() with a fixed CSS style class
// for displaying error messages
function flash_error($message) {
    flash($message, 'flashError');
}

// Execute a view. Variables in $view are placed in the
// local namespace as variables. This is the preffered
// way of calling a template, because globals are not
// easily accessible.
function execute_view($view_file_name, $view) {
    global $identity_user;

    // retrieve recent page history
    // some pages display it as navigation breadcrumbs
    $recent_pages = getattr($_SESSION, '_ia_recent_pages', array());

    // update recent page history
    $query = url_from_args($_GET);
    if (!preg_match('/\/json\//', $query) && !request_is_post()) {
        $hashkey = strtolower($query);
        $recent_pages[$hashkey] = array($query, getattr($view, 'title', $query)); 
        if (5 < count($recent_pages)) {
            array_shift($recent_pages);
        }
        $_SESSION['_ia_recent_pages'] = $recent_pages;
    }

    // let view access recent_pages
    $view['current_url_key'] = strtolower($query);
    $view['recent_pages'] = $recent_pages;

    // expand $view members into global scope
    $GLOBALS['view'] = $view;

    foreach ($view as $view_hash_key => $view_hash_value) {
        if ($view_hash_key == 'view_hash_key') continue;
        if ($view_hash_key == 'view_hash_value') continue;
        if ($view_hash_key == 'view_file_name') continue;
        if ($view_hash_key == 'view') continue;
        //echo "added $view_hash_key = $view_hash_value into globals";
        $GLOBALS[$view_hash_key] = $view_hash_value;
        $$view_hash_key = $view_hash_value;
    }

    // NOTE: no includes here, unless you want to get
    // warnings about function redeclaration.
    include($view_file_name);
    //include('views/vardump.php');
}

// Execute view and then die.
function execute_view_die($view_file_name, $view) {
    execute_view($view_file_name, $view);
    session_write_close();
    die();
}

// smart ass diff
function string_diff($string1, $string2) {
    $name1 = tempnam(IA_ATTACH_DIR, "ia");
    $name2 = tempnam(IA_ATTACH_DIR, "ia");
    $fp1 = fopen($name1, "w");
    if (!$fp1) {
        flash_error("Eroare la comparare!");
        request(url(''));
    }
    $string1 .= "\n";
    fputs($fp1, $string1);
    fclose($fp1);

    $fp2 = fopen($name2, "w");
    if (!$fp2) {
        flash_error("Eroare la comparare!");
        request(url(''));
    }
    $string2 .= "\n";
    fputs($fp2, $string2);
    fclose($fp2);

    ob_start();
    system("diff -au ".$name1." ".$name2);
    $ret = ob_get_contents();
    ob_end_clean();
    if (!unlink($name1)) {
        flash_error("Eroare la comparare!");
        request(url(''));
    }
    if (!unlink($name2)) {
        flash_error("Eroare la comparare!");
        request(url(''));
    }
    return $ret;
}

// send mail function, does it need a description?
function send_email($to, $subject, $message,
                    $from = IA_MAIL_SENDER_NO_REPLY, $reply = 0)
{
    // if we don't specify reply-to, should be the same as the from
    if ($reply === 0) {
        $reply = $from;
    }

    // put [infoarena] tag in mail subject
    $subject = '[infoarena] '.$subject;

    // word-wrap message, some mail-clients are stupid
    $message = wordwrap($message, 70);

    // headers
    $headers = 'From: ' . $from . "\r\n" .
               'Reply-To: ' . $reply . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    // log
    log_print("Sending mail to: {$to}, subject: {$subject}, message length: "
              .strlen($message));

    // send e-mail
    mail($to, $subject, $message, $headers);
}

// Resize 2D coordinates according to 'textual' instructions
// Given a (width, height) pair, resize it (compute new pair) according to
// resize instructions.
//
// Resize instructions are in WxH format, where W and/or H can be a
// percentage (with %). By default it keeps the original aspect ratio,
// prefix with @ to avoid this.
//
// Alternatively you can just use X% to resize both dimensions.
//
// Returns 2-element array: (width, height) or null if invalid format
// FIXME: Does not belong here.
function resize_coordinates($width, $height, $resize) {
    // log_print("Parsing resize '$resize'");
    // Both with and height.
    if (preg_match('/^(\@?)([0-9]+\%?)x([0-9]+\%?)$/', $resize, $matches)) {
        $flags = $matches[1];
        $targetw = (float)$matches[2];
        $targeth = (float)$matches[3];

        if (preg_match("/\%/", $targetw)) {
            $targetw = $width * preg_match("/[0-9]+/", $targetw) / 100.0;
        }
        if (preg_match("/\%/", $targeth)) {
            $targeth = $height * preg_match("/[0-9]+/", $targeth) / 100.0;
        }

        // log_print("$targetw x $targeth with flags $flags");

        if ($flags != '@') {
            $targetw = min($targeth * $width / $height, $width);
            $targeth = min($targetw * $height / $width, $height);
            $targetw = min($targeth * $width / $height, $width);
        }
    } else if (preg_match('/^([0-9]+)\%$/', $resize, $matches)) {
        //log_print("Scaling at ".$matches[1]."%.");
        $targetw = $width * $matches[1] / 100.0;
        $targeth = $height * $matches[1] / 100.0;
    } else {
        return null;
    }

    return array(floor($targetw), floor($targeth));
}

?>
