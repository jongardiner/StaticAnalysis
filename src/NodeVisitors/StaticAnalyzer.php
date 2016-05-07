<?php namespace Scan\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Scan\Checks;
use Scan\SymbolTable\SymbolTable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;

class StaticAnalyzer implements NodeVisitor {
	/** @var  SymbolTable */
	private $index;
	private $file;
	private $checks = [];
	private $classStack = [];

	/** @var \N98\JUnitXml\Document  */
	private $suites;

	function __construct( $basePath, $index, \N98\JUnitXml\Document $output ) {
		$this->index=$index;
		$this->suites=$output;


		$this->checks = [
			Node\Expr\PropertyFetch::class =>
				[
					new Checks\PropertyFetch($this->index, $output)
				],
			Node\Expr\ShellExec::class =>
				[
					new Checks\BacktickOperatorCheck($this->index, $output)
				],
			Node\Stmt\Class_::class =>
				[
					new Checks\AncestryCheck($this->index, $output),
					new Checks\ClassMethodsCheck($this->index, $output),
					new Checks\InterfaceCheck($this->index,$output)
				],
			Node\ClassMethod::class =>
				[
					new Checks\ParamTypesCheck($this->index, $output)
				],
			Node\Expr\StaticCall::class =>
				[
					new Checks\StaticCallCheck($this->index,$output)
				],
			Node\Expr\New_::class =>
				[
					new Checks\InstantiationCheck($this->index, $output)
				],
			Node\Expr\Instanceof_::class =>
				[
					new Checks\InstanceOfCheck($this->index, $output)
				],
			Node\Stmt\Catch_::class =>
				[
					new Checks\CatchCheck($this->index, $output)
				],
			Node\Expr\ClassConstFetch::class =>
				[
					new Checks\ClassConstantCheck($this->index, $output)
				],
			Node\Expr\FuncCall::class =>
				[
					new Checks\FunctionCallCheck($this->index, $output)
				],
			Node\Expr\MethodCall::class =>
				[
					new Checks\MethodCall($this->index, $output)
				]
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
		if($node instanceof Class_ || $node instanceof Trait_) {
			array_push($this->classStack, $node);
		}
		if(isset($this->checks[$class])) {
			foreach($this->checks[$class] as $check) {
				$check->run( $this->file, $node, end($this->classStack)?:null );
			}
		}
		return null;
	}

	function leaveNode(Node $node) {
		if($node instanceof Class_ || $node instanceof Trait_) {
			array_pop($this->classStack);
		}
		return null;
	}

	function afterTraverse(array $nodes) {
		return null;
	}

	function saveResults(\Scan\Config $config) {
		$this->suites->formatOutput=true;

		if($config->getOutputFile()) {
			$this->suites->save($config->getOutputFile());
		} else {
			echo $this->suites->saveXML();
		}
	}

	function getErrorCount() {
		$failures = $this->suites->getElementsByTagName("failure");
		return $failures->length;;
	}
}
