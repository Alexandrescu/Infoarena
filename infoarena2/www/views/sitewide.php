<?php

// site-wide templates (templates shared with SMF / search)
//
// This file may be included from different environments
// (currently SMF and info-arena website) so it can use only
// limited application logic.

// display site header
function ia_template_header() {
?>
<div id="header" class="clear">
    <form id="search" action="get">
        <input type="text" id="inputbox" />
        <input type="submit" value="Cauta &raquo;"/>
    </form>
    <h1><a title="informatica de performanta" href="<?= url('', array(), true) ?>">infoarena,
        informatica de performanta</a></h1>
</div>
<?php
}

// display main navigation bar 
function ia_template_topnav($selected = 'infoarena', $smf_admin = false) {
    $pre = array($selected => '<strong>');
    $post = array($selected => '</strong>');
?>
<div id="topnav">
<ul>
    <li><?= getattr($pre, 'infoarena') ?><a href="<?= url('', array(), true) ?>">info<em>arena</em></a><?= getattr($post, 'infoarena') ?></li>
    <li><?= getattr($pre, 'forum') ?><a href="<?= IA_SMF_URL ?>">forum</a><?= getattr($post, 'forum') ?></li>
    <li><?= getattr($pre, 'calendar') ?><a href="<?= IA_SMF_URL ?>?action=calendar">calendar</a><?= getattr($post, 'calendar') ?></li>
<?php if (identity_anonymous()) { ?>
    <li><?= getattr($pre, 'login') ?><a href="<?= url('login', array(), true) ?>">autentificare</a><?= getattr($post, 'login') ?></li>
    <li><?= getattr($pre, 'register') ?><a href="<?= url('register', array(), true) ?>">inregistrare</a><?= getattr($post, 'register') ?></li>
<?php } else { ?>
    <li><?= getattr($pre, 'profile') ?><a href="<?= url('profile', array(), true) ?>">profilul meu</a><?= getattr($post, 'profile') ?></li>
    <li><?= getattr($pre, 'pm') ?><a href="<?= IA_SMF_URL ?>?action=pm">mesaje</a><?= getattr($post, 'pm') ?></li>
<?php if ($smf_admin) { ?>
    <li><?= getattr($pre, 'smf_admin') ?><a href="<?= IA_SMF_URL ?>?action=admin">forum admin</a><?= getattr($post, 'smf_admin') ?></li>
<?php } ?>
    <li><?= getattr($pre, 'logout') ?><a href="<?= url('logout', array(), true) ?>">inchide sesiunea</a><?= getattr($post, 'logout') ?></li>
<?php } ?>
</ul>
</div>
<div class="clear"></div>

<?php
}

function ia_template_footer() {
?>
<div id="footer">
    <ul class="clear">
        <li><a href="<?= url('', array(), true) ?>">Prima pagina</a></li>
        <li><a href="<?= url('Despre', array(), true) ?>">Despre info-arena</a></li>
        <li><a href="<?= url('Termeni', array(), true) ?>">Termeni si conditii</a></li>
        <li><a href="<?= url('Contact', array(), true) ?>">Contact</a></li>
        <li class="top"><a href="#header">Sari la inceputul paginii &uarr;</a></li>
    </ul>
    <p>&copy; 2006 - <a href="<?= url('Asociatia_info-arena', array(), true) ?>">asociatia info-arena</a></p>
</div>
<?php
}

?>
