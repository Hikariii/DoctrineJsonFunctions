<?php
namespace Syslogic\DoctrineJsonFunctions\Tests\Query;

use Doctrine\DBAL\Platforms\MySQL57Platform;

class MysqlTestCase extends DbTestCase
{
	public function setUp()
	{
		parent::setUp(array('driver' => 'pdo_sqlite', 'memory' => true , 'platform' => new MySQL57Platform() ));
		ConfigLoader::load($this->configuration, ConfigLoader::MYSQL);
	}
}
