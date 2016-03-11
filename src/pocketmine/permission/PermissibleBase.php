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

namespace pocketmine\permission;

use pocketmine\event\Timings;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\PluginException;

class PermissibleBase implements Permissible{
	/** @var ServerOperator */
	private $opable = null;

	/** @var Permissible */
	private $parent = null;

	/**
	 * @var PermissionAttachment[]
	 */
	private $attachments = [];

	/**
	 * @var PermissionAttachmentInfo[]
	 */
	private $permissions = [];

	/**
	 * @param ServerOperator $opable
	 */
	public function __construct(ServerOperator $opable){
		$this->opable = $opable;
		if($opable instanceof Permissible){
			$this->parent = $opable;
		}
	}

	public function __destruct(){
		$this->parent = null;
		$this->opable = null;
	}

	/**
	 * @return bool
	 */
	public function isOp(){
		if($this->opable === null){
			return false;
		}else{
			return $this->opable->isOp();
		}
	}

	/**
	 * @param bool $value
	 *
	 * @throws \Exception
	 */
	public function setOp($value){
		if($this->opable === null){
			throw new \LogicException("Cannot change op value as no ServerOperator is set");
		}else{
			$this->opable->setOp($value);
		}
	}

	/**
	 * @param Permission|string $name
	 *
	 * @return bool
	 */
	public function isPermissionSet($name){
		return isset($this->permissions[$name instanceof Permission ? $name->getName() : $name]);
	}

	/**
	 * @param Permission|string $name
	 *
	 * @return bool
	 */
	public function hasPermission($name){
		if($name instanceof Permission){
			$name = $name->getName();
		}

		if($this->isPermissionSet($name)){
			return $this->permissions[$name]->getValue();
		}

		if(($perm = Server::getInstance()->getPluginManager()->getPermission($name)) !== null){
			$perm = $perm->getDefault();

			return $perm === Permission::DEFAULT_TRUE or ($this->isOp() and $perm === Permission::DEFAULT_OP) or (!$this->isOp() and $perm === Permission::DEFAULT_NOT_OP);
		}else{
			return Permission::$DEFAULT_PERMISSION === Permission::DEFAULT_TRUE or ($this->isOp() and Permission::$DEFAULT_PERMISSION === Permission::DEFAULT_OP) or (!$this->isOp() and Permission::$DEFAULT_PERMISSION === Permission::DEFAULT_NOT_OP);
		}

	}

	/**
	 * //TODO: tick scheduled attachments
	 *
	 * @param Plugin $plugin
	 * @param string $name
	 * @param bool   $value
	 *
	 * @return PermissionAttachment
	 *
	 * @throws PluginException
	 */
	public function addAttachment(Plugin $plugin, $name = null, $value = null){
		if($plugin === null){
			throw new PluginException("Plugin cannot be null");
		}elseif(!$plugin->isEnabled()){
			throw new PluginException("Plugin " . $plugin->getDescription()->getName() . " is disabled");
		}

		$result = new PermissionAttachment($plugin, $this->parent !== null ? $this->parent : $this);
		$this->attachments[spl_object_hash($result)] = $result;
		if($name !== null and $value !== null){
			$result->setPermission($name, $value);
		}

		$this->recalculatePermissions();

		return $result;
	}

	/**
	 * @param PermissionAttachment $attachment
	 *
	 * @throws \Exception
	 */
	public function removeAttachment(PermissionAttachment $attachment){
		if($attachment === null){
			throw new \InvalidStateException("Attachment cannot be null");
		}

		if(isset($this->attachments[spl_object_hash($attachment)])){
			unset($this->attachments[spl_object_hash($attachment)]);
			if(($ex = $attachment->getRemovalCallback()) !== null){
				$ex->attachmentRemoved($attachment);
			}

			$this->recalculatePermissions();

		}

	}

	public function recalculatePermissions(){
		Timings::$permissibleCalculationTimer->startTiming();

		$this->clearPermissions();
		$defaults = Server::getInstance()->getPluginManager()->getDefaultPermissions($this->isOp());
		Server::getInstance()->getPluginManager()->subscribeToDefaultPerms($this->isOp(), $this->parent !== null ? $this->parent : $this);

		foreach($defaults as $perm){
			$name = $perm->getName();
			$this->permissions[$name] = new PermissionAttachmentInfo($this->parent !== null ? $this->parent : $this, $name, null, true);
			Server::getInstance()->getPluginManager()->subscribeToPermission($name, $this->parent !== null ? $this->parent : $this);
			$this->calculateChildPermissions($perm->getChildren(), false, null);
		}

		foreach($this->attachments as $attachment){
			$this->calculateChildPermissions($attachment->getPermissions(), false, $attachment);
		}

		Timings::$permissibleCalculationTimer->stopTiming();
	}

	public function clearPermissions(){
		foreach(array_keys($this->permissions) as $name){
			Server::getInstance()->getPluginManager()->unsubscribeFromPermission($name, $this->parent !== null ? $this->parent : $this);
		}

		Server::getInstance()->getPluginManager()->unsubscribeFromDefaultPerms(false, $this->parent !== null ? $this->parent : $this);
		Server::getInstance()->getPluginManager()->unsubscribeFromDefaultPerms(true, $this->parent !== null ? $this->parent : $this);

		$this->permissions = [];
	}

	/**
	 * @param bool[]               $children
	 * @param bool                 $invert
	 * @param PermissionAttachment $attachment
	 */
	private function calculateChildPermissions(array $children, $invert, $attachment){
		foreach($children as $name => $v){
			$perm = Server::getInstance()->getPluginManager()->getPermission($name);
			$value = ($v xor $invert);
			$this->permissions[$name] = new PermissionAttachmentInfo($this->parent !== null ? $this->parent : $this, $name, $attachment, $value);
			Server::getInstance()->getPluginManager()->subscribeToPermission($name, $this->parent !== null ? $this->parent : $this);

			if($perm instanceof Permission){
				$this->calculateChildPermissions($perm->getChildren(), !$value, $attachment);
			}
		}
	}

	/**
	 * @return PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions(){
		return $this->permissions;
	}
}
