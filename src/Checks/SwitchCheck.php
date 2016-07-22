<?php
namespace Scan\Checks;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;
use Scan\Scope;


class SwitchCheck extends BaseCheck
{
	function getCheckNodeTypes() {
		return [ \PhpParser\Node\Stmt\Switch_::class ];
	}

	static function endWithBreak(array $stmts) {
		$lastStatement = end($stmts);
		$count=0;
		foreach($stmts as $stmt) {
			if(!$stmt instanceof \PhpParser\Node\Stmt\Nop) {
				$count++;
			}
		}
		return
			$count==0 ||
			$lastStatement instanceof \PhpParser\Node\Stmt\Break_ ||
			$lastStatement instanceof \PhpParser\Node\Stmt\Return_ ||
			$lastStatement instanceof \PhpParser\Node\Expr\Exit_ ||
			(
				$lastStatement instanceof \PhpParser\Node\Expr\FuncCall &&
				$lastStatement->name instanceof \PhpParser\Node\Name &&
				$lastStatement->name=="die"
			);
	}

	function allIfBranchesExit(\PhpParser\Node\Stmt\If_ $lastStatement) {
		if(!$lastStatement->else && !$lastStatement->elseifs) {
			return false;
		}
		$trueCond = self::allBranchesExit($lastStatement->stmts);
		if(!$trueCond) {
			return false;
		}
		if($lastStatement->else && !self::allBranchesExit($lastStatement->else->stmts)) {
			return false;
		}
		if($lastStatement->elseifs) {
			foreach($lastStatement->elseifs as $elseIf) {
				if(!self::allBranchesExit($elseIf->stmts)) {
					return false;
				}
			}
		}
		return true;
	}

	function allSwitchCasesExit(\PhpParser\Node\Stmt\Switch_ $lastStatement) {
		$hasDefault = false;
		foreach($lastStatement->cases as $case) {
			if(!$case->cond) {
				$hasDefault = true;
			}
			$stmts = $case->stmts;

			// Remove the trailing break (if found) and just look for a return the statement prior
			if(end($stmts) instanceof Break_) {
				$stmts=array_slice($stmts, 0, -1);
			}
			if($case->stmts && !self::allBranchesExit($stmts)) {
				return false;
			}
		}
		return $hasDefault;
	}


	/**
	 * @param \PhpParser\Node\Stmt[] $stmts
	 * @param $allowBreak
	 */
	function allBranchesExit(array $stmts) {
		$lastStatement=end($stmts);
		if(!$lastStatement) {
			return false;
		} else if($lastStatement instanceof Exit_ || $lastStatement instanceof Return_) {
			return true;
		} else if($lastStatement instanceof If_) {
			return self::allIfBranchesExit($lastStatement);
		} else if($lastStatement instanceof Switch_) {
			return self::allSwitchCasesExit($lastStatement);
		} else {
			return false;
		}
	}

	/**
	 * @param                              $fileName
	 * @param \PhpParser\Node\Stmt\Switch_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		if(is_array($node->cases)) {
			$nextError=null;
			/* Note: this algorithm (intentionally) doesn't output an error in the
			   final case clause.  A missing break there has no effect.
			*/

			foreach($node->cases as $index=>$case) {
				if($nextError) {
					$comments = $case->getAttribute('comments');
					if(is_array($comments)) {
						/** @var \PhpParser\Comment\Doc $comment */
						foreach ($comments as $comment) {
							if(preg_match("/fall *through/i", $comment)) {
								$nextError = null;
							}
						}
					}
					if($nextError) {
						$this->emitError($fileName, $nextError, "Control flow", "Switch case does not end with break statement");
						$nextError = null;
					}
				}
				if (!self::endWithBreak($case->stmts) && !self::allBranchesExit($case->stmts)) {
					$nextError = $case;
				}
			}
		}
	}
}
