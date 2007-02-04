<?php

require_once(IA_ROOT_DIR."common/db/db.php");
require_once(IA_ROOT_DIR."common/db/round.php");
require_once(IA_ROOT_DIR."common/db/task.php");
require_once(IA_ROOT_DIR."common/round.php");

// Displays form to either create a new round or edit an existing one.
// This form does not edit round content (its associated textblock)
// (textblock editor does that)
//
// Initially, the form is filled in with either:
//      * values for the existing round we edit
//      * default initial values when creating a new round
//
// Form submits to controller_round_save_details().
// When a validation error occurs in controller_round_save_details() it calls
// this controller as an error handler in order to display the form
// with the user-submitted data and their corresponding errors.
function controller_round_details($round_id) {
    // validate round_id
    if (!is_round_id($round_id)) {
        flash_error('Identificatorul rundei este invalid');
        redirect(url_home());
    }

    // Get round
    $round = round_get($round_id);
    if (!$round) {
        flash_error("Runda nu exista");
        redirect(url_home());
    }

    // Security check
    identity_require('round-edit', $round);

    // get parameter list for rounds (in general, not for this specific round)
    $param_infos = round_get_parameter_infos();
    $all_tasks = task_get_all();
    $all_task_ids = array();
    foreach ($all_tasks as $task) {
        $all_task_ids[$task['id']] = true;
    }

    // Get parameters and task list.
    $round_params = round_get_parameters($round['id']);
    $round_tasks = array();
    foreach (round_get_tasks($round_id) as $task) {
        $round_tasks[] = $task['id'];
    }

    // Form stuff.
    $values = array();
    $errors = array();

    // Fill in form values from request, defaults in $round
    $values['type'] = request('type', $round['type']);
    $values['title'] = request('title', $round['title']);
    $values['page_name'] = request('page_name', $round['page_name']);
    $values['start_time'] = request('start_time', $round['start_time']);

    // Get tasks. WTF, this works? wicked!
    $values['tasks'] = request('tasks', $round_tasks);

    // Parameter values, for all possible types of rounds.
    // Yucky, but functional.
    foreach (round_get_types() as $round_type => $pretty_name) {
        foreach ($param_infos[$round_type] as $name => $info) {
            $form_name = "param_{$round_type}_{$name}";
            $def = $info['default'];
            if ($round_type == $round['type']) {
                $def = getattr($round_params, $name, $def);
            }
            $values[$form_name] = request($form_name, $def);
        }
    }

    // Validate the monkey.

    // Build new round
    $new_round = $round;
    $new_round['type'] = $values['type'];
    $new_round['title'] = $values['title'];
    $new_round['page_name'] = $values['page_name'];
    $new_round['start_time'] = $values['start_time'];

    $errors = round_validate($new_round);

    // Validate task list.
    $new_round_tasks = $values['tasks'];
    if (!is_array($new_round_tasks)) {
        $errors['tasks'] = 'Valori invalide.';
    } else {
        foreach ($new_round_tasks as $tid) {
            if (!is_string($tid)) {
                $errors['tasks'] = 'Valori invalide.';
                break;
            }
            if (!array_key_exists($tid, $all_task_ids)) {
                log_print_r($all_task_ids);
                $errors['tasks'] = "Nu exista task-ul $tid.";
                break;
            }
        }
    }
    if (array_key_exists('tasks', $errors)) {
        $values['tasks'] = array();
    }

    // Validate round parameters. Only for current type, and only if
    // properly selected.
    // FIXME: refactor
    $new_round_params = $round_params;
    if (!array_key_exists('type', $errors)) {
        $round_type = $new_round['type'];
        foreach ($param_infos[$round_type] as $name => $info) {
            $form_name = "param_{$round_type}_{$name}";
            $new_round_params[$name] = $values[$form_name];
        }
        $round_params_errors = round_validate_parameters(
                $round_type, $new_round_params);
        // Properly copy errors. Sucky
        foreach ($param_infos[$round_type] as $name => $info) {
            $form_name = "param_{$round_type}_{$name}";
            if (array_key_exists($name, $round_params_errors)) {
                $errors[$form_name] = $round_params_errors[$name];
            }
        }
    }
    // Always copy timestamp for ratings
    $new_round_params['rating_timestamp'] = db_date_parse($new_round['start_time']);

    // If posting with no errors then do the db monkey
    if (request_is_post() && !$errors) {
        // FIXME: error handling? Is that even remotely possible in php?
        round_update($new_round);
        round_update_parameters($round_id, $new_round_params);
        round_update_task_list($round_id, $new_round_tasks);

        flash("Runda a fost modificata cu succes.");
        redirect(url_round_edit($round_id));
    }

    // Create view.
    $view = array();
    $view['title'] = "Editare $round_id";
    $view['page_name'] = url_round_edit($round_id);
    $view['round_id'] = $round_id;
    $view['round'] = $round;
    $view['form_values'] = $values;
    $view['form_errors'] = $errors;
    $view['param_infos'] = $param_infos;
    $view['all_tasks'] = $all_tasks;

    execute_view_die("views/round_edit.php", $view);
}

// Creates a round. Minimalist
function controller_round_create()
{
    global $identity_user;

    // Security check. FIXME: sort of a hack.
    identity_require_login();
    identity_require("round-create",
            round_init('new_round', 'classic', $identity_user));

    // Form stuff.
    $values = array();
    $errors = array();

    // Get form values
    $values['id'] = request('id', '');
    // FIXME: type hidden
    $values['type'] = request('type', 'classic');

    if (request_is_post()) {
        if (!is_round_id($values['id'])) {
            $errors['id'] = "Id-ul rundei este invalid";
        } else if (round_get($values['id'])) {
            $errors['id'] = "Exista deja o runda cu acest id";
        }
        if (!array_key_exists($values['type'], round_get_types())) {
            $errors['type'] = "Tip de runda invalid";
        }

        if (!$errors) {
            $round = round_init(
                    $values['id'],
                    $values['type'],
                    $identity_user);
            $round_params = array();
            // FIXME: array_ magic?
            $param_infos = round_get_parameter_infos();
            foreach ($param_infos[$values['type']] as $name => $info) {
                $round_params[$name] = $info['default'];
            }

            // This should never fail.
            log_assert(round_create(
                        $round,
                        $round_params,
                        identity_get_user_id()
            ));
            flash("O noua runda a fost creata, acum poti sa editezi detalii.");
            redirect(url_round_edit($round['id']));
        }
    }

    // Create view.
    $view = array();
    $view['title'] = "Creare runda";
    $view['page_name'] = url_round_create();
    $view['form_values'] = $values;
    $view['form_errors'] = $errors;

    execute_view_die("views/round_create.php", $view);
}

?>
