<?php
namespace pocketmine\level\generator {

	const VERSION = "Alpha_1.4dev";
	const API_VERSION = "1.0.0";
	const CODENAME = "絶好(Zekkou)ケーキ(Cake)";
	const MINECRAFT_VERSION = "v0.9.0 alpha";
	const PHP_VERSION = "5.5";
	@define("pocketmine\\PATH", \getcwd() . DIRECTORY_SEPARATOR);

	if(!class_exists("SplClassLoader", false)){
		require_once(\pocketmine\PATH . "src/spl/SplClassLoader.php");
	}

	$autoloader = new \SplClassLoader();
	$autoloader->setMode(\SplAutoloader::MODE_DEBUG);
	$autoloader->add("pocketmine", [
		\pocketmine\PATH . "src"
	]);

	$autoloader->register(true);

	$opts = getopt("", array("enable-ansi", "disable-ansi", "data:", "plugins:", "no-wizard"));

	define("pocketmine\\DATA", isset($opts["data"]) ? realpath($opts["data"]) . DIRECTORY_SEPARATOR : \getcwd() . DIRECTORY_SEPARATOR);

	$logger = new \DumbLogger();

	$sock = socket_create(AF_UNIX, SOCK_STREAM, 0);
	socket_connect($sock, $argv[1]);
	socket_set_block($sock); //IMPORTANT!
	@socket_set_option($sock, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 2);
	@socket_set_option($sock, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 2);

	$generationManager = new GenerationManager($sock, $logger, $autoloader);
}
