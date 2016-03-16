<?php
namespace Scan\Checks;

use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Class_;

class InterfaceCheck extends BaseCheck {
	protected function traitImplements($fileName, array $traitUses, $methodName) {
		foreach($traitUses as $name) {
			$trait = $this->symbolTable->getTrait( $name->toString() );
			if(!$trait) {
				$this->emitError("Unknown trait", $fileName." ".$name->getLine().": Unknown trait ".$name->toString());
			} else {
				if ($trait->getMethod($methodName)) {
					return true;
				}
				if(is_array($trait->stmt)) {
					foreach ($trait->stmt as $stmt) {
						if ($stmt instanceof TraitUse) {
							if ($this->traitImplements($stmt->traits, $methodName)) {
								return true;
							}
						}
					}
				}
			}
		}
		return false;
	}

	protected function implementsMethod( $fileName, Class_ $node, $name) {
		while ($node) {
			// Is it directly in the class
			if($node->getMethod($name)) return true;

			// Is it in the trait or a trait that the trait uses.
			foreach($node->stmts as $stmt) {
				if($stmt instanceof TraitUse) {
					if($this->traitImplements( $fileName, $stmt->traits, $name)) {
						return true;
					}
				}
			}
			if ($node->extends) {
				$parent = $node->extends->toString();
				$node = $this->symbolTable->getClass($parent);
			} else {
				$node=null;
			}
		}
		return false;

	}

	function run($fileName, $node) {

		if ($node->implements) {
			$arr = is_array($node->implements) ? $node->implements : [$node->implements];
			foreach ($arr as $interface) {
				$name = $interface->toString();
				$this->incTests();
				if ($name) {
					$interface = $this->symbolTable->getInterface($name);
					if (!$interface) {
						$this->emitError('Unknown interface',
							$fileName." ".$node->getLine() . ": " . $node->name . " implements unknown interface " . $name
						);
					} else {
						/*
						foreach($interface->getMethods() as $method) {
							if(!$this->implementsMethod( $fileName, $node, $method->name)) {
								$this->emitError(
									"Missing implementation",
									$fileName." ".$node->getLine().": ".$node->name ." does not implement method ".$method->name
								);
							}
						}
						*/
					}
				}
			}
		}
	}
}
