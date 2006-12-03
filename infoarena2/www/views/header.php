<?php

require_once(IA_ROOT.'www/views/sitewide.php');
require_once(IA_ROOT.'www/views/utilities.php');

check_view($view);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2" />
    <title><?= htmlentities(getattr($view, 'title')) ?></title>

    <link type="text/css" rel="stylesheet" href="<?= htmlentities(url('static/css/sitewide.css')) ?>"/>
    <link type="text/css" rel="stylesheet" href="<?= htmlentities(url('static/css/screen.css')) ?>"/>
    <link type="text/css" rel="stylesheet" href="<?= htmlentities(url('static/css/tabber.css')) ?>"/>
    <link type="text/css" rel="stylesheet" href="<?= htmlentities(url('static/css/SyntaxHighlighter.css')) ?>"/>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/config.js.php')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/MochiKit.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/default.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/tabber-minimized.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/submit.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/remotebox.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/sh/shCore.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/sh/shBrushCpp.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/sh/shBrushDelphi.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/sh/shBrushJava.js')) ?>"></script>
    <script type="text/javascript" src="<?= htmlentities(url('static/js/sh/shInit.js')) ?>"></script>

    <?= getattr($view, 'head') ?>
</head>
<body<?= getattr($view, 'body_onload') ? ' onload="' . htmlentities(getattr($view, 'body_onload')) . '"' : '' ?>>

<div id="page">

<?php
if (!isset($topnav_select)) {
    $topnav_select = 'infoarena';
}
ia_template_header();
$smf_admin = ('admin' == getattr($identity_user, 'security_level'));
ia_template_topnav($topnav_select, $smf_admin);
?>

<div id="content_small" class="clear">
<div id="sidebar">
    <?php if (!identity_anonymous()) { $username = $identity_user['username']; ?>
    <div id="avatar">
        <?= format_link(url_user_profile($username), format_user_avatar($username, 50, 50), false) ?>
        <p><strong><?= format_link(url_user_profile($username), $username) ?></strong><br/>
        <?= htmlentities($identity_user['full_name']) ?></p>
    </div>
    <?php } ?>

    <ul id="nav" class="clear">
        <li><a href="<?= htmlentities(url('')) ?>">Home</a></li>
        <li><a href="<?= htmlentities(url('arhiva')) ?>">Arhiva de probleme</a></li>
        <li><a href="<?= htmlentities(url('concursuri')) ?>">Concursuri online</a></li>
        <li><a href="<?= htmlentities(url('articole')) ?>">Articole</a></li>
        <li><a href="<?= htmlentities(url('documentatie')) ?>">Documentatie</a></li>
        <li><a href="<?= htmlentities(url('downloads')) ?>">Downloads</a></li>
        <li><a href="<?= htmlentities(url('links')) ?>">Links</a></li>
        <li><a href="<?= htmlentities(url('stiri')) ?>">Arhiva de stiri</a></li>
        <li><a href="<?= htmlentities(url('despre-infoarena')) ?>">Despre infoarena</a></li>
        <li class="separator"><hr/></li>
        <li><a href="<?= htmlentities(url('monitor')) ?>">Monitorul de evaluare</a></li>
        <?php if (!identity_anonymous()) { ?>
        <li><a href="<?= htmlentities(url('submit')) ?>"><strong>Trimite solutii</strong></a></li>
        <li><a href="<?= htmlentities(url('account')) ?>">Contul meu</a></li>
        <?php } ?>
    </ul>

    <?php if (identity_anonymous()) { ?>
    <div id="login">
        <?php if (!isset($no_sidebar_login)) include(IA_ROOT.'www/views/form_login.php') ?>
        <p>
        <a href="<?= htmlentities(url('register')) ?>">Ma inregistrez!</a><br/>
        <a href="<?= htmlentities(url("resetpass")) ?>">Mi-am uitat parola&hellip;</a>
        </p>
    </div>
    <?php } ?>
</div>

<div id="main">
<?php

// breadcrumbs with recent pages
if (isset($recent_pages) && (1 < count($recent_pages))) {
    $bstring = '';
    foreach ($recent_pages as $rec_key => $rec_entry) {
        list($rec_url, $rec_title) = $rec_entry;

        $rec_title = htmlentities($rec_title);

        if ($bstring) {
            $bstring .= ' <span class="separator">|</span> ';
        }
        if ($current_url_key == $rec_key) {
            $bstring .= "<strong>{$rec_title}</strong>";
        }
        else {
            $bstring .= "<a href=\"" . htmlentities($rec_url) . "\">{$rec_title}</a>";
        }
    }
    echo '<p id="breadcrumbs">Pagini recente &raquo; ' . $bstring . '</p>';
}

?>

<?php
    // display flash message
    if (isset($_SESSION['_ia_flash'])) { ?>

<div id="flash" class="flash <?= htmlentities(getattr($_SESSION, '_ia_flash_class')) ?>"><?= htmlentities($_SESSION['_ia_flash']) ?></div>

<?php
        // clear flash message 
        unset($_SESSION['_ia_flash']);
        if (isset($_SESSION['_ia_flash_class'])) {
            unset($_SESSION['_ia_flash_class']);
        }
    }
?>
