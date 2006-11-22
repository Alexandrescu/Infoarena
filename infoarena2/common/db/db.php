<?php
// This module contains various database-related functions and routines.
//
// Note: We keep database-persisted "models" very simple. Most of them are
// simple dictionaries. 

// Establish database connection
// Repetitive include guard. Is this really needed?
log_assert(!isset($dbLink));
$dbLink = mysql_connect(DB_HOST, DB_USER, DB_PASS) or log_error('Cannot connect to database.');
mysql_select_db(DB_NAME, $dbLink) or die ('Cannot select database.');

// Escapes a string to be safely included in a query.
function db_escape($str) {
    return mysql_escape_string($str);
}

function db_num_rows($res) {
    return mysql_num_rows($res);
}

// Executes query. Outputs error messages
// Returns native PHP mysql resource handle
function db_query($query) {
    global $dbLink;
    $result = mysql_query($query, $dbLink);
    if (!$result) {
        log_print("Query: '$query'");
        log_error("MYSQL error: ".mysql_error($dbLink));
    }
    return $result;
}

// Executes query, fetches only FIRST result row
function db_fetch($query) {
    global $dbLink;
    $result = db_query($query);
    if ($result) {
        $row = mysql_fetch_assoc($result);
        if ($row === false) {
            return null;
        }
        return $row;
    }
    else {
        return null;
    }
}

// Executes query, fetches the all result rows
function db_fetch_all($query) {
    global $dbLink;
    $result = db_query($query);
    if ($result) {
        $buffer = array();
        while ($row = mysql_fetch_assoc($result)) {
            $buffer[] = $row;
        }
        return $buffer;
    }
    else {
        return null;
    }
}

// Executes SQL query and returns value of the first column in the first
// result row.
// When query yields no results, it returns $default_value
//
// WARNING: This function asserts there is at most 1 result row and 1 column.
function db_query_value($query, $default_value = null) {
    global $dbLink;

    $rows = db_fetch_all($query);

    if (is_null($rows)) {
        return $default_value;
    }

    // failsafe
    log_assert(1 == count($rows), 'db_query_value() expects 1 row at most');
    $row = array_values($rows[0]);
    log_assert(1 == count($row), 'db_query_value() expects 1 column at most');

    return $row[0];
}

// Executes SQL INSERT statement (wrapper for db_query)
// Returns last SQL insert id
//
// Arguments:
// $table   SQL table name
// $dict    dictionary of fields to insert
//
// Example:
// $user = array(
//      'full_name' => 'Gigi Kent',
//      'username' => 'gigikent'
// );
// db_insert('user', $user);
//
// will execute:
// INSERT INTO `user` (`full_name`, `username`)
// VALUES ('Gigi Kent', 'gigikent')
//
// Returns last insert-ed primary key value
function db_insert($table, $dict) {
    global $dbLink;

    foreach ($dict as $k => $v) {
        if (is_null($v)) {
            unset($dict[$k]);
        }
    }

    $table = db_escape($table);

    $query = "INSERT INTO `{$table}` (`";
    $query .= join('`, `', array_keys($dict));
    $query .= "`) VALUES ('";
    $query .= join("', '", array_map('db_escape', array_values($dict)));
    $query .= "')";

    db_query($query);

    return mysql_insert_id($dbLink);
}

// Executes SQL UPDATE statement (wrapper for db_query)
// Returns number of affected rows
//
// PHP null values are expanded to SQL NULL
//
// Arguments:
// $table   SQL table name
// $dict    dictionary of fields to update
// $where   pre-escaped WHERE clause to be inserted inline
//
// Example:
// $user = array(
//      'full_name' => 'Gigi Kent',
//      'password' => 'xxx'
// );
// db_update('user', $user, "username='wickedman'");
//
// will execute:
// UPDATE `user`
// SET `full_name` = 'Gigi Kent', `password` = 'xxx'
// WHERE username='wickedman'
function db_update($table, $dict, $where = null) {
    global $dbLink;

    // fail safe
    log_assert(1 <= count($dict), 'db_update() called with empty $dict');

    // build query
    $table = db_escape($table);
    $query = "UPDATE `{$table}`\nSET ";
    $first = true;
    foreach ($dict as $k => $v) {
        //  - comma
        if (!$first) {
            $query .= ', ';
        }
        $first = false;

        //  - field-value pair
        if (is_null($v)) {
            $v = 'NULL';
        }
        else {
            $v = "'".db_escape($v)."'";
        }
        $query .= "`{$k}` = {$v}";
    }
    //  - WHERE clause
    if (!is_null($where)) {
        $query .= " WHERE ".$where;
    }

    db_query($query);

    return mysql_affected_rows($dbLink);
}

// Include actual db functions.
require_once(IA_ROOT . "common/db/job.php");
require_once(IA_ROOT . "common/db/round.php");
require_once(IA_ROOT . "common/db/task.php");
require_once(IA_ROOT . "common/db/textblock.php");
require_once(IA_ROOT . "common/db/user.php");
require_once(IA_ROOT . "common/db/score.php");
require_once(IA_ROOT . "common/db/attachment.php");

/**
 * Parameter
 * FIXME: This is sort of shared between rounds and tasks.
 */

// Replaces all parameter values according to the given dictionary
// :WARNING: This function does not check for parameter validity!
// It only stores them to database.
//
// $object_type is "task" or "round"
function parameter_update_values($object_type, $object_id, $dict) {
    log_assert($object_type == 'task' || $object_type == 'round');

    // delete all parameters connected to this task
    $query = sprintf("DELETE FROM ia_parameter_value
                      WHERE object_type = '%s' AND object_id = LCASE('%s')",
                     db_escape($object_type), db_escape($object_id));
    db_query($query);

    // insert given parameters
    foreach ($dict as $k => $v) {
        $query = sprintf("INSERT INTO ia_parameter_value
                            (object_type, object_id, parameter_id, `value`)
                          VALUES ('%s', '%s', '%s', '%s')",
                         db_escape($object_type), db_escape($object_id),
                         db_escape($k), db_escape($v));
        db_query($query);
    }
}

// Returns hash with task parameter values
function parameter_get_values($object_type, $object_id) {
    $query = sprintf("SELECT *
                      FROM ia_parameter_value
                      WHERE object_type = '%s' AND object_id = LCASE('%s')",
                     db_escape($object_type), db_escape($object_id));
    $dict = array();
    foreach (db_fetch_all($query) as $row) {
        $dict[$row['parameter_id']] = $row['value'];
    }
    return $dict;
}

/**
 * News
 * This is for the special "news" controller.
 */
function news_get_range($start, $range, $prefix = null) {
    $query = sprintf("SELECT
                        *
                      FROM ia_textblock
                      WHERE LCASE(`name`) LIKE 'news/%s%%'
                      ORDER BY ia_textblock.`timestamp` DESC
                      LIMIT %s,%s",
                     db_escape($prefix), db_escape($start), db_escape($range));
    return db_fetch_all($query);
}

function news_count($prefix = null) {
    $query = sprintf("SELECT COUNT(*) AS `cnt`
                      FROM ia_textblock
                      WHERE LCASE(`name`) LIKE 'news/%s%%'",
                      db_escape($prefix));
    $tmp = db_fetch($query);
    return $tmp['cnt'];
}

?>
