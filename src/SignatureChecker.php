<?php namespace Scan;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;

class SignatureChecker {
	/**
	 * @var SymbolTableInterface
	 */
	private $symbolTable;

	private $basePath;

	function __construct($basePath, SymbolTableInterface $table) {
		$this->symbolTable=$table;
		$this->basePath=$basePath;
	}

	function checkAncestry($fileName, Class_ $node) {
		$current = $node;
		while($node && $node->extends) {
			$parent=Util::implodeParts($node->extends);
			if($this->symbolTable->ignoreType($parent)) {
				return;
			} else {
				$node = $this->symbolTable->getClass($parent);

				if (!$node) {
					echo $fileName . " " . $current->getLine() . ":Unable to find parent $parent\n";
				}
			}
		}

	}

	function checkCatch($fileName, Node\Stmt\Catch_ $node) {
		$name = Util::implodeParts($node->type);
		if($this->symbolTable->ignoreType($name)) {
			return;
		}
		if(!$this->symbolTable->getClassFile($name)) {
			echo $fileName . " " . $node->getLine() . ": attempt to catch unknown type: $name\n";
		}
	}

	function findParentWithMethod(Class_ $node, $name) {
		$current = $node;
		while(true) {
			if($node->extends) {
				$parent=Util::implodeParts($node->extends);
				$node=$this->symbolTable->getClass($parent);

				if(!$node) {
					$file = $this->symbolTable->getClassFile(Util::fqn($current));
					//echo $file." ".$current->getLine().":Unable to find parent $parent\n";
					return null;
				}

				$method=$this->symbolTable->getClassMethod(Util::fqn($node), $name);
				if ($method) {
					return [$node, $method];
				}
			} else {
				return null;
			}
		} 
	}

	function checkInterfaces(Class_ $node) {
		if($node->implements) {
			$arr = is_array($node->implements) ? $node->implements : [$node->implements];
			foreach ($arr as $interface) {
				$name = Util::fqn($interface);
				if($name) {
					$file = $this->symbolTable->getInterfaceFile($name);
					if (!$file) {
						echo $node->getLine() . ": " . $node->name . " implements unknown interface " . $name . "\n";
					}
				}
			}
		}
	}

	function checkNewCall($fileName, Node\Expr\New_ $node) {
		if($node->class instanceof Name) {
			$name=Util::implodeParts($node->class);
			if(!$this->symbolTable->ignoreType($name)) {
				$class = $this->symbolTable->getClassFile($name);
				if (!$class) {
					echo $fileName . " " . $node->getLine() . ": attempt to instantiate unknown class $name\n";
				}
			}
		}
	}

	function checkClassMethods($fileName, Class_  $node) {
		$this->checkInterfaces($node);
		foreach($this->symbolTable->getClassMethods(Util::fqn($node)) as $name=>$methodNode) {
			list($parentClass,$parentMethod)=$this->findParentWithMethod( $node, $methodNode->name );
			if ($parentMethod && $methodNode->name!="__construct") {
				// $this->checkMethod( $node, $methodNode, $parentClass, $parentMethod );
			}
		}
	}

	function checkStaticCall($fileName, StaticCall $call, Class_ $insideClass=null) {
		if($call->class instanceof Name) {
			$name=Util::implodeParts($call->class);
			// Todo
			if($name=='self' || $name=='static' || $name=='parent') {
				//echo "Static call to $name\n";
				return;
			}
			if($this->symbolTable->ignoreType($name)) {
				return;
			}

			$class=$this->symbolTable->getClassFile($name);
			if(!$class && !$this->symbolTable->ignoreType($name)) {
				echo "$fileName ".$call->getLine().": Static call to unknown class ".Util::implodeParts($call->class)."::".$call->name."\n";
			}
		}
	}

	function checkMethod(Class_ $class, ClassMethod $method, Class_ $parentClass, ClassMethod $parentMethod) {
		$visibility=Util::getMethodAccessLevel($method);
		$oldVisibility=Util::getMethodAccessLevel($parentMethod);
		// "public" and "protected" can be redefined," private can not.
		$fileName=$this->symbolTable->getClassFile(Util::fqn($class));
		$fileName=str_replace($this->basePath, "", $fileName);
		if( 
			$oldVisibility!=$visibility && $oldVisibility=="private"
		) {
			echo $fileName." ".$method->getLine().":Access level mismatch in ".$method->name."() ".Util::getMethodAccessLevel($method)." vs ".Util::getMethodAccessLevel($parentMethod)."\n";
		}
		$count1=count($method->params);
		$count2=count($parentMethod->params);
		if( $count1 != $count2)  {
			echo $fileName." ".$method->getLine().": parameter mismatch ".Util::methodSignatureString($method)." vs ".Util::finalPart($parentClass->name)."::".Util::methodSignatureString($parentMethod)."\n";
		} else foreach($method->params as $index=>$param) {
			$parentParam=$parentMethod->params[$index];
			$name1=Util::implodeParts($param->type);
			$name2=Util::implodeParts($parentParam->type);
			if(
				strcasecmp($name1,$name2) !== 0
			) {
				echo $fileName." ".$method->getLine().": parameter mismatch ".Util::methodSignatureString($method)." vs ".Util::finalPart($parentClass->name)."::".Util::methodSignatureString($parentMethod)."\n";
				break;
			}
			$nameLower=strtolower($name1);
			if($nameLower!="" && $nameLower!="array") {
				$file = $this->symbolTable->getClassFile($name1) ?: $this->symbolTable->getInterfaceFile($name1);
				if (!$file && !$this->symbolTable->ignoreType($name1)) {
					echo $fileName . " " . $method->getLine() . ":reference to an unknown type $name1\n";
				}
			}
		}
	}
}
