<?php
namespace Scan\Checks;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use Scan\Scope;
use Scan\Util;


class MethodCall extends BaseCheck
{

	/**
	 * @param                                    $fileName
	 * @param \PhpParser\Node\Expr\MethodCall $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {

		$self = null;
		$methodName = "";
		if($inside instanceof Trait_) {
			// Traits should be converted into methods in the class, so that we can check them in context.
			return;
		}
		if($node->var instanceof Variable) {
			//echo "Call ".$node->var->name." ".$node->name."()\n";
			if($node->var->name=='this') {

				if(!$inside) {
					$this->emitError($fileName, $node, "Scope error", "Can't use \$this outside of a class");
					return;
				}
				if(!($node->name instanceof Name)) {
					// Variable method name.  Yuck!
					return;
				}
				$self = $inside;

			} else if($scope) {
				$className = $scope->getVarType($node->var->name);
				if($className) {
					$self = $this->symbolTable->getClass($className);
				}
			}
			if($self) {
				$method = Util::findMethod($self, $node->name, $this->symbolTable);
				if ($method) {
					$this->checkMethod($fileName, $node, $self, $scope, $method);
				} else {
					$this->emitError($fileName, $node, "Unknown method","Call to unknown method $className->".$node->name);
				}
			}
		}
	}

	/**
	 * @param           $fileName
	 * @param           $node
	 * @param ClassLike $inside
	 * @param Scope     $scope
	 * @param           $method
	 */
	protected function checkMethod($fileName, $node, ClassLike $inside, Scope $scope, $method) {
		if (!$method) {
			$this->emitError($fileName, $node, "Unknown method", "Call to unknown method of $inside->namespacedName: \$this->" . $node->name);
			return;
		}
		if ($method->isStatic()) {
			$this->emitError($fileName, $node, "Unknown method", "Call to call static method of $inside->namespacedName: \$this->" . $node->name . " non-statically");
			return;
		}
		$minimumArgs = 0;
		foreach ($method->params as $param) {
			if ($param->default) break;
			$minimumArgs++;
		}
		if (count($node->args) < $minimumArgs) {
			$this->emitError($fileName, $node, "Signature mismatch", "Function call parameter count mismatch to method " . $method->name . " (passed " . count($node->args) . " requires $minimumArgs)");
		}
		foreach ($node->args as $index => $arg) {
			if ($scope && $arg->value instanceof \PhpParser\Node\Expr\Variable && $index < count($method->params) && $method->params[$index]->type) {
				$variableName = $arg->value->name;
				$type = $scope->getVarType($variableName);
				$expectedType = $method->params[$index]->type;
				if (!in_array($type, [Scope::SCALAR_TYPE, Scope::MIXED_TYPE, Scope::UNDEFINED]) && $type != $method->params[$index]->type) {
					$this->emitError($fileName, $node, "Signature mistmatch", "Variable passed to method " . $inside->namespacedName . "->" . $node->name . "() parameter $variableName must be a $expectedType, passing $type");
				}
			}
		}
	}
}
