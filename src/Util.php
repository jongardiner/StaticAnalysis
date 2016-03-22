<?php namespace Scan;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\PropertyProperty;
use Scan\SymbolTable\SymbolTable;
use Webmozart\Glob\Glob;

class Util {

	static function finalPart( $parts ) {
		return is_array($parts->parts) ? $parts->parts[ count($parts->parts)-1 ] : $parts;
	}

	static function methodSignatureString(ClassMethod $method) {
		$ret = [];
		foreach($method->params as $param) {
			$ret[]=static::finalPart($param->type) ?: "?";
		}
		return static::finalPart($method->name)."(".implode(",", $ret).")";
	}
	static function getMethodAccessLevel(ClassMethod $level) {
		if($level->isPublic()) return "public";
		if($level->isPrivate()) return "private";
		if($level->isProtected()) return "protected";
		trigger_error("Impossible");
	}

	static function matchesGlobs($basePath, $path, $globArr) {
		foreach($globArr as $glob) {
			if(Glob::match($path, $basePath."/".$glob)) {
				return true;
			}
		}
		return false;
	}

	static function removeInitialPath($path, $name) {
		if(strpos($name,$path)===0) {
			return substr($name,strlen($path));
		} else {
			return $name;
		}
	}

	/**
	 * @param Class_      $node
	 * @param             $name
	 * @param SymbolTable $symbolTable
	 * @return ClassMethod
	 */
	static function findMethod(Class_ $node, $name, SymbolTable $symbolTable) {
		while ($node) {
			$methods = \Scan\NodeVisitors\Grabber::filterByType($node->stmts, \PhpParser\Node\Stmt\ClassMethod::class);
			foreach($methods as $method) {
				if(strcasecmp($method->name,$name)==0) {
					return $method;
				}
			}
			if ($node->extends) {
				$parent = $node->extends->toString();
				$node = $symbolTable->getClass($parent);
			} else {
				return null;
			}
		}
		return null;
	}

	/**
	 * @param Class_      $node
	 * @param             $name
	 * @param SymbolTable $symbolTable
	 * @return ClassMethod
	 */
	static function findProperty(Class_ $node, $name, SymbolTable $symbolTable) {
		while ($node) {
			$properties = \Scan\NodeVisitors\Grabber::filterByType($node->stmts, \PhpParser\Node\Stmt\Property::class);
			foreach($properties as $propertyList) {
				/** @var PropertyProperty $propertyProperty */
				foreach($propertyList as $propertyProperty) {
					if (strcasecmp($propertyProperty->name, $name) == 0) {
						return $propertyProperty;
					}
				}
			}
			if ($node->extends) {
				$parent = $node->extends->toString();
				$node = $symbolTable->getClass($parent);
			} else {
				return null;
			}
		}
		return null;
	}

	static function callIsCompatible(ClassMethod $method,MethodCall $call) {

	}
}



