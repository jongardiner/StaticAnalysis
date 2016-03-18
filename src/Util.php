<?php namespace Scan;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassMethod;
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
}



