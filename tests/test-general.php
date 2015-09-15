<?php

class AvhEmPermalinksTestGeneral extends WP_UnitTestCase
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Run a simple test to ensure that the tests are running
     */
    public function testAlwaysTrue()
    {
        $this->assertTrue(true);
    }
}
