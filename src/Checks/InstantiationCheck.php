<?php
namespace Scan\Checks;

class InstantiationCheck extends BaseCheck
{

	function run($fileName, $node) {
		if ($node->class instanceof Name) {
			$name = Util::implodeParts($node->class);
			if (strcasecmp($name, "self") != 0 && strcasecmp($name, "static") != 0 && !$this->symbolTable->ignoreType($name)) {
				$class = $this->symbolTable->getClassFile($name);
				if (!$class) {
					$this->emitError('Unknown interface',
						$fileName . " " . $node->getLine() . ": attempt to instantiate unknown class $name"
					);
				}
			}
		}
	}
}
