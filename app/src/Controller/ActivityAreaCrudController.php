<?php

namespace App\Controller;

use App\Entity\ActivityArea;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class ActivityAreaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ActivityArea::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tätigkeitsbereich')
            ->setEntityLabelInPlural('Tätigkeitsbereiche')
            ->setDefaultSort(['id' => 'ASC'])
            ->overrideTemplate('crud/index', 'admin/activity_area/index.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Tätigkeitsbereich'),
            DateTimeField::new('createdAt', 'Erstellt am')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Aktualisiert am')->hideOnForm(),
        ];
    }
}
