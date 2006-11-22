<?php include('header.php'); ?>

    <h1>Ultimele modificari de pe infoarena</h1>

<a class="feed" href="<?= url('changes', array('format' => 'rss')) ?>" title="RSS Modificari" >RSS</a>

    <ul class="changes">
    <?php foreach ($revisions as $rev) { ?>
    <li>

<?php
$userlink = format_user_tiny($rev['user_name'], $rev['user_fullname']);
$pagelink = format_link($rev['title'], url($rev['name'], array(), true));
$diffurl_params = array(
        'action' => 'diff',
        'rev_from' => $rev['revision_id'],
        'rev_to' => $rev['revision_id'] - 1,
);
$difflink = format_link("modificari", url($rev['name'], $diffurl_params, true));
$tstamp = $rev['timestamp'];
echo "$pagelink, modificata de $userlink la $tstamp($difflink).";
?>
    </li>
    <?php } ?>
    </ul>

<?php include('footer.php'); ?>
