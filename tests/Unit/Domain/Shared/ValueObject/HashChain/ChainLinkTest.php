<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\ValueObject\HashChain;

use DateTimeImmutable;
use Domain\Shared\ValueObject\HashChain\ChainLink;
use Domain\Shared\ValueObject\HashChain\ContentHash;
use PHPUnit\Framework\TestCase;

final class ChainLinkTest extends TestCase
{
    public function test_creates_chain_link(): void
    {
        $previousHash = ContentHash::fromContent('previous');
        $contentHash = ContentHash::fromContent('current content');
        $timestamp = new DateTimeImmutable('2025-01-01 10:00:00');

        $link = new ChainLink($previousHash, $contentHash, $timestamp);

        $this->assertTrue($link->previousHash()->equals($previousHash));
        $this->assertTrue($link->contentHash()->equals($contentHash));
        $this->assertEquals($timestamp, $link->timestamp());
    }

    public function test_computes_deterministic_hash(): void
    {
        $previousHash = ContentHash::fromContent('previous');
        $contentHash = ContentHash::fromContent('current');
        $timestamp = new DateTimeImmutable('2025-01-01 10:00:00');

        $link1 = new ChainLink($previousHash, $contentHash, $timestamp);
        $link2 = new ChainLink($previousHash, $contentHash, $timestamp);

        $this->assertTrue($link1->computeHash()->equals($link2->computeHash()));
    }

    public function test_different_previous_hash_produces_different_link_hash(): void
    {
        $contentHash = ContentHash::fromContent('same content');
        $timestamp = new DateTimeImmutable('2025-01-01 10:00:00');

        $link1 = new ChainLink(ContentHash::fromContent('prev1'), $contentHash, $timestamp);
        $link2 = new ChainLink(ContentHash::fromContent('prev2'), $contentHash, $timestamp);

        $this->assertFalse($link1->computeHash()->equals($link2->computeHash()));
    }

    public function test_verifies_against_expected_previous_hash(): void
    {
        $previousHash = ContentHash::fromContent('expected previous');
        $contentHash = ContentHash::fromContent('content');
        $timestamp = new DateTimeImmutable();

        $link = new ChainLink($previousHash, $contentHash, $timestamp);

        $this->assertTrue($link->verify($previousHash));
        $this->assertFalse($link->verify(ContentHash::fromContent('wrong')));
    }

    public function test_chain_integrity_across_multiple_links(): void
    {
        // Genesis
        $genesis = ContentHash::genesis('test-company');

        // First entry
        $content1 = ContentHash::fromArray(['entry' => 1]);
        $link1 = new ChainLink($genesis, $content1, new DateTimeImmutable('2025-01-01 10:00:00'));
        $hash1 = $link1->computeHash();

        // Second entry links to first
        $content2 = ContentHash::fromArray(['entry' => 2]);
        $link2 = new ChainLink($hash1, $content2, new DateTimeImmutable('2025-01-01 10:00:01'));
        $hash2 = $link2->computeHash();

        // Third entry links to second
        $content3 = ContentHash::fromArray(['entry' => 3]);
        $link3 = new ChainLink($hash2, $content3, new DateTimeImmutable('2025-01-01 10:00:02'));

        // Verify chain
        $this->assertTrue($link1->verify($genesis));
        $this->assertTrue($link2->verify($hash1));
        $this->assertTrue($link3->verify($hash2));

        // Tampering detection: link2 should NOT verify against genesis
        $this->assertFalse($link2->verify($genesis));
    }
}
