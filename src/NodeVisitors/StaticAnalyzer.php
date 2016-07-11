<?php namespace Scan\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Scan\Checks;
use Scan\Scope;
use Scan\SymbolTable\SymbolTable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;

class StaticAnalyzer implements NodeVisitor {
	/** @var  SymbolTable */
	private $index;
	private $file;
	private $checks = [];
	private $classStack = [];
	private $scopeStack = [];

	/** @var \N98\JUnitXml\Document  */
	private $suites;

	function __construct( $basePath, $index, \N98\JUnitXml\Document $output, $config ) {
		$this->index=$index;
		$this->suites=$output;

		$emitErrors = $config->getOutputLevel()==1;

		$this->checks = [
			Node\Expr\ConstFetch::class=>
				[

			//		new Checks\DefinedConstantCheck($this->index, $output, $emitErrors)

				],

			Node\Expr\PropertyFetch::class =>
				[
					new Checks\PropertyFetch($this->index, $output, $emitErrors)
				],
			Node\Expr\ShellExec::class =>
				[
			//		new Checks\BacktickOperatorCheck($this->index, $output, $emitErrors)
				],
			Node\Stmt\Class_::class =>
				[
					new Checks\AncestryCheck($this->index, $output, $emitErrors),
					new Checks\ClassMethodsCheck($this->index, $output, $emitErrors),
					new Checks\InterfaceCheck($this->index,$output, $emitErrors)
				],
			Node\Stmt\ClassMethod::class =>
				[
					new Checks\ParamTypesCheck($this->index, $output, $emitErrors)
				],
			Node\Expr\StaticCall::class =>
				[
					new Checks\StaticCallCheck($this->index,$output, $emitErrors)
				],
			Node\Expr\New_::class =>
				[
					new Checks\InstantiationCheck($this->index, $output, $emitErrors)
				],
			Node\Expr\Instanceof_::class =>
				[
					new Checks\InstanceOfCheck($this->index, $output, $emitErrors)
				],
			Node\Stmt\Catch_::class =>
				[
					new Checks\CatchCheck($this->index, $output, $emitErrors)
				],
			Node\Expr\ClassConstFetch::class =>
				[
					new Checks\ClassConstantCheck($this->index, $output, $emitErrors)
				],
			Node\Expr\FuncCall::class =>
				[
					new Checks\FunctionCallCheck($this->index, $output, $emitErrors)
				],
			Node\Expr\MethodCall::class =>
				[
					new Checks\MethodCall($this->index, $output, $emitErrors)
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
		if($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
			$this->pushFunctionScope($node);
		}
		if(isset($this->checks[$class])) {
			foreach($this->checks[$class] as $check) {
				$check->run( $this->file, $node, end($this->classStack)?:null, end($this->scopeStack)?:null );
			}
		}
		return null;
	}

	function pushFunctionScope(Node\FunctionLike $func) {
		$scope=new Scope();
		foreach($func->getParams() as $param) {
			echo "Adding ".$param->name." to scope\n";
			$scope->addVarType(strval($param->name), strval($param->type));
		}
		array_push($this->scopeStack, $scope);
	}

	function leaveNode(Node $node) {
		if($node instanceof Class_ || $node instanceof Trait_) {
			array_pop($this->classStack);
		}
		if($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
			echo "Pop scope\n";
			array_pop($this->scopeStack);
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
