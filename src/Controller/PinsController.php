<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Repository\PinRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PinsController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(PinRepository $pinRepository): Response
    {
        //dd($pinRepository->findAll());
        $pins = $pinRepository->findBy([], ['created_at' => 'DESC']);
        return $this->render('pins/index.html.twig', ['pins'=> $pins]);
    }

    /**
     * @Route("/pins/{id<[1-9]+>}", name="app_pin_show")
     */

    public function show(Pin  $pin): Response
    {
    return $this->render('pins/show.html.twig', compact('pin'));
    }
}
