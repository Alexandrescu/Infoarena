<?php

require_once(IA_ROOT_DIR."common/db/db.php");
require_once(IA_ROOT_DIR."common/db/round.php");
require_once(IA_ROOT_DIR."common/parameter.php");
require_once(IA_ROOT_DIR."common/rating.php");

// Escape an array of strings.
function db_escape_array($array)
{
    $ret = '';
    foreach ($array as $element) {
        if ($ret) {
            $ret .= ", ";
        }
        $ret .= "'" . db_escape($element) . "'";
    }
    return $ret;
}

// Update a score.
// user/task/round can be null.
function score_update($name, $user_id, $task_id, $round_id, $value)
{
    log_assert(is_score_name($name), "Bad score name '$name'");
    log_assert(is_null($user_id) || is_user_id($user_id), "Bad user id '$user_id'");
    log_assert(is_null($task_id) || is_task_id($task_id), "Bad task id '$task_id'");
    log_assert(is_null($round_id) || is_round_id($round_id), "Bad round id '$round_id'");
    $query = sprintf("
            INSERT INTO ia_score (`name`, `score`, `user_id`, `task_id`, `round_id`)
            VALUES ('%s', %s, %s, %s, %s) ON DUPLICATE KEY UPDATE `score`=%s",
            db_escape($name), $value,
            ($user_id === null ? 'NULL' : $user_id),
            ($task_id === null ? 'NULL' : "'".db_escape($task_id)."'"),
            ($round_id === null ? 'NULL' : "'".db_escape($round_id)."'"),
            $value);
    //log_print($query);
    return db_query($query);
}

// Builds a where clause for a score query.
// Returns an array of conditions; you should do something like
// join($where, ' AND ');
function score_build_where_clauses($user, $task, $round)
{
    $where = array();

    if ($user != null) {
        if (is_array($user) && count($user) > 0) {
            $where[] = "(`user_id` IN (" . db_escape_array($user) . "))";
        } else if (is_string($user)) {
            $where[] = sprintf("(`user_id` == '%s')", $user);
        }
    }
    if ($task != null) {
        if (is_array($task) && count($task) > 0) {
            $where[] = "(`task_id` IN (" . db_escape_array($task) . "))";
        } else if (is_string($task)) {
            $where[] = sprintf("(`task_id` = '%s')", $task);
        }
    }
    if ($round != null) {
        if (is_string($round))
            $round = array($round);

        if (is_array($round) && count($round) > 0) {
            $rounds_where = "(`id` IN (" . db_escape_array($round) . "))";
            $query = "SELECT `id`, `state`, `public_eval` FROM `ia_round` WHERE " . $rounds_where;
            $round_objects = db_fetch_all($query);
            $allowed_round_ids = array();

            foreach ($round_objects as $round) {
                if (identity_can('round-view-scores', $round)) {
                    $allowed_round_ids[] = $round["id"];
                }
            }

            if (count($allowed_round_ids) == 0) {
                $where[] = "(TRUE = FALSE)";
            } else {
                $where[] = "(`round_id` IN (" . db_escape_array($allowed_round_ids) . "))";
            }
        }
    }

    return $where;
}

// Build a query for a certain score.
// Can be used as a subquery.
function score_build_query($score, $user, $task, $round)
{
    $cond = score_build_where_clauses($user, $task, $round);
    $cond[] = "(id = '".db_escape($score['name'])."')";
    $query = "SELECT SUM(score) FROM ia_score WHERE " . implode(" AND ", $cond);
}

// Get scores.
// $user, $task, $round can be null, string or an array.
// If null it's ignored, otherwise only scores for those users/tasks/rounds
// are counted.
function score_get_range($score_name, $user, $task, $round, $groupby = "user_id", $start = 0, $count = 999999, $with_rankings = false)
{
    log_assert(is_score_name($score_name));
    $where = score_build_where_clauses($user, $task, $round);
    $where[] = sprintf("ia_score.`name` = '%s'", db_escape($score_name));
    $query = sprintf("SELECT
                ia_score.`name` AS `score_name`, `user_id`, `task_id`, `round_id`, SUM(`score`) AS score, 
                ia_user.username AS user_name, ia_user.full_name AS user_full,
                ia_user.rating_cache AS user_rating
            FROM ia_score
                LEFT JOIN ia_user ON ia_user.id = ia_score.user_id
            WHERE %s GROUP BY %s
            ORDER BY `score` DESC LIMIT %s, %s",
            join($where, " AND "), $groupby, $start, $count);
    $scores = db_fetch_all($query);

    if ($with_rankings && count($scores)) {
        $first_score = $scores[0]['score'];

        // We need to count all users with a greater score than the first user
        // in the requested range.
        $where = score_build_where_clauses($user, $task, $round);
        $where[] = sprintf("ia_score.`name` = '%s'", db_escape($score_name));
        $query = sprintf("SELECT SUM(`score`) AS score 
                          FROM ia_score
                          WHERE %s GROUP BY %s
                          HAVING score > %s",
                         join($where, " AND "), $groupby, $first_score);
        $users_before = db_num_rows(db_query($query));

        // store rankings in result
        $scores[0]['ranking'] = $users_before + 1;
        $equal_scores = $start - $users_before + 1;
        for ($i = 1; $i < count($scores); ++$i) {
            $last_row = $scores[$i - 1];
            $row =& $scores[$i];
            if ($row['score'] == $last_row['score']) {
                $row['ranking'] = $last_row['ranking'];
                $equal_scores = $equal_scores + 1;
            }
            else {
                $row['ranking'] = $last_row['ranking'] + $equal_scores;
                $equal_scores = 1;
            }
        }
    }

    return $scores;
}

// Count function for score_get_range
function score_get_count($score_name, $user, $task, $round, $groupby) {
    log_assert(is_score_name($score_name));
    $where = score_build_where_clauses($user, $task, $round);
    $where[] = sprintf("ia_score.`name` = '%s'", db_escape($score_name));
    if ($user !== null) {
        $join = "LEFT JOIN ia_user ON ia_user.id = ia_score.user_id";
    } else {
        $join = "";
    }
    $query = sprintf("SELECT COUNT(DISTINCT user_id) AS `cnt`
            FROM ia_score $join
            WHERE %s",
            join($where, " AND "), $groupby);
    $res = db_fetch($query);
    return $res['cnt'];
}

// Get a score value.
// Returns 0 or null (if missing).
function score_get_value($score_name, $user_id, $task_id, $round_id)
{
    log_assert(is_score_name($score_name));
    log_assert(is_whole_number($user_id));
    log_assert(is_task_id($task_id));
    log_assert(is_user_id($round_id));

    $query = sprintf("SELECT score FROM ia_score
                WHERE name = '%s', task_id='%s', round_id='%s', user_id = %s",
                $score_name, $task_id, $round_id, $user_id);
    $res = db_fetch($query);
    return getattr($res, 'score', null);
}

// Return rating history for given user_id (not username).
// Output array format is:
//  array(
//      round_id =>
//          array(
//              rating => (int)
//              deviation => (int)
//              timestamp => (int UNIX timestamp)
//              round_page_name => (string)
//              round_title => (string)
//          ),
//      ...
//  );
// Rounds are ordered in chronological order.
function rating_history($user_id) {
    log_assert(is_whole_number($user_id));

    // get round list, chronologically ordered
    $history = rating_rounds();

    // get user scores
    $query = sprintf("SELECT * FROM `ia_score`
                      LEFT JOIN ia_round ON round_id = ia_round.id
                      WHERE `name` IN ('deviation', 'rating')
                            AND ia_score.user_id = '%s'
                            AND ia_round.state = 'complete'
                     ",
                     db_escape($user_id));
    $rows = db_fetch_all($query);

    // process user scores
    foreach ($rows as $row) {
        $round_id = $row['round_id'];
        log_assert(isset($history[$round_id]));

        switch ($row['name']) {
            case 'rating':
                $history[$round_id]['rating'] = $row['score'];
                break;
            case 'deviation':
                $history[$round_id]['deviation'] = $row['score'];
                break;
            default:
                log_error("Query returned invalid rating scores");
        }
    }

    // filter out rows which user has not participated in
    foreach (array_keys($history) as $round_id) {
        if (!isset($history[$round_id]['rating'])) {
            unset($history[$round_id]);
        }
    }

    // pretty much done
    return $history;
}

// Returns all COMPLETED rounds in chronological order that have ratings
// enabled.
//
// Output array format is:
//  array(
//      round_id =>
//          array(
//              timestamp => (int UNIX timestamp)
//              round_page_name => (string)
//              round_title => (string)
//          ),
//      ...
//  );
function rating_rounds() {
    $query = "
        SELECT
               object_id AS round_id, `value` AS `timestamp`,
               ia_round.page_name AS round_page_name,
               ia_round.title AS round_title
        FROM `ia_parameter_value`
        LEFT JOIN ia_round ON ia_round.id = ia_parameter_value.object_id
        WHERE parameter_id = 'rating_timestamp' AND object_type = 'round'
              AND NOT ia_round.id IS NULL AND ia_round.`state` = 'complete'
        ORDER BY `timestamp`, round_id
    ";
    $rows = db_fetch_all($query);
    $rounds = array();
    foreach ($rows as $row) {
        $rounds[$row['round_id']] = array(
            'timestamp' => $row['timestamp'],
            'round_page_name' => $row['round_page_name'],
            'round_title' => $row['round_title']
        );
    }

    // filter out rounds having rating_update off
    $query = "SELECT object_id AS round_id, parameter_id, `value`
        FROM `ia_parameter_value`
        WHERE parameter_id = 'rating_update' AND object_type = 'round'
    ";
    $rows = db_fetch_all($query);
    foreach ($rows as $row) {
        $round_id = $row['round_id'];
        if (!isset($rounds[$round_id])) {
            log_warn("Round {$round_id} has rating_update but no "
                      ."rating_timestamp parameter!");
            unset($rounds[$round_id]);
            continue;
        }
        $value = parameter_decode($row['parameter_id'], $row['value']);
        if ($value) {
            $rounds[$round_id]['rating_update'] = true;
            continue;
        }

        // Round parameters say round does not affect rating
        unset($rounds[$round_id]);
    }
    foreach ($rounds as $round_id => $round) {
        if (!isset($round['rating_update'])) {
            unset($rounds[$round_id]);
        }
    }

    return $rounds;
}

// Init user array with last rating, deviation & timestamp
// Output array format is:
//  array(
//      username => array(
//                      rating => (int),
//                      deviation => (int),
//                      timestamp => (int)
//                  )
//      ...
//  );
//
// NOTE: This array does not contain users never rated!
//
// WARNING: This function is VERY RESOURCE INTENSIVE! Don't use it in
// normal website operations.
//
// Last user ratings (but no deviation / timestamp) is stored directly in
// table ia_user.
function rating_last_scores() {
    // FIXME: horrible query
    $query = "SELECT
        ia_score.name AS `name`, ia_score.score AS score,
               ia_score.user_id, ia_score.round_id,
               pv.`value` AS `timestamp`, ia_user.username
        FROM ia_score
        LEFT JOIN ia_parameter_value AS pv
            ON pv.object_type = 'round' AND pv.object_id = ia_score.round_id
            AND pv.parameter_id = 'rating_timestamp'
        LEFT JOIN ia_user ON ia_user.id = ia_score.user_id
        WHERE ia_score.name IN ('rating', 'deviation')
        ORDER BY `timestamp` DESC, ia_score.round_id DESC
    ";
    $rows = db_fetch_all($query);

    // FIXME: We should filter out rounds having rating_update off
    // but these should not have any ratings stored in database anyway...

    // parse rows 
    $users = array();
    foreach ($rows as $row) {
        $username = $row['username'];
        $field = $row['name'];
        if (isset($users[$username])) {
            if (isset($users[$username][$field])) {
                // FIXME: This is currently a hack.
                // Query shouldn't return more then one rating for each user
                continue;
            }
            else {
                $users[$username][$field] = $row['score'];
            }
        }
        else {
            $users[$username] = array(
                $field => $row['score'],
                'timestamp' => parameter_decode('rating_timestamp',
                                                $row['timestamp'])
            );
        }
    }

    return $users;
}

// Return current rating distribution based on cached ratings.
// NOTE: $bucket_size refers to the absolute rating stored in database
// (ranging to around ~2500).
//
// Output array format:
//  array(
//      13 => <count>,
//      14 => <count>,
//      20 => <count>,
//      ...
//  );
// Key X corresponds to rating bucket [ x*$bucket_size; $bucket_size )
// NOTE: Some buckets may be missing completely
function rating_distribution($bucket_size) {
    log_assert(is_numeric($bucket_size));
    $query = "SELECT
            COUNT(*) AS `count`,
            FLOOR(rating_cache/{$bucket_size}) AS `bucket`
        FROM ia_user
        WHERE 0 < rating_cache
        GROUP BY `bucket`
        ORDER BY rating_cache
    ";
    $rows = db_fetch_all($query);

    $buckets = array();
    foreach ($rows as $row) {
        $buckets[$row['bucket']] = $row['count'];
    }

    return $buckets;
}

// Get top rated users list.
function get_users_by_rating_range($start, $count, $with_rankings = false)
{
    $query = "SELECT *
        FROM ia_user
        WHERE rating_cache > 0
        AND security_level != 'admin'
        ORDER BY rating_cache DESC
        LIMIT %s, %s
    ";
    $query = sprintf($query, $start, $count);
    $rows = db_fetch_all($query);

    if ($with_rankings && count($rows)) {
        $query = sprintf("SELECT `rating_cache` AS rating_cache
                          FROM ia_user
                          WHERE rating_cache > 1.5 + 3 * ROUND(%s/3)
                          AND security_level != 'admin'",
                         $rows[0]['rating_cache']);

        $users_before = db_num_rows(db_query($query));


        $rows[0]['position'] = $users_before + 1;
        $equal_scores = $start - $users_before + 1;          
        for ($i = 1; $i < count($rows); ++$i) {
            $last_row = $rows[$i - 1];
            $row =& $rows[$i];
            if (rating_scale($row['rating_cache']) == rating_scale($last_row['rating_cache'])) {
                $row['position'] = $last_row['position'];
                $equal_scores = $equal_scores + 1;
            }
            else {
                $row['position'] = $last_row['position'] + $equal_scores;
                $equal_scores = 1;
            }
        }
    }

    return $rows;
}

// Count function for get_users_by_rating_range.
function get_users_by_rating_count() {
    $query = "SELECT COUNT(*) AS `cnt`
        FROM `ia_user`
        WHERE `rating_cache` > 0
        AND `security_level` != 'admin'";
    $res = db_fetch($query);
    return $res['cnt'];
}

// Clears ALL user ratings & rating history
function rating_clear() {
    db_query("DELETE FROM ia_score WHERE `name` IN ('rating', 'deviation')");
    db_query("UPDATE ia_user SET rating_cache = NULL");
}

?>
