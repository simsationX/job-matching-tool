<?php

namespace App\Controller;

use App\Entity\CandidateJobMatch;
use App\Entity\Enum\CandidateJobMatchStatus;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Process\Process;

class CandidateJobMatchCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return CandidateJobMatch::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Job Match')
            ->setEntityLabelInPlural('Job Matches')
            ->setDefaultSort(['candidate' => 'DESC', 'score' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $softDeleteOverview = Action::new('softDelete', 'Löschen')
            ->linkToCrudAction('softDelete');

        $softDeleteDetail = Action::new('softDelete', 'Löschen', 'fa-solid fa-trash-can')
            ->linkToCrudAction('softDelete')
            ->addCssClass('btn btn-danger');

        $matchNew = Action::new('matchNew', 'Match neue Kandidaten')
            ->linkToCrudAction('matchNewCandidates')
            ->createAsGlobalAction();

        $matchAll = Action::new('matchAll', 'Match alle Kandidaten')
            ->linkToCrudAction('matchAllCandidates')
            ->createAsGlobalAction();

        $downloadCsv = Action::new('downloadCsv', 'CSV Export')
            ->setIcon('fa fa-download')
            ->linkToUrl($this->generateUrl('admin_match_report_download'))
            ->createAsGlobalAction();

        $softDeleteBatch = Action::new('softDeleteBatch', 'Löschen')
            ->linkToCrudAction('softDeleteBatch')
            ->addCssClass('btn btn-warning');

        return $actions
            ->addBatchAction($softDeleteBatch)
            ->add(Crud::PAGE_INDEX, $downloadCsv)
            ->add(Crud::PAGE_INDEX, $matchAll)
            ->add(Crud::PAGE_INDEX, $matchNew)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $softDeleteOverview)
            ->add(Crud::PAGE_DETAIL, $softDeleteDetail)
            ->add(Crud::PAGE_EDIT, $softDeleteDetail)
            ->disable(Action::DELETE)
            ->disable(Action::NEW);

    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            AssociationField::new('candidate', 'Kandidat'),
            NumberField::new('score', 'Score')
                ->setNumDecimals(2)
                ->setStoredAsString(false),
            TextField::new('company', 'Firma'),
        ];

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = TextField::new('position', 'Position')
                ->setTemplatePath('admin/fields/job_highlight.html.twig')
                ->setLabel('Position');
            $fields[] = TextField::new('website', 'Website');
            $fields[] = TextField::new('companyPhone', 'Telefon');
            $fields[] = TextField::new('contactPerson', 'Ansprechpartner');
            $fields[] = TextField::new('contactEmail', 'E-Mail Ansprechpartner');
            $fields[] = TextField::new('contactPhone', 'Telefon Ansprechpartner');
            $fields[] = TextField::new('description', 'Beschreibung')
                ->setTemplatePath('admin/fields/job_highlight.html.twig')
                ->setLabel('Beschreibung');
        } elseif ($pageName === Crud::PAGE_EDIT) {
            $fields[] = TextField::new('position', 'Position');
            $fields[] = TextField::new('website', 'Website');
            $fields[] = TextField::new('companyPhone', 'Telefon');
            $fields[] = TextField::new('contactPerson', 'Ansprechpartner');
            $fields[] = TextField::new('contactEmail', 'E-Mail Ansprechpartner');
            $fields[] = TextField::new('contactPhone', 'Telefon Ansprechpartner');
            $fields[] = TextField::new('description', 'Beschreibung');
        } else {
            $fields[] = TextField::new('position', 'Position');
            $fields[] = TextField::new('location', 'Ort');
            $fields[] = DateTimeField::new('foundAt', 'Gefunden am');
        }

        return $fields;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): \Doctrine\ORM\QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $qb->andWhere('entity.status = :status')
            ->setParameter('status', CandidateJobMatchStatus::ACTIVE);

        $search = $searchDto->getQuery();
        if ($search) {
            $qb->orWhere('candidate.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb;
    }

    public function softDelete(AdminContext $context, AdminUrlGenerator $adminUrlGenerator)
    {
        $request = $context->getRequest();
        $id = $request->query->get('entityId');

        if (!$id) {
            $this->addFlash('danger', 'Keine Entity-ID übergeben.');
            return $this->redirect($request->headers->get('referer'));
        }

        $entity = $this->entityManager->getRepository(CandidateJobMatch::class)->find($id);

        if (!$entity) {
            $this->addFlash('danger', 'Entity nicht gefunden.');
            return $this->redirect($request->headers->get('referer'));
        }

        $entity->setStatus(CandidateJobMatchStatus::IGNORED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Job Match wurde gelöscht (Status ignored).');

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
    }

    public function softDeleteBatch(AdminContext $context, AdminUrlGenerator $adminUrlGenerator)
    {
        $request = $context->getRequest();

        $ids = $request->request->all('batchActionEntityIds');

        if (empty($ids)) {
            $this->addFlash('danger', 'Keine Entities ausgewählt.');
            return $this->redirect($request->headers->get('referer'));
        }

        $repository = $this->entityManager->getRepository(CandidateJobMatch::class);

        foreach ($ids as $id) {
            $entity = $repository->find($id);
            if ($entity) {
                $entity->setStatus(CandidateJobMatchStatus::IGNORED);
            }
        }

        $this->entityManager->flush();
        $this->addFlash('success', sprintf('%d Job Matches wurden gelöscht (Status ignored).', count($ids)));

        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
    }

    public function matchNewCandidates(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $this->runAsyncProcess('new');
        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
    }

    public function matchAllCandidates(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        $this->runAsyncProcess('all');
        $this->addFlash('success', 'Matching für alle Kandidaten wurde angestoßen.');
        return $this->redirect($adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
    }

    private function runAsyncProcess(string $mode): void
    {
        $projectDir = $this->params->get('kernel.project_dir');
        $consoleCommand = sprintf('%s/bin/console app:candidate-job-match %s', $projectDir, escapeshellarg($mode));

        $pidFile = '/tmp/candidate-match.pid';

        if (file_exists($pidFile)) {
            $pid = (int) file_get_contents($pidFile);

            if (posix_kill($pid, 0)) {
                $this->addFlash('error', 'Matching-Prozess läuft bereits.');
                return;
            }

            unlink($pidFile);
        }

        $command = sprintf(
            '%s > /tmp/candidate-match.log 2>&1 & echo $!',
            $consoleCommand
        );

        $pid = (int) shell_exec($command);
        file_put_contents($pidFile, $pid);

        $this->addFlash('success', 'Matching-Prozess wurde angestoßen.');
    }
}
