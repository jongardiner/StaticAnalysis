<?php
namespace Scan\Checks;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use Scan\NodeVisitors\StaticAnalyzer;
use Scan\Scope;
use Scan\Util;
use Scan\Abstractions\FunctionLikeInterface;


class MethodCall extends BaseCheck
{

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
		if($node->var instanceof Variable) {
			if(gettype($node->var->name)=="string") {
				$varName = $node->var->name;
			}
			if ($node->var->name == "this") {
				if (!$inside) {
					$this->emitError($fileName, $node, "Scope error", "Can't use \$this outside of a class");
					return;
				} else {
					$className = strval($inside->namespacedName);
				}
			} else if ($scope) {
				$className = StaticAnalyzer::inferType($inside, $node->var, $scope);
			}
		}
		if($className!="" && $className[0]!="!") {
			//echo $fileName." ".$node->getLine(). " : Looking up $className->$methodName\n";
			$method = Util::findAbstractedMethod( $className, $methodName, $this->symbolTable);
			if ($method) {
				$this->checkMethod($fileName, $node, $className, $scope, $method);
			} else {
				// If there is a magic __call method, then we can't know if it will handle these calls.
				if(!Util::findAbstractedMethod( $className, "__call", $this->symbolTable) ) {
					$this->emitError($fileName, $node, "Unknown method", "Call to unknown method of $className: \$".$varName."->" .$methodName);
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
			//$this->emitError($fileName, $node, "Unknown method", "Call to static method of $inside::" . $method->getName(). " non-statically");
			return;
		}
		$params = $method->getParameters();
		$minimumArgs=$method->getMinimumRequiredParameters();
		if (count($node->args) < $minimumArgs) {
			$this->emitError($fileName, $node, "Signature mismatch", "Function call parameter count mismatch to method " . $method->getName() . " (passed " . count($node->args) . " requires $minimumArgs)");
		}

		foreach ($node->args as $index => $arg) {

			if ($scope && $arg->value instanceof \PhpParser\Node\Expr\Variable && $index < count($params) && $params[$index]->getType()!="") {
				$variableName = $arg->value->name;
				$type = $scope->getVarType($variableName);
				$expectedType = $params[$index]->getType();

				if (!in_array($type, [Scope::SCALAR_TYPE, Scope::MIXED_TYPE, Scope::UNDEFINED]) && $type!="" && !$this->symbolTable->isParentClassOrInterface($expectedType, $type)) {
					$this->emitError($fileName, $node, "Signature mismatch", "Variable passed to method " . $inside . "->" . $node->name . "() parameter $variableName must be a $expectedType, passing $type");
				}
			}
		}
	}
}
