<?php

// src/Controller/AccountController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class AccountController extends AbstractController
{

    #[Route('/account', name: 'app_account')]
    public function index(UserInterface $user)
    {
        // Assurez-vous que l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('account/index.html.twig', [
            'user' => $user,
        ]);
    }
}
