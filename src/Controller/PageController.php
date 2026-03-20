<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/page', name: 'app_page')]
    public function home(): Response
    {
        return $this->render('page/index.html.twig');
    }
    #[Route('/accessories', name: 'app_page_accessories')]
    public function accessories(): Response
    {
        return $this->render('page/shirts.html.twig');
    }
}  
