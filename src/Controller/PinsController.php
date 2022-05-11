<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Form\PinType;
use App\Repository\PinRepository;
// use http\Client\Request;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class PinsController extends AbstractController
{

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/", name="app_home", methods="GET")
     */
    public function index(PinRepository $pinRepository): Response
    {
        //dd($pinRepository->findAll());
        $pins = $pinRepository->findBy([], ['created_at' => 'DESC']);
        return $this->render('pins/index.html.twig', ['pins'=> $pins]);
    }

    /**
     * @Route("/pins/create", name="app_pins_create", methods="GET|POST")
     */

    public function create(Request $request, EntityManagerInterface $em, UserRepository $userRepo ): Response
    {
        $pin = new Pin;

        $form = $this -> createForm(PinType::class, $pin);


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $pin -> setUser($this->security->getUser());
            $em->persist($pin);
            $em->flush();

            $this->addFlash('success', 'Pin successfully created');

            return $this->redirectToRoute("app_home");
        }

        return $this -> render('pins/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/pins/{id<[0-9]+>}", name="app_pin_show", methods="GET")
     */

    public function show(Pin  $pin): Response
    {
    return $this->render('pins/show.html.twig', compact('pin'));
    }


    /**
     * @Route("/pins/{id<[0-9]+>}/edit", name="app_pins_edit", methods="GET|PUT")
     */

    public function edit(Pin  $pin, Request $request, EntityManagerInterface $em): Response
    {
        if ($pin->getUser() !== $this->security->getUser()){
            return $this->redirectToRoute("app_home");

        }
        $form = $this -> createForm(PinType::class, $pin, [
            'method' => 'PUT'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){

            $em->flush();

            $this->addFlash('success', 'Pin successfully updated');

            return $this->redirectToRoute("app_home");
        }

        return $this->render('pins/edit.html.twig', [

            "pin" => $pin,
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/pins/{id<[0-9]+>}/delete", name="app_pins_delete", methods="DELETE")
     */

    public function delete(Request $request, Pin  $pin, EntityManagerInterface $em): Response
    {
        if ($this -> isCsrfTokenValid('pins_deletion_' . $pin-> getId(), $request->request->get('csrf_token'))){ // fonction heritée depuis le controller
            $em -> remove($pin);
            $em -> flush();
            $this->addFlash('info', 'Pin successfully deleted');

        }
        return $this->redirectToRoute("app_home");
    }
}
