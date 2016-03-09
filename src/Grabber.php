<?php namespace Scan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitor;

class ClassGrabber implements NodeVisitor {
	private $searchingForName;
	private $foundClass=null;

	function __construct( $searchingForName="" ) {
		if($searchingForName) {
			$this->initForSearch($searchingForName);
		}
	}

	function initForSearch( $searchingForName) {
		$this->searchingForName = $searchingForName;
		$this->foundClass = null;
	}

	/**
	 * @return Class_|null
	 */
	function getFoundClass() {
		return $this->foundClass;
	}

	function beforeTraverse(array $nodes) {
		return null;
	}

	function enterNode(Node $node) {
		if ($node instanceof Class_ && strcasecmp(Util::fqn($node),$this->searchingForName)==0) {
			$this->foundClass = $node;
		}
	}

	function leaveNode(Node $node) {
		return null;
	}

	function afterTraverse(array $nodes) {
		return null;
	}


}

?>
