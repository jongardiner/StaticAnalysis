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
	const NO_TYPE = "";
	private $vars = [];

	function addVarType($name, $type) {
		$this->vars[$name]=$type;
	}

	function getVarType($name) {
		if(isset($this->vars[$name])) {
			return $this->vars[$name];
		}
		return isset($this->vars[$name]) ? $this->vars[$name] : "";
	}
}