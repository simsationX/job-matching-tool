<?php

namespace App\Controller;

use App\Entity\Candidate;
use App\Repository\CandidateRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CandidateCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CandidateRepository $candidateRepository,
        private ParameterBagInterface $params,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Candidate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Kandidat')
            ->setEntityLabelInPlural('Kandidaten')
            ->setDefaultSort(['id' => 'ASC'])
            ->overrideTemplate('crud/index', 'admin/candidate/index.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Custom CSV Import Button
        $csvImport = Action::new('csvImport', 'CSV Import', 'fa fa-file-csv')
            ->createAsGlobalAction()
            ->setTemplatePath('admin/candidate/_csv_import_button.html.twig')
            ->linkToCrudAction('csvImport');

        $matchCandidate = Action::new('matchCandidate', 'Match Kandidat', 'fa fa-handshake')
            ->linkToCrudAction('matchCandidate')
            ->setCssClass('action-matchCandidate');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $csvImport)
            ->add(Crud::PAGE_DETAIL, $matchCandidate)
            ->reorder(
                Crud::PAGE_DETAIL,
                [
                    Action::EDIT,
                    'matchCandidate',
                    Action::INDEX,
                    Action::DELETE,
                ]
            );
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('name'),
            TextField::new('position', 'Position'),
            TextField::new('industry', 'Branche'),
            TextField::new('location', 'Ort'),
        ];

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields = [
                TextField::new('name'),
                TextField::new('position', 'Position'),
                TextField::new('industry', 'Branche'),
                TextField::new('additionalIndustriesText', 'Weitere Branchen'),
                TextField::new('skills', 'Skills'),
                TextField::new('activityAreasText', 'Tätigkeitsbereiche'),
                TextField::new('additionalActivityAreas', 'Weitere Tätigkeitsbereiche'),
                TextField::new('location', 'Ort'),
                TextField::new('additionalLocations', 'Weitere Einsatzorte'),
                TextField::new('consultant', 'Berater'),
                AssociationField::new('matches')
                    ->setCrudController(CandidateJobMatchCrudController::class)
                    ->setTemplatePath('admin/fields/candidate_matches.html.twig'),
            ];
        }

        if ($pageName === CRUD::PAGE_EDIT || $pageName === CRUD::PAGE_NEW) {
            $fields = [
                TextField::new('name'),
                TextField::new('position', 'Position (durch Kommas getrennt)'),
                TextField::new('industry', 'Branche (durch Kommas getrennt)'),
                AssociationField::new('additionalIndustries', 'Weitere Branchen')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setHelp('Wähle eine oder mehrere Branchen aus.'),
                TextField::new('skills', 'Skills (durch Kommas getrennt)'),
                AssociationField::new('activityAreas', 'Tätigkeitsbereiche')
                    ->setFormTypeOptions(['by_reference' => false])
                    ->setHelp('Wähle eine oder mehrere Tätigkeitsbereiche aus.'),
                TextField::new('additionalActivityAreas', 'Weitere Tätigkeitsbereiche (durch Kommas getrennt)'),
                TextField::new('location', 'Ort'),
                TextField::new('additionalLocations', 'Weitere Einsatzorte (durch Kommas getrennt)'),
                AssociationField::new('consultant', 'Berater')
                    ->setCrudController(ConsultantCrudController::class),
            ];
        }

//        foreach ($fields as $field) {
//            $field->setCustomOption('rowClickable', true);
//        }

        return $fields;
    }

    public function csvImport(Request $request): RedirectResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('csv');

        if (!$file) {
            $this->addFlash('warning', 'Bitte eine CSV-Datei auswählen.');
            return $this->redirect($request->headers->get('referer'));
        }

        $errors = [];
        $rows = array_map(fn($line) => str_getcsv($line, ';'), file($file->getPathname()));
        array_shift($rows);

        foreach ($rows as $i => $row) {
            $name = trim($row[0] ?? '');
            if ($name === '') {
                $errors[] = ['line' => $i + 1, 'error' => 'Kein Name'];
                continue;
            }

            try {
                $candidate = new Candidate();
                $candidate->setName($name);
                $candidate->setPosition($row[3] ?? '');
                $candidate->setIndustry($row[2] ?? '');
                $candidate->setLocation($row[1] ?? '');
                $this->entityManager->persist($candidate);
            } catch (\Exception $e) {
                $errors[] = ['line' => $i + 1, 'error' => $e->getMessage()];
            }
        }

        $this->entityManager->flush();

        if (!empty($errors)) {
            $errorDetails = array_map(
                fn($err) => sprintf('Zeile %d: %s', $err['line'], $err['error']),
                $errors
            );

            $message = sprintf(
                'Import abgeschlossen, aber folgende Zeilen übersprungen:<br>%s',
                implode('<br>', $errorDetails)
            );

            $this->addFlash('warning', $message);
        } else {
            $this->addFlash('success', 'CSV Import erfolgreich!');
        }

        return $this->redirect($request->headers->get('referer'));
    }

    public function matchCandidate(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator,
    ): RedirectResponse
    {
        $request = $context->getRequest();
        $id = $request->query->get('entityId');

        if (!$id) {
            $this->addFlash('danger', 'Keine Entity-ID übergeben.');

            return $this->redirect($request->headers->get('referer'));
        }

        /** @var Candidate $candidate */
        $candidate = $this->candidateRepository->find($id);

        if (null === $candidate) {
            $this->addFlash('danger', 'Kandidat nicht gefunden.');

            return $this->redirect($request->headers->get('referer'));
        }

        $projectDir = $this->params->get('kernel.project_dir');
        $tmpDir = sprintf('%s/var/tmp', $projectDir);

        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmpDir));
        }

        $pidFile = sprintf('%s/candidate-match.pid', $tmpDir);

        if (file_exists($pidFile)) {
            $pid = (int)file_get_contents($pidFile);

            if (posix_kill($pid, 0)) {
                $this->addFlash('error', 'Matching-Prozess läuft bereits.');

                return $this->redirect(
                    $adminUrlGenerator->setAction('detail')->setEntityId($candidate->getId())->generateUrl()
                );
            }

            unlink($pidFile);
        }

        $logFile = sprintf('%s/candidate-match.log', $tmpDir);
        $candidateId = $candidate->getId();

        $command = sprintf(
            '/bin/bash -c "source /opt/venv/bin/activate && exec %s/bin/console app:candidate-job-match %s %d > %s 2>&1 < /dev/null & echo \$!"',
            $projectDir,
            'single',
            $candidateId,
            $logFile
        );

        $pid = (int) shell_exec($command);
        file_put_contents($pidFile, $pid);

        $this->addFlash('success', 'Matching-Prozess wurde angestoßen.');

        return $this->redirect(
            $adminUrlGenerator->setAction('detail')->setEntityId($candidate->getId())->generateUrl()
        );
    }
}
