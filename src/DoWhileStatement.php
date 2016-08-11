<?php
namespace Guardrail;

use PhpParser\Node\Stmt\Do_;

class DoWhileStatement extends Do_ {
	static public function fromDo_(Do_ $from) {
		return new DoWhileStatement($from->cond, $from->stmts, $from->attributes);
	}
	public function getSubNodeNames() {
		return ["stmts","cond"];
	}
}