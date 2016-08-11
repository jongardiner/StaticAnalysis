<?php
namespace Guardrail\Exceptions;

class UnknownTraitException extends \Exception {


	/**
	 * UnknownTraitException constructor.
	 */
	public function __construct($name, $file, $line) {
		parent::__construct("Unknown trait $name imported in file $file on line $line");
	}
}