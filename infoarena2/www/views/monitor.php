<?php include('header.php'); ?>

<h1><?= htmlentities($view['title']) ?></h1>
<h3>Elementele de la pagina <?=$page?></h3>
<table class='monitor'>
    <thead>
    <tr>
<?php
        foreach ($jobs[0] as $key => $val) {
            if ($key == 'round_id') continue; // we only use round_id for links
            echo '<th>';
            echo $key;
            echo '</th>';
        }
?>
    </tr>
    </thead>
    <tbody>
<?php
    $con = 1;
    foreach ($jobs as $line) {
        // style each line based on parity
        if ($con++ % 2 == 0) {
            echo "<tr class='even'>\n";
        }
        else {
            echo "<tr class='odd'>\n";
        }
        foreach ($line as $key => $val) {
            if ($key == 'round_id') continue; // we only use round_id for links
            echo "<td>";
            if ($key == 'username') {   // create link to user page
                echo '<a href="' . url("user/".$val) . '">' . $val . '</a>';
            }
            elseif ($key == 'task_id') {    // create link to task page
                echo '<a href="' . url("task/".$val) . '">' . $val . '</a>';
            }
            elseif ($key == 'title') {  // create link to round page
                echo '<a href="' . url("round/".$line['round_id']) . '">' . $val . '</a>';
            }
            else {
                echo $val;
            }
            echo "</td>";
        }
        echo "</tr>\n";
    }
?>
    </tbody>
</table>

<div class='paginator'>
    <div class='prev_next'>
<?php   if ($page > 1) { ?>
            <a href="<?= url("monitor", array('page_num' => $page-1)) ?>">Inapoi</a>
<?php   }
        if ($page < $page_max) { ?>
            <a href="<?= url("monitor", array('page_num' => $page+1 )) ?>">Inainte</a>
<?php   } ?>
    </div>
    <div class='jump'>
<?php
        // calculate exponential page numbers decreasing from current page to 1
        for ($i=8, $ac=0; $page-$i>0; $i*=2) {
            $pn = $page-$i;
            $a[$ac++] = '<a href="' .
                            url("monitor", array('page_num' => ($page-$i))) .
                        '">' . ($page-$i) . '</a> ';
        }
        // show link to first page if not already showed
        if (1 < $page-3 && 1 != ($page-$i/2)) {
            echo '<a href="'.
                    url("monitor", array('page_num' => 1)) .
                 '">' . 1 . '</a> ';
        }
        // show links to exponential page numbers increasing
        for ($i=$ac-1; $i>=0; --$i) {
            echo $a[$i];
        }
        // show << 
        if (1 < $page-3) {
            echo "\n".'<span class="separator_left"> &laquo; </span>'."\n";
        }
        unset($a); unset($ac); // remove unused variables
        // show links to prev 3 pages, <strong> current page and next 3 pages
        for ($i=max(1, $page-3); $i<=min($page_max, $page+3); ++$i) {
            if ($i==$page) {
                echo "<strong>".$i."</strong> ";
            }
            else {
                echo '<a href="' .
                        url("monitor", array('page_num' => $i)) .
                     '">' . $i . '</a> ';
            }
        }
        // show >>
        if ($page_max > $page+3) {
            echo '<span class="separator_right"> &raquo; </span>'."\n";
        }
        // show links to exponential page numbers increasing from current page
        for ($i=8; $page+$i<=$page_max; $i*=2) {
            echo '<a href="'.
                    url("monitor", array('page_num' => ($page+$i))) .
                 '">' . ($page+$i) . '</a> ';
        }
        // show link to last page if not already showed
        if ($page_max > $page+3 && $page_max != ($page+$i/2)) {
            echo '<a href="'.
                    url("monitor", array('page_num' => $page_max)) .
                 '">' . $page_max . '</a> ';
        }
?>
    </div>
</div>
<?php include('footer.php'); ?>