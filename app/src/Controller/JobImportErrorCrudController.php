<?php

namespace App\Controller;

use App\Entity\JobImportError;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class JobImportErrorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return JobImportError::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Job Import Error')
            ->setEntityLabelInPlural('Job Import Errors')
            ->setDefaultSort(['importedAt' => 'DESC'])
            ->overrideTemplate('crud/index', 'admin/job_import_error/index.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Nur Anzeigen und Löschen erlauben
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('company', 'Firma'),
            TextField::new('position', 'Position'),
            TextField::new('error', 'Fehler'),
            DateTimeField::new('importedAt', 'Importiert am'),
        ];

        if (in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW], true)) {
            $fields[] = TextField::new('location', 'Ort');
            $fields[] = TextField::new('companyPhone', 'Telefon Firma');
            $fields[] = TextField::new('website', 'Website');
            $fields[] = TextField::new('contactPerson', 'Ansprechpartner');
            $fields[] = TextField::new('contactEmail', 'E-Mail Ansprechpartner');
            $fields[] = TextField::new('contactPhone', 'Telefon Ansprechpartner');
            $fields[] = IntegerField::new('adId', 'Ad ID');
            $fields[] = IntegerField::new('positionId', 'Position ID');
            $fields[] = TextareaField::new('description', 'Beschreibung');
        }

//        foreach ($fields as $field) {
//            $field->setCustomOption('rowClickable', true);
//        }

        return $fields;
    }
}
