<?php

namespace App\Service;

use App\Entity\CollabRequest;
use App\Entity\Contract;
use App\Repository\ContractClauseRepository;
use App\Repository\ContractTemplateRepository;

class LegalEngineService
{
    private ContractTemplateRepository $templateRepo;
    private ContractClauseRepository $clauseRepo;

    public function __construct(
        ContractTemplateRepository $templateRepo,
        ContractClauseRepository $clauseRepo
    ) {
        $this->templateRepo = $templateRepo;
        $this->clauseRepo = $clauseRepo;
    }

    public function generateContractContent(Contract $contract): string
    {
        $template = $this->templateRepo->findOneBy(['isMaster' => true]);
        if (!$template) {
            return $contract->getTerms() ?? '';
        }

        $collabRequest = $contract->getCollabRequest();
        $clauses = $this->clauseRepo->findBy(['isActive' => true, 'isMandatory' => true]);

        $clausesHtml = "<ul>";
        foreach ($clauses as $clause) {
            $clausesHtml .= "<li><strong>" . $clause->getTitle() . ":</strong> " . $clause->getContent() . "</li>";
        }
        $clausesHtml .= "</ul>";

        $content = $template->getContent();

        $variables = [
            '{{ title }}' => $contract->getTitle(),
            '{{ budget }}' => $contract->getAmount(),
            '{{ creator_name }}' => $contract->getCreator() ? $contract->getCreator()->getUsername() : 'N/A',
            '{{ collaborator_name }}' => $contract->getCollaborator() ? $contract->getCollaborator()->getCompanyName() : 'N/A',
            '{{ deliverables }}' => $contract->getTerms(),
            '{{ payment_terms }}' => $contract->getPaymentSchedule(),
            '{{ clauses }}' => $clausesHtml,
        ];

        return str_replace(array_keys($variables), array_values($variables), $content);
    }
}
