<?php

namespace Syslogic\DoctrineJsonFunctions\Query\AST\Functions\Mysql;

/**
 * "JSON_APPEND" "(" StringPrimary "," StringPrimary "," Literal { "," StringPrimary "," Literal }* ")"
 */
class JsonAppend extends JsonSet
{
	const FUNCTION_NAME = 'JSON_APPEND';
}
