<?php

declare(strict_types=1);

namespace Application\Handler\Account;

use Application\Command\Account\CreateAccountCommand;
use Application\Command\CommandInterface;
use Application\Dto\Account\AccountDto;
use Application\Handler\HandlerInterface;
use Domain\ChartOfAccounts\Entity\Account;
use Domain\ChartOfAccounts\Repository\AccountRepositoryInterface;
use Domain\ChartOfAccounts\ValueObject\AccountCode;
use Domain\ChartOfAccounts\ValueObject\AccountId;
use Domain\Company\ValueObject\CompanyId;
use Domain\Shared\Event\EventDispatcherInterface;

/**
 * Handler for creating an account.
 *
 * @implements HandlerInterface<CreateAccountCommand>
 */
final readonly class CreateAccountHandler implements HandlerInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): AccountDto
    {
        assert($command instanceof CreateAccountCommand);

        $companyId = CompanyId::fromString($command->companyId);
        $accountCode = AccountCode::fromString($command->code);

        // Resolve parent account ID if provided
        $parentAccountId = $command->parentAccountId
            ? AccountId::fromString($command->parentAccountId)
            : null;

        // Create account (type derived from code per BR-COA-002)
        $account = Account::create(
            accountCode: $accountCode,
            accountName: $command->name,
            companyId: $companyId,
            description: $command->description,
            parentAccountId: $parentAccountId,
        );

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
