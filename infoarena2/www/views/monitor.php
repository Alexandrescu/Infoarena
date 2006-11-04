<?php

require_once('format_table.php');

include('header.php');

print('<h1>'.htmlentities($view['title']).'</h1>');
if (!$jobs) {
    print "<h3>Nu s-a gasit nici o submisie</h3>";
} else {
    // For the score column.
    function format_state($row) {
        $url = url("job_detail", array('id' => $row['id']));
        if ($row['status'] == 'done') {
            $msg = $row['eval_message'].": ".$row['score']." puncte";
            $msg = "<span style=\"color:green\">$msg</span>";
            return href($url, $msg);
        }
        if ($row['status'] == 'processing') {
            // FIXME: animation? :)
            $msg = '<span style="color:red">se evalueaza</span>';
            return href($url, $msg);
        }
        if ($row['status'] == 'waiting') {
            $msg = '<span style="color:blue">in asteaptare</span>';
            return href($url, $msg);
        }
        log_die("Invalid job status");
    }

    // For the task column.
    function format_task_link($row)
    {
        $url = url("task/" . $row['task_id']);
        return '<a href="' . $url . '">' . $row['task_title'] . '</a>';
    }

    // For the detail column.
    function format_jobdetail_link($val)
    {
        $url = url("job_detail", array('id' => $val));
        return href($url, "Job #$val");
    }

    $column_infos = array(
            array(
                    'title' => 'ID',
                    'key' => 'id',
                    'valform' => 'format_jobdetail_link',
            ),
            array(
                    'title' => 'Utilizator',
                    'key' => 'username',
                    'valform' => 'format_user_link',
            ),
            array(
                    'title' => 'Problema',
                    'rowform' => 'format_task_link',
            ),
            array(
                    'title' => 'Data',
                    'key' => 'timestamp',
//                    'dateform' => 'H:i:s',
            ),
            array(
                    'title' => 'Stare (click pentru detalii)',
                    'rowform' => 'format_state',
            ),
    );
    $options = array(
            'css_class' => 'monitor',
            'url_page' => $view['url_page'],
            'url_args' => $view['url_args'],
            'display_rows' => $view['display_rows'],
            'total_rows' => $view['total_rows'],
            'first_row' => $view['first_row'],
            'pager_style' => 'standard',
            'surround_pages' => 3,
    );

    print format_table($jobs, $column_infos, $options);
//    print format_table($jobs);
}

include('footer.php');

?>
