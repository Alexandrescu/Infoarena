<?php

require_once(IA_ROOT . "common/db/task.php");
require_once(IA_ROOT . "common/task.php");

/* FIXME: broken
// Displays form to either create a new task or edit an existing one.
// This form does not edit task content (its associated textblock)
// (textblock editor does that)
//
// Initially, the form is filled in with either:
//      * values for the existing task we edit
//      * default initial values when creating a new task
//
// Form submits to controller_task_save_details().
// When a validation error occurs in controller_task_save_details() it calls
// this controller as an error handler in order to display the form
// with the user-submitted data and their corresponding errors.
function controller_task_edit_details($task_id, $form_data = null, $form_errors = null) {
    global $identity_user;

    $task = task_get($task_id);
    if ($task) {
        identity_require('task-edit', $task);
    }
    else {
        identity_require('task-create');
    }

    // validate task_id
    if (!is_task_id($task_id)) {
        flash_error('Identificatorul de task este invalid');
        redirect(url_home());
    }

    // get parameter list for tasks (in general, not for this specific task)
    $param_list = task_get_parameter_infos_hack();
    // here we store parameter values
    $param_values = array();

    if (is_null($form_data)) {
        // initial form data (when displaying the form for the first time)
        $form_data = array();
        $form_errors = array();
        if (!$task) {
            // - default values (when creating a new task)
            $form_data['author'] = getattr($identity_user, 'full_name');
            $form_data['source'] = '';
            $form_data['type'] = '';
            $form_data['hidden'] = '1';

            // - default parameter values
            foreach ($param_list as $k => $v) {
                $param_values[$k] = $v['default'];
                $form_data['p_' . $k] = $v['default'];
            }
        }
        else {
            // - values from existing task
            $form_data['author'] = $task['author'];
            $form_data['source'] = $task['source'];
            $form_data['type'] = $task['type'];
            $form_data['hidden'] = $task['hidden'];

            // get task parameter values
            $param_values = task_get_parameters($task_id);
            foreach ($param_values as $k => $v) {
                $form_data['p_' . $k] = $v;
            }
        }
    }
    else {
        // form was submitted. there was an error with the input
        // - $form_data already contains input data
        // - $form_errors already contains input errors

        // - extract parameter values from form_data.
        //   the `save` controller does a nice thing and extracts these values
        //   for convenience
        $param_values = $form_data['_param_values'];
    }

    // view form
    if (!$task) {
        $view['title'] = "Creaza task: " . $task_id;
    }
    else {
        $view['title'] = "Modifica task: ".$task_id;
    }
    $view['action'] = url_task_admin($task_id);
    $view['form_values'] = $form_data;
    $view['form_errors'] = $form_errors;
    $view['page_name'] = url_task_admin($task_id);
    $view['param_list'] = $param_list;
    $view['param_values'] = $param_values;
    execute_view_die("views/task_edit.php", $view);
}

// save details controller
// Workflow is:
//      * controller_task_edit_details() displays form
//      * form submits to controller_task_save_details()
//      * controller_task_save_details() validates and uses
//        controller_task_edit_details() as error handler
function controller_task_save_details($task_id) {
    global $identity_user;

    $task = task_get($task_id);
    if ($task) {
        identity_require('task-edit', $task);
    }
    else {
        identity_require('task-create');
    }

    // validate task_id
    if (!$task && !task_is_valid_id($task_id)) {
        flash_error('Identificatorul de task este invalid');
        redirect(url_home());
    }

    // get parameter list for tasks (in general, not for this specific task)
    $param_list = task_get_parameter_infos_hack();

    // Validate data. Put incoming data in `data` and errors in `errors`
    $data = array();
    $errors = array();
    $data['author'] = getattr($_POST, 'author');
    $data['source'] = getattr($_POST, 'source');
    $data['type'] = getattr($_POST, 'type');
    $data['hidden'] = getattr($_POST, 'hidden');
    // get parameter values (all incoming POST variables that start with 'p_')
    $param_values = array();
    foreach ($_POST as $k => $v) {
        if ('p_' != substr($k, 0, 2)) continue;
        $id = substr($k, 2);
        if (!isset($param_list[$id])) continue;
        $param_values[$id] = $v;
        $data[$k] = $v;
    }
    $data['_param_values'] = $param_values;
    // validate task values
    if (strlen($data['author']) < 1) {
        $errors['author'] = "Va rugam sa completati autorul/autorii.";
    }
    log_print("Task type is {$data['type']}");
    if (!in_array($data['type'], task_get_types())) {
        $errors['type'] = "Alegeti tipul task-ului.";
    }
    if ('0' != $data['hidden'] && '1' != $data['hidden']) {
        $errors['hidden'] = "Valoare invalida";
    }
    if ('0' == $data['hidden'] && !identity_can('task-publish', $task)) {
        $errors['hidden'] = "Nu aveti permisiunea sa publicati task-uri. Luati legatura cu un administrator.";
    }

    // validate parameter values
    if (in_array($data['type'], task_get_types())) {
        $p_errors = task_validate_parameters($data['type'], $param_values);
        if ($p_errors) {
            foreach ($p_errors as $k => $v) {
                $errors['p_' . $k] = $v;
            }
        }
    }

    // process data
    if (!$errors) {
        // no errors => do stuff
        
        // - create/update task
        if ($task) {
            task_update($task_id, $data['type'], $data['hidden'],
                        $data['author'], $data['source']);
            // note: updating a task does not change its owner (user_id)
        }
        else {
            task_create($task_id, $data['type'], $data['hidden'],
                        $data['author'], $data['source'],
                        getattr($identity_user, 'id'));

            // Copy templates.
            require_once(IA_ROOT . "common/textblock.php");
            $replace = array("task_id" => $task_id);
            textblock_copy_replace("template/newtask", TB_TASK_PREFIX."$task_id", $replace, "task: $task_id", $user_id);
        }
        // - update parameter values
        task_update_parameters($task_id, $param_values);
        // - done
        if ($task) {
            flash('Informatiile despre task au fost actualizate.');
            redirect(url_textblock($task['page_name']));
        }
        else {
            flash('Un nou task a fost creat. Acum trebuie sa editezi continutul ...');
            redirect(url_textblock_edit($task['page_name']));
        }
    }
    else {
        // errors occured => call on error handler
        flash_error('Unul sau mai multe campuri au fost completate incorect!');
        controller_task_edit_details($task_id, $data, $errors);
    }
} */

?>
