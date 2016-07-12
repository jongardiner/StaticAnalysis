<?php
namespace Scan\Abstractions;

interface FunctionLikeInterface {
	function getParameters();
	function getMinimumRequiredParameters();
	function getReturnType();
	function isStatic();
	function isInternal();
	function getName();
}