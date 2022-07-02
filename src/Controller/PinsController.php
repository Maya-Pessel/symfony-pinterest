<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Entity\PinComs;
use App\Entity\User;
use App\Form\ComType;
use App\Form\PinType;
use App\Repository\PinRepository;
// use http\Client\Request;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormEvent;
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
     * @Route("pin/{type}", name="home_ordered")
     */
    public function index($type = null): Response
    {

        if ($type == 'likes') {
            $pins = $this->getDoctrine()
                ->getRepository(Pin::class)
                ->findSortedByLikes();
        } else {
            $pins = $this->getDoctrine()
                ->getRepository(Pin::class)
                ->findBy(array(), array('created_at' => 'DESC'));
        }

        return $this->render('pins/index.html.twig', [
            'pins' => $pins
        ]);

    }

    /**
     * @Route("/pins/create", name="app_pins_create", methods="GET|POST")
     */

    public function create(Request $request, EntityManagerInterface $em): Response
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
     * @Route("/pins/{id<[0-9]+>}", name="app_pin_show", methods="GET|POST")
     */

    public function show(Pin  $pin, Request $request, EntityManagerInterface $em ): Response
    {
        $referer = $request->headers->get('referer');

        $com = new PinComs();

        $comForm = $this->createForm(ComType::class, $com);
        $comForm->handleRequest($request);

        if ($comForm->isSubmitted() && $comForm->isValid()){
            $com->setOwner($this->security->getUser());
            $com->setPin($pin);
            $em->persist($com);
            $em->flush();
            $this->addFlash('success', 'Commentary successfully created');
            return $this->redirect($referer);
        }
        return $this->render('pins/show.html.twig', [
            'comForm' => $comForm->createView(),
            'pin' => $pin
        ]);
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
        if ($this -> isCsrfTokenValid('pins_deletion_'.$pin-> getId(), $request->request->get('csrf_token'))){ // fonction heritée depuis le controller
            $em -> remove($pin);
            $em -> flush();
            $this->addFlash('info', 'Pin successfully deleted');

        }
        return $this->redirectToRoute("app_home");
    }

    /**
     * @Route("/pins/{id<[0-9]+>}/likes", name="add_like")
     */
    public function likes(Request $request, Pin $pin): Response
    {
        $referer = $request->headers->get('referer');

        $entityManager = $this->getDoctrine()->getManager();

        // check si user est connecté

        $user = $this->security->getUser();

        if (!$user) return $this->redirectToRoute('app_home');

        // ici la logique pour liker
        if ($pin->getLikes()->contains($user)) {
            $pin->removeLike($user);
        } else {

            $pin->addLike($user);
        }
        $entityManager->persist($pin);
        $entityManager->flush();

        // return $this->redirectToRoute('app_pin_show', ['id' => $pin->getId()]);
        return $this->redirect($referer);
    }


}
