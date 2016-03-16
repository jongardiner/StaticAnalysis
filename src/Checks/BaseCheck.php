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
		$this->case = $suite->addTest();
		$this->case->setClassName( get_class($this) );
		$this->symbolTable=$symbolTable;
	}

	function incTests() {
		$this->case->incAssertions();
	}
	function emitError($name, $message) {
		$this->case->addFailure($message, $name);
		echo "ERROR: ".get_class($this).": $message\n";
	}

	abstract function run($fileName, $node);
}