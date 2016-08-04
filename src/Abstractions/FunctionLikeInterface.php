<?php
namespace Scan\Abstractions;

interface FunctionLikeInterface {
	/** @return FunctionLikeParameter[] */
	function getParameters();
	function getMinimumRequiredParameters();
	function getReturnType();
	function isInternal();


	function getName();


	function getStartingLine();
	function isVariadic();
}