<?php

require_once("format_table.php");

// Displays *interactive* rankings table summing up score points from a
// pre-defined set of contest rounds.
//
// Arguments:
//     rounds   (required) a | (pipe) separated list of round names.
//     count    (optional) how many to display at once, defaults to infinity
//
// Examples:
//      Rankings(rounds="preONI2007/1/a | preONI2007/2/a")
//      Rankings(rounds="preONI2007/1/a | preONI2007/2/a" count="10")
function macro_rankings($args) {
    $options = pager_init_options($args);

    // Rounds parameters
    $roundstr = getattr($args, 'rounds', '');
    if ($roundstr == '') {
        return macro_error("Parameters 'rounds' is required.");
    }
    $rounds = preg_split('/\s*\|\s*/', $roundstr);

    // FIXME: user/ task parameters.

    $res = score_get("score", null, null, $rounds, $options['first_entry'], $options['display_entries']);
    $rankings = $res['scores'];

    $column_infos = array(
            array(
                'title' => 'Nume',
                'key' => 'user_full',
                'rowform' => create_function('$row',
                        'return format_user_normal($row["user_name"], $row["user_full"]);'),
            ),
            array('title' => 'Scor', 'key' => 'score'),
    );
    $options['pager_style'] = 'standard';
    $options['total_entries'] = $res['total_rows'];

    return format_table($rankings, $column_infos, $options);
}

?>
