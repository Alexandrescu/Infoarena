<?php

//
// Wiki page displayer.
//

include('header.php');

?>
    <ul id="wikiOps">
        <li><a href="<?= url($wikipage['name'], array('action' => 'edit')) ?>">Editeaza</a></li>
        <li><a href="<?= url($wikipage['name'], array('action' => 'history')) ?>">Vezi istoria</a></li>
        <li><a href="<?= url($wikipage['name'], array('action' => 'attach')) ?>">Ataseaza</a></li>
        <li><a href="<?= url($wikipage['name'], array('action' => 'attach-list')) ?>">Listeaza atasamente</a></li>
        <li><a href="<?= url($wikipage['name'], array('action' => 'delete')) ?>">Sterge</a></li>
    </ul>
<?php
echo '<h1>'.htmlentities($wikipage['title']).'</h1>';
echo '<div class="wiki_text_block">';
echo wiki_process_text($wikipage['text'], $wikipage['name']);
echo '</div>';
#echo 'Modificat ultima data la ' . $view[''];

include('footer.php');

?>
