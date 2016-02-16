<?php namespace Scan;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;

class SignatureChecker {

	function __construct(SymbolTable $table) {
		$this->symbolTable=$table;
	}

	function findParentWithMethod(Class_ $node, $name) {
		while(true) {
			$node=$node->extends ? $this->symbolTable->getClass(Util::implodeParts($node->extends)):null;
			if($node) {
				$method=$this->symbolTable->getClassMethod(Util::fqn($node), $name);
				if ($method) {
					return [$node, $method];
				}
			} else {
				return null;
			}
		} 
	}

	function checkClassMethods(Class_  $node) {
		foreach($this->symbolTable->getClassMethods(Util::fqn($node)) as $name=>$methodNode) {
			list($parentClass,$parentMethod)=$this->findParentWithMethod( $node, $methodNode->name );
			if ($parentMethod && $methodNode->name!="__construct") {
				$this->checkMethod( $node, $methodNode, $parentClass, $parentMethod );
			}
		}
	}

	function checkMethod(Class_ $class, ClassMethod $method, Class_ $parentClass, ClassMethod $parentMethod) {
		$visibility=Util::getMethodAccessLevel($method);
		$oldVisibility=Util::getMethodAccessLevel($parentMethod);
		// "public" and "protected" can be redefined," private can not.
		if( 
			$oldVisibility!=$visibility && $oldVisibility=="private"
		) {
			echo "Access level mismatch in ".Util::fqn($class)."::".$method->name." ".Util::getMethodAccessLevel($method)." vs ".Util::getMethodAccessLevel($parentMethod)."\n";
		}
		if( count($method->params) != count($parentMethod->params) )  {
			echo "Parameter count of ".Util::fqn($class)."::".$method->name." does not match ancestor ".Util::fqn($parentClass)."::".$parentMethod->name."\n";
		} else foreach($method->params as $index=>$param) {
			$parentParam=$parentMethod->params[$index];
			if(
				Util::implodeParts($param->type) !== Util::implodeParts($parentParam->type)
			) {
				echo "Parameter mismatch for ".$param->name." in method ".Util::fqn($class)."::".$method->name." vs ".Util::fqn($parentClass)."::".$parentMethod->name."\n";
			}
		}
	}
}
