<?php
$page_name = join($urlpath, '/');

$action = request('action', 'view');

$page = wikipage_get($page_name);
if (is_null($page)) {
    if ($action == 'view') {
        $action = 'edit';
        $page_title = $page_name;
        $page_content = "Scrie aici despre " . $page_name;
    }
} else {
    $page_title = $page['title'];
    $page_content = $page['text'];
}

// Initialize view.
$view['page_name'] = $page_name;
$view['form_values'] = $form_values = array();
$view['form_errors'] =
$form_errors = array();

switch ($action) {
    case 'save':
        identity_require('wiki-edit', $page);
        // Validate data here and place stuff in errors.
        $page_content = getattr($_POST, 'content', "");
        $page_title = getattr($_POST, 'title', "");
        if (strlen($page_content) < 1) {
            $form_errors['content'] = "Continutul paginii este prea scurt.";
        }
        if (strlen($page_title) < 1) {
            $form_errors['title'] = "Titlul este prea scurt.";
        }
        if (!$form_errors) {
            wikipage_add_revision($page_name, $page_title, $page_content,
                                  getattr($identity_user, 'id'));
            flash('Am actualizat continutul');
            redirect(url($page_name));

            break;
        }
        else {
            $view['title'] = "Editare " . $page_name;
            $view['action'] = url($page_name, array('action' => 'save'));
            $form_values['content'] = $page_content;
            $form_values['title'] = $page_title;
            $view['form_values'] = $form_values;
            $view['form_errors'] = $form_errors;
            execute_view("views/wikiedit.php", $view);
            break;
        }

    case 'edit':
        identity_require('wiki-edit', $page);
        // This is the creation action.
        $view['title'] = "Creare " . $page_name;
        $view['action'] = url($page_name, array('action' => 'save'));
        $form_values['content'] = $page_content;
        $form_values['title'] = $page_title;
        $view['form_values'] = $form_values;
        $view['form_errors'] = $form_errors;
        execute_view("views/wikiedit.php", $view);

        break;

    case 'view':
        identity_require('wiki-view', $page);
        // Viewer. Nicest thing in the world.
        $view['title'] = $page_title;
        $view['content'] = $page_content;
        execute_view_die('views/wikiview.php', $view);

    case 'attach-submit':
        identity_require('wiki-attach', $page);
        // user submitted a file for upload. Process it
        $form_values['file_name'] = basename($_FILES['file_name']['name']);
        $form_values['file_size'] = $_FILES['file_name']['size'];

        // validate data
        if (!preg_match('/^[a-z0-9\.\-_]+$/i', $form_values['file_name'])) {
            $form_errors['file_name'] = 'Nume de fisier invalid (nu folositi '.
                                   'spatii)';
        }                
        if ($form_values['file_size'] < 0 || $form_values['file_size'] >
            IA_ATTACH_MAXSIZE) {
            $form_errors['file_size'] = 'Fisierul depaseste limita de '
                                   .(IA_ATTACH_MAXSIZE / 1024).' kbytes';
        }
        if (!$form_errors) {
            // Do the SQL dance.
            $attach = attachment_get($form_values['file_name'], $page_name);
            if ($attach) {
                identity_require('attach-overwrite', $attach);
                $disk_name = attachment_update($form_values['file_name'],
                                               $form_values['file_size'],
                                               $page_name,
                                               $identity_user['id']);
            }
            else {
                $disk_name = attachment_insert($form_values['file_name'],
                                               $form_values['file_size'],
                                               $page_name,
                                               $identity_user['id']);
            }
                                           
            // Check if something went wrong.
            if (!isset($disk_name)) {
                $form_errors['file_name'] = 'Fisierul nu a putut fi atasat';
            }
        }
        if (!$form_errors) {
            $disk_name = IA_ATTACH_DIR . $disk_name;
            if (!move_uploaded_file($_FILES['file_name']['tmp_name'],
                        $disk_name)) {
                $form_errors['file_name'] = 'Fisierul nu a putut fi incarcat pe '.
                                       'server'; 
            }
        }
        if (!$form_errors) {
            flash("Fisierul a fost atasat");
            redirect(url($page_name));
        }
        $view['form_errors'] = $form_errors;
        $view['form_values'] = $form_values;
        execute_view_die('views/attachment.php', $view);

    case 'attach':
        identity_require('wiki-attach', $page);
        // Initial attachment page. Rather empty.
        $view['form_values'] = array();
        $view['form_errors'] = array();
        execute_view_die('views/attachment.php', $view);

    case 'download':
        $file_name = request('file');
        if (!$file_name) {
            flash_error('Cerere malformata');
            redirect(url($page_name));
        }
        $sql_result = attachment_get($file_name, $page_name);
        identity_require('attach-download', $sql_result);
        if (!$sql_result) {
            flash_error('Fisierul nu exista.');
            redirect(url($page_name));
        }
        $real_name = IA_ATTACH_DIR . $sql_result['id'];
        $fp = fopen($real_name, 'rb');
        if (!$fp) {
            flash_error("Nu am gasit fisierul pe server");
            redirect(url($page_name));
            break;
        }

        header("Content-Type: application/force-download");
        header("Content-disposition: attachment; filename=".$file_name.";");
        header('Content-Length: ',$sql_result['size']);
        fpassthru($fp);
        die();

    case 'delattach':
        $file_name = request('file');
        if (!$file_name) {
            flash_error('Cerere malformata');
            redirect(url($page_name));
        }
        $sql_result = attachment_get($file_name, $page_name);
        identity_require('attach-delete', $sql_result);
        if (!$sql_result) {
            flash_error('Fisierul nu exista.');
            redirect(url($page_name));
        }
        if (!attachment_delete($file_name, $page_name)) {
            flash_error('Nu am reusit sa sterg din baza de date.');
            redirect(url($page_name));
        }
        $real_name = IA_ATTACH_DIR.$sql_result['id'];
        if (!unlink($real_name)) {
            flash_error('Nu am reusit sa sterg fisierul de pe disc.');
            redirect(url($page_name));
        }
        flash('Fisierul '.$file_name.' a fost sters cu succes.');
        redirect(url($page_name));

    case 'listattach':
        identity_require('wiki-listattach', $page);
        $view['attach_list'] = attachment_get_all($page_name);
        $view['page_name'] = $page_name;
        $view['page_title'] = $page_title;
        execute_view_die('views/listattach.php', $view);
        break;

    default:
        flash_error('Actiunea nu este valida.');
        redirect(url($page_name));
}
?>
