<?php

declare(strict_types=1);

namespace Tests\Integration;

use Infrastructure\Persistence\Mysql\Connection\PdoConnectionFactory;
use PHPUnit\Framework\TestCase;
use PDO;

abstract class BaseIntegrationTestCase extends TestCase
{
    protected ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we are testing against the test DB
        // But in Docker, we use ENV variables.
        $this->pdo = PdoConnectionFactory::getConnection();
        
        $this->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        
        $this->pdo = null;
        parent::tearDown();
    }
    
    protected function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }
}
