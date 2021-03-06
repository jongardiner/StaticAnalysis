<?php
namespace Guardrail\Checks;

use Guardrail\Abstractions\ClassInterface;
use Guardrail\Abstractions\ClassMethod;
use Guardrail\Abstractions\MethodInterface;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Class_;
use Guardrail\Abstractions\FunctionLikeParameter;
use Guardrail\Scope;
use Guardrail\Util;

class InterfaceCheck extends BaseCheck {
	function getCheckNodeTypes() {
		return [\PhpParser\Node\Stmt\Class_::class];
	}

	protected function checkMethod(Class_ $class, ClassMethod $method, ClassInterface $parentClass, MethodInterface $parentMethod) {

		$visibility = $method->getAccessLevel();
		$oldVisibility = $parentMethod->getAccessLevel();

		$fileName = $this->symbolTable->getClassFile( strval($class->namespacedName) );
		$this->incTests();

		// "public" and "protected" can be redefined," private can not.
		if (
			$oldVisibility != $visibility && $oldVisibility == "private"
		) {
			$this->emitError($fileName, $class,self::TYPE_SIGNATURE_TYPE, "Access level mismatch in ".$method->getName()."() ".$visibility." vs ".$oldVisibility);
		}

		$params = $method->getParameters();
		$parentMethodParams = $parentMethod->getParameters();
		$count1 = count($params);
		$count2 = count($parentMethodParams);
		if ($count1 < $count2) {
			$this->emitError($fileName,$class,self::TYPE_SIGNATURE_COUNT, "Parameter count mismatch $count1 vs $count2 in method ".$class->namespacedName."->".$method->getName());
		} else foreach ($params as $index => $param) {
			/** @var FunctionLikeParameter $param */
			// Only parameters specified by the parent need to match.  (Child can add more as long as they have a default.)
			if($index<$count2) {
				$parentParam = $parentMethodParams[$index];
				$name1 = strval($param->getType());
				$name2 = strval($parentParam->getType());
				if (
					strcasecmp($name1, $name2) !== 0
				) {
					$this->emitErrorOnLine($fileName, $method->getStartingLine(), self::TYPE_SIGNATURE_TYPE, "Parameter mismatch type mismatch ".$class->namespacedName."::".$method->getName()." : $name1 vs $name2");
					break;
				}
				if($param->isReference() != $parentParam->isReference()) {
					$this->emitErrorOnLine($fileName, $method->getStartingLine(), self::TYPE_SIGNATURE_TYPE, "Child Method ".$class->name."::".$method->getName()." add or removes & in \$".$param->getName());
					break;
				}
				if(!$param->isOptional() && $parentParam->isOptional()) {
					$this->emitErrorOnLine($fileName, $method->getStartingLine(), self::TYPE_SIGNATURE_TYPE, "Child method ".$class->name."::".$method->getName()." changes parameter \$".$param->getName()." to be required.");
					break;
				}
			} else {
				if(!$param->isOptional()) {
					$this->emitErrorOnLine($fileName, $method->getStartingLine(), self::TYPE_SIGNATURE_TYPE, "Child method ".$method->getName()." adds parameter \$".$param->getName()." that doesn't have a default value");
					break;
				}
			}
		}
	}

	protected function implementsMethod( $fileName, Class_ $node, $interfaceMethod) {
		$current = new \Guardrail\Abstractions\Class_($node);
		while (true) {
			// Is it directly in the class
			$classMethod = $current->getMethod($interfaceMethod);
			if ($classMethod) {
				return $classMethod;
			}

			if ($current->getParentClassName()) {
				$current = $this->symbolTable->getAbstractedClass($current->getParentClassName());
			} else {
				return null;
			}
		}
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
					$interface = $this->symbolTable->getAbstractedClass($name);
					if (!$interface) {
						$this->emitError($fileName,$node,self::TYPE_UNKNOWN_CLASS,  $node->name . " implements unknown interface " . $name);
					} else {
						// Don't force abstract classes to implement all methods.
						if(!$node->isAbstract()) {
							foreach ($interface->getMethodNames() as $interfaceMethod) {
								$classMethod = $this->implementsMethod($fileName, $node, $interfaceMethod);
								if (!$classMethod) {
									if(!$node->isAbstract()) {
										$this->emitError($fileName,$node,self::TYPE_UNIMPLEMENTED_METHOD, $node->name . " does not implement method " . $interfaceMethod);
									}
								} else {
									$this->checkMethod(
										$node,$classMethod, $interface, $interface->getMethod($interfaceMethod)
									);
								}
							}
						}
					}
				}
			}
		}

		if($node->extends) {
			$class = new \Guardrail\Abstractions\Class_($node);
			$parentClass = $this->symbolTable->getAbstractedClass($node->extends);
			if(!$parentClass) {
				$this->emitError($fileName,$node->extends,self::TYPE_UNKNOWN_CLASS, "Unable to find parent ".$node->extends);
			}
			foreach ($class->getMethodNames() as $methodName) {
				if($methodName!="__construct") {
					$method = Util::findAbstractedMethod($node->extends, $methodName, $this->symbolTable);
					if ($method) {
						$this->checkMethod($node, $class->getMethod($methodName), $parentClass, $method);
					}
				}
			}
		}
	}
}
