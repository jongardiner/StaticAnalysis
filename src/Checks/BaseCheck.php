<?php

namespace Scan\Checks;

use N98\JUnitXml;
use PhpParser\Node;
use Scan\Scope;
use Scan\SymbolTable\SymbolTable;
use Scan\Output\OutputInterface;

abstract class BaseCheck {

	/** @var SymbolTable */
	protected $symbolTable;

	/** @var \Scan\Output\OutputInterface  */
	private $doc;

	function __construct(SymbolTable $symbolTable, OutputInterface $doc) {
		$this->symbolTable=$symbolTable;
		$this->doc=$doc;
	}

	function emitError($file, \PhpParser\Node $node, $class, $message="") {
		return $this->doc->emitError(get_class($this), $file, $node->getLine(), $class, $message);
	}

	function incTests() {
		$this->doc->incTests();
	}

	/**
	 * @return string[]
	 */
	abstract function getCheckNodeTypes();

	abstract function run($fileName, $node, Node\Stmt\ClassLike $inside=null, Scope $scope=null);
}