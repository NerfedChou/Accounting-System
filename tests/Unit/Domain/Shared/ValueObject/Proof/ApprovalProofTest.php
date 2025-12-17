<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\ValueObject\Proof;

use DateTimeImmutable;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\ValueObject\HashChain\ContentHash;
use Domain\Shared\ValueObject\Proof\ApprovalProof;
use PHPUnit\Framework\TestCase;

final class ApprovalProofTest extends TestCase
{
    public function test_creates_approval_proof(): void
    {
        $approverId = UserId::generate();
        $entityHash = ContentHash::fromContent('transaction data');

        $proof = ApprovalProof::create(
            entityType: 'Transaction',
            entityId: 'txn-123',
            approvalType: 'negative_equity',
            approverId: $approverId,
            entityHash: $entityHash,
            notes: 'Approved for year-end adjustment'
        );

        $this->assertEquals('Transaction', $proof->entityType());
        $this->assertEquals('txn-123', $proof->entityId());
        $this->assertEquals('negative_equity', $proof->approvalType());
        $this->assertTrue($proof->approverId()->equals($approverId));
        $this->assertNotNull($proof->proofId());
    }

    public function test_computes_deterministic_proof_hash(): void
    {
        $approverId = UserId::generate();
        $entityHash = ContentHash::fromContent('same data');
        $timestamp = new DateTimeImmutable('2025-01-01 10:00:00');

        $proof = ApprovalProof::createWithTimestamp(
            entityType: 'Transaction',
            entityId: 'txn-123',
            approvalType: 'high_value',
            approverId: $approverId,
            entityHash: $entityHash,
            approvedAt: $timestamp,
            notes: null
        );

        $hash1 = $proof->computeProofHash();
        $hash2 = $proof->computeProofHash();

        $this->assertTrue($hash1->equals($hash2));
    }

    public function test_verifies_entity_unchanged(): void
    {
        $approverId = UserId::generate();
        $originalHash = ContentHash::fromContent('original data');

        $proof = ApprovalProof::create(
            entityType: 'Transaction',
            entityId: 'txn-123',
            approvalType: 'backdated',
            approverId: $approverId,
            entityHash: $originalHash,
            notes: null
        );

        // Entity unchanged
        $this->assertTrue($proof->verify($originalHash));

        // Entity was tampered
        $tamperedHash = ContentHash::fromContent('modified data');
        $this->assertFalse($proof->verify($tamperedHash));
    }

    public function test_json_serializable(): void
    {
        $approverId = UserId::generate();
        $entityHash = ContentHash::fromContent('data');

        $proof = ApprovalProof::create(
            entityType: 'Transaction',
            entityId: 'txn-123',
            approvalType: 'negative_equity',
            approverId: $approverId,
            entityHash: $entityHash,
            notes: 'Test'
        );

        $json = json_encode($proof);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('proof_id', $decoded);
        $this->assertArrayHasKey('entity_type', $decoded);
        $this->assertArrayHasKey('proof_hash', $decoded);
    }
}
