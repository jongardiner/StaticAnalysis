<?php namespace Scan;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassMethod;

class Util {
	static function implodeParts( $parts ) {
		return isset($parts->parts) ? implode("\\", $parts->parts) : ($parts?: "");
	}

	static function finalPart( $parts ) {
		return is_array($parts->parts) ? $parts->parts[ count($parts->parts)-1 ] : $parts;
	}

	/**
	 * @param $node Class_|Interface_
	 * @return string
	 */
	static function fqn($node) {
		return self::implodeParts($node->namespacedName);
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
}



