<?php
namespace Scan\Abstractions;


class ReflectedClassMethod implements FunctionLikeInterface {
	private $refl;

	function __construct(\ReflectionMethod $refl) {
		$this->refl = $refl;
	}

	function isStatic() {
		return $this->refl->isStatic();
	}

	function isInternal() {
		return true;
	}

	function getReturnType() {
		return "";
	}

	function isAbstract() {
		return $this->refl->isAbstract();
	}

	function getAccessLevel() {
		if($this->refl->isPrivate()) return "private";
		if($this->refl->isPublic()) return "public";
		if($this->refl->isProtected()) return "protected";
	}

	function getMinimumRequiredParameters() {
		if(strcasecmp($this->refl->class,'imagick')==0 && strcasecmp($this->refl->getName(),'__construct')==0) {
			return 0;
		}
		return $this->refl->getNumberOfRequiredParameters();
	}

	function getParameters() {
		$ret = [];
		$params = $this->refl->getParameters();
		/** @var ReflectionParameter $param */
		foreach($params as $param) {
			$type = $param->getClass() ? $param->getClass()->name : '';
			$ret[] = new FunctionLikeParameter( $type , $param->name, $param->isOptional() );
		}
		return $ret;
	}

	function getName() {
		return $this->refl->getName();
	}
}