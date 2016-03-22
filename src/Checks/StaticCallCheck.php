<?php
namespace Scan\Checks;

use PhpParser\Node\Param;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;

class StaticCallCheck extends BaseCheck
{

	/**
	 * @param $fileName
	 * @param \PhpParser\Node\Expr\StaticCall $call
	 */
	function run($fileName, $call, ClassLike $inside=null) {
		if ($call->class instanceof Name && $call->name instanceof Name) {

			$name = $call->class->toString();
			if ($this->symbolTable->ignoreType($name)) {
				return;
			}

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
					if ($inside->extends) {
						$name = strval($inside->extends);
					} else {
						$this->emitError($fileName, $call, "Scope error", "Can't access using parent:: in a class with no parent");
						return;
					}
					break;
			}



			$this->incTests();
			$class = $this->symbolTable->getClass($name);
			if (!$class) {
				if (!$this->symbolTable->ignoreType($name)) {
					$this->emitError($fileName,$call,"Unknown class", "Static call to unknown class $name::" . $call->name);
				}
			} else {

				$method=Util::findMethod($class, $call->name, $this->symbolTable);

				if(!$method) {
					$this->emitError($fileName,$call,"Unknown method", "Unable to find method.  $name::".$call->name);
				} else {
					$minimumParams=0;
					/** @var \PhpParser\Node\Param $param */
					foreach($method->params as $param) {
						if($param->default) break;
						$minimumParams++;
					}
					if(count($call->args)<$minimumParams) {
						$this->emitError($fileName,$method,"Signature mismatch", "Static call to method $name::".$call->name." does not pass enough parameters (".count($call->args)." passed $minimumParams required)");
					}
				}
			}
		}
	}
}