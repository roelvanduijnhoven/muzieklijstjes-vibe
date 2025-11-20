<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(StandardAlbumListCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Register Vibe Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Lists');
        yield MenuItem::linkToCrud('Standard Lists', 'fas fa-list-ol', \App\Entity\AlbumList::class)
            ->setController(StandardAlbumListCrudController::class);
        yield MenuItem::linkToCrud('Aggregate Lists', 'fas fa-layer-group', \App\Entity\AlbumList::class)
            ->setController(AggregateAlbumListCrudController::class);

        yield MenuItem::section('Database');
        yield MenuItem::linkToCrud('Albums', 'fas fa-compact-disc', \App\Entity\Album::class);
        yield MenuItem::linkToCrud('Artists', 'fas fa-microphone', \App\Entity\Artist::class);
        yield MenuItem::linkToCrud('Critics', 'fas fa-pen-fancy', \App\Entity\Critic::class);
        yield MenuItem::linkToCrud('Magazines', 'fas fa-newspaper', \App\Entity\Magazine::class);
    }
}
