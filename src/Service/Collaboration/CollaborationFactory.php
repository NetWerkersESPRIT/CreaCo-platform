<?php

namespace App\Service\Collaboration;

use App\Entity\CollabRequest;
use App\Entity\Collaborator;
use App\Entity\ContractClause;
use App\Entity\ContractTemplate;
use App\Entity\Contract;
use App\Entity\Notification;

class CollaborationFactory
{
    public function createCollabRequest(): CollabRequest
    {
        return new CollabRequest();
    }

    public function createCollaborator(): Collaborator
    {
        return new Collaborator();
    }

    public function createContractClause(): ContractClause
    {
        return new ContractClause();
    }

    public function createContractTemplate(): ContractTemplate
    {
        return new ContractTemplate();
    }

    public function createNotification(): Notification
    {
        return new Notification();
    }

    public function createContract(): \App\Entity\Contract
    {
        return new \App\Entity\Contract();
    }
}
