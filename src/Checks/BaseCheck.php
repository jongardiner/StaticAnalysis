<?php

namespace Scan\Checks;

use N98\JUnitXml;
use PhpParser\Node;
use Scan\Scope;
use Scan\SymbolTable\SymbolTable;
use Scan\Output\OutputInterface;

abstract class BaseCheck {
	const TYPE_SECURITY_BACKTICK="Standard.Security.Backtick";
	const TYPE_UNKNOWN_CLASS="Standard.Unknown.Class";
	const TYPE_UNKNOWN_CLASS_CONSTANT="Standard.Unknown.Constant.Class";
	const TYPE_UNKNOWN_GLOBAL_CONSTANT="Standard.Unknown.Constant.Global";
	const TYPE_UNKNOWN_METHOD="Standard.Unknown.ClassMethod";
	const TYPE_UNKNOWN_FUNCTION="Standard.Unknown.Function";
	const TYPE_INHERITANCE="Standard.Inheritance";
	const TYPE_PHP7_INHERITANCE="Standard.Php7.Inheritance";
	const TYPE_INCORRECT_STATIC_CALL="Standard.Incorrect.Static";
	const TYPE_INCORRECT_DYNAMIC_CALL="Standard.Incorrect.Dynamic";
	const TYPE_SCOPE_ERROR="Standard.Scope";
	const TYPE_SIGNATURE_COUNT="Standard.Signature.Count";
	const TYPE_SIGNATURE_COUNT_EXCESS="Standard.Signature.Count.Excess";
	const TYPE_SIGNATURE_TYPE="Standard.Signature.Type";
	const TYPE_UNIMPLEMENTED_METHOD="Standard.Unimplemented.Method";
	const TYPE_MISSING_BREAK="Standard.Missing.Break";
	const TYPE_PARSE_ERROR="Standard.Parse.Error";

	/** @var SymbolTable */
	protected $symbolTable;

	/** @var \Scan\Output\OutputInterface  */
	private $doc;

	function __construct(SymbolTable $symbolTable, OutputInterface $doc) {
		$this->symbolTable=$symbolTable;
		$this->doc=$doc;
	}

	function emitError($file, \PhpParser\Node $node, $class, $message="") {
		return $this->emitErrorOnLine($file, $node->getLine(), $class, $message);
	}

	function emitErrorOnLine($file, $lineNumber, $class, $message="") {
		return $this->doc->emitError(get_class($this), $file, $lineNumber, $class, $message);
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