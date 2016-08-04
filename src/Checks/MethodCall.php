<?php
namespace Scan\Checks;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use Scan\NodeVisitors\StaticAnalyzer;
use Scan\Output\OutputInterface;
use Scan\Scope;
use Scan\SymbolTable\SymbolTable;
use Scan\TypeInferrer;
use Scan\Util;
use Scan\Abstractions\FunctionLikeInterface;


class MethodCall extends BaseCheck
{
	/** @var TypeInferrer */
	private $inferenceEngine;

	function __construct(SymbolTable $symbolTable, OutputInterface $doc) {
		parent::__construct($symbolTable, $doc);
		$this->inferenceEngine = new TypeInferrer($symbolTable);
	}

	function getCheckNodeTypes() {
		return [\PhpParser\Node\Expr\MethodCall::class];
	}

	/**
	 * @param                                    $fileName
	 * @param \PhpParser\Node\Expr\MethodCall $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {

		if($inside instanceof Trait_) {
			// Traits should be converted into methods in the class, so that we can check them in context.
			return;
		}
		if($node->name instanceof Expr) {
			// Variable method name.  Yuck!
			return;
		}
		$methodName = strval($node->name);

		$varName = "{expr}";
		$className = "";
		if($node->var instanceof Variable && $node->var->name == "this" && !$inside) {
			$this->emitError($fileName, $node, self::TYPE_SCOPE_ERROR, "Can't use \$this outside of a class");
			return;
		}
		if ($scope) {
			$className = $this->inferenceEngine->inferType($inside, $node->var, $scope);
		}
		if($className!="" && $className[0]!="!") {
			if(!$this->symbolTable->getAbstractedClass($className)) {
				$this->emitError($fileName, $node, self::TYPE_UNKNOWN_CLASS, "Unknown class $className in method call to $methodName()");
				return;
			}
			//echo $fileName." ".$node->getLine(). " : Looking up $className->$methodName\n";
			$method = Util::findAbstractedMethod( $className, $methodName, $this->symbolTable);
			if ($method) {
				$this->checkMethod($fileName, $node, $className, $scope, $method);
			} else {
				// If there is a magic __call method, then we can't know if it will handle these calls.
				if(
					!Util::findAbstractedMethod( $className, "__call", $this->symbolTable) &&
					!$this->symbolTable->isParentClassOrInterface("iteratoriterator", $className)
				) {
					$this->emitError($fileName, $node, self::TYPE_UNKNOWN_METHOD, "Call to unknown method of $className: \$".$varName."->" .$methodName);
				}
			}
		}
	}

	/**
	 * @param           $fileName
	 * @param           $node
	 * @param string    $inside
	 * @param Scope     $scope
	 * @param           $method
	 */
	protected function checkMethod($fileName, $node, $inside, Scope $scope, FunctionLikeInterface $method) {
		if ($method->isStatic()) {
			$this->emitError($fileName, $node, self::TYPE_INCORRECT_DYNAMIC_CALL, "Call to static method of $inside::" . $method->getName(). " non-statically");
			return;
		}
		$params = $method->getParameters();
		$minimumArgs=$method->getMinimumRequiredParameters();
		if (count($node->args) < $minimumArgs) {
			$this->emitError($fileName, $node, self::TYPE_SIGNATURE_COUNT, "Function call parameter count mismatch to method " . $method->getName() . " (passed " . count($node->args) . " requires $minimumArgs)");
		}
		if(count($node->args) > count($params) && !$method->isVariadic()) {
			$this->emitError($fileName, $node, self::TYPE_SIGNATURE_COUNT_EXCESS, "Too many parameters to non-variadic method ".$method->getName()." (passed ".count($node->args). " only takes ".count($params).")");
		}

		foreach ($node->args as $index => $arg) {

			if ($scope && $arg->value instanceof \PhpParser\Node\Expr\Variable && $index < count($params) && $params[$index]->getType()!="") {
				$variableName = $arg->value->name;
				$type = $scope->getVarType($variableName);
				$expectedType = $params[$index]->getType();

				if (!in_array($type, [Scope::SCALAR_TYPE, Scope::MIXED_TYPE, Scope::UNDEFINED]) && $type!="" && !$this->symbolTable->isParentClassOrInterface($expectedType, $type)) {
					$this->emitError($fileName, $node, self::TYPE_SIGNATURE_TYPE, "Variable passed to method " . $inside . "->" . $node->name . "() parameter \$$variableName must be a $expectedType, passing $type");
				}
			}
		}
	}
}
