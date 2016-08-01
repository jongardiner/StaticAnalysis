<?php
namespace Scan\Abstractions;


interface ClassInterface {
	function getName();
	function isDeclaredAbstract();
	function getMethodNames();
	function getParentClassName();
	function getInterfaceNames();
	function getMethod($name);
	function hasConstant($name);
	function isInterface();
}