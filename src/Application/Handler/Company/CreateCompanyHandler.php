<?php

declare(strict_types=1);

namespace Application\Handler\Company;

use Application\Command\CommandInterface;
use Application\Command\Company\CreateCompanyCommand;
use Application\Dto\Company\CompanyDto;
use Application\Handler\HandlerInterface;
use Domain\Company\Entity\Company;
use Domain\Company\Repository\CompanyRepositoryInterface;
use Domain\Company\ValueObject\Address;
use Domain\Company\ValueObject\TaxIdentifier;
use Domain\Shared\Event\EventDispatcherInterface;
use Domain\Shared\ValueObject\Currency;

/**
 * Handler for creating a company.
 *
 * @implements HandlerInterface<CreateCompanyCommand>
 */
final readonly class CreateCompanyHandler implements HandlerInterface
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(CommandInterface $command): CompanyDto
    {
        assert($command instanceof CreateCompanyCommand);

        $taxId = TaxIdentifier::fromString($command->taxId);

        // Check if tax ID already exists
        if ($this->companyRepository->existsByTaxId($taxId)) {
            throw new \DomainException('Tax ID already registered');
        }

        // Parse address string (assuming simple format for now, or use empty default)
        // Since command just has "address" string, we'll map it to street and use defaults/placeholders
        // In a real app, the command should have structured address fields.
        $address = Address::create(
            street: $command->address ?? 'Unknown Street',
            city: 'Unknown City',
            state: null,
            postalCode: null,
            country: 'Unknown'
        );

        // Create company
        $company = Company::create(
            companyName: $command->name,
            legalName: $command->name,
            taxId: $taxId,
            address: $address,
            currency: Currency::from($command->currency),
        );

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
