<?php
/**
 * This file contains configuration settings specific for the infoarena
 * WEBSITE.
 *
 * Please note that the "big" configuration file (residing one directory up)
 * is meant to keep settings that are common accross all infoarena
 * applications.
 * 
 * This file has some decent defaults.
 */

// maximum attachment size for wiki pages
define("IA_ATTACH_MAXSIZE", 20*1024*1024);

// directory where to upload files AND from where users download them
define("IA_ATTACH_DIR", "/tmp/");

// maximum avatar file-size
define("IA_AVATAR_MAXSIZE", 20*1024);

// maximum avatar dimensions
define("IA_AVATAR_WIDTH", 100);
define("IA_AVATAR_HEIGHT", 100);


// Boolean whether to display SQL queries and helpful debug messages
// when encountering a SQL error.
//
// :WARNING: Disable this option when uploading the website to a production
// environment! Telling poeple too much about your database is rarely a good
// thing.
define("IA_SQL_TRACE", true);

?>
