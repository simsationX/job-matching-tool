<?php

namespace App\Controller;

use App\Entity\Industry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class IndustryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Industry::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Branche')
            ->setEntityLabelInPlural('Branchen')
            ->setDefaultSort(['id' => 'ASC'])
            ->overrideTemplate('crud/index', 'admin/industry/index.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Branche'),
            DateTimeField::new('createdAt', 'Erstellt am')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Aktualisiert am')->hideOnForm(),
        ];
    }
}
