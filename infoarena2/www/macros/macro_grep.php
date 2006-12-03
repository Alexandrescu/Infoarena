<?php

require_once(IA_ROOT."common/db/textblock.php");

// FIXME: document this macro
function macro_grep($args) {
    $substr = getattr($args, 'substr');
    $page = getattr($args, 'page');

    if (!$substr) {
        return macro_error('Expecting parameter `substr`');
    }
    if (!$page) {
        return macro_error('Expecting parameter `page`');
    }

    if (!identity_can('macro-grep')) {
        return macro_permission_error();
    }

    $textblocks = textblock_grep($substr, $page);

    ob_start();
?>
<div class="macroToc">
<p><strong><?= count($textblocks) ?></strong> rezultate.</p>
<ul>
<?php foreach ($textblocks as $textblock) { ?>
    <li><a href="<?= htmlentities(url($textblock['name'])) ?>"><?= htmlentities($textblock['title']) ?></a></li>
<?php } ?>
</ul>
</div>
<?php
    $buffer = ob_get_contents();
    ob_end_clean();

    return $buffer;
}

?>
