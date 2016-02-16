<?php namespace Scan;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class Util {
	static function implodeParts( $parts ) {
		return isset($parts->parts) ? implode("\\", $parts->parts) : ($parts?: "");
	}

	static function fqn(Class_ $node) {
		return self::implodeParts($node->namespacedName);
	}
	
	static function getMethodAccessLevel(ClassMethod $level) {
		if($level->isPublic()) return "public";
		if($level->isPrivate()) return "private";
		if($level->isProtected()) return "protected";
		trigger_error("Impossible");
	}
}



