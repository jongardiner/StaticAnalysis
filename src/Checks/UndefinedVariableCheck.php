<?php
namespace Guardrail\Checks;

use Guardrail\Checks\BaseCheck;
use PhpParser\Node\Stmt\ClassLike;
use Guardrail\Scope;

class UndefinedVariableCheck extends BaseCheck
{
	function getCheckNodeTypes() {
		return [\PhpParser\Node\Expr\Variable::class];
	}

	/**
	 * @param string $fileName
	 * @param \PhpParser\Node\Expr\Variable $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {

		if(gettype($node->name)=='string' && $scope && !$scope->isGlobal()) {
			$name = $name=$node->name;
			if($name!="GLOBALS" && $name!="_GET" && $name!="_POST" && $name!="_COOKIE" && $name!="_REQUEST" && $name!="this" && $name!="_SERVER" && $name!="_SESSION" && $name!="_FILES" && $name!="http_response_header") {
				$this->incTests();
				if($scope->getVarType($name)==Scope::UNDEFINED) {
					$this->emitError($fileName, $node, self::TYPE_UNKNOWN_VARIABLE, "Undefined variable: $name");
				}
			}
		}
	}
}