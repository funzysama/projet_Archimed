<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController
 * @package App\Controller
 * @Route("/admin", name="admin_")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        return $this->render('admin/Panels/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    /**
     * @Route("/listUser", name="listUser")
     */
    public function listUser(UtilisateurRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/Panels/listUsers.html.twig', [
            'utilisateurs' => $users,
        ]);
    }
}
