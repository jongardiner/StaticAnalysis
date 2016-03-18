<?php
namespace Scan\Checks;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

class InstantiationCheck extends BaseCheck
{

	function run($fileName, $node) {
		if ($node->class instanceof Name) {
			$name = $node->class->toString();
			if (strcasecmp($name, "self") != 0 && strcasecmp($name, "static") != 0 && !$this->symbolTable->ignoreType($name)) {
				$this->incTests();
				$class = $this->symbolTable->getClassFile($name);
				if (!$class) {
					$this->emitError($fileName,$node,"Unknown class", "Attempt to instantiate unknown class $name");
				}
			}
		}
	}
}
