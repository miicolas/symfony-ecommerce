<?php

// src/Controller/AccountController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', $translator->trans('login-required'));

            return $this->redirectToRoute('app_login');
        }

        $carts = $user->getCarts();

        return $this->render('account/index.html.twig', [
            'user' => $user,
            'carts' => $carts,
        ]);
    }
}