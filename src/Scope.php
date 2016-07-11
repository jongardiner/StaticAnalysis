<?php
/**
 * Created by PhpStorm.
 * User: jgardiner
 * Date: 6/27/16
 * Time: 4:42 PM
 */

namespace Scan;


class Scope
{
	const UNDEFINED = "!0";
	const MIXED_TYPE = "!1";
	const SCALAR_TYPE = "!2";

	private $vars = [];

	function setVarType($name, $type) {
		$this->vars[$name]=$type;
	}

	function getVarType($name) {
		if(isset($this->vars[$name])) {
			return $this->vars[$name];
		}
		return isset($this->vars[$name]) ? $this->vars[$name] : self::UNDEFINED;
	}
}