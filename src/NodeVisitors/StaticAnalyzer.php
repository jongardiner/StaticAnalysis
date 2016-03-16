<?php namespace Scan\NodeVisitors;

use Llaumgui\JunitXml\JunitXmlTestSuites;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use Scan\Checks;
use Scan\SymbolTable\SymbolTable;

class StaticAnalyzer implements NodeVisitor {
	/** @var  SymbolTable */
	private $index;
	private $file;
	private $checks = [];

	/** @var JunitXmlTestSuites  */
	private $suites;

	function __construct( $basePath, $index ) {
		$this->index=$index;

		$this->suites = new JunitXmlTestSuites('My testsuites');
		$suite=$this->suites->addTestSuite("Static Analysis");

		$this->checks = [
			Node\Stmt\Class_::class =>
				[
					new Checks\AncestryCheck($this->index, $suite),
					new Checks\ClassMethodsCheck($this->index, $suite),
					new Checks\InterfaceCheck($this->index,$suite)
				],
			Node\ClassMethod::class =>
				[ new Checks\ParamTypesCheck($this->index, $suite) ],
			Node\Expr\StaticCall::class =>
				[ new Checks\StaticCallCheck($this->index,$suite) ],
			Node\Expr\New_::class =>
				[ new Checks\InstantiationCheck($this->index, $suite) ],
			Node\Stmt\Catch_::class =>
				[ new Checks\CatchCheck($this->index, $suite) ],
			Node\Expr\ClassConstFetch::class =>
				[ new Checks\ClassConstantCheck($this->index, $suite) ]
		];
	}
	function beforeTraverse(array $nodes) {
		return null;
	}

	function setFile($name) {
		$this->file=$name;
	}

	function enterNode(Node $node) {
		$class=get_class($node);
		if(isset($this->checks[$class])) {
			foreach($this->checks[$class] as $check) {
				$check->run( $this->file, $node );
			}
		}
		return null;
	}

	function leaveNode(Node $node) {
		return null;
	}

	function afterTraverse(array $nodes) {
		return null;
	}

	function getResults() {
		return $this->suites->getXml();
	}
}
