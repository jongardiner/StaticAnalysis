<?php namespace Scan\NodeVisitors;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitor;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;
use Scan\Util;

class Grabber implements NodeVisitor {
	const FROM_NAME=1;
	const FROM_FQN=2;

	private $searchingForName;
	private $foundClass=null;
	private $classType=Class_::class;
	private $fromVar="fqn";

	function __construct( $searchingForName="", $classType=Class_::class, $fromVar=self::FROM_FQN ) {
		if($searchingForName) {
			$this->initForSearch($searchingForName, $classType, $fromVar);
		}
	}

	function initForSearch( $searchingForName, $classType=Class_::class, $fromVar="fqn") {
		$this->searchingForName = $searchingForName;
		$this->classType=$classType;
		$this->foundClass = null;
		$this->fromVar=$fromVar;
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
		if (strcasecmp(get_class($node),$this->classType)==0) {

			$var = ($this->fromVar == self::FROM_FQN ? Util::fqn($node) : $node->name);
			if(strcasecmp($var,$this->searchingForName)==0) {
				$this->foundClass = $node;

			}
		}
	}

	function leaveNode(Node $node) {
		return null;
	}

	function afterTraverse(array $nodes) {
		return null;
	}

	static function getClassFromStmts( $stmts, $className, $classType=Class_::class, $fromVar=self::FROM_FQN) {
		$traverser = new NodeTraverser;
		$traverser->addVisitor(new NameResolver());
		$grabber = new Grabber($className, $classType, $fromVar);
		$traverser->addVisitor($grabber);
		$traverser->traverse( $stmts );
		return $grabber->getFoundClass();
	}

	static function getClassFromFile( $fileName, $className, $classType=Class_::class ) {
		static $lastFile="";
		static $lastContents;
		if($lastFile==$fileName) {
			$stmts = $lastContents;
		} else {
			$lastFile = $fileName;
			$contents = file_get_contents($fileName);
			$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
			$lastContents = $stmts = $parser->parse($contents);
		}

		if($stmts) {
			return self::getClassFromStmts($stmts, $className, $classType);
		}
		return null;
	}


}

?>
