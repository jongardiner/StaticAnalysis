<?php
namespace Guardrail\Abstractions;

use Guardrail\Abstractions\FunctionLikeParameter;

interface FunctionLikeInterface {
	/** @return FunctionLikeParameter[] */
	function getParameters();
	function getMinimumRequiredParameters();
	function getReturnType();
	function getDocBlockReturnType();
	function isInternal();


	function getName();


	function getStartingLine();
	function isVariadic();
}