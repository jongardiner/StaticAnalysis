<?php namespace Scan;

use PhpParser\Node\Stmt\Class_;

class Util {
	static function implodeParts( $parts ) {
		return isset($parts->parts) ? implode("\\", $parts->parts) : ($parts?: "");
	}

	static function fqn(Class_ $node) {
		return self::implodeParts($node->namespacedName);
	}
}



