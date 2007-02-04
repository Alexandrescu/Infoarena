<?php

require_once(IA_ROOT_DIR."common/db/db.php");
require_once(IA_ROOT_DIR."common/user.php");
require_once(IA_ROOT_DIR."common/cache.php");

/**
 * User-related functions.
 */

function _user_cache_add($user) {
    mem_cache_set("user-by-name:{$user['username']}", $user);
    mem_cache_set("user-by-id:{$user['username']}", $user);
    return $user;
}

function _user_cache_delete($user) {
    mem_cache_delete("user-by-name:{$user['username']}");
    mem_cache_delete("user-by-id:{$user['username']}");
}

// Test password in IA1 format.
// NOTE: not cached, this is correct.
function user_test_ia1_password($username, $password) {
    // old ia1 users are expected to have the ia1 hashed password
    // as their actual password
    $password = db_query_value(sprintf("SELECT PASSWORD('%s')",
                                       db_escape($password)));
    // hash password ia2 style
    $password = user_hash_password($password, $username);
    // test
    $query = sprintf("SELECT *
                      FROM ia_user
                      WHERE username = '%s' AND '%s' = `password`",
                     db_escape($username), db_escape($password));
    return db_fetch($query);
}

// Check user's password
// NOTE: not cached, this is correct.
function user_test_password($username, $password) {
    // hash password
    $password = user_hash_password($password, $username);
    // test
    $query = sprintf("SELECT *
                      FROM ia_user
                      WHERE username = '%s' AND '%s' = `password`",
                     db_escape($username),
                     db_escape($password));
    return db_fetch($query);
}

function user_get_by_email($email) {
    $query = sprintf("SELECT *
                      FROM ia_user
                      WHERE email = '%s'",
                     db_escape($email));
    return _user_cache_add(db_fetch($query));
}

// Get user information.
function user_get_by_username($user_name) {
    if (($res = mem_cache_get("user-by-name:$user_name")) !== false) {
        return $res;
    }
    $query = sprintf("SELECT *
                      FROM ia_user
                      WHERE username = '%s'",
                     db_escape($user_name));
    return _user_cache_add(db_fetch($query));
}

function user_get_by_id($user_id) {
    if (($res = mem_cache_get("user-by-id:$user_name")) !== false) {
        return $res;
    }
    $query = sprintf("SELECT *
                      FROM ia_user
                      WHERE id = %s",
                     db_quote($user_id));
    return _user_cache_add(db_fetch($query));
}

// Create a new user.
function user_create($data) {
    $query = "INSERT INTO ia_user (";
    foreach ($data as $key => $val) {
        $query .= '`' . $key . '`,';
    }
    $query = substr($query, 0, strlen($query)-1);
    $query .= ') VALUES (';
    foreach ($data as $key => $val) {
        if ($key == 'password') {
            $val = user_hash_password($val, $data['username']);
        }

        $query .= "'" . db_escape($val) . "',";
    }
    $query = substr($query, 0, strlen($query)-1); // delete last ,
    $query .= ')';

    db_query($query);
    mem_cache_delete("user-by-username:{$data['username']}");
    $new_user = user_get_by_username($data['username']);
    log_assert($new_user, 'Registration input data was validated OK but no database entry was created');

    require_once(IA_ROOT_DIR . "common/textblock.php");
    $replace = array("user_id" => $data['username']);
    textblock_copy_replace("template/newuser", IA_USER_TEXTBLOCK_PREFIX.$data['username'],
                           $replace, "public", $new_user['id']);

    return _user_cache_add($new_user);
}

// Update user information.
// NOTE: When updating password, it is mandatory that you also specify username
function user_update($data, $id) {
    $query = "UPDATE ia_user SET ";
    foreach ($data as $key => $val) {
        if ($key == 'password') {
            $val = user_hash_password($val, $data['username']);
        }
        $query .= "`" . $key . "`='" . db_escape($val) . "',";
    }
    $query = substr($query, 0, strlen($query)-1); // delete last ,
    $query .= " WHERE `id` = '" . db_escape($id) . "'";

    mem_cache_delete("user-by-username:{$data['username']}");
    mem_cache_delete("user-by-id:$id");
    return _user_cache_add($new_user);
}

// Returns array with *all* registered usernames.
// Please use this wisely.
function user_get_list($all_fields = false) {
    $rows = db_fetch_all("SELECT * FROM ia_user");
    if ($all_fields) {
        return $rows;
    }
    $users = array();
    foreach ($rows as $row) {
        $users[] = $row['username'];
    }
    return $users;
}

// Counts number of users
function user_count() {
    if (($res = mem_cache_get("total-user-count")) !== false) {
        return $res;
    }
    $result = db_query_value("SELECT COUNT(*) FROM ia_user");
    return mem_cache_set("total-user-count", $result);
}

// Returns array with user submitted tasks. Filter tasks by choosing whether
// to select failed and solved tasks.
function user_submitted_tasks($user_id, $solved = true, $failed = true) {
    // construct where
    if ($solved && $failed) {
        // no condition
        $where = '';
    }
    elseif ($solved) {
        $where = 'AND ia_score.score = 100';
    }
    elseif ($failed) {
        $where = 'AND ia_score.score < 100';
    }
    else {
        // This shouldn't happen
        log_error('You can\'t select nothing.');
    }

    $query = "SELECT *
        FROM ia_score
        LEFT JOIN ia_task ON ia_task.id = ia_score.task_id
        WHERE ia_score.`name` = 'score' AND ia_score.user_id = '%s'
              AND ia_score.round_id = 'arhiva' AND NOT ia_task.id IS NULL %s
        GROUP BY ia_task.id
        ORDER BY ia_task.`order`";
    $query = sprintf($query, $user_id, $where);

    return db_fetch_all($query);
}

// Returns array with rounds that user has submitted to tasks.
function user_submitted_rounds($user_id) {
    // FIXME: Find a way to remove the hard-coded "<> 'arhiva'"
    $query = "SELECT *
        FROM ia_score
        LEFT JOIN ia_round ON ia_round.id = ia_score.round_id
        WHERE ia_score.`name` = 'score' AND ia_score.user_id = '%s'
              AND NOT ia_round.id IS NULL AND ia_round.id <> 'arhiva'
        GROUP BY ia_round.id";
    $query = sprintf($query, $user_id);

    return db_fetch_all($query);
}

?>
