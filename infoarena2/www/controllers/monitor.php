<?

require_once("pager.php");

// Job monitor controller.
function controller_monitor() {
    // First row.
    $options = pager_init_options();

    $view = array();
    $view['title'] = 'Monitor de evaluare';
    $view['jobs'] = job_get_range($options['first_entry'], $options['display_entries']); 
    $view['first_entry'] = $options['first_entry'];
    $view['total_entries'] = job_get_count();
    $view['display_entries'] = $options['display_entries'];

    execute_view('views/monitor.php', $view);
}

?>
