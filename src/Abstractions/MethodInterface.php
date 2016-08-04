<?php
namespace Scan\Abstractions;

interface MethodInterface extends FunctionLikeInterface {
	function isAbstract();
	function isStatic();
	function getAccessLevel();
}