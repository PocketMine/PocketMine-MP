<?php

/*

           -
         /   \
      /         \
   /   PocketMine  \
/          MP         \
|\     @shoghicp     /|
|.   \           /   .|
| ..     \   /     .. |
|    ..    |    ..    |
|       .. | ..       |
\          |          /
   \       |       /
      \    |    /
         \ | /

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.


*/

define("ASYNC_CURL_GET", 1);
define("ASYNC_CURL_POST", 2);

class StackableArray{
	public $counter = 0;
	public function run(){}
}

class AsyncMultipleQueue extends Thread{
	public $input;
	public $output;
	public $stop;
	public function __construct(){
		$this->input = "";
		$this->output = "";
		$this->stop = false;
		$this->start();
	}
	
	private function get($len){
		$str = "";
		while(!isset($str{$len - 1})){
			$str .= $this->input{0};
			$this->input = (string) substr($this->input, 1);
		}
		return $str;
	}
	
	public function run(){
		while($this->stop === false){
			if(isset($this->input{5})){ //len 6 min
				$rID = Utils::readInt($this->get(4));
				switch(Utils::readShort($this->get(2), false)){
					case ASYNC_CURL_GET:
						$url = $this->get(Utils::readShort($this->get(2), false));
						$timeout = Utils::readShort($this->get(2));
						
						$res = (string) Utils::curl_get($url, $timeout);
						$this->lock();
						$this->output .= Utils::writeInt($rID).Utils::writeShort(ASYNC_CURL_GET).Utils::writeInt(strlen($res)).$res;
						$this->unlock();
						break;
					case ASYNC_CURL_POST:
						$url = $this->get(Utils::readShort($this->get(2), false));
						$timeout = Utils::readShort($this->get(2));
						$cnt = Utils::readShort($this->get(2), false);
						$d = array();
						for($c = 0; $c < $cnt; ++$c){
							$key = $this->get(Utils::readShort($this->get(2), false));
							$d[$key] = $this->get(Utils::readInt($this->get(4), false));
						}
						$res = (string) Utils::curl_post($url, $d, $timeout);
						$this->lock();
						$this->output .= Utils::writeInt($rID).Utils::writeShort(ASYNC_CURL_POST).Utils::writeInt(strlen($res)).$res;
						$this->unlock();
						break;
				}
			}
			usleep(10000);
		}
	}
}

class Async extends Thread{
	public function __construct($method, $params = array()){
		$this->method = $method;
		$this->params = $params;
		$this->result = null;
		$this->joined = false;
	}

	public function run(){
		if(($this->result=call_user_func_array($this->method, $this->params))){
			return true;
		}else{
			return false;
		}
	}

	public static function call($method, $params = array()){
		$thread = new Async($method, $params);
		if($thread->start()){
			return $thread;
		}
	}

	public function __toString(){
		if(!$this->joined){
			$this->joined = true;
			$this->join();
		}

		return $this->result;
	}
}