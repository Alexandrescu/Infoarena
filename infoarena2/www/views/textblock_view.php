<?php

require_once(IA_ROOT.'www/wiki/wiki.php');

// site header
include('header.php');

// wiki page header (actions)
include('textblock_header.php');

// revision warning
if (getattr($view, 'revision')) {
    include('revision_warning.php');
}

// textblock content
echo '<div class="wiki_text_block">';
echo wiki_process_text(getattr($textblock, 'text'));
echo '</div>';

// site footer
include('footer.php');

?>
