<?php

// src/Controller/AccountController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;

class AccountController extends AbstractController
{

    #[Route('/account', name: 'app_account')]
    public function index(EntityManagerInterface $em)
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        // Assurez-vous que l'utilisateur est connecté
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $carts = $user->getCarts();

        return $this->render('account/index.html.twig', [
            'user' => $user,
            'carts' => $carts,
        ]);
    }
}
