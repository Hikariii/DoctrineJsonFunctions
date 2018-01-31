<?php
namespace Syslogic\DoctrineJsonFunctions\Tests\Query\Mysql;

class JsonAppendTest extends \Syslogic\DoctrineJsonFunctions\Tests\Query\MysqlTestCase
{
	public function testJsonAppend()
	{
		$q = $this->entityManager->createQuery("SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', 1) from Syslogic\DoctrineJsonFunctions\Tests\Entities\Blank");
		$this->assertEquals(
			"SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', 1) AS sclr_0 FROM Blank b0_",
			$q->getSql()
		);

		$q = $this->entityManager->createQuery("SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', NULL) from Syslogic\DoctrineJsonFunctions\Tests\Entities\Blank");
		$this->assertEquals(
			"SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', NULL) AS sclr_0 FROM Blank b0_",
			$q->getSql()
		);

		$q = $this->entityManager->createQuery("SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', false) from Syslogic\DoctrineJsonFunctions\Tests\Entities\Blank");
		$this->assertEquals(
			"SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', false) AS sclr_0 FROM Blank b0_",
			$q->getSql()
		);

		$q = $this->entityManager->createQuery("SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', (SELECT 4)) from Syslogic\DoctrineJsonFunctions\Tests\Entities\Blank");
		$this->assertEquals(
			"SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', false) AS sclr_0 FROM Blank b0_",
			$q->getSql()
		);

		$dql = "SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', b.id) FROM Syslogic\DoctrineJsonFunctions\Tests\Entities\Blank b";
		$q = $this->entityManager->createQuery($dql);
		$sql = "SELECT JSON_APPEND('[\"a\", [\"b\", \"c\"], \"d\"]', '$[1]', 1) AS sclr_0 FROM Blank b0_";
		$this->assertEquals($sql, $q->getSql());
	}
}
