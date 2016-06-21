<?php
interface Traversable {

}

interface Iterator extends Traversable {

}

interface ArrayAccess {

}


function collator_create() { }

function collator_set_attribute() { }

function collator_compare() { }

function min($arr) { }

function max($arr) { }

function rand() { }

function mt_rand() { }

function _() { }

interface DateTimeInterface {
	public function diff ( DateTimeInterface $datetime2, $absolute = false );
	public function format ( $format );
	public function getOffset();
	public function getTimestamp();
	public function getTimezone();
	public function __wakeup();
}

class DateTimeImmutable implements DateTimeInterface {
	public function diff ( DateTimeInterface $datetime2, $absolute = false ) { }
	public function format ( $format ) { }
	public function getOffset() { }
	public function getTimestamp() { }
	public function getTimezone() { }
	public function __wakeup() { }
}

function date_diff($d1,$d2) { }

function memcache_connect($host) { }

abstract class FilterIterator extends IteratorIterator implements OuterIterator {
	/* Methods */
	public abstract function accept ();
	public function __construct ( Iterator $iterator ) { }
	public function current () { }
	public function getInnerIterator () { }
	public function key() { }
	public function next() { }
	public function rewind () { }
	public function valid () { }
}

abstract class RecursiveFilterIterator extends FilterIterator implements OuterIterator , RecursiveIterator
{
	public function __construct (RecursiveIterator $iterator) { }
	public function getChildren () { }
	public function hasChildren () { }
}

function cli_set_process_title() { }

function newrelic_disable_autorum() { }
function newrelic_end_transaction() { }
function newrelic_end_of_transaction() { }
function newrelic_ignore_transaction() {}
function newrelic_notice_error() { }
function newrelic_get_browser_timing_header() { }
function newrelic_get_browser_timing_footer() { }
function newrelic_name_transaction($name) { }

function curl_file_create($fileName, $mimeType="", $postname="") { }


class SplHeap {

}

class finfo { }

class XmlWriter { }

function array_column() { }
function boolval($val) { }
function opcache_reset() { }

function mysqli_connect() { }

function levenshtein($str1 , $str2) { }

class Exception {
	function __construct($message = "", $code = 0,$previous = NULL) {}
}

class ImagickException extends Exception {
}

class InvalidArgumentException extends Exception { }
class RuntimeException extends Exception { }
class BadMethodCallException extends Exception { }