<<?php
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
namespace pocketmine;
/**
 * This class must be extended by all custom threading classes
 */
abstract class Thread extends \Thread{
	public function start($options = PTHREADS_INHERIT_ALL){
		ThreadManager::getInstance()->add($this);
		if(!$this->isRunning() and !$this->isJoined() and !$this->isTerminated()){
			return parent::start($options);
		}
		return false;
	}
	/**
	 * Stops the thread using the best way possible. Try to stop it yourself before calling this.
	 */
	public function quit(){
		if($this->isRunning()){
			$this->kill();
			$this->detach();
		}elseif(!$this->isJoined()){
			if(!$this->isTerminated()){
				$this->join();
			}else{
				$this->kill();
				$this->detach();
			}
		}else{
			$this->detach();
		}
		ThreadManager::getInstance()->remove($this);
	}
}
