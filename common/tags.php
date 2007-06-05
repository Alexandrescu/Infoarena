<?php

require_once(IA_ROOT_DIR."common/db/db.php");
require_once(IA_ROOT_DIR."common/db/tags.php");

function tag_split($tag_data) {
    $tags = explode(",", trim($tag_data));
    $result = array();
    foreach ($tags as &$tag) {
        $tag = trim($tag);
        if (strlen($tag) > 0) {
            $result[] = $tag;
        }
    }
    return $result;
}

function tag_validate($data, &$errors) {
    $tags = getattr($data, 'tags');
    if (is_null($tags)) {
        return;
    }
    foreach ($tags as $tag) {
        if (!is_tag_name($tag)) {
            $errors['tags'] = "Cel putin un tag este gresit";
            return;
        }
    }
}

function tag_build_list($obj, $obj_id) {
    $tag_list = tag_get_names($obj, $obj_id);
    $tag_names = array();
    foreach ($tag_list as $tag) {
        $tag_names[] = $tag['tag_name'];
    }
    return implode(", ", $tag_names);
}

function tag_update($obj, $obj_id, $tag_names) {
    tag_clear($obj, $obj_id);
    foreach ($tag_names as $tag_name) {
        $tag_id = tag_assign_id($tag_name);
        tag_add($obj, $obj_id, $tag_id);
    }
}

?>
