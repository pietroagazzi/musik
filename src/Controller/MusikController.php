<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MusikController extends AbstractController
{
    #[Route('', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('musik/index.html.twig');
    }
}
