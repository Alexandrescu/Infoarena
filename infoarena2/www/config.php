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

// maximum file size for user-submitted files - solutions to tasks
define("IA_SUBMISSION_MAXSIZE", 256*1024);

// maximum avatar file-size
define("IA_AVATAR_MAXSIZE", 20*1024);

// maximum avatar dimensions
define("IA_AVATAR_WIDTH", 100);
define("IA_AVATAR_HEIGHT", 100);

// Number of news items to place on one page.
define('IA_MAX_NEWS', 3);

// Number of items in a RSS feed
define('IA_MAX_FEED_ITEMS', 15);

// mail sender
define("IA_MAIL_SENDER_NO_REPLY", 'info-arena <no-reply@infoarena.ro>');

// Maximum number of recursive includes in the wiki.
define('IA_MAX_RECURSIVE_INCLUDES', 5);

// Boolean whether to display SQL queries and helpful debug messages
// when encountering a SQL error.
//
// :WARNING: Disable this option when uploading the website to a production
// environment! Telling poeple too much about your database is rarely a good
// thing.
define("IA_SQL_TRACE", true);

?>
