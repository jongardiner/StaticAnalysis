<?php namespace Scan;

use PhpParser\Node;
use PhpParser\NodeVisitor;

class StaticAnalyzer implements NodeVisitor {
	private $index;
	private $checker;
	private $file;

	function __construct( $basePath, $index ) {
		$this->index=$index;
		$this->checker=new SignatureChecker($basePath,$index);
	}
	function beforeTraverse(array $nodes) {
		return null;
	}

	function setFile($name) {
		$this->file=$name;
	}

	function enterNode(Node $node) {
		switch(get_class($node)) {
			case Node\Stmt\Class_::class:
				$this->checker->checkAncestry( $this->file, $node );
				$this->checker->checkClassMethods( $this->file, $node );
				break;
			case Node\Expr\StaticCall::class:
				$this->checker->checkStaticCall($this->file, $node);
				break;
			case Node\Expr\New_::class:
				$this->checker->checkNewCall($this->file, $node);
				break;
			case Node\Stmt\Catch_::class:
				$this->checker->checkCatch($this->file,$node);
				break;
			case Node\Expr\ClassConstFetch::class:
				$this->checker->checkClassConstant($this->file, $node);
				break;
		}
		return null;
	}

	function leaveNode(Node $node) {
		return null;
	}

	function afterTraverse(array $nodes) {
		return null;
	}
}
