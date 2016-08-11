<?php
namespace Guardrail\Abstractions;

class FunctionLikeParameter {
	private $type;
	private $name;
	private $optional;
	private $reference;

	function __construct($type, $name, $optional, $reference) {
		$this->type = $type;
		$this->name = $name;
		$this->optional = $optional;
		$this->reference = $reference;
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

	function isReference() {
		return $this->reference;
	}
}