<?php

namespace Scan\Checks;

use Llaumgui\JunitXml\JunitXmlTestElement;
use Llaumgui\JunitXml\JunitXmlTestSuite;
use Scan\SymbolTable\SymbolTable;

abstract class BaseCheck {
	/** @var JunitXmlTestElement  */
	protected $case;

	/** @var SymbolTable */
	protected $symbolTable;

	function __construct(SymbolTable $symbolTable, JunitXmlTestSuite $suite) {
		$this->case = $suite->addTest( __CLASS__ );
		$this->symbolTable=$symbolTable;
	}

	function emitError($name, $message) {
		$this->case->addError($message, $name);
		echo "ERROR: $message\n";
	}

	abstract function run($fileName, $node);
}