<?php
namespace Scan\Checks;

use PhpParser\Node\Param;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use Scan\Scope;
use Scan\Util;

class StaticCallCheck extends BaseCheck
{
	function getCheckNodeTypes() {
		return [\PhpParser\Node\Expr\StaticCall::class];
	}

	/**
	 * @param $fileName
	 * @param \PhpParser\Node\Expr\StaticCall $call
	 */
	function run($fileName, $call, ClassLike $inside=null, Scope $scope = null) {
		if ($call->class instanceof Name && gettype($call->name)=="string") {

			$name = $call->class->toString();
			if ($this->symbolTable->ignoreType($name)) {
				return;
			}
			$originalName=$name;
			$possibleDynamic = false;

			switch(strtolower($name)) {
				case 'self':
				case 'static':
					if(!$inside) {
						$this->emitError($fileName, $call, "Scope error", "Can't access using self:: outside of a class");
						return;
					}
					$name = $inside->namespacedName;
					break;
				case 'parent':
					if(!$inside) {
						$this->emitError($fileName, $call, "Scope error", "Can't access using parent:: outside of a class");
						return;
					}
					$possibleDynamic=true;
					if ($inside->extends) {
						$name = strval($inside->extends);
					} else {
						$this->emitError($fileName, $call, "Scope error", "Can't access using parent:: in a class with no parent");
						return;
					}
					break;
			}



			$this->incTests();
			$class = $this->symbolTable->getAbstractedClass($name);
			if (!$class) {
				if (!$this->symbolTable->ignoreType($name)) {
					$this->emitError($fileName,$call,"Unknown class", "Static call to unknown class $name::" . $call->name);
				}
			} else {

				$method = Util::findAbstractedMethod($name, $call->name, $this->symbolTable );

				if(!$method) {
					if(!Util::findAbstractedMethod($name, "__callStatic", $this->symbolTable) &&
						(!$possibleDynamic || !Util::findAbstractedMethod($name,"__call", $this->symbolTable))
					) {
						$this->emitError($fileName, $call, "Unknown method", "Unable to find method.  $name::" . $call->name);
					}
				} else {
					if(!$method->isStatic() && !$possibleDynamic) {
						$this->emitError($fileName,$call,"Signature mismatch", "Attempt to call non-static method: $name::".$call->name." statically");
						return;
					}
					$minimumParams=$method->getMinimumRequiredParameters();
					if(count($call->args)<$minimumParams) {
						$this->emitError($fileName,$call,"Signature mismatch", "Static call to method $name::".$call->name." does not pass enough parameters (".count($call->args)." passed $minimumParams required)");
					}
				}
			}
		}
	}
}