<?php
namespace Syslogic\DoctrineJsonFunctions\Tests\Query;

class PostgresqlTestCase extends DbTestCase
{
	public function setUp()
	{
		parent::setUp();
		ConfigLoader::load($this->configuration, ConfigLoader::POSTGRES);
	}
}
