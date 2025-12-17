<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\ValueObject\HashChain;

use Domain\Shared\ValueObject\HashChain\ContentHash;
use Domain\Shared\ValueObject\HashChain\MerkleTree;
use PHPUnit\Framework\TestCase;

final class MerkleTreeTest extends TestCase
{
    /**
     * Create standard test leaves for multiple tests.
     *
     * @return array<ContentHash>
     */
    private function createFourLeaves(): array
    {
        return [
            ContentHash::fromContent('company-1'),
            ContentHash::fromContent('company-2'),
            ContentHash::fromContent('company-3'),
            ContentHash::fromContent('company-4'),
        ];
    }

    /**
     * Create two test leaves.
     *
     * @return array<ContentHash>
     */
    private function createTwoLeaves(): array
    {
        return [
            ContentHash::fromContent('a'),
            ContentHash::fromContent('b'),
        ];
    }

    public function test_creates_tree_from_single_leaf(): void
    {
        $leaf = ContentHash::fromContent('single leaf');
        $tree = MerkleTree::fromLeaves([$leaf]);

        $this->assertNotNull($tree->root());
        $this->assertEquals(1, $tree->leafCount());
    }

    public function test_creates_tree_from_multiple_leaves(): void
    {
        $tree = MerkleTree::fromLeaves($this->createFourLeaves());

        $this->assertEquals(4, $tree->leafCount());
        $this->assertNotNull($tree->root());
    }

    public function test_same_leaves_produce_same_root(): void
    {
        $leaves = $this->createTwoLeaves();

        $tree1 = MerkleTree::fromLeaves($leaves);
        $tree2 = MerkleTree::fromLeaves($leaves);

        $this->assertTrue($tree1->root()->equals($tree2->root()));
    }

    public function test_different_leaves_produce_different_root(): void
    {
        $tree1 = MerkleTree::fromLeaves([ContentHash::fromContent('a')]);
        $tree2 = MerkleTree::fromLeaves([ContentHash::fromContent('b')]);

        $this->assertFalse($tree1->root()->equals($tree2->root()));
    }

    public function test_generates_inclusion_proof(): void
    {
        $tree = MerkleTree::fromLeaves($this->createFourLeaves());
        $proof = $tree->generateProof(1);

        $this->assertNotEmpty($proof->path());
    }

    public function test_verifies_valid_proof(): void
    {
        $leaves = $this->createFourLeaves();
        $tree = MerkleTree::fromLeaves($leaves);
        $targetLeaf = $leaves[2];
        $proof = $tree->generateProof(2);

        $this->assertTrue($proof->verify($targetLeaf, $tree->root()));
    }

    public function test_rejects_invalid_proof(): void
    {
        $tree = MerkleTree::fromLeaves($this->createTwoLeaves());
        $proof = $tree->generateProof(0);

        $wrongLeaf = ContentHash::fromContent('wrong');
        $this->assertFalse($proof->verify($wrongLeaf, $tree->root()));
    }

    public function test_handles_odd_number_of_leaves(): void
    {
        $leaves = [
            ContentHash::fromContent('a'),
            ContentHash::fromContent('b'),
            ContentHash::fromContent('c'),
        ];

        $tree = MerkleTree::fromLeaves($leaves);

        $this->assertEquals(3, $tree->leafCount());
        $this->assertNotNull($tree->root());
    }
}
