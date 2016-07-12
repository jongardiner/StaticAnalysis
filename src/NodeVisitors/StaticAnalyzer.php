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
		$this->scopeStack = [ new Scope() ];

		$emitErrors = $config->getOutputLevel()==1;

		$this->checks = [/*
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
				],*/
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
		$this->scopeStack = [ new Scope() ];
	}

	function enterNode(Node $node) {
		$class=get_class($node);
		if($node instanceof Class_ || $node instanceof Trait_) {
			array_push($this->classStack, $node);
		}
		if($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
			$this->pushFunctionScope($node);
		}
		if($node instanceof Node\Expr\Assign) {
			$this->handleAssignment($node);
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
			$scope->setVarType(strval($param->name), strval($param->type));
		}
		array_push($this->scopeStack, $scope);
	}

	/**
	 * Do some simplistic checks to see if we can figure out object type.  If we can, then we can check method calls
	 * using that variable for correctness.
	 * @param Node\Expr $expr
	 * @param Scope     $scope
	 * @return string
	 */
	static function inferType(Node\Expr $expr, Scope $scope) {
		if($expr instanceof Node\Scalar || $expr instanceof Node\Expr\AssignOp) {
			return Scope::SCALAR_TYPE;
		} else if($expr instanceof Node\Expr\New_ && !($expr->class instanceof Node)) {
			return strval($expr->class);
		} else if($expr instanceof Node\Expr\Variable && !( $expr->name instanceof Node) ) {
			$varName= strval($expr->name);
			$scopeType = $scope->getVarType($varName);
			if($scopeType!=Scope::UNDEFINED) {
				return $scopeType;
			}
		}
		return Scope::MIXED_TYPE;
	}


	/**
	 * Assignment can cause a new variable to come into scope.  We infer the type of the expression (if possible) and
	 * add an entry to the variable table for this scope.
	 * @param Node\Expr\Assign $op
	 */
	private function handleAssignment(Node\Expr\Assign $op) {
		$scope = end($this->scopeStack);
		if ($op->var instanceof Node\Expr\Variable && !($op->var->name instanceof Node)) {
			$varName = strval($op->var->name);

			$oldType = $scope->getVarType($varName);
			$newType = self::inferType($op->expr, $scope);
			if ($oldType != $newType) {
				if ($oldType == Scope::UNDEFINED) {
					$scope->setVarType($varName, $newType);
				} else {
					// The variable has been used with 2 different types.  Update it in the scope as a mixed type.
					$scope->setVarType($varName, Scope::MIXED_TYPE);
				}
			}
		} else if ($op->var instanceof Node\Expr\List_) {
			// We're not going to examine a potentially complex right side of the assignment, so just set all vars to mixed.
			foreach($op->var->vars as $var) {
				if($var && $var->name instanceof Node\Name) {
					$scope->setVarType(strval($var->name), Scope::MIXED_TYPE );
				}
			}
		}
	}

	function leaveNode(Node $node) {
		if($node instanceof Class_ || $node instanceof Trait_) {
			array_pop($this->classStack);
		}
		if($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
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
		return $failures->length;
	}
}
