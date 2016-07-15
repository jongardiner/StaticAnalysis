<?php
namespace Scan\Abstractions;

class FunctionLikeParameter {
	private $type;
	private $name;
	private $optional;

	function __construct($type, $name, $optional) {
		$this->type = $type;
		$this->name = $name;
		$this->optional = $optional;
	}

	function getType() {
		return $this->type;
	}

	function getName() {
		return $this->name;
	}

	function isOptional() {
		return $this->optional;
	}
}