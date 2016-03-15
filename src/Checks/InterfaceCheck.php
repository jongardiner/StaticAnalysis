<?php
namespace Scan\Checks;

use Scan\Util;

class InterfaceCheck extends BaseCheck {
	function run($fileName, $node) {
		if ($node->implements) {
			$arr = is_array($node->implements) ? $node->implements : [$node->implements];
			foreach ($arr as $interface) {
				$name = Util::fqn($interface);
				if ($name) {
					$file = $this->symbolTable->getInterfaceFile($name);
					if (!$file) {
						$this->emitError('Unknown interface',
							$node->getLine() . ": " . $node->name . " implements unknown interface " . $name
						);
					}
				}
			}
		}
	}
}
