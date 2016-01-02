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

abstract class ExceptionHandler{
	/**
	 * @param $errno
	 * @param $errstr
	 * @param $errfile
	 * @param $errline
	 *
	 * @return \Exception
	 */
	public static function handler($errno, $errstr, $errfile, $errline){
		if(error_reporting() === 0){
			return false;
		}

		$exception = null;

		if(self::errorStarts($errstr, "Undefined offset: ")){
			$exception = new ArrayOutOfBoundsException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Undefined index: ")){
			$exception = new ArrayOutOfBoundsException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Uninitialized string offset: ")){
			$exception = new StringOutOfBoundsException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Uninitialized string offset: ")){
			$exception = new StringOutOfBoundsException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Undefined variable: ")){
			$exception = new UndefinedVariableException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Undefined property: ")){
			$exception = new UndefinedPropertyException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Illegal string offset ")){
			$exception = new InvalidKeyException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Illegal offset type: ")){
			$exception = new InvalidKeyException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Use of undefined constant ")){
			$exception = new UndefinedConstantException($errstr, $errno);
		}elseif(self::errorStarts($errstr, "Accessing static property ")){
			$exception = new InvalidStateException($errstr, $errno);
		}elseif(strpos($errstr, " could not be converted to ") !== false){
			$exception = new ClassCastException($errstr, $errno);
		}elseif(
			$errstr === "Trying to get property of non-object"
			or $errstr === "Attempt to assign property of non-object"
		){
			$exception = new InvalidStateException($errstr, $errno);
		}elseif(
			strpos($errstr, " expects parameter ") !== false
			or strpos($errstr, " must be ") !== false
		){
			$exception = new InvalidArgumentException($errstr, $errno);
		}elseif(
			self::errorStarts($errstr, "Wrong parameter count for ")
			or self::errorStarts($errstr, "Missing argument 1 for ")
			or preg_match('/^.*\\(\\) expects [a-z]{1,} [0-9]{1,} parameters?, [0-9]{1,} given$/', $errstr) > 0
		){
			$exception = new InvalidArgumentCountException($errstr, $errno);
		}

		if($exception === null){
			$exception = new RuntimeException($errstr, $errno);
		}

		$er = new ReflectionObject($exception);
		$file = $er->getProperty("file");
		$file->setAccessible(true);
		$file->setValue($exception, $errfile);
		$line = $er->getProperty("line");
		$line->setAccessible(true);
		$line->setValue($exception, $errline);

		throw $exception;
	}

	private static function errorStarts($error, $str){
		return substr($error, 0, strlen($str)) === $str;
	}
}