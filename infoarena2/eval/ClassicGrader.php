<?php

class ClassicGrader {
    public $task_id;

    public $test_count;

    public $time_limit;

    public $memory_limit;

    public $uses_ok_files;

    public $unique_output;

    public $evaluator;

    function __construct($id, $parameters) {
        $this->task_id = $id;
        $this->evaluator = (string)$parameters['evaluator'];
        $this->test_count = (int)$parameters['tests'];
        $this->time_limit = (int)$parameters['timelimit'];
        $this->memory_limit = (int)$parameters['memlimit'];
        $this->unique_output = (bool)$parameters['unique_output'];
        $this->has_ok_files = (bool)$parameters['okfiles'];

        if ($this->unique_output == true && $this->has_ok_files == false) {
            tprint("Task has unique output but no ok files");
        }

        if ($this->unique_output == true && $this->evaluator != "") {
            tprint("Task has both unique output and evaluator");
        }
    }

    // FIXME: move validation here.
    static function validate($parameters)
    {
    }

    function handle_test($testno)
    {
        // Copiem input. 
    }

    function Grade($file_contents, $file_extension) {
        $result = new JobResult();
        $result->score = 0;

        // Clean jail and temp
        if (!clean_dir(IA_EVAL_TEMP_DIR)) {
            return JobResult::SystemError();
        }

        // chdir to temp dir.
        if (!chdir(IA_EVAL_TEMP_DIR)) {
            tprint("Can't chdir to temp dir.");
            return JobResult::SystemError();
        }

        // Compile custom evaluator.
        if (!$this->unique_output) {
            if (!copy(IA_GRADER_DIR . $this->task_id . '/' . $this->evaluator,
                        IA_EVAL_TEMP_DIR . $this->evaluator)) {
                tprint("Can't move evaluator source to temp dir");
                return JobResult::SystemError();
            }

            if (!compile_file($this->evaluator , $compiler_messages)) {
                tprint("Can't compiler evaluator");
                return JobResult::SystemError();
            }
        }

        // Compile user source.
        if (!file_put_contents("user." . $file_extension, $file_contents)) {
            tprint("Can't write user file on disk");
            return JobResult::SystemError();
        }
        if (!compile_file("user." . $file_extension, $compiler_messages)) {
            if ($compiler_messages === false) {
                return JobResult::SystemError();
            }
            $result->message = "Eroare de compilare";
            $result->log = "Eroare de compilare:\n" . $compiler_messages;
        } else {
            $result->log = "Compilare:\n" . $compiler_messages . "\n";
        }

        for ($testno = 1; $testno <= $this->test_count; ++$testno) {
            $result->log .= "\nRulez testul $testno: ";

            if (!chdir(IA_EVAL_DIR)) {
                tprint("Can't chdir to eval dir.");
                return JobResult::SystemError();
            }
            if (!clean_dir(IA_EVAL_JAIL_DIR)) {
                return JobResult::SystemError();
            }
            if (!chdir(IA_EVAL_JAIL_DIR)) {
                tprint("Can't chdir to jail dir.");
                return JobResult::SystemError();
            }

            if (!copy(IA_GRADER_DIR . $this->task_id . '/test' . $testno . '.in',
                        IA_EVAL_JAIL_DIR . $this->task_id . '.in')) {
                tprint("Failed copying test $testno");
                return JobResult::SystemError();
            }

            if (!copy(IA_EVAL_TEMP_DIR . 'user', IA_EVAL_JAIL_DIR . 'user')) {
                tprint("Failed copying user program");
                return JobResult::SystemError();
            }
            @system("chmod a+x user", $res);
            if ($res) {
                tprint("Failed to chmod a+x user program");
                return JobResult::SystemError();
            }
         
            // Run user program.
            $jrunres = jail_run('user', $this->time_limit, $this->memory_limit);
            tprint("JRUN: ".$jrunres['result'].": ".$jrunres['message']);
            if ($jrunres['result'] == 'ERROR') {
                return JobResult::SystemError();
            } else if ($jrunres['result'] == 'FAIL') {
                $result->log .= "eroare: ".$jrunres['message'].": 0 puncte";
                continue;
            } else {
                $result->log .= "ok: timp ".$jrunres['time']."ms ".
                        $jrunres['memory']."kb: ";
            }

            // Copy ok file, if used.
            if ($this->has_ok_files) {
                if (!copy(IA_GRADER_DIR . $this->task_id . '/test' . $testno . '.ok',
                            IA_EVAL_JAIL_DIR . $this->task_id . '.ok')) {
                    tprint("Failed copying test $testno of file");
                    return JobResult::SystemError();
                }
            }

            if ($this->has_unique_output) {
                tprint("Nu stiu ce sa fac cu output unic");
                return JobResult::SystemError();
            } else {
                // Custom grader.
                if (!copy(IA_EVAL_TEMP_DIR . 'eval', IA_EVAL_JAIL_DIR . 'eval')) {
                    tprint("Failed copying custom grader");
                    return JobResult::SystemError();
                }
                @system("chmod a+x eval", $res);
                if ($res) {
                    tprint("Failed to chmod a+x custom grader");
                    return JobResult::SystemError();
                }

                // FIXME: Run grader here.
                $score = 100 / $this->test_count;
                $result->score += $score;
                $result->log .= "bonus de la fluffy: $score puncte";
            }
        }

        $result->log .= "\n\nPunctaj total: {$result->score}\n";

        return $result;
    }
}

?>
