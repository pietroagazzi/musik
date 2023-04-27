<?php

namespace App\Controller;

use App\Entity\Service;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MusikController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    #[Route('', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('musik/index.html.twig');
    }
}
