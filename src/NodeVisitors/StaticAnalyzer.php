<?php namespace Scan\NodeVisitors;

use PhpParser\Node;
use PhpParser\NodeTraverserInterface;
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

	/** @var \N98\JUnitXml\Document */
	private $suites;

	function __construct($basePath, $index, \N98\JUnitXml\Document $output, $config) {
		$this->index = $index;
		$this->suites = $output;
		$this->scopeStack = [new Scope(true)];

		$emitErrors = $config->getOutputLevel() == 1;

		/** @var Checks\BaseCheck[] $checkers */
		$checkers = [
			//	new Checks\DefinedConstantCheck($this->index, $output, $emitErrors),
			new Checks\PropertyFetch($this->index, $output, $emitErrors),
			//new Checks\BacktickOperatorCheck($this->index, $output, $emitErrors),
			new Checks\AncestryCheck($this->index, $output, $emitErrors),
			new Checks\ClassMethodsCheck($this->index, $output, $emitErrors),
			new Checks\InterfaceCheck($this->index, $output, $emitErrors),
			new Checks\ParamTypesCheck($this->index, $output, $emitErrors),
			new Checks\StaticCallCheck($this->index, $output, $emitErrors),
			new Checks\InstantiationCheck($this->index, $output, $emitErrors),
			new Checks\InstanceOfCheck($this->index, $output, $emitErrors),
			new Checks\CatchCheck($this->index, $output, $emitErrors),
			new Checks\ClassConstantCheck($this->index, $output, $emitErrors),
			new Checks\FunctionCallCheck($this->index, $output, $emitErrors),
			new Checks\MethodCall($this->index, $output, $emitErrors),
			new Checks\SwitchCheck($this->index, $output, $emitErrors)
		];


		foreach ($checkers as $checker) {
			foreach ($checker->getCheckNodeTypes() as $nodeType) {
				if (!isset($this->checks[$nodeType])) {
					$this->checks[$nodeType] = [$checker];
				} else {
					$this->checks[$nodeType][] = $checker;
				}
			}
		}
	}

	function beforeTraverse(array $nodes) {
		return null;
	}

	function setFile($name) {
		$this->file = $name;
		$this->scopeStack = [new Scope(true)];
	}

	function enterNode(Node $node) {
		$class = get_class($node);
		if ($node instanceof Trait_) {
			return NodeTraverserInterface::DONT_TRAVERSE_CHILDREN;
		}
		if ($node instanceof Class_ || $node instanceof Trait_) {
			array_push($this->classStack, $node);
		}
		if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Expr\Closure) {
			$this->pushFunctionScope($node);
		}
		if ($node instanceof Node\Expr\Assign) {
			$this->handleAssignment($node);
		}
		if ($node instanceof Node\Stmt\Catch_) {
			$this->setScopeType(strval($node->var), strval($node->type));
		}
		if($node instanceof Node\Stmt\If_ || $node instanceof Node\Stmt\ElseIf_) {
			if($node instanceof Node\Stmt\ElseIf_) {
				// Pop the previous if's scope
				array_pop($this->scopeStack);
			}
			$this->pushIfScope($node);
		}

		if ($node instanceof Node\Stmt\Else_) {
			// The previous scope was only valid for the if side.
			array_pop($this->scopeStack);
		}

		if (isset($this->checks[$class])) {
			foreach ($this->checks[$class] as $check) {
				$check->run($this->file, $node, end($this->classStack) ?: null, end($this->scopeStack) ?: null);
			}
		}
		return null;
	}

	/**
	 * @param Node\Stmt\If_|Node\Stmt\ElseIf_ $node
	 */
	function pushIfScope(Node $node) {
		/** @var Scope $scope */
		$scope = end($this->scopeStack);

		if (self::isCastableIf($node)) {
			$newScope = $scope->getScopeClone();
			$this->addCastedScope($node, $newScope);
		} else {
			// No need to actually instantiate a different scope, since it's identical to the old.
			$newScope = $scope;
		}
		array_push($this->scopeStack, $newScope);
	}

	/**
	 * When a node is of the form "if ($var instanceof ClassName)" (with no else clauses) then we can
	 * relax the scoping rules inside the if statement to allow a different set of methods that might not
	 * be normally visible.  This is primarily used for downcasting.
	 *
	 * "ClassName" inside the true clause.
	 * @param Node\Stmt\If_|Node\Stmt\ElseIf_ $node
	 */
	function addCastedScope(Node $node, Scope $newScope) {

		/** @var Node\Expr\Instanceof_ $cond */
		$cond = $node->cond;

		if ($cond->expr instanceof Node\Expr\Variable && gettype($cond->expr->name) == "string" && $cond->class instanceof Node\Name) {
			$newScope->setVarType($cond->expr->name, strval($cond->class));
		}

	}

	function pushFunctionScope(Node\FunctionLike $func) {
		$isStatic = true;
		if($func instanceof Node\Stmt\ClassMethod) {
			$isStatic = $func->isStatic();
		}
		$scope = new Scope( $isStatic );
		foreach ($func->getParams() as $param) {
			$scope->setVarType(strval($param->name), strval($param->type));
		}
		array_push($this->scopeStack, $scope);
	}

	/**
	 * An if is castable if there are no elseifs and the expr is a simple "InstanceOf" expression.
	 * @param Node $node
	 * @return bool
	 */
	static function isCastableIf(Node $node) {
		return ($node instanceof Node\Stmt\If_ || $node instanceof Node\Stmt\ElseIf_) && $node->cond instanceof Node\Expr\Instanceof_;
	}

	/**
	 * Do some simplistic checks to see if we can figure out object type.  If we can, then we can check method calls
	 * using that variable for correctness.
	 * @param Node\Expr $expr
	 * @param Scope     $scope
	 * @return string
	 */
	static function inferType(Node\Stmt\ClassLike $inside = null, Node\Expr $expr, Scope $scope) {
		if ($expr instanceof Node\Scalar || $expr instanceof Node\Expr\AssignOp) {
			return Scope::SCALAR_TYPE;
		} else if ($expr instanceof Node\Expr\New_ && $expr->class instanceof Node\Name) {
			$className = strval($expr->class);
			if (strcasecmp($className, "self") == 0) {
				$className = $inside ? strval($inside->namespacedName) : Scope::MIXED_TYPE;
			} else if (strcasecmp($className, "static") == 0) {
				$className = Scope::MIXED_TYPE;
			}
			return $className;
		} else if ($expr instanceof Node\Expr\Variable && gettype($expr->name) == "string") {
			$varName = strval($expr->name);
			$scopeType = $scope->getVarType($varName);
			if ($scopeType != Scope::UNDEFINED) {
				return $scopeType;
			}
		} else if ($expr instanceof Node\Expr\Closure) {
			return "callable";
		}
		return Scope::MIXED_TYPE;
	}

	private function setScopeExpression($varName, $expr) {
		$scope = end($this->scopeStack);
		$class = end($this->classStack) ?: null;
		$newType = self::inferType($class, $expr, $scope);
		$this->setScopeType($varName, $newType);
	}

	private function setScopeType($varName, $newType) {
		$scope = end($this->scopeStack);
		$oldType = $scope->getVarType($varName);
		if ($oldType != $newType) {
			if ($oldType == Scope::UNDEFINED) {
				$scope->setVarType($varName, $newType);
			} else {
				// The variable has been used with 2 different types.  Update it in the scope as a mixed type.
				$scope->setVarType($varName, Scope::MIXED_TYPE);
			}
		}
	}


	/**
	 * Assignment can cause a new variable to come into scope.  We infer the type of the expression (if possible) and
	 * add an entry to the variable table for this scope.
	 * @param Node\Expr\Assign $op
	 */
	private function handleAssignment(Node\Expr\Assign $op) {
		if ($op->var instanceof Node\Expr\Variable && !($op->var->name instanceof Node)) {
			$varName = strval($op->var->name);
			$this->setScopeExpression($varName, $op->expr);
		} else if ($op->var instanceof Node\Expr\List_) {
			// We're not going to examine a potentially complex right side of the assignment, so just set all vars to mixed.
			foreach ($op->var->vars as $var) {
				if ($var && $var instanceof Node\Expr\Variable && $var->name instanceof Node\Name) {
					$this->setScopeType($var->name, Scope::MIXED_TYPE);
				}
			}
		}
	}

	function leaveNode(Node $node) {
		if ($node instanceof Class_) {
			array_pop($this->classStack);
		}
		if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Expr\Closure) {
			array_pop($this->scopeStack);
		}

		if ($node instanceof Node\Stmt\If_ && $node->else==null) {
			// We only need to pop the scope if there wasn't an else clause.  Otherwise, it has already been popped.
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
