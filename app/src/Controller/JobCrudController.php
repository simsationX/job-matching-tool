<?php

namespace App\Controller;

use App\Entity\Candidate;
use App\Entity\CandidateJobMatch;
use App\Entity\Job;
use App\Repository\CandidateJobMatchRepository;
use App\Repository\JobRepository;
use App\Service\JobLocationResolverService;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class JobCrudController extends AbstractCrudController
{
    public function __construct(private JobLocationResolverService $jobLocationResolverService) {
    }

    public static function getEntityFqcn(): string
    {
        return Job::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Job')
            ->setEntityLabelInPlural('Jobs')
            ->setSearchFields(['company', 'position', 'location', 'description'])
            ->setPageTitle(Crud::PAGE_INDEX, 'Jobs')
            ->overrideTemplate('crud/index', 'admin/job/index.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('company', 'Firma'),
            TextField::new('position', 'Position'),
            TextField::new('location', 'Ort'),
        ];

        if (in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            $fields[] = TextField::new('companyPhone', 'Telefon Firma');
            $fields[] = TextField::new('website', 'Website');
            $fields[] = TextField::new('contactPerson', 'Ansprechpartner');
            $fields[] = TextField::new('contactEmail', 'E-Mail Ansprechpartner');
            $fields[] = TextField::new('contactPhone', 'Telefon Ansprechpartner');
            $fields[] = IntegerField::new('adId', 'Ad ID');
            $fields[] = IntegerField::new('positionId', 'Position ID');
            $fields[] = TextareaField::new('description', 'Beschreibung');
        }

        if (in_array($pageName, [Crud::PAGE_INDEX, Crud::PAGE_DETAIL], true)) {
            $fields[] = ArrayField::new('geoCities')
                ->setLabel('Verknüpfter Geo-Ort');
        }

        $fields[] = DateTimeField::new('importedAt', 'Importiert am');

//        foreach ($fields as $field) {
//            $field->setCustomOption('rowClickable', true);
//        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        $xlsxImport = Action::new('xlsxImport', 'XLSX Import', 'fa fa-file-excel')
            ->createAsGlobalAction()
            ->setTemplatePath('admin/job/_xlsx_upload_button.html.twig')
            ->linkToCrudAction('xlsxUpload');

        $matchCandidateActionButton = Action::new('matchCandidate', 'Mit Kandidat matchen', 'fa fa-handshake')
            ->linkToCrudAction('matchCandidate');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $xlsxImport)
            ->add(Crud::PAGE_DETAIL, $matchCandidateActionButton)
            ->add(Crud::PAGE_EDIT, $matchCandidateActionButton)
            ->add(Crud::PAGE_INDEX, $matchCandidateActionButton)
            ->reorder(
                Crud::PAGE_EDIT,
                [
                    Action::SAVE_AND_RETURN,
                    'matchCandidate',
                    Action::SAVE_AND_CONTINUE,
                ]
            )
            ->reorder(
                Crud::PAGE_DETAIL,
                [
                    Action::EDIT,
                    'matchCandidate',
                    Action::INDEX,
                    Action::DELETE,
                ]
            )
            ->disable(Action::NEW);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Job) {
            $this->updateGeoCities($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    // Bestehende Jobs
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Job) {
            $this->updateGeoCities($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }


    #[Route('/admin/job/xlsx-upload', name: 'job_xlsx_upload', methods: ['POST'])]
    public function xlsxUpload(Request $request): RedirectResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('xlsx');

        if (!$file) {
            $this->addFlash('warning', 'Bitte eine XLSX-Datei auswählen.');
            return $this->redirect($request->headers->get('referer'));
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';

        try {
            $filename = 'job_import.'. $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);

            $this->addFlash('success', 'Datei erfolgreich hochgeladen. Import wird in ca. 30 Minuten ausgeführt sein.');
        } catch (FileException $e) {
            $this->addFlash('danger', 'Fehler beim Hochladen: ' . $e->getMessage());
        }

        return $this->redirect($request->headers->get('referer'));
    }

    private function updateGeoCities(Job $job): void
    {
        $location = $job->getLocation();
        $job->getGeoCities()->clear();

        if ($this->jobLocationResolverService->shouldResolveLocation($location)) {
            $geoCities = $this->jobLocationResolverService->resolve($location);

            foreach ($geoCities as $geoCity) {
                $job->addGeoCity($geoCity);
            }
        }
    }

    public function matchCandidate(
        AdminUrlGenerator $adminUrlGenerator,
        AdminContext $context,
        JobRepository $jobRepository,
        CandidateJobMatchRepository $candidateJobMatchRepository,
        EntityManagerInterface $em): Response
    {
        $request = $context->getRequest();
        $id = $request->query->get('entityId');

        /** @var Job $job */
        $job = $jobRepository->find($id);

        if (null === $job) {
            throw new \Exception("Job not found.");
        }

        $builder = $this->createFormBuilder();
        $builder
            ->add('candidate', EntityType::class, [
                'class' => Candidate::class,
                'label' => 'Kandidat auswählen',
                'choice_label' => function(Candidate $candidate) {
                    return sprintf('%s (ID: %d)', $candidate->getName(), $candidate->getId());
                },
                'placeholder' => 'Bitte auswählen',
                'attr' => ['class' => 'candidate-select'],
                'query_builder' => function ($repo) {
                    return $repo->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'required' => true,
                'multiple' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Match erstellen',
            ]);

        $form = $builder->getForm();
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $candidate = $form->get('candidate')->getData();
            $candidateJobMatch = $candidateJobMatchRepository->findOneBy([
                'candidate' => $candidate,
                'positionId' => $job->getPositionId(),
                'adId' => $job->getAdId()
            ]);

            if (null !== $candidateJobMatch) {
                $this->addFlash('warning', sprintf('Kandidat %s ist bereits mit diesem Job gematcht.', $candidate->getName()));

                return $this->redirect(
                    $adminUrlGenerator
                        ->setController(JobCrudController::class)
                        ->setAction('index')
                        ->generateUrl());
            }

            $candidateJobMatch = new CandidateJobMatch();
            $candidateJobMatch
                ->setCandidate($candidate)
                ->setCompany($job->getCompany())
                ->setWebsite($job->getWebsite())
                ->setCompanyPhone($job->getCompanyPhone())
                ->setContactEmail($job->getContactEmail())
                ->setContactPerson($job->getContactPerson())
                ->setContactPhone($job->getContactPhone())
                ->setLocation($job->getLocation())
                ->setPosition($job->getPosition())
                ->setPositionId($job->getPositionId())
                ->setAdId($job->getAdId())
                ->setDescription($job->getDescription())
                ->setScore(100)
                ->setFoundAt(new \DateTimeImmutable())
                ->setExported(false);

            $em->persist($candidateJobMatch);
            $em->flush();

            $this->addFlash('success', sprintf('Kandidat %s wurde mit Job %s gematcht.', $candidate->getName(), $job->getPosition()));

            return $this->redirect(
                $adminUrlGenerator
                    ->setController(JobCrudController::class)
                    ->setAction('index')
                    ->generateUrl());
        }

        return $this->render('admin/job/match_candidate.html.twig', [
            'form' => $form->createView(),
            'job' => $job,
        ]);
    }
}
