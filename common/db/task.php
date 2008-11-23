<?php

require_once(IA_ROOT_DIR . "common/db/db.php");
require_once(IA_ROOT_DIR . "common/task.php");
require_once(IA_ROOT_DIR . "common/db/parameter.php");

// Add $task to cache if not null, return $task.
function _task_cache_add($task) {
    if (!is_null($task)) {
        log_assert_valid(task_validate($task));
        mem_cache_set("task-by-id:{$task['id']}", $task);
    }
    return $task;
}

function _task_cache_delete($task) {
    mem_cache_delete("task-by-id:{$task['id']}");
}

// Get task by id. No params.
function task_get($task_id) {
    // this assert brakes templates pages with round_id = %round_id%
    log_assert(is_task_id($task_id));

    if (($res = mem_cache_get("task-by-id:$task_id")) !== false) {
        return $res;
    }

    $query = sprintf("SELECT * FROM ia_task WHERE `id` = '%s'",
                     db_escape($task_id));

    // This way nulls (missing tasks) get cached too.
    return mem_cache_set("task-by-id:$task_id", db_fetch($query));
}

// Create new task
function task_create($task, $task_params) {
    log_assert_valid(task_validate($task));
    log_assert_valid(task_validate_parameters($task['type'], $task_params));

    $res = db_insert('ia_task', $task);
    if ($res) {
        // Insert parameters.
        task_update_parameters($task['id'], $task_params);

        // Copy templates.
        require_once(IA_ROOT_DIR . "common/textblock.php");
        $replace = array("task_id" => $task['id'], "task_title" => ucfirst($task['id']));
        textblock_copy_replace("template/newtask", $task['page_name'],
                $replace, "task: {$task['id']}", $task['user_id']);

        _task_cache_add($task);
    }
    return $res;
}

// Delete a task, including scores, jobs and page
// WARNING: This is irreversible.
function task_delete($task) {
    log_assert_valid(task_validate($task));

    // Delete task from cache
    _task_cache_delete($task);

    // Delete problem page
    textblock_delete($task["page_name"]);

    // Remove task from all rounds
    db_query("DELETE FROM `ia_round_task`
              WHERE `task_id` = " . db_quote($task["id"]));

    // Delete all scores received on task
    db_query("DELETE FROM `ia_score`
              WHERE `task_id` = " . db_quote($task["id"]));

    // Delete task jobs
    $job_ids_fetched = db_fetch_all("
        SELECT `id`
        FROM `ia_job`
        WHERE `task_id` = " . db_quote($task["id"]));

    $job_ids = array();
    foreach ($job_ids_fetched as $job) {
        $job_ids[] = (int)$job["id"];
    }

    if (count($job_ids)) {
        $formated_job_ids = implode(", ", array_map("db_quote", $job_ids));
        db_query("DELETE FROM `ia_job_test`
                  WHERE `job_id` IN ({$formated_job_ids})");
        db_query("DELETE FROM `ia_job`
                  WHERE `id` IN ({$formated_job_ids})");
    }

    // Delete task
    db_query("DELETE FROM `ia_task` WHERE `id` = '" . db_escape($task["id"]) . "'");

    // Delete all task parameters
    task_update_parameters($task["id"], array());
}

function task_update($task) {
    log_assert_valid(task_validate($task));
    if (db_update('ia_task', $task,
            "`id` = '".db_escape($task['id'])."'")) {
        _task_cache_add($task);
    } else {
        _task_cache_delete($task);
    }
}

// binding for parameter_get_values
function task_get_parameters($task_id) {
    log_assert(is_task_id($task_id));
    return parameter_get_values('task', $task_id);
}

// binding for parameter_update_values
function task_update_parameters($task_id, $param_values) {
    log_assert(is_task_id($task_id));
    parameter_update_values('task', $task_id, $param_values);
}

// Get all tasks.
function task_get_all() {
    $res = db_fetch_all("SELECT * FROM ia_task");
    foreach ($res as $task) {
        _task_cache_add($task);
    }
    return $res;
}

// Returns list of round ids that include this task
function task_get_parent_rounds($task_id) {
    log_assert(is_task_id($task_id));
    $query = sprintf("
        SELECT DISTINCT round_id
        FROM ia_round_task
        WHERE task_id=%s
        ORDER BY round_id
    ", db_quote($task_id));

    $rows = db_fetch_all($query);

    // transform rows into id list
    $idlist = array();
    foreach ($rows as $row) {
        $idlist[] = $row['round_id'];
    }

    return $idlist;
}

?>
