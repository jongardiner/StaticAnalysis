<?php
namespace Scan\Checks;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use Scan\Scope;

class InstanceOfCheck extends BaseCheck
{
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		if ($node->class instanceof Name) {
			$name = $node->class->toString();
			if (strcasecmp($name, "self") != 0 && strcasecmp($name, "static") != 0 && !$this->symbolTable->ignoreType($name)) {
				$this->incTests();
				$class = $this->symbolTable->getClassFile($name);
				if (!$class) {
					$class = $this->symbolTable->getInterfaceFile($name);
				}
				if(!$class) {
					$this->emitError($fileName,$node,"Unknown class", "Instance of references unknown class $name");
				}
			}
		}
	}
}
