<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use Domain\Company\Entity\Company;
use Domain\Company\ValueObject\Address;
use Domain\Company\ValueObject\CompanyId;
use Domain\Company\ValueObject\TaxIdentifier;
use Domain\Shared\ValueObject\Currency;
use Infrastructure\Persistence\Mysql\Repository\MysqlCompanyRepository;
use Tests\Integration\BaseIntegrationTestCase;

class MysqlCompanyRepositoryTest extends BaseIntegrationTestCase
{
    private MysqlCompanyRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MysqlCompanyRepository($this->pdo);
    }

    public function testSaveAndFindById(): void
    {
        $company = Company::create(
            'Test Company',
            'Test Company Legal',
            TaxIdentifier::fromString('TAX-12345'),
            Address::create('123 Main St', 'Test City', 'Test State', '12345', 'Test Country'),
            Currency::USD
        );

        $this->repository->save($company);

        $retrieved = $this->repository->findById($company->id());

        $this->assertNotNull($retrieved);
        $this->assertEquals('Test Company', $retrieved->companyName());
        $this->assertEquals('TAX-12345', $retrieved->taxId()->toString());
    }

    public function testFindByTaxId(): void
    {
        $taxId = TaxIdentifier::fromString('UNIQUE-TAX-999');
        
        $company = Company::create(
            'Tax Lookup Company',
            'Tax Lookup Legal',
            $taxId,
            Address::create('456 Tax St', 'Tax City', 'TX', '54321', 'USA'),
            Currency::USD
        );

        $this->repository->save($company);

        $retrieved = $this->repository->findByTaxId($taxId);

        $this->assertNotNull($retrieved);
        $this->assertEquals($company->id()->toString(), $retrieved->id()->toString());
    }

    public function testFindByIdReturnsNullForNonExistent(): void
    {
        $result = $this->repository->findById(CompanyId::generate());
        $this->assertNull($result);
    }
}
