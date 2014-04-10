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

/**
 * Various Utilities used around the code
 */
namespace pocketmine\utils;

/**
 * WARNING: This class is available on the PocketMine-MP Zephir project.
 * If this class is modified, remember to modify the PHP C extension.
 */
class Binary{
	const BIG_ENDIAN = 0x00;
	const LITTLE_ENDIAN = 0x01;


	/**
	 * Reads a 3-byte big-endian number
	 *
	 * @param $str
	 *
	 * @return mixed
	 */
	public static function readTriad($str){
		list(, $unpacked) = @unpack("N", "\x00" . $str);

		return $unpacked;
	}

	/**
	 * Writes a 3-byte big-endian number
	 *
	 * @param $value
	 *
	 * @return string
	 */
	public static function writeTriad($value){
		return substr(pack("N", $value), 1);
	}

	/**
	 * Writes a coded metadata string
	 * TODO: Replace and move this to entity
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public static function writeMetadata(array $data){
		$m = "";
		foreach($data as $bottom => $d){
			$m .= chr(($d[0] << 5) | ($bottom & 0x1F));
			switch($d[0]){
				case 0:
					$m .= self::writeByte($d[1]);
					break;
				case 1:
					$m .= self::writeLShort($d[1]);
					break;
				case 2:
					$m .= self::writeLInt($d[1]);
					break;
				case 3:
					$m .= self::writeLFloat($d[1]);
					break;
				case 4:
					$m .= self::writeLShort(strlen($d[1])) . $d[1];
					break;
				case 5:
					$m .= self::writeLShort($d[1][0]);
					$m .= self::writeByte($d[1][1]);
					$m .= self::writeLShort($d[1][2]);
					break;
				case 6:
					$m .= self::writeLInt($d[1][0]);
					$m .= self::writeLInt($d[1][1]);
					$m .= self::writeLInt($d[1][2]);
					break;
			}
		}
		$m .= "\x7f";

		return $m;
	}

	/**
	 * Reads a metadata coded string
	 * TODO: Change
	 *
	 * @param      $value
	 * @param bool $types
	 *
	 * @return array
	 */
	public static function readMetadata($value, $types = false){
		$offset = 0;
		$m = array();
		$b = ord($value{$offset});
		++$offset;
		while($b !== 127 and isset($value{$offset})){
			$bottom = $b & 0x1F;
			$type = $b >> 5;
			switch($type){
				case 0:
					$r = self::readByte($value{$offset});
					++$offset;
					break;
				case 1:
					$r = self::readLShort(substr($value, $offset, 2));
					$offset += 2;
					break;
				case 2:
					$r = self::readLInt(substr($value, $offset, 4));
					$offset += 4;
					break;
				case 3:
					$r = self::readLFloat(substr($value, $offset, 4));
					$offset += 4;
					break;
				case 4:
					$len = self::readLShort(substr($value, $offset, 2));
					$offset += 2;
					$r = substr($value, $offset, $len);
					$offset += $len;
					break;
				case 5:
					$r = array();
					$r[] = self::readLShort(substr($value, $offset, 2));
					$offset += 2;
					$r[] = ord($value{$offset});
					++$offset;
					$r[] = self::readLShort(substr($value, $offset, 2));
					$offset += 2;
					break;
				case 6:
					$r = array();
					for($i = 0; $i < 3; ++$i){
						$r[] = self::readLInt(substr($value, $offset, 4));
						$offset += 4;
					}
					break;

			}
			if($types === true){
				$m[$bottom] = array($r, $type);
			}else{
				$m[$bottom] = $r;
			}
			$b = ord($value{$offset});
			++$offset;
		}

		return $m;
	}
	/**
	 * Reads a byte boolean
	 *
	 * @param $b
	 *
	 * @return bool
	 */
	public static function readBool($b){
		return self::readByte($b, false) === 0 ? false : true;
	}

	/**
	 * Writes a byte boolean
	 *
	 * @param $b
	 *
	 * @return bool|string
	 */
	public static function writeBool($b){
		return self::writeByte($b === true ? 1 : 0);
	}

	/**
	 * Reads an unsigned/signed byte
	 *
	 * @param string $c
	 * @param bool $signed
	 *
	 * @return int
	 */
	public static function readByte($c, $signed = true){
		$b = ord($c{0});
		if($signed === true and ($b & 0x80) === 0x80){ //calculate Two's complement
			$b = -0x80 + ($b & 0x7f);
		}

		return $b;
	}

	/**
	 * Writes an unsigned/signed byte
	 *
	 * @param $c
	 *
	 * @return string
	 */
	public static function writeByte($c){
		if($c < 0 and $c >= -0x80){
			$c = 0xff + $c + 1;
		}

		return chr($c);
	}

	/**
	 * Reads a 16-bit signed/unsigned big-endian number
	 *
	 * @param      $str
	 * @param bool $signed
	 *
	 * @return int
	 */
	public static function readShort($str, $signed = true){
		$unpacked = @unpack("n", $str)[1];
		if($unpacked > 0x7fff and $signed === true){
			$unpacked -= 0x10000; // Convert unsigned short to signed short
		}

		return $unpacked;
	}

	/**
	 * Writes a 16-bit signed/unsigned big-endian number
	 *
	 * @param $value
	 *
	 * @return string
	 */
	public static function writeShort($value){
		if($value < 0){
			$value += 0x10000;
		}

		return pack("n", $value);
	}

	/**
	 * Reads a 16-bit signed/unsigned little-endian number
	 *
	 * @param      $str
	 * @param bool $signed
	 *
	 * @return int
	 */
	public static function readLShort($str, $signed = true){
		$unpacked = @unpack("v", $str)[1];
		if($unpacked > 0x7fff and $signed === true){
			$unpacked -= 0x10000; // Convert unsigned short to signed short
		}

		return $unpacked;
	}

	/**
	 * Writes a 16-bit signed/unsigned little-endian number
	 *
	 * @param $value
	 *
	 * @return string
	 */
	public static function writeLShort($value){
		if($value < 0){
			$value += 0x10000;
		}

		return pack("v", $value);
	}

	public static function readInt($str){
		$unpacked = @unpack("N", $str)[1];
		if($unpacked > 2147483647){
			$unpacked -= 4294967296;
		}

		return (int) $unpacked;
	}

	public static function writeInt($value){
		return pack("N", $value);
	}

	public static function readLInt($str){
		$unpacked = @unpack("V", $str)[1];
		if($unpacked >= 2147483648){
			$unpacked -= 4294967296;
		}

		return (int) $unpacked;
	}

	public static function writeLInt($value){
		return pack("V", $value);
	}

	public static function readFloat($str){
		return ENDIANNESS === self::BIG_ENDIAN ? @unpack("f", $str)[1] : @unpack("f", strrev($str))[1];
	}

	public static function writeFloat($value){
		return ENDIANNESS === self::BIG_ENDIAN ? pack("f", $value) : strrev(pack("f", $value));
	}

	public static function readLFloat($str){
		return ENDIANNESS === self::BIG_ENDIAN ? @unpack("f", strrev($str))[1] : @unpack("f", $str)[1];
	}

	public static function writeLFloat($value){
		return ENDIANNESS === self::BIG_ENDIAN ? strrev(pack("f", $value)) : pack("f", $value);
	}

	public static function printFloat($value){
		return preg_replace("/(\\.\\d+?)0+$/", "$1", sprintf("%F", $value));
	}

	public static function readDouble($str){
		return ENDIANNESS === self::BIG_ENDIAN ? @unpack("d", $str)[1] : @unpack("d", strrev($str))[1];
	}

	public static function writeDouble($value){
		return ENDIANNESS === self::BIG_ENDIAN ? pack("d", $value) : strrev(pack("d", $value));
	}

	public static function readLDouble($str){
		return ENDIANNESS === self::BIG_ENDIAN ? @unpack("d", strrev($str))[1] : @unpack("d", $str)[1];
	}

	public static function writeLDouble($value){
		return ENDIANNESS === self::BIG_ENDIAN ? strrev(pack("d", $value)) : pack("d", $value);
	}

	public static function readLong($x, $signed = true){
		$value = "0";
		for($i = 0; $i < 8; $i += 2){
			$value = bcmul($value, "65536", 0);
			$value = bcadd($value, self::readShort(substr($x, $i, 2), false), 0);
		}

		if($signed === true and bccomp($value, "9223372036854775807") == 1){
			$value = bcadd($value, "-18446744073709551616");
		}

		return $value;
	}

	public static function writeLong($value){
		$x = "";

		if(bccomp($value, "0") == -1){
			$value = bcadd($value, "18446744073709551616");
		}

		$x .= self::writeShort(bcmod(bcdiv($value, "281474976710656"), "65536"));
		$x .= self::writeShort(bcmod(bcdiv($value, "4294967296"), "65536"));
		$x .= self::writeShort(bcmod(bcdiv($value, "65536"), "65536"));
		$x .= self::writeShort(bcmod($value, "65536"));

		return $x;
	}

	public static function readLLong($str){
		return self::readLong(strrev($str));
	}

	public static function writeLLong($value){
		return strrev(self::writeLong($value));
	}

}