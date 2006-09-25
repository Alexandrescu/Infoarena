<?php

require_once("format_table.php");

// Displays *interactive* rankings table summing up score points from a
// pre-defined set of contest rounds.
//
// Parameters:
//     rounds: a | separated list of round names.
//     count: How many to display at once, defaults to IA_DEFAULT_ROWS_PER_PAGE.
function macro_rankings($args) {
    // How many rows to display at a time.
    $display_rows = getattr($args, 'count', IA_DEFAULT_ROWS_PER_PAGE);
    if (!preg_match('/^[0-9]{1,4}$/', $display_rows)) {
        return make_error_div("Invalid count parameter.");
    }
    // Pager style.
/*    $pager_style = getattr($args, 'pager_style', 'none');
    if (!valid_pager_style($pager_style)) {
        return make_error_div("Invalid pager style '$pager_style'");
    }*/

    // Make a list of round ids
    $roundStr = getattr($args, 'rounds', '');
    $rounds = preg_split('/\s*\|\s*/', $roundStr);

    // construct query
    //  - first, we need a WHERE condition for round_id.
    //    goal:  '<round-id-1>', '<round-id-2>' ... '<round-id-n>'
    $whereRound = '';
    foreach ($rounds as $round_id) {
        if ($whereRound) {
            $whereRound .= ', ';
        }
        $whereRound .= "'" . db_escape($round_id) . "'";
    }

    //  - SQL frame
    $query = "
        SELECT
            user_id, ia_user.username AS `username`,
            ia_user.full_name AS full_name, SUM(`score`) AS totalScore
        FROM ia_score
        LEFT JOIN ia_user ON ia_user.id = ia_score.user_id
        WHERE round_id IN (%s)
        GROUP BY user_id
        ORDER BY totalScore DESC
        LIMIT 0, %s
    ";
    $query = sprintf($query, $whereRound, $display_rows);

    // query database
    $rankings = db_fetch_all($query);

    log_backtrace();
    $column_infos = array(
            array('title' => 'ID', 'key' => 'user_id'),
            array('title' => 'User', 'key' => 'username'),
            array('title' => 'Nume', 'key' => 'full_name', 'rowform' => '_format_full_name'),
            array('title' => 'Scor', 'key' => 'totalScore'),
    );
    $options = array(
//            'pager_style' => $pager_style,
            'display_rows' => $display_rows,
    );

    return format_table($rankings, $column_infos, $options);
}

// Function for printing user link.
function _format_full_name($row) {
    return "<a href='".url('user/' . $row['username'])."'>{$row['full_name']}</a></td>";
}

?>
