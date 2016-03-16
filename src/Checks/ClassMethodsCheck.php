<?php
namespace Scan\Checks;

use Scan\Util;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class ClassMethodsCheck extends BaseCheck
{
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
		$fileName = str_replace($this->basePath, "", $fileName);
		$this->incTests();
		if (
			$oldVisibility != $visibility && $oldVisibility == "private"
		) {
			$this->emitError("Method",
				$fileName." ".$method->getLine().":Access level mismatch in ".$method->name."() ".Util::getMethodAccessLevel($method)." vs ".Util::getMethodAccessLevel($parentMethod)
			);
		}

		return ;
		// PHP 7, parameter counts and type hints must match.
		$count1 = count($method->params);
		$count2 = count($parentMethod->params);
		if ($count1 != $count2) {
/*			$this->emitError("Method",
				$fileName . " " . $method->getLine() . ": parameter count mismatch " . Util::methodSignatureString($method) . " vs " . Util::finalPart($parentClass->name) . "::" . Util::methodSignatureString($parentMethod)
			);*/
		} else foreach ($method->params as $index => $param) {
			$parentParam = $parentMethod->params[$index];
			$name1 = strval($param->type);
			$name2 = strval($parentParam->Type);
			if (
				strcasecmp($name1, $name2) !== 0
			) {
				//$this->emitError("Method",
				//	$fileName . " " . $method->getLine() . ": parameter mismatch " . Util::methodSignatureString($method) . " vs " . Util::finalPart($parentClass->name) . "::" . Util::methodSignatureString($parentMethod)
				//);
				break;
			}
		}
	}

	/**
	 * @param string                      $fileName
	 * @param \PhpParser\Node\Stmt\Class_ $node
	 */
	function run($fileName, $node) {
		foreach ($this->symbolTable->getClassMethods($node->namespacedName->toString()) as $name => $methodNode) {
			list($parentClass, $parentMethod) = $this->findParentWithMethod($node, $methodNode->name);
			if ($parentMethod && $methodNode->name != "__construct") {
				$this->checkMethod($node, $methodNode, $parentClass, $parentMethod);
			}
		}
	}
}