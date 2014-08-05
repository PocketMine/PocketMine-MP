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

// Whether or not to load hexadecimal versions of world files in server RAM to make terrain sending faster.
define('LOAD_OCHUNKS_IN_RAM',true);

// The maximum number of chunks to cache per world. 256 chunks / world. Larger cache = more ram, faster chunk sending
define('MAX_OCHUNKS_PER_LEVEL',128);

// Force chunk cache to generate if does not exist on level load.
define('FORCE_OCHUNK_GEN_ON_LEVEL_LOAD',true);