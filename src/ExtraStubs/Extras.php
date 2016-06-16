<?php
interface Iterator {

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

class DateTimeImmutable {

}

function cli_set_process_title() { }

function newrelic_disable_autorum() { }
function newrelic_end_transaction() { }
function newrelic_end_of_transaction() { }
function newrelic_ignore_transaction() {}
function newrelic_notice_error() { }

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

class InvalidArgumentException extends Exception { }
class RuntimeException extends Exception { }
class BadMethodCallException extends Exception { }