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

// maximum file size for user-submitted files - solutions to tasks
define("IA_SUBMISSION_MAXSIZE", 256*1024);

// maximum avatar file-size
define("IA_AVATAR_MAXSIZE", 400*1024);

// Number of items in a RSS feed
define('IA_MAX_FEED_ITEMS', 15);
date_default_timezone_set('GMT');

// Constrains and default value for pager display_rows.
define('IA_PAGER_DEFAULT_DISPLAY_ENTRIES', 50);
define('IA_PAGER_MAX_DISPLAY_ENTRIES', 200);
define('IA_PAGER_MIN_DISPLAY_ENTRIES', 3);

// mail sender
define("IA_MAIL_SENDER_NO_REPLY", 'infoarena <no-reply@infoarena.ro>');

// Maximum number of recursive includes in the wiki.
define('IA_MAX_RECURSIVE_INCLUDES', 5);

// Image resampling
//  - constraints for image resampling
define("IMAGE_MAX_WIDTH", 800);
define("IMAGE_MAX_HEIGHT", 800);
//  - whether to enable the image cache (avoid resizing the same image twice)
define("IMAGE_CACHE_ENABLE", true);
//  - where to store image cache (resampled versions of the normal image attachments)
//    Feel free to empty the cache directory at any time
define("IMAGE_CACHE_DIR", IA_ROOT.'cache/');
//  - maximum directory size for image cache (bytes). When directory exceeds quota,
//    image resamples are not cached any more but computed & served on-the-fly
define("IMAGE_CACHE_QUOTA", 256 * 1024 * 1024); // (bytes please)

?>
