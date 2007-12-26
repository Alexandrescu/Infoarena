<?php

include('header.php');

?>

<h1>Diferente pentru
<?= format_link(url_textblock($page_name), $page_name) ?> intre reviziile
    <?= format_link(url_textblock_revision($page_name, $revfrom_id), "#$revfrom_id") ?> si
    <?= format_link(url_textblock_revision($page_name, $revto_id), "#$revto_id") ?>
</h1>
<?php

function print_diff($diff) {
    foreach ($diff as $block) {
        echo '<div class="diff">';
        foreach ($block as $op) {
            echo '<span class="'.$op['type'].'">';
            foreach ($op['lines'] as $line) {
                echo str_replace('  ', '&nbsp;&nbsp;', htmlentities($line));
                echo '<br/>';
            }
            echo '</span>';
        }
        echo '</div>';
    }
}

if (empty($view['diff_title'])) {
    echo "<h3>Nu exista diferente intre titluri.</h3>";
}
else {
    echo "<h3>Diferente intre titluri:</h3>";
    print_diff($view['diff_title']);
}
?>

<?php
if (empty($view['diff_content'])) {
    echo "<h3>Nu exista diferente intre continut.</h3>";
}
else {
    echo "<h3>Diferente intre continut:</h3>";
    print_diff($view['diff_content']);
}
?>

<?php include('footer.php'); ?>
