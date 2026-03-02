<?php

namespace App\Tests\Unit\Service\Collaboration;

use App\Entity\CollabRequest;
use App\Entity\Collaborator;
use App\Entity\Contract;
use App\Entity\ContractClause;
use App\Entity\ContractTemplate;
use App\Entity\Notification;
use App\Service\Collaboration\CollaborationFactory;
use PHPUnit\Framework\TestCase;

class CollaborationFactoryTest extends TestCase
{
    private CollaborationFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new CollaborationFactory();
    }

    public function testCreateCollabRequest(): void
    {
        $result = $this->factory->createCollabRequest();
        $this->assertInstanceOf(CollabRequest::class, $result);
    }

    public function testCreateCollaborator(): void
    {
        $result = $this->factory->createCollaborator();
        $this->assertInstanceOf(Collaborator::class, $result);
    }

    public function testCreateContract(): void
    {
        $result = $this->factory->createContract();
        $this->assertInstanceOf(Contract::class, $result);
    }

    public function testCreateContractClause(): void
    {
        $result = $this->factory->createContractClause();
        $this->assertInstanceOf(ContractClause::class, $result);
    }

    public function testCreateContractTemplate(): void
    {
        $result = $this->factory->createContractTemplate();
        $this->assertInstanceOf(ContractTemplate::class, $result);
    }

    public function testCreateNotification(): void
    {
        $result = $this->factory->createNotification();
        $this->assertInstanceOf(Notification::class, $result);
    }
}
