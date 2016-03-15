<?php namespace Scan\SymbolTable;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Class SqliteSymbolTable
 */
class SqliteSymbolTable extends BaseSymbolTable implements SymbolTableInterface {
	private $con;
	const TYPE_CLASS="class";
	const TYPE_FUNCTION="function";
	const TYPE_INTERFACE="interface";

	function __construct() {
		$this->con = new PDO("sqlite:./symbol_table.sqlite3");
		$this->init();
	}

	function init() {
		$this->con->execute('
			create table symbol_table( name varchar(255) not null primary key, type enum("class","interface","function"), file varchar(255) not null );
		');
	}

	private function addType($name, $file, $type) {
		$sql="INSERT INTO symbol_table set name=?, file=?, type";
		try {
			$this->con->prepare($sql)->execute([$name, $file]);
		}
		catch(PDOException $e) {
			throw new Exception("Class $name has already been declared");
		}
	}

	function getType($name, $type) {
		$sql="SELECT file FROM symbol_table WHERE name=?";
		$result = $this->con->prepare($sql)->execute([$name, $file]);
		if(count($result)>0) {
			return $result[0][0];
	 	} else {
			return "";
		}
	}

	function addClass($name, Class_ $class, $file) {
		$this->addType($name, $file, self::TYPE_CLASS);
	}

	function addInterface($name, Interface_ $interface, $file) {
		$this->addType($name, $file, self::TYPE_INTERFACE);
	}

	function addMethod($className, $methodName, ClassMethod $method) {
		// TODO: Implement addMethod() method.
	}

	function getClassFile($className) {
		return $this->getType($className, self::TYPE_CLASS);
	}

	function getInterfaceFile($interfaceName) {
		return $this->getType($interfaceName, self::TYPE_INTERFACE);
	}

	function getFunctionFile($functionName) {
		return $this->getType($functionName, self::TYPE_FUNCTION);
	}
}