<?php
namespace Scan\Checks;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;

class InstanceOfCheck extends BaseCheck
{
	function run($fileName, $node) {
		if ($node->class instanceof Name) {
			$name = $node->class->toString();
			if (strcasecmp($name, "self") != 0 && strcasecmp($name, "static") != 0 && !$this->symbolTable->ignoreType($name)) {
				$this->incTests();
				$class = $this->symbolTable->getClassFile($name);
				if (!$class) {
					$class = $this->symbolTable->getInterfaceFile($name);
				}
				if(!$class) {
					$this->emitError('Unknown class',
						$fileName . " " . $node->getLine() . ": instance of references unknown class $name"
					);
				}
			}
		}
	}
}
