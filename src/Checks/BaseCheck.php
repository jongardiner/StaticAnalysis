<?php

namespace Scan\Checks;

use N98\JUnitXml;
use PhpParser\Node;
use Scan\Scope;
use Scan\SymbolTable\SymbolTable;

abstract class BaseCheck {
	/** @var JUnitXml\TestSuiteElement */
	protected $suite;

	/** @var SymbolTable */
	protected $symbolTable;

	private $files;

	function __construct(SymbolTable $symbolTable, JUnitXml\Document $doc, $emitErrors=false) {
		$this->symbolTable=$symbolTable;
		$this->suite=$doc->addTestSuite();
		$this->suite->setName(get_class($this));
		$this->emitErrors=$emitErrors;
	}

	function incTests() {
		//$this->suite->addTestCase();
	}

	function emitError($fileName, Node $node, $name, $message="") {
		if(!isset($this->files[$fileName])) {
			$case=$this->suite->addTestCase();
			$case->setName($fileName);
			$case->setClassname( get_class($this) );
			$this->files[$fileName]=$case;
		} else {
			$case=$this->files[$fileName];
		}

		$lineNumber = $node->getLine();
		$case->addFailure($message . " on line ".$lineNumber, $name);
		if($this->emitErrors) {
			echo "E";
		}
		//echo "ERROR: $fileName $lineNumber: $message\n";

	}

	abstract function run($fileName, $node, Node\Stmt\ClassLike $inside=null, Scope $scope=null);
}