<?php

namespace App\Controller;

use App\Entity\Job;
use App\Service\JobLocationResolverService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class JobCrudController extends AbstractCrudController
{
    private const IGNORED_LOCATIONS = [
        'deutschland',
        'germany',
        'allemagne',
    ];

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
            ->setSearchFields(['company', 'position'])
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

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $xlsxImport);
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Job) {
            $this->updateGeoCities($entityInstance);
        }

        parent::persistEntity($em, $entityInstance);
    }

    // Bestehende Jobs
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Job) {
            $this->updateGeoCities($entityInstance);
        }

        parent::updateEntity($em, $entityInstance);
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
}
