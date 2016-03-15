<?php
namespace Scan\Checks;

use Scan\Util;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class ClassMethodsCheck extends BaseCheck
{
	protected function findParentWithMethod(Class_ $node, $name) {

		$current = $node;
		while (true) {
			if ($node->extends) {
				$parent = Util::implodeParts($node->extends);
				$node = $this->symbolTable->getClass($parent);

				if (!$node) {
					$file = $this->symbolTable->getClassFile(Util::fqn($current));
					$this->emitError('Unknown class',
						$file . " " . $current->getLine() . ":Unable to find parent $parent"
					);
					return null;
				}

				$method = $this->symbolTable->getClassMethod(Util::fqn($node), $name);
				if ($method) {
					return [$node, $method];
				}
			} else {
				return null;
			}
		}
	}

	protected function checkMethod(Class_ $class, ClassMethod $method, Class_ $parentClass, ClassMethod $parentMethod) {
		$visibility = Util::getMethodAccessLevel($method);
		$oldVisibility = Util::getMethodAccessLevel($parentMethod);
		// "public" and "protected" can be redefined," private can not.
		$fileName = $this->symbolTable->getClassFile(Util::fqn($class));
		$fileName = str_replace($this->basePath, "", $fileName);
		if (
			$oldVisibility != $visibility && $oldVisibility == "private"
		) {
			//echo $fileName." ".$method->getLine().":Access level mismatch in ".$method->name."() ".Util::getMethodAccessLevel($method)." vs ".Util::getMethodAccessLevel($parentMethod)."\n";
		}
		$count1 = count($method->params);
		$count2 = count($parentMethod->params);
		if ($count1 != $count2) {
			$this->emitError("Method",
				$fileName . " " . $method->getLine() . ": parameter mismatch " . Util::methodSignatureString($method) . " vs " . Util::finalPart($parentClass->name) . "::" . Util::methodSignatureString($parentMethod)
			);
		} else foreach ($method->params as $index => $param) {
			$parentParam = $parentMethod->params[$index];
			$name1 = Util::implodeParts($param->type);
			$name2 = Util::implodeParts($parentParam->type);
			if (
				strcasecmp($name1, $name2) !== 0
			) {
				$this->emitError("Method",
					$fileName . " " . $method->getLine() . ": parameter mismatch " . Util::methodSignatureString($method) . " vs " . Util::finalPart($parentClass->name) . "::" . Util::methodSignatureString($parentMethod)
				);
				break;
			}
		}
	}

	/**
	 * @param string                      $fileName
	 * @param \PhpParser\Node\Stmt\Class_ $node
	 */
	function run($fileName, $node) {
		foreach ($this->symbolTable->getClassMethods(Util::fqn($node)) as $name => $methodNode) {
			list($parentClass, $parentMethod) = $this->findParentWithMethod($node, $methodNode->name);
			if ($parentMethod && $methodNode->name != "__construct") {
				$this->checkMethod($node, $methodNode, $parentClass, $parentMethod);
			}
		}
	}
}