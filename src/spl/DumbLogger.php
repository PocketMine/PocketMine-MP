<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

class DumbLogger implements Logger{

	/**
	 * System is unusable
	 *
	 * @param string $message
	 */
	public function emergency($message) {
		log("Emergency", $message);
	}

	/**
	 * Action must me taken immediately
	 *
	 * @param string $message
	 */
	public function alert($message) {
		log("Alert", $message);
	}
	/**
	 * Critical conditions
	 *
	 * @param string $message
	 */
	public function critical($message) {
		log("Critical", $message);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 */
	public function error($message) {
		log("Error", $message);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 */
	public function warning($message) {
		log("Warning", $message);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 */
	public function notice($message) {
		log("Notice", $message);
	}

	/**
	 * Inersting events.
	 *
	 * @param string $message
	 */
	public function info($message) {
		log("Info", $message);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 */
	public function debug($message) {
		log("Debug", $message);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed  $level
	 * @param string $message
	}
	 */
	public function log($level, $message) {
		echo($level . ": " . $message . "\n");
	}
}
