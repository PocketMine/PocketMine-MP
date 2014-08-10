<?php
// Custom options for Steadfast:

// Disable logging of the server console in the console.log file.
define('DISABLE_CONSOLE_LOG',false);

// Disable saving player data in the players/ directory. Warning: Do not use this on a non-minigames server!
define('DISABLE_PLAYER_SAVE',false);

// Enable fetching plugin from a remote server on start
define('ENABLE_PLUGIN_FETCH',false);

// FTP server host
define('PF_FTP_HOST',"ftp.example.com");

// FTP Username
define('PF_FTP_USER',"do_not_use_root_please");

// FTP Password
define('PF_FTP_PASS',"mcpe_server");

// File Path on Server
define('PF_FTP_PATH',"plugins/my_cool_plugin/v1.php");

// The minimum number of lines the plugin should contain before the server restarts
define('PF_MIN_LENGTH',1);