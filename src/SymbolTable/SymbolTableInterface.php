<?php namespace Scan\SymbolTable;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;

interface SymbolTableInterface
{
	function addClass($name, Class_ $class, $file);

	function addInterface($name, Interface_ $interface, $file);

	/**
	 * @param string      $className  Full namespace path to a class name
	 * @param string      $methodName Name of the method
	 * @param ClassMethod $method     Class method
	 * @return void
	 */
	function addMethod($className, $methodName, ClassMethod $method);

	/**
	 * @param $className
	 * @return string
	 */
	function getClassFile($className);

	/**
	 * @param $interfaceName
	 * @return string
	 */
	function getInterfaceFile($interfaceName);

	/**
	 * @param $methodName
	 * @return string
	 */
	function getFunctionFile($methodName);

	/**
	 * @param $name
	 * @return bool
	 */
	function ignoreType($name);

	function getClass($name);
	function getFunction($name);
	function getInterface($name);
	function getClassMethods($name);
}