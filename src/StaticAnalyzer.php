<?php namespace Scan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
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
		if($node instanceof Class_) {
			$this->checker->checkAncestry( $this->file, $node );
			$this->checker->checkClassMethods( $this->file, $node );
		}

		if($node instanceof Node\Expr\StaticCall) {
			$this->checker->checkStaticCall($this->file, $node);
		}

		if($node instanceof Node\Expr\New_) {
			$this->checker->checkNewCall($this->file, $node);
		}
		if($node instanceof Node\Stmt\Catch_) {
			$this->checker->checkCatch($this->file,$node);
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

?>
