<?php
namespace Scan\Checks;


use Scan\Scope;
use Scan\Util;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class ClassMethodsCheck extends BaseCheck
{
	function getCheckNodeTypes() {
		return [\PhpParser\Node\Stmt\Class_::class];
	}

	protected function findParentWithMethod(Class_ $node, $name) {

		while ($node) {
			if ($node->extends) {
				$parent = $node->extends->toString();
				$node = $this->symbolTable->getClass($parent);
				if($node) {
					$method = $this->symbolTable->getClassMethod($node->namespacedName->toString(), $name);
					if($method) {
						return [$node, $method];
					}
				}
			} else {
				$node=null;
			}
		}
		return [null,null];
	}

	protected function checkMethod(Class_ $class, ClassMethod $method, Class_ $parentClass, ClassMethod $parentMethod) {
		$visibility = Util::getMethodAccessLevel($method);
		$oldVisibility = Util::getMethodAccessLevel($parentMethod);
		// "public" and "protected" can be redefined," private can not.
		$fileName = $this->symbolTable->getClassFile($class->namespacedName->toString());
		//$fileName = str_replace($this->basePath, "", $fileName);
		$this->incTests();
		if (
			$oldVisibility != $visibility && $oldVisibility == "private"
		) {
			$this->emitError($fileName,$method,$method->name."()", "Access level mismatch in ".$method->name."() ".Util::getMethodAccessLevel($method)." vs ".Util::getMethodAccessLevel($parentMethod));
		}

		// PHP 7, parameter counts and type hints must match.
		$count1 = count($method->params);
		$count2 = count($parentMethod->params);
		if ($count1 != $count2) {
			$this->emitError( $fileName, $method, "Method signature", "Parameter count mismatch " .$class->name."::". Util::methodSignatureString($method) . " vs " . $parentClass->name . "::" . Util::methodSignatureString($parentMethod ) );
		} else foreach ($method->params as $index => $param) {
			$parentParam = $parentMethod->params[$index];
			$name1 = strval($param->type);
			$name2 = strval($parentParam->type);
			if (
				strcasecmp($name1, $name2) !== 0
			) {
				$this->emitError( $fileName, $method, "Method signature", "Parameter mismatch " .$class->name."::".Util::methodSignatureString($method) . " vs " . $parentClass->name . "::" . Util::methodSignatureString($parentMethod) );
				break;
			}
		}
	}


	/**
	 * @param string                      $fileName
	 * @param \PhpParser\Node\Stmt\Class_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		foreach ($this->symbolTable->getClassMethods($node->namespacedName->toString()) as $name => $methodNode) {
			list($parentClass, $parentMethod) = $this->findParentWithMethod($node, $methodNode->name);
			if ($parentMethod && $methodNode->name != "__construct") {
				$this->checkMethod($node, $methodNode, $parentClass, $parentMethod);
			}
		}
	}
}