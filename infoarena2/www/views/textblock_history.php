<?php
include('header.php');
require_once(IA_ROOT . "www/format/table.php");
require_once(IA_ROOT . "www/format/format.php");
?>

    <h1>Istoria paginii <a href="<?= url($view['page_name']) ?>"><?= htmlentities($view['page_name']) ?></a></h1>

<?php
    // Format links to a certain textblock revision.
    function format_textblock_revision($row) {
        global $page_name;
        $rev_id = $row['revision_id'];
        $title = $row['title'];
        $url = url($page_name, array('revision' => $rev_id));
        return '<a href="'.htmlentities($url).'">#'.$rev_id.': '.$title.'</a>';
    }

    function format_operations($row)
    {
        global $page_name, $total_entries;
        $diffurl = url($page_name, array(
                'revision' => $row['revision_id'],
                'action' => 'diff',
        ));
        $restoreurl = url($page_name, array(
                'revision' => $row['revision_id'],
                'action' => 'restore',
        ));
        if ($row['revision_id'] == $total_entries) {
            return '<strong>Ultima versiune</strong>';
        } else {
            return  '[<a href="'.$diffurl.'">Compara</a>]'.
                    '[<a href="'.$restoreurl.'">Incarca</a>]';
        }
    }

    $column_infos = array(
            array(
                    'title' => 'Revizia',
                    'key' => 'title',
                    'rowform' => 'format_textblock_revision'
            ),
            array(
                    'title' => 'Utilizator',
                    'key' => 'username',
                    'rowform' => create_function('$row',
                            'return format_user_tiny($row["user_name"], $row["user_fullname"]);'),
            ),
            array(
                    'title' => 'Data',
                    'key' => 'timestamp',
            ),

            array(
                    'title' => 'Operatii',
                    'rowform' => 'format_operations',
            ),
    );

    $options = array(
            'css_class' => 'textblock-history',
            'display_entries' => $display_entries,
            'total_entries' => $total_entries,
            'first_entry' => $first_entry,
            'pager_style' => 'standard',
            'surround_pages' => 3,
    );

    print format_table($revisions, $column_infos, $options);
 
?>
<?php include('footer.php'); ?>
