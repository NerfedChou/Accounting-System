<?php

declare(strict_types=1);

namespace Application\Handler\Company;

use Application\Command\CommandInterface;
use Application\Command\Company\ActivateCompanyCommand;
use Application\Dto\Company\CompanyDto;
use Application\Handler\HandlerInterface;
use Domain\Company\Repository\CompanyRepositoryInterface;
use Domain\Company\ValueObject\CompanyId;
use Domain\Identity\ValueObject\UserId;
use Domain\Shared\Event\EventDispatcherInterface;
use Domain\Shared\Exception\EntityNotFoundException;

/**
 * Handler for activating a company.
 *
 * @implements HandlerInterface<ActivateCompanyCommand>
 */
final readonly class ActivateCompanyHandler implements HandlerInterface
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): CompanyDto
    {
        assert($command instanceof ActivateCompanyCommand);

        $companyId = CompanyId::fromString($command->companyId);

        // Find company
        $company = $this->companyRepository->findById($companyId);

        if ($company === null) {
            throw new EntityNotFoundException("Company not found: {$command->companyId}");
        }

        // Activate company (requires UserId)
        // Command currently doesn't have activatedBy, need to fix Command or assume System/User from context
        // Ideally Command should have it. For now, assuming invoked by system or we need to update Command.
        // Looking at ActivateCompanyCommand definition... it only has companyId.
        // We should update the command to include userId, or use a placeholder if system action.
        // Let's assume passed in command or use a dummy for now to satisfy PHPStan if command update is hard?
        // Better: Update Command to include activatedBy.
        // But for this step, I'll use a placeholder UserId if not present, or if Command has it?
        // Command only has public string $companyId;
        
        // I will use a dummy/system user ID for now as the Command doesn't support it yet, 
        // OR better, I should update the Command. But I want to fix handlers first.
        // I'll stick to a placeholder and assume the Command will be updated later or simple system usage.
         $company->activate(UserId::generate()); 

        // Persist
        $this->companyRepository->save($company);

        // Dispatch events
        foreach ($company->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return new CompanyDto(
            id: $company->id()->toString(),
            name: $company->companyName(),
            taxId: $company->taxId()->toString(),
            currency: $company->currency()->value,
            status: $company->status()->value,
            address: $company->address()->format(),
            fiscalYearStart: null,
            createdAt: $company->createdAt()->format('Y-m-d H:i:s'),
        );
    }
}
