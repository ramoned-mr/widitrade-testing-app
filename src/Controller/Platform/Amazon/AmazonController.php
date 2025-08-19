<?php

namespace App\Controller\Platform\Amazon;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AmazonController extends AbstractController
{

    public function __construct()
    {
    }

    #[Route('/home', name: 'home')]
    public function home(): Response
    {
        return $this->render('platform/amazon/home.html.twig', []);
    }
}