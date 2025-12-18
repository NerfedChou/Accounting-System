<?php

declare(strict_types=1);

namespace Application\Handler\Account;

use Application\Command\Account\DeactivateAccountCommand;
use Application\Command\CommandInterface;
use Application\Dto\Account\AccountDto;
use Application\Handler\HandlerInterface;
use Domain\ChartOfAccounts\Repository\AccountRepositoryInterface;
use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\Shared\Event\EventDispatcherInterface;
use Domain\Shared\Exception\EntityNotFoundException;

/**
 * Handler for deactivating an account.
 *
 * @implements HandlerInterface<DeactivateAccountCommand>
 */
final readonly class DeactivateAccountHandler implements HandlerInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): AccountDto
    {
        assert($command instanceof DeactivateAccountCommand);

        $accountId = AccountId::fromString($command->accountId);

        // Find account
        $account = $this->accountRepository->findById($accountId);

        if ($account === null) {
            throw new EntityNotFoundException("Account not found: {$command->accountId}");
        }

        // Deactivate
        $account->deactivate();

        // Persist
        $this->accountRepository->save($account);

        // Dispatch events
        foreach ($account->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return new AccountDto(
            id: $account->id()->toString(),
            companyId: $account->companyId()->toString(),
            code: $account->code()->toString(),
            name: $account->name(),
            type: $account->code()->accountType()->value,
            normalBalance: $account->code()->accountType()->normalBalance()->value,
            isActive: $account->isActive(),
            parentAccountId: $account->parentAccountId()?->toString(),
            description: $account->description(),
            createdAt: (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        );
    }
}
