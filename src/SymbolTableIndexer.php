<?php namespace Scan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitor;

class SymbolTableIndexer implements NodeVisitor {
	private $index;
	private $classStack = [];
	private $filename = "";

	function __construct( $index ) {
		$this->index=$index;
	}
	function beforeTraverse(array $nodes) {
		return null;
	}

	function setFilename($filename) {
		$this->filename=$filename;
	}

	function enterNode(Node $node) {
		if($node instanceof Class_) {
			$name=Util::fqn($node);
			$this->index->addClass($name, $node, $this->filename);
			array_push($this->classStack, $node);
			
		}
		if($node instanceof Interface_) {
			$name=Util::fqn($node);
			$this->index->addInterface($name, $node, $this->filename);
			array_push($this->classStack, $node);
		}
		if($node instanceof ClassMethod && count($this->classStack)>0) {
			$classNode=$this->classStack[count($this->classStack)-1];
			$className=Util::fqn($classNode);
			$this->index->addMethod($className, $node->name, $node);
		}
		return null;
	}

	function leaveNode(Node $node) {
		if($node instanceof Class_ || $node instanceof Interface_) {
			array_pop($this->classStack);
		}
		return null;
	}

	function afterTraverse(array $nodes) { 
		return null;
	}
}

?>
