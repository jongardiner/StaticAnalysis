<?php
namespace Scan\Checks;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassMethod;
use Scan\Util;


class PropertyFetch extends BaseCheck
{

	/**
	 * @param                                    $fileName
	 * @param \PhpParser\Node\Expr\PropertyFetch $node
	 */
	function run($fileName, $node) {

	}
}
