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

namespace pocketmine\plugin;

use LogLevel;
use pocketmine\Server;

class PluginLogger implements \AttachableLogger{

	private $pluginName;

	/** @var \LoggerAttachment[] */
	private $attachments = [];

	public function addAttachment(\LoggerAttachment $attachment){
		$this->attachments[spl_object_hash($attachment)] = $attachment;
	}

	public function removeAttachment(\LoggerAttachment $attachment){
		unset($this->attachments[spl_object_hash($attachment)]);
	}

	public function removeAttachments(){
		$this->attachments = [];
	}

	public function getAttachments(){
		return $this->attachments;
	}

	/**
	 * @param Plugin $context
	 */
	public function __construct(Plugin $context){
		$prefix = $context->getDescription()->getPrefix();
		$this->pluginName = $prefix != null ? "[$prefix] " : "[" . $context->getDescription()->getName() . "] ";
	}

	public function emergency($message){
		$this->log(LogLevel::EMERGENCY, $message);
	}

	public function alert($message){
		$this->log(LogLevel::ALERT, $message);
	}

	public function critical($message){
		$this->log(LogLevel::CRITICAL, $message);
	}

	public function error($message){
		$this->log(LogLevel::ERROR, $message);
	}

	public function warning($message){
		$this->log(LogLevel::WARNING, $message);
	}

	public function notice($message){
		$this->log(LogLevel::NOTICE, $message);
	}

	public function info($message){
		$this->log(LogLevel::INFO, $message);
	}

	public function debug($message){
		$this->log(LogLevel::DEBUG, $message);
	}

	public function log($level, $message){
		Server::getInstance()->getLogger()->log($level, $this->pluginName . $message);
		foreach($this->attachments as $attachment){
			$attachment->log($level, $message);
		}
	}

        public function logException(\Throwable $e, $trace = null){
		if($trace === null){
			$trace = $e->getTrace();
		}
		$errstr = $e->getMessage();
		$errfile = $e->getFile();
		$errno = $e->getCode();
		$errline = $e->getLine();
		$errorConversion = [
			0 => "EXCEPTION",
			E_ERROR => "E_ERROR",
			E_WARNING => "E_WARNING",
			E_PARSE => "E_PARSE",
			E_NOTICE => "E_NOTICE",
			E_CORE_ERROR => "E_CORE_ERROR",
			E_CORE_WARNING => "E_CORE_WARNING",
			E_COMPILE_ERROR => "E_COMPILE_ERROR",
			E_COMPILE_WARNING => "E_COMPILE_WARNING",
			E_USER_ERROR => "E_USER_ERROR",
			E_USER_WARNING => "E_USER_WARNING",
			E_USER_NOTICE => "E_USER_NOTICE",
			E_STRICT => "E_STRICT",
			E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
			E_DEPRECATED => "E_DEPRECATED",
			E_USER_DEPRECATED => "E_USER_DEPRECATED",
		];
		if($errno === 0){
			$type = LogLevel::CRITICAL;
		}else{
			$type = ($errno === E_ERROR or $errno === E_USER_ERROR) ? LogLevel::ERROR : (($errno === E_USER_WARNING or $errno === E_WARNING) ? LogLevel::WARNING : LogLevel::NOTICE);
		}
		$errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
		if(($pos = strpos($errstr, "\n")) !== false){
			$errstr = substr($errstr, 0, $pos);
		}
		$errfile = \pocketmine\cleanPath($errfile);
		$this->log($type, get_class($e) . ": \"$errstr\" ($errno) in \"$errfile\" at line $errline");
		foreach(@\pocketmine\getTrace(1, $trace) as $i => $line){
			$this->debug($line);
		}
	}
}
