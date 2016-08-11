<?php
namespace Guardrail\Abstractions;

use Guardrail\Abstractions\FunctionLikeInterface;

interface MethodInterface extends FunctionLikeInterface {
	function isAbstract();
	function isStatic();
	function getAccessLevel();
}