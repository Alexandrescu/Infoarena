<?php

require_once(IA_ROOT."www/macros/macros.php");
require_once(IA_ROOT."www/wiki/MyTextile.php");

// This processes a big chunk of wiki-formatted text and returns html.
function wiki_process_text($content) {
    $options = array(
            'disable_html' => true,
            'disable_filters' => true,
            'trim_spaces' => false,
            'preserve_spaces' => true,
    );
    $weaver = new MyTextile($options);
    return $weaver->process($content);
}

// This is just like wiki_process_text, but it's meant for recursive calling.
// You should use this from macros that include other text blocks.
//
// This returns a html block. That html block can be an error div.
function wiki_process_text_recursive($content) {
    // This uses some black static magic.
    // include_count is the number of recursions in this function.
    // When include_count reaches the maximum level then we set
    // $include_stop to true and quickly kill the whole stack.
    // At the end of the stack rewind we return an error message
    // and set $include_stop
    static $include_count = 0;
    static $include_stop = false;
    ++$include_count;

    if ($include_count > IA_MAX_RECURSIVE_INCLUDES) {
        // Start unwinding.
        $include_stop = true;
        --$include_count;
        //echo "hit maximum recursion $include_count <br />";
        return;
    }
    //echo "going in level $include_count $args[page]<br />";

    //echo "calling wiki <br />";
    $res = wiki_process_text($content);
    //echo "done calling wiki <br />";

    --$include_count;
    // Unwind
    if ($include_stop) {
        if ($include_count == 0) {
            // Stop unwinding. This is the first include.
            $include_stop = false;
            return make_error_div("Prea multe include-uri recursive");
        } else {
            return;
        }
    }
    return $res;
}

?>
