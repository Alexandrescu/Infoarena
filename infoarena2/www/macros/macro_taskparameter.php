<?php

// Displays a task field, be it a hard-coded field such as task author or a grader parameter such as `timelimit`.
// NOTE: The macro employs a simple caching mechanism (via static variables, cache expires at the end of the request)
//       to avoid multiple database queries.
//
// Arguments:
//      task_id (required)            Task identifier (without task/ prefix)
//      param (required)              Parameter name. See the source code for possible values.
//      default_value (optional)      Display this when no such parameter is found
//
// Examples:
//      TaskParam(task_id="adunare" param="author")
//      TaskParam(task_id="adunare" param="timelimit")
function macro_taskparam($args) {
    static $last_task_id = null;
    static $task;
    static $params;

    $task_id = getattr($args, 'task_id');
    $param = getattr($args, 'param');

    // validate arguments
    if ($task_id) {
        return make_error_div("Expecting parameter `task_id`");
    }
    if ($param) {
        return make_error_div("Expecting parameter `param`");
    }

    // fetch task & parameters
    if ($last_task_id != $task_id) {
        $task = task_get($task_id);
        $params = task_get_parameters($task_id);
        $last_task_id = $task_id;
    }

    // validate task id
    if (!$task) {
        return make_error_div("Invalid task identifier");
    }

    // serve desired value
    switch ($param) {
        case 'author':
            return $task['author'];

        case 'source':
            return $task['source'];

        case 'type':
            return $task['type'];

        case 'id':
            return $task['id'];

        default:
            if (!isset($params[$param])) {
                return make_error_div("Task doesn't have parameter '$param'");
            }
    }
}
?>
