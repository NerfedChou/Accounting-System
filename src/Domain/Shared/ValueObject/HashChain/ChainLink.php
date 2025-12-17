<?php

declare(strict_types=1);

namespace Domain\Shared\ValueObject\HashChain;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Represents a link in a hash chain.
 * Contains the previous hash, current content hash, and timestamp.
 * The computed hash of this link becomes the "previous hash" for the next entry.
 */
final class ChainLink implements JsonSerializable
{
    public function __construct(
        private readonly ContentHash $previousHash,
        private readonly ContentHash $contentHash,
        private readonly DateTimeImmutable $timestamp
    ) {
    }

    public function previousHash(): ContentHash
    {
        return $this->previousHash;
    }

    public function contentHash(): ContentHash
    {
        return $this->contentHash;
    }

    public function timestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * Compute the hash of this link (becomes previous hash for next entry).
     */
    public function computeHash(): ContentHash
    {
        return ContentHash::fromContent(
            $this->previousHash->toString() .
            $this->contentHash->toString() .
            $this->timestamp->format('Y-m-d H:i:s.u')
        );
    }

    /**
     * Verify that this link correctly chains to the expected previous hash.
     */
    public function verify(ContentHash $expectedPreviousHash): bool
    {
        return $this->previousHash->equals($expectedPreviousHash);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'previous_hash' => $this->previousHash->toString(),
            'content_hash' => $this->contentHash->toString(),
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s.u'),
            'link_hash' => $this->computeHash()->toString(),
        ];
    }
}
