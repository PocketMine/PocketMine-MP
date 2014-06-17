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

namespace pocketmine\inventory;

interface TransactionGroup{

	/**
	 * @return float
	 */
	function getCreationTime();

	/**
	 * @return Transaction[]
	 */
	function getTransactions();

	/**
	 * @return Inventory[]
	 */
	function getInventories();

	/**
	 * @param Transaction $transaction
	 */
	function addTransaction(Transaction $transaction);

	/**
	 * @return bool
	 */
	function canExecute();

	/**
	 * @return bool
	 */
	function execute();

	/**
	 * @return bool
	 */
	function hasExecuted();

}