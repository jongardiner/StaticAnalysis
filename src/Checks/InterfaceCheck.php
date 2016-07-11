<?php
namespace Scan\Checks;

use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassMethod;
use Scan\Scope;
use Scan\Util;

class InterfaceCheck extends BaseCheck {

	protected function checkMethod(Class_ $class, ClassMethod $method, Interface_ $parentClass, ClassMethod $parentMethod) {
		$visibility = Util::getMethodAccessLevel($method);
		$oldVisibility = Util::getMethodAccessLevel($parentMethod);
		// "public" and "protected" can be redefined," private can not.
		$fileName = $this->symbolTable->getClassFile($class->namespacedName->toString());
		//$fileName = str_replace($this->basePath, "", $fileName);

		$this->incTests();
		if (
			$oldVisibility != $visibility && $oldVisibility == "private"
		) {
			$this->emitError($fileName,$method,"Signature mismatch", "Access level mismatch in ".$method->name."() ".Util::getMethodAccessLevel($method)." vs ".Util::getMethodAccessLevel($parentMethod));
		}

		return ;
		// PHP 7, parameter counts and type hints must match.
		$count1 = count($method->params);
		$count2 = count($parentMethod->params);
		if ($count1 != $count2) {
			$this->emitError($fileName,$method,"Signature mismatch", "Parameter count mismatch " . Util::methodSignatureString($method) . " vs " . Util::finalPart($parentClass->name) . "::" . Util::methodSignatureString($parentMethod));
		} else foreach ($method->params as $index => $param) {
			$parentParam = $parentMethod->params[$index];
			$name1 = strval($param->type);
			$name2 = strval($parentParam->Type);
			if (
				strcasecmp($name1, $name2) !== 0
			) {
				$this->emitError($fileName,$method,"Signature mismatch", "Parameter mismatch " . Util::methodSignatureString($method) . " vs " . Util::finalPart($parentClass->name) . "::" . Util::methodSignatureString($parentMethod));
				break;
			}
		}
	}

	protected function getTraitMethod($fileName, array $traitUses, $methodName) {
		foreach($traitUses as $traits) {
			foreach ($traits->traits as $name) {
				$trait = $this->symbolTable->getTrait($name->toString());
				if (!$trait) {
					$this->emitError($fileName,$name,"Unknown trait", "Unknown trait " . $name->toString());
				} else {
					$method = $trait->getMethod($methodName);
					if($method) {
						return $method;
					}
					if (is_array($trait->stmts)) {
						foreach ($trait->stmts as $stmt) {
							if ($stmt instanceof TraitUse) {
								$method = $this->getTraitMethod($fileName, $stmt->traits, $methodName);
								if ($method) {
									return $method;
								}
							}
						}
					}
				}
			}
		}
		return null;
	}

	protected function implementsMethod( $fileName, Class_ $node, ClassMethod $interfaceMethod) {
		while ($node) {
			// Is it directly in the class
			$classMethod = $node->getMethod($interfaceMethod->name);
			if ($classMethod) {
				return $classMethod;
			}

			// Is it in the trait or a trait that the trait uses.
			$traits = \Scan\NodeVisitors\Grabber::filterByType($node->stmts, TraitUse::class);
			$classMethod = $this->getTraitMethod($fileName, $traits, $interfaceMethod->name);
			if($classMethod) {
				return $classMethod;
			}

			if ($node->extends) {
				$parent = $node->extends->toString();
				$node = $this->symbolTable->getClass($parent);
			} else {
				$node=null;
			}
		}
		return null;

	}

	/**
	 * @param $fileName
	 * @param \PhpParser\Node\Stmt\Class_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {

		if ($node->implements) {
			$arr = is_array($node->implements) ? $node->implements : [$node->implements];
			foreach ($arr as $interface) {
				$name = $interface->toString();
				$this->incTests();
				if ($name) {
					$interface = $this->symbolTable->getInterface($name);
					if (!$interface) {
						$this->emitError($fileName,$node,"Unknown interface",  $node->name . " implements unknown interface " . $name);
					} else {
						// Don't force abstract classes to implement all methods.
						if(!$node->isAbstract()) {
							foreach ($interface->getMethods() as $interfaceMethod) {
								$classMethod = $this->implementsMethod($fileName, $node, $interfaceMethod);
								if (!$classMethod) {
									if(!$node->isAbstract()) {
										$this->emitError($fileName,$node,"Missing implementation", $node->name . " does not implement method " . $interfaceMethod->name);
									}
								} else {
									$this->checkMethod(
										$node,$classMethod, $interface, $interfaceMethod
									);
								}
							}
						}
					}
				}
			}
		}
	}
}
