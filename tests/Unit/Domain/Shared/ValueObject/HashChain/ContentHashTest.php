<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\ValueObject\HashChain;

use Domain\Shared\ValueObject\HashChain\ContentHash;
use PHPUnit\Framework\TestCase;

final class ContentHashTest extends TestCase
{
    public function test_creates_from_string_content(): void
    {
        $hash = ContentHash::fromContent('test content');

        $this->assertNotEmpty($hash->toString());
        $this->assertEquals(64, strlen($hash->toString())); // SHA-256 = 64 hex chars
    }

    public function test_same_content_produces_same_hash(): void
    {
        $hash1 = ContentHash::fromContent('identical content');
        $hash2 = ContentHash::fromContent('identical content');

        $this->assertTrue($hash1->equals($hash2));
    }

    public function test_different_content_produces_different_hash(): void
    {
        $hash1 = ContentHash::fromContent('content A');
        $hash2 = ContentHash::fromContent('content B');

        $this->assertFalse($hash1->equals($hash2));
    }

    public function test_creates_from_array_deterministically(): void
    {
        $data = ['name' => 'John', 'age' => 30, 'active' => true];

        $hash1 = ContentHash::fromArray($data);
        $hash2 = ContentHash::fromArray($data);

        $this->assertTrue($hash1->equals($hash2));
    }

    public function test_array_order_does_not_affect_hash(): void
    {
        $data1 = ['name' => 'John', 'age' => 30];
        $data2 = ['age' => 30, 'name' => 'John'];

        $hash1 = ContentHash::fromArray($data1);
        $hash2 = ContentHash::fromArray($data2);

        // Keys are sorted, so order should not matter
        $this->assertTrue($hash1->equals($hash2));
    }

    public function test_json_serializable(): void
    {
        $hash = ContentHash::fromContent('test');
        $json = json_encode($hash);

        $this->assertIsString($json);
        $this->assertStringContainsString($hash->toString(), $json);
    }

    public function test_creates_genesis_hash(): void
    {
        $genesis = ContentHash::genesis('company-123');

        $this->assertNotEmpty($genesis->toString());
        $this->assertStringStartsWith('GENESIS:', $genesis->prefix());
    }
}
