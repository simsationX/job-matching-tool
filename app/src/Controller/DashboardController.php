<?php

namespace App\Controller;

use App\Entity\ActivityArea;
use App\Entity\Candidate;
use App\Entity\CandidateJobMatch;
use App\Entity\Consultant;
use App\Entity\Industry;
use App\Entity\Job;
use App\Entity\JobImportError;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/', routeName: 'admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // Leite automatisch auf die Kandidatenliste weiter
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $url = $adminUrlGenerator
            ->setController(CandidateCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/images/logo-bullheads.png" alt="Bullheads" style="height:40px;vertical-align:middle;"/>')
            ->setFaviconPath('/favicon-bullheads.ico')
            ->setTextDirection('ltr');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Kandidaten', 'fa fa-user', Candidate::class);
        yield MenuItem::linkToCrud('Jobs', 'fa fa-clipboard-list', Job::class);
        yield MenuItem::linkToCrud('Job Matches', 'fa fa-handshake', CandidateJobMatch::class);
        yield MenuItem::linkToCrud('Branchen', 'fa fa-compass', Industry::class);
        yield MenuItem::linkToCrud('Tätigkeitsbereiche', 'fa fa-tags', ActivityArea::class);
        yield MenuItem::linkToCrud('Berater', 'fa fa-user-tie', Consultant::class);
        yield MenuItem::linkToCrud('Job Import Error', 'fa fa-file-circle-exclamation', JobImportError::class);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('css/easyadmin-custom.css');
    }
}
