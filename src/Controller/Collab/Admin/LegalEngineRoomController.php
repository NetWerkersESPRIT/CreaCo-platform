<?php

namespace App\Controller\Collab\Admin;

use App\Entity\ContractClause;
use App\Entity\ContractTemplate;
use App\Service\Collaboration\CollaborationFactory;
use App\Repository\ContractClauseRepository;
use App\Repository\ContractTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/collab/legal-engine')]
class LegalEngineRoomController extends AbstractController
{
    #[Route('/', name: 'admin_collab_legal_engine', methods: ['GET'])]
    public function index(ContractClauseRepository $clauseRepo, ContractTemplateRepository $templateRepo, EntityManagerInterface $em, Request $request, CollaborationFactory $factory): Response
    {
        $session = $request->getSession();
        if ($session->get('user_role') !== 'ROLE_ADMIN') {
            throw $this->createAccessDeniedException("Strictly restricted to Agency Administrators.");
        }

        // Seed default clauses if empty
        if ($clauseRepo->count([]) === 0) {
            $this->seedDefaults($em, $factory);
        }

        return $this->render('back/collab/legal_engine_room.html.twig', [
            'clauses' => $clauseRepo->findAll(),
            'master_template' => $templateRepo->findOneBy(['isMaster' => true]),
        ]);
    }

    #[Route('/clause/toggle/{id}', name: 'admin_collab_clause_toggle', methods: ['POST'])]
    public function toggleClause(ContractClause $clause, EntityManagerInterface $em): Response
    {
        $clause->setIsActive(!$clause->isActive());
        $em->flush();

        return $this->json(['success' => true, 'isActive' => $clause->isActive()]);
    }

    #[Route('/clause/edit', name: 'admin_collab_clause_edit', methods: ['POST'])]
    public function editClause(Request $request, EntityManagerInterface $em, ContractClauseRepository $repo): Response
    {
        $data = json_decode($request->getContent(), true);
        $clause = $repo->find($data['id']);

        if (!$clause) {
            return $this->json(['success' => false, 'error' => 'Clause not found'], 404);
        }

        $clause->setTitle($data['title'] ?? $clause->getTitle());
        $clause->setContent($data['content'] ?? $clause->getContent());
        $clause->setVersion($clause->getVersion() + 1);

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/template/update', name: 'admin_collab_template_update', methods: ['POST'])]
    public function updateTemplate(Request $request, EntityManagerInterface $em, ContractTemplateRepository $repo, CollaborationFactory $factory): Response
    {
        $data = json_decode($request->getContent(), true);
        $template = $repo->findOneBy(['isMaster' => true]);

        if (!$template) {
            $template = $factory->createContractTemplate();
            $template->setName('Master Collaboration Contract');
            $em->persist($template);
        }

        $template->setContent($data['content'] ?? $template->getContent());
        $template->setVersion($template->getVersion() + 1);
        $template->setUpdatedAt(new \DateTime());

        $em->flush();

        return $this->json(['success' => true]);
    }

    private function seedDefaults(EntityManagerInterface $em, CollaborationFactory $factory): void
    {
        $defaults = [
            [
                'cat' => 'Confidentiality',
                'title' => 'Standard Non-Disclosure',
                'content' => 'Both parties agree to treat all shared information as strictly confidential for the duration of this agreement and 2 years thereafter.',
                'mandatory' => true
            ],
            [
                'cat' => 'Payment Terms',
                'title' => 'Net 30 Settlement',
                'content' => 'Payment shall be disbursed within 30 days of deliverable approval and confirmed invoice receipt.',
                'mandatory' => true
            ],
            [
                'cat' => 'Cancellation',
                'title' => 'Mutual Termination',
                'content' => 'Either party may terminate the agreement with 15 days written notice. Fees for work completed to date remain payable.',
                'mandatory' => false
            ],
            [
                'cat' => 'Usage Rights',
                'title' => 'Commercial License',
                'content' => 'The brand partner receives a non-exclusive, worldwide commercial license for the deliverables upon full payment.',
                'mandatory' => true
            ],
            [
                'cat' => 'Intellectual Property',
                'title' => 'Work for Hire',
                'content' => 'All intellectual property developed specifically for this campaign belongs to the brand partner upon final disbursement.',
                'mandatory' => true
            ]
        ];

        foreach ($defaults as $d) {
            $c = $factory->createContractClause();
            $c->setCategory($d['cat']);
            $c->setTitle($d['title']);
            $c->setContent($d['content']);
            $c->setIsMandatory($d['mandatory']);
            $em->persist($c);
        }

        // Master Template
        $mt = $factory->createContractTemplate();
        $mt->setName('Master Executive Template');
        $mt->setContent("<h1>{{ title }}</h1>\n<p>This agreement is entered into between {{ creator_name }} and {{ collaborator_name }}.</p>\n\n<h2>1. Scope of Work</h2>\n<p>{{ deliverables }}</p>\n\n<h2>2. Financial Terms</h2>\n<p>Total Budget: {{ budget }} DT</p>\n<p>{{ payment_terms }}</p>\n\n{{ clauses }}");
        $em->persist($mt);

        $em->flush();
    }
}
