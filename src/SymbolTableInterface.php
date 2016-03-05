<?php

interface SymbolTableInterface
{
	function addClass($name, Class_ $class, $file);

	/**
	 * @param $name string Full namespace path to a class name
	 * @return \PhpParser\Builder\Class_
	 */
	function getClass($name);

	/**
	 * @param string      $className  Full namespace path to a class name
	 * @param string      $methodName Name of the method
	 * @param ClassMethod $method     Class method
	 * @return void
	 */
	function addMethod($className, $methodName, ClassMethod $method);

	/**
	 * @return Class_[]
	 */
	function getAllClasses();

	/**
	 * @param $className
	 * @param $methodName
	 * @return mixed
	 */
	function getClassMethod($className, $methodName);

	/**
	 * @param $className Full namespace path to a class
	 * @return ClassMethod[]
	 */
	function getClassMethods($className);
}