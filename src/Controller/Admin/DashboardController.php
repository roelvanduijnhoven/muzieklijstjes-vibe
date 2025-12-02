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
        return $this->render('admin/dashboard.html.twig');
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
        yield MenuItem::linkToCrud('Ordered Lists', 'fas fa-list-ol', \App\Entity\AlbumList::class)
            ->setController(OrderedAlbumListCrudController::class);
        yield MenuItem::linkToCrud('Unordered Lists', 'fas fa-list-ul', \App\Entity\AlbumList::class)
            ->setController(UnorderedAlbumListCrudController::class);
        yield MenuItem::linkToCrud('Mentioned Lists', 'fas fa-comment-dots', \App\Entity\AlbumList::class)
            ->setController(MentionedAlbumListCrudController::class);
        yield MenuItem::linkToCrud('Aggregate Lists', 'fas fa-layer-group', \App\Entity\AlbumList::class)
            ->setController(AggregateAlbumListCrudController::class);

        yield MenuItem::section('Database');
        yield MenuItem::linkToCrud('Albums', 'fas fa-compact-disc', \App\Entity\Album::class);
        yield MenuItem::linkToCrud('Artists', 'fas fa-microphone', \App\Entity\Artist::class);
        yield MenuItem::linkToCrud('Critics', 'fas fa-pen-fancy', \App\Entity\Critic::class);
        yield MenuItem::linkToCrud('Genres', 'fas fa-tags', \App\Entity\Genre::class);
        yield MenuItem::linkToCrud('Features', 'fas fa-tag', \App\Entity\Feature::class);
        yield MenuItem::linkToCrud('Magazines', 'fas fa-newspaper', \App\Entity\Magazine::class);
        yield MenuItem::linkToCrud('Rubrics', 'fas fa-columns', \App\Entity\Rubric::class);
        yield MenuItem::linkToCrud('Reviews', 'fas fa-star', \App\Entity\Review::class);
    }
}
