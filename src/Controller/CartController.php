<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Cart;
use App\Entity\CartContent;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;


class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(EntityManagerInterface $em, UserInterface $user): Response
    {

        $cart = $em->getRepository(Cart::class)->findOneBy(
            ['user_id' => $user->getId()]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUserId($user);
            $cart->setPaid(false);
            $em->persist($cart);
            $em->flush();
        }
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/{cartId}/add/{id}', name: 'app_cart_add')]
    public function add(EntityManagerInterface $em, Cart $cart, Product $product): Response
    {

        $cartContent = new CartContent();
        $cartContent->setCartId($cart);
        $cartContent->setProductId($product);
        $cartContent->setQuantity(1);
        $em->persist($cartContent);
        $em->flush();

        return $this->redirectToRoute('app_cart');
    }
}

