<?php

/*
 * PocketMine Standard PHP Library
 * Copyright (C) 2014 PocketMine Team <https://github.com/PocketMine/PocketMine-SPL>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

class ThreadedFactory{
	/** @var ThreadedFactory */
	protected static $instance;
	/** @var \Threaded[] */
	protected $threadedList = [];

	protected function __construct(){
		global $threadedFactoryInstance;
		$threadedFactoryInstance = $this;
		self::$instance = $this;
	}

	/**
	 * @return ThreadedFactory
	 */
	public static function getInstance(){
		if(self::$instance === null){
			global $threadedFactoryInstance;
			if($threadedFactoryInstance instanceof ThreadedFactory){
				self::$instance = $threadedFactoryInstance;
			}else{
				new ThreadedFactory();
			}
		}

		return self::$instance;
	}

	/**
	 * @param \Threaded $class
	 * @param ...$arguments
	 *
	 * @return \Threaded
	 */
	public static function create($class = \Threaded::class, ...$arguments){
		/** @var \Threaded $threaded */
		$threaded = new $class(...$arguments);
		self::getInstance()->threadedList[spl_object_hash($threaded)] = $threaded;
		return $threaded;
	}
	
	public static function destroy(\Threaded $threaded){
		$instance = self::getInstance();
		if(isset($instance->threadedList[$hash = spl_object_hash($threaded)])){
			$threaded->synchronized(function(\Threaded $t){
				$t->notify();
			}, $threaded);
			unset($instance->threadedList[$hash]);
			return true;
		}
		return false;
	}

	/**
	 * @return \Threaded[]
	 */
	public static function all(){
		return self::getInstance()->threadedList;
	}
}
