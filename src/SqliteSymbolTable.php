<?php namespace Scan;

/**
 * Class SqliteSymbolTable
 */
class SqliteSymbolTable implements SymbolTableInterface {
	private $con;

	function __construct() {
		$this->con = new PDO("sqlite:./symbol_table");
		$this->init();
	}

	function init() {
		$this->con->execute('
			create table classes( name varchar(255) not null primary key, file varchar(255) not null );
		');
	}

	function addClass($name, Class_ $class, $file) {
		$sql="INSERT INTO classes set name=?, file=?";
		try {
			$this->con->prepare($sql)->execute([$name, $file]);
		}
		catch(PDOException $e) {
			throw new Exception("Class $name has already been declared");
		}
	}

	function getClass($name) {
		$sql="SELECT file FROM classes WHERE name=?";
		$result = $this->con->prepare($sql)->execute([$name]);
		if(count($result)<1) {
			return null;
		} else {
			$fileName=$result[0]['file'];
		}

		$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
		$stmts = $parser->parse( file_get_contents($fileName ) );
		$grabber = new ClassGrabber($name);
		$traverser = new NodeTraverser;
		$traverser->addVisitor($grabber);

		if($stmts) {
			$traverser->traverse( $stmts );
		}
		return $grabber->getFoundClass();
	}

	function addMethod($className, $methodName, ClassMethod $method) {
		// TODO: Implement addMethod() method.
	}

	function getAllClasses() {
		// TODO: Implement getAllClasses() method.
	}

	function getClassMethod($className, $methodName) {
		// TODO: Implement getClassMethod() method.
	}

	function getClassMethods($className) {
		// TODO: Implement getClassMethods() method.
	}

}