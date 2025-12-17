<?php

declare(strict_types=1);

namespace Domain\Shared\Exception;

class EntityNotFoundException extends DomainException
{
    public function __construct(string $message = 'Entity not found')
    {
        parent::__construct($message);
    }
}
