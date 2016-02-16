<?php namespace Scan;
require_once '../../vendor/autoload.php';

use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;

$symbolTable = new SymbolTable();

$traverser = new NodeTraverser;
$traverser->addVisitor(new NameResolver());
$traverser->addVisitor(new SymbolTableIndexer( $symbolTable ) );


$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
echo "Phase 1\n";
$it=new \RecursiveDirectoryIterator("../../tests");
foreach($it as $file) {
	if($file->getExtension()=="php" && $file->isFile()) {
		try {
		    echo " - ".$file->getPathname()."\n";
		    $fileData = file_get_contents( $file->getPathname() );
		    $stmts = $parser->parse($fileData);
		    $traverser->traverse( $stmts );
		} catch (\Error $e) {
		    echo 'Parse Error: '. $e->getMessage()."\n";
		}
	}
}


echo "\nPhase 2\n";

$checker = new SignatureChecker( $symbolTable );
foreach($symbolTable->getClasses() as $class) {
	$checker->checkClassMethods( $class );
}
echo "Done\n\n";

