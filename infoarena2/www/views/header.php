<?php check_view($view); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2" />
    <title><?= htmlentities(getattr($view, 'title')) ?></title>

    <link type="text/css" rel="stylesheet" href="<?= url('static/css/screen.css') ?>"/>
    <link type="text/css" rel="stylesheet" href="<?= url('static/css/tabber.css') ?>"/>
    <script type="text/javascript" src="<?= url('static/js/config.js.php') ?>"></script>
    <script type="text/javascript" src="<?= url('static/js/MochiKit.js') ?>"></script>
    <script type="text/javascript" src="<?= url('static/js/default.js') ?>"></script>
    <script type="text/javascript" src="<?= url('static/js/tabber-minimized.js') ?>"></script>
    <script type="text/javascript" src="<?= url('static/js/submit.js') ?>"></script>
    <script type="text/javascript" src="<?= url('static/js/remotebox.js') ?>"></script>

    <?= getattr($view, 'head') ?>
</head>
<body<?= getattr($view, 'body_onload') ? ' onload="' . htmlentities(getattr($view, 'body_onload')) . '"' : '' ?>>

<div id="page">

<div id="header" class="clear">
    <form id="search">
        <input type="text" id="inputbox" />
        <input type="submit" value="Cauta &raquo;"/>
    </form>
    <h1><a title="informatica de performanta" href="<?= url('') ?>">info-arena, informatica de performanta</a></h1>
</div>

<div id="content_small" class="clear">
<div id="sidebar">
    <?php if (!identity_anonymous()) { ?>
    <div id="avatar">
        <a href="<?= url("profile") ?>"><img src="<?= url('user/'.$identity_user['username'], array('action' => 'download', 'file' => 'avatar', 'resize'=>'@50x50')) ?>" /></a>
        <p><strong><?= $identity_user['username'] ?></strong></p>
    </div>
    <?php } ?>

    <ul id="nav" class="clear">
        <li><a href="<?= url('Home') ?>">Prima pagina</a></li>
        <li><a href="<?= url('News') ?>">Arhiva stiri</a></li>
        <li><a href="<?= url('round/arhiva') ?>">Arhiva de probleme</a></li>
        <li><a href="<?= url('Contests') ?>">Concursuri</a></li>
        <li><a href="<?= url('Articles') ?>">Articole</a></li>
        <li><a href="<?= url('Links') ?>">Link-uri</a></li>
        <li><a href="<?= url('Downloads') ?>">Download-uri</a></li>
        <li><a href="<?= url('Forum') ?>">Forum</a></li>
        <?php if (identity_can('user-editprofile', $identity_user)) { ?>
        <li class="separator"><hr/></li>
        <li><a href="<?= url('profile') ?>">Profilul meu</a></li>
        <li><a href="<?= url('logout') ?>">Inchide sesiunea</a></li>
        <? } ?>
        <li class="separator"><hr/></li>
        <?php if (!identity_anonymous()) { ?>
        <li><a href="<?= url('submit') ?>">Trimite solutii</a></li>
        <?php } ?>
        <li><a href="<?= url('monitor') ?>">Monitorul de evaluare</a></li>
    </ul>

    <?php if (identity_anonymous()) { ?>
    <div id="login">
        <h2>Autentificare</h2>
        <form action="<?= url('login', array('action' => 'login')) ?>" method="post">
            <label for="form_username">Utilizator</label>
            <input type="text" name="username" id="form_username" value="" />
            <label for="form_password">Parola</label>
            <input type="password" name="password" id="form_password" value="" />
            <input type="submit" value="Autentificare" id="form_submit" class="button important" />
        </form>
        <ul>
            <li><a href="<?= url("reset_pass") ?>">Am uitat parola</a></li>
            <li><a href="<?= url('register') ?>">Inregistreaza-te!</a></li>
        </ul>
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
        if ($bstring) {
            $bstring .= ' <span class="separator">|</span> ';
        }
        if ($current_url_key == $rec_key) {
            $bstring .= "<strong>{$rec_title}</strong>";
        }
        else {
            $bstring .= "<a href=\"" . $rec_url . "\">{$rec_title}</a>";
        }
    }
    echo '<p id="breadcrumbs">Pagini recente &raquo; ' . $bstring . '</p>';
}

?>

<?php
    // display flash message
    if (isset($_SESSION['_flash'])) { ?>

<div id="flash" class="flash <?= getattr($_SESSION, '_flash_class') ?>"><?= $_SESSION['_flash'] ?></div>

<?php
        // clear flash message 
        unset($_SESSION['_flash']);
        if (isset($_SESSION['_flash_class'])) {
            unset($_SESSION['_flash_class']);
        }
    }
?>
