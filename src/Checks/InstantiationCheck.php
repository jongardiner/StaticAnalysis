<?php
namespace Scan\Checks;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassLike;
use Scan\Abstractions\FunctionLikeParameter;
use Scan\Scope;
use Scan\Util;

class InstantiationCheck extends BaseCheck
{

	/**
	 * @param $fileName
	 * @param \PhpParser\Node\Expr\New_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		if ($node->class instanceof Name) {
			$name = $node->class->toString();
			if (strcasecmp($name, "self") != 0 && strcasecmp($name, "static") != 0 && !$this->symbolTable->ignoreType($name)) {
				$this->incTests();
				$class = $this->symbolTable->getAbstractedClass($name);
				if (!$class) {
					$this->emitError($fileName,$node,"Unknown class", "Attempt to instantiate unknown class $name");
					return;
				}
				if($class->isDeclaredAbstract()) {
					$this->emitError($fileName, $node,"Signature mismatch","Attempt to instantiate abstract class $name");
					return;
				}

				$method=Util::findAbstractedMethod($name, "__construct", $this->symbolTable);


				if(!$method) {
					$minParams=$maxParams=0;
				} else {
					if($method->getAccessLevel()=="private" && (!$inside || strcasecmp($inside->namespacedName,$name)!=0)) {
						$this->emitError($fileName,$node,"Signature mismatch", "Attempt to call private constructor outside of class $name");
						return;
					}
					$maxParams = count($method->getParameters());
					$minParams = $method->getMinimumRequiredParameters();
				}

				$passedArgCount=count($node->args);
				if($passedArgCount<$minParams) {
					$this->emitError($fileName, $node, "Parameter mismatch","Call to $name::__construct passing $passedArgCount count, required count=$minParams");
				}
				if($passedArgCount>$maxParams) {
					//$this->emitError($fileName, $node, "Parameter mismatch","Call to $name::__construct passing too many parameters ($passedArgCount instead of $maxParams)");
				}
			}
		}
	}
}
