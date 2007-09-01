<?php 
include('header.php');
require_once(IA_ROOT_DIR . "www/format/table.php");
require_once(IA_ROOT_DIR . "www/format/format.php");
?>
    
<script type="text/javascript">
    function rename_form(id) {
        if (document.getElementById("rename_"+id).style.display == "none") {
            document.getElementById("rename_"+id).style.display = "inline";
            document.getElementById("link_"+id).style.display = "none";
            document.getElementById("rename_link_"+id).textContent = "Anuleaza";
        } 
        else {
            document.getElementById("rename_"+id).style.display = "none";
            document.getElementById("link_"+id).style.display = "inline";
            document.getElementById("rename_link_"+id).textContent = "Redenumeste";
        }

    }
</script>

<?php
function format_attach_id($row) {
    $id = $row['id'];
    return "#$id";
}

function format_attach_name($row) {
    global $page_name;
    
    $attachurl = '<span id="rename_'.$row['id'].'" style="display: none">';
    $attachurl .= '<form action="'.url_attachment_rename($page_name).'" method="post">';
    $attachurl .= '<input type="hidden" name="old_name" value="'.$row['name'].'"/>';
    $attachurl .= '<input type="text" name="new_name" value="'.$row['name'].'"/>';
    $attachurl .= '<input type="submit" value="OK" class="button"/>';
    $attachurl .= '</form></span>';
    $attachurl .= '<span id="link_'.$row['id'].'">';
    $attachurl .= format_link(url_attachment($page_name, $row['name']), $row['name']);
    $attachurl .= '</span>';
    return $attachurl;
}

function format_attach_size($row) {
    $size = number_format((double) $row['size'] / 1024, 2);
    return "$size kb";

}

function format_operations($row) {
    global $page_name;
    
    $delurl = '<a href="';
    $delurl .= htmlentities(url_attachment_delete($page_name, $row['name']));
    $delurl .= "\" onclick=\"return confirm('Aceasta actiune este ireversibila! Doresti sa continui?')\">Sterge</a>";
    $renurl = '<a href="#" id="rename_link_'.$row['id'].'" onclick="rename_form('.$row['id'].')">Redenumeste</a>';

    return '['.$delurl.'] ['.$renurl.']';
}

$column_infos = array(
    array(
        'title' => 'Numar',
        'key' => 'id',
        'rowform' => 'format_attach_id'
    ),
    array(
        'title' => 'Nume',
        'key' => 'name',
        'rowform' => 'format_attach_name'
    ),
    array(
        'title' => 'Utilizator',
        'key' => 'username',
        'rowform' => create_function_cached('$row',
                'return format_user_tiny($row["username"], $row["user_fullname"]);'),
    ),
    array(
        'title' => 'Marime',
        'key' => 'size',
        'rowform' => 'format_attach_size'
    ),
    array(
        'title' => 'Data',
        'key' => 'timestamp',
        'valform' => 'format_date',
    ),
    array(
        'title' => 'Operatii',
        'rowform' => 'format_operations',
    ),
);
?>

    <h1>Atasamente pentru pagina <?= format_link(url_textblock($view['page_name']), $view['page_name']) ?></h1>
    <?php
        $options = array(
            'display_entries' => $view['display_entries'],
            'total_entries' => $view['total_entries'],
            'first_entry' => $view['first_entry'],
            'pager_style' => 'standard',
            'show_count' => true,
            'show_display_entries' => true,
        );

        echo format_table($view['attach_list'], $column_infos, $options);
    ?>

    <p><?= format_link(url_attachment_new($page_name), 'Ataseaza un alt fisier') ?></p>

<?php include('footer.php'); ?>
