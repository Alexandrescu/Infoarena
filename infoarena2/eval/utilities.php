<?php

// Sleeps for a number of miliseconds.
function milisleep($ms) {
    usleep($ms * 1000);
}

// print with a timestamp
function tprint($msg) {
    print(date("d-m-y H:i:s") . ": $msg\n");
}

// Delete and remake a directory.
function clean_dir($dir)
{
    system("rm -rf " . $dir, $res);
    system("mkdir -m 0777 -p " . $dir, $res);
    if ($res) {
        tprint("Failed cleaning up directory $dir");
        return false;
    }
    tprint("Cleaned up directory $dir");
    return true;
}

// Compile a certain file.
// Returns success value.
function compile_file($file_name, &$compiler_message)
{
    $compiler_lines = array(
            'c' => 'gcc -Wall -O2 -static -lm %file_name% -o %exe_name%',
            'cpp' => 'g++ -Wall -O2 -static -lm %file_name% -o %exe_name%',
            'pas' => 'fpc -O2 -Xs %file_name%');
    if (!preg_match("/^(.*)\.(c|cpp|pas)$/i", $file_name, $matches)) {
        tprint("Can't figure out compiler for file $file_name");
        return false;
    }
    $exe_name = $matches[1];
    $extension = $matches[2];
    tprint("Compiling file '$file_name' extension '$extension'");
    if (!isset($compiler_lines[$extension])) {
        tprint("Can't find compiler line for extension $extension");
        return false;
    }

    $cmdline = $compiler_lines[$extension];
    $cmdline = preg_replace('/%file_name%/', $file_name, $cmdline);
    $cmdline = preg_replace('/%exe_name%/', $exe_name, $cmdline);

    tprint("Running $cmdline");
    @system("$cmdline &> compiler.log");
    if ($res) {
        tprint("Compilation failed");
        return false;
    }
    $compiler_message = file_get_contents('compiler.log');
    if ($compiler_message === false) {
        tprint("Failed getting compiler messages");
        $compiler_message = false;
        return false;
    }
    tprint($compiler_messages);
    return true;
}

// Parses jrun output.
// Returns an array with result, time, memory and message.
//
// Result is 'OK', 'FAIL' or 'ERROR'
// If result is ERROR time and memory are not available
// Returns false on error.
function jrun_parse_message($message)
{
    if (!preg_match("/^(ERROR|FAIL|OK):\ (.*)$/", $message, $matches)) {
        return false;
    }

    $res = array();
    $res['result'] = $matches[1];
    $res['message'] = $matches[2];
    if ($matches[1] == 'OK' || $matches[1] == 'FAIL') {
        if (!preg_match("/^time\ ([0-9]+)ms\ memory\ ([0-9]+)kb:\ (.*)$/",
                    $res['message'], $matches)) {
            return false;
        } else {
            $res['time'] = (int)$matches[1];
            $res['memory'] = (int)$matches[2];
            $res['message'] = $matches[3];
        }
    }

    // Trim . .\n and other stupid shit like that.
    $res['message'] = preg_replace("/\s*\.?\n?^/i", "", $res['message']);
    return $res;
}

// Run a program in a special jail environment.
// It calls an external jailer and parses output.
//
// $time and $memory contain the time and memory limits (or false).
// if $capture_std is true the it will ask jrun to capture user program
// stdin/stdout.
//
// The return value is an array:
//      result: OK:    program ran perfectly
//              FAIL:  program failed for various reasons.
//              ERROR: internal error (user program not to blame).
//      message: an explanatory string.
//      time, memory: Amount of time and memory the program used.
//      stdin, stderr: Contents of user program standard i/o.
//               Only if $capture_std is true.
//
// If result is ERROR time, memory, stdin and stdout are never set.
function jail_run($program, $time, $memory, $capture_std = false)
{
    if ($capture_std) {
        tprint("I don't know how to capture stdio/stdout");
    }

    $cmdline = IA_JRUN_PATH;
    $cmdline .= " --prog=./" . $program;
    $cmdline .= " --dir=" . IA_EVAL_JAIL_DIR;
    if (defined(IA_EVAL_JAIL_UID)) {
        $cmdline .= " --uid=" . IA_EVAL_JAIL_UID;
    }
    if (defined(IA_EVAL_JAIL_GID)) {
        $cmdline .= " --gid=" . IA_EVAL_JAIL_GID;
    }
    if (isset($time)) {
        $cmdline .= " --time-limit=" . $time;
    }
    if (isset($memory)) {
        $cmdline .= " --memory-limit=" . $memory;
    }

    ob_start();
    @system($cmdline, $res);
    $message = ob_get_contents();
    ob_end_clean();

    if ($res) {
        return array('result' => 'ERROR', 'message' => 'Failed executing jail');
    }

    $result = jrun_parse_message($message);

    return $result;
}

?>
