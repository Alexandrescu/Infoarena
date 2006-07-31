<?php

function request($paramName, $defaultValue = null) {
    return getattr($_REQUEST, $paramName, $defaultValue);
}

// Nicer way to get an element from an array. It returns a default value
// (defaulting to null) instead of throwing an error.
function getattr($dict, $attribute, $defaultValue = null) {
    if (isset($dict[$attribute])) {
        return $dict[$attribute];
    }
    else {
        return $defaultValue;
    }
}

// Call this function for a http-level redirect.
// NOTE: this function DOES NOT RETURN.
// FIXME: Detect if output started and still do a redirect?
// FIXME: Is that even remotely possible?
// FIXME: Would be usefull for debugging though.
function redirect($absoluteUrl) {
    header("Location: {$absoluteUrl}\n\n");
    session_write_close();
    die();
}

// Get an url.
// The params array contains http get parameter,
// it's formatted in the end result as a series
// of key1=value1&key2=value2.
//
// NOTE: Only use this function for urls.
// NOTE: don't add ?x=y stuff in document.
function url($document, $params = array(), $absolute = false) {
    assert(false === strpos($document, '?'));

    $pairs = array();
    foreach ($params as $k => $v) {
        $pairs[] = $k . '=' . urlencode($v);
    }

    if ($absolute) {
        $prefix = IA_URL;
    }
    else {
        $prefix = IA_URL_PREFIX;
    }

    if (0 < count($pairs)) {
        return $prefix . $document . '?' . join('&amp;', $pairs);
    }
    else {
        return $prefix . $document;
    }
}


// Use flash() to display a message right after redirecting the user.
// Message is displayed only once.
function flash($message, $styleClass = null) {
    global $_SESSION;
    $_SESSION['_flash'] = $message;
    $_SESSION['_flash_class'] = $styleClass;
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
/*    foreach ($GLOBALS as $the_key => $the_value) {
        // Don't unset magic shit.
        if ($the_key[0] != '_' && strpos($the_key, 'HTTP_') !== 0) {
            unset($GLOBALS[$the_key]);
            echo "Am sters $the_key";
        }
    }*/
    require_once('views/utilities.php');
    include($view_file_name);
    //include('views/vardump.php');
}

// Execute and the die.
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

    // put [info-arena] tag in mail subject
    $subject = '[info-arena] ' . $subject;

    $headers = 'From: ' . $from . "\r\n" .
               'Reply-To: ' . $reply . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
    echo $to . '<br>' . $subject . '<br>' . $message; // debug info
}
?>