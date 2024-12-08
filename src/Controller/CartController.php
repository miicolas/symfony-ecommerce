<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Cart;
use App\Entity\CartContent;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $cart = $em->getRepository(Cart::class)->findOneBy(
            ['user_id' => $user->getId(), 'is_paid' => false]
        );

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

    #[Route('/cart/add/{productId}', name: 'app_cart_add')]
    public function add(EntityManagerInterface $em, int $productId): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $product = $em->getRepository(Product::class)->find($productId);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $cart = $em->getRepository(Cart::class)->findOneBy(
            ['user_id' => $user->getId(), 'is_paid' => false]
        );

        if (!$cart) {
            $cart = new Cart();
            $cart->setUserId($user);
            $cart->setPaid(false);
            $em->persist($cart);
            $em->flush();
        }

        // Check if the product already exists in the cart
        $cartContent = $em->getRepository(CartContent::class)->findOneBy([
            'cart' => $cart,
            'product' => $product
        ]);

        if ($cartContent) {
            // If the product exists in the cart, increase the quantity
            $cartContent->setQuantity($cartContent->getQuantity() + 1);
        } else {
            // If the product does not exist in the cart, create a new CartContent entry
            $cartContent = new CartContent();
            $cartContent->setCart($cart);
            $cartContent->setProduct($product);
            $cartContent->setQuantity(1);
            $cartContent->setAddedDate(new \DateTime());
            $em->persist($cartContent);
        }

        $em->flush();

        $this->addFlash('success', 'Produit ajouté au panier');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{cartContentId}', name: 'app_cart_remove')]
    public function remove(EntityManagerInterface $em, int $cartContentId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $cartContent = $em->getRepository(CartContent::class)->find($cartContentId);

        if (!$cartContent) {
            throw $this->createNotFoundException('Cart content not found');
        }

        // Check if the cart content belongs to the user's cart
        $cart = $cartContent->getCart();
        if ($cart->getUserId()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You do not have permission to remove this item');
        }

        $em->remove($cartContent);
        $em->flush();

        $this->addFlash('success', 'Produit retiré du panier');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/increase/{cartContentId}', name: 'app_cart_increase')]
    public function increase(EntityManagerInterface $em, int $cartContentId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $cartContent = $em->getRepository(CartContent::class)->find($cartContentId);

        if (!$cartContent) {
            throw $this->createNotFoundException('Cart content not found');
        }

        $cart = $cartContent->getCart();
        if ($cart->getUserId()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You do not have permission to update this item');
        }

        $cartContent->setQuantity($cartContent->getQuantity() + 1);

        $em->persist($cartContent);
        $em->flush();

        $this->addFlash('success', 'Quantité augmentée');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/decrease/{cartContentId}', name: 'app_cart_decrease')]
    public function decrease(EntityManagerInterface $em, int $cartContentId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $cartContent = $em->getRepository(CartContent::class)->find($cartContentId);

        if (!$cartContent) {
            throw $this->createNotFoundException('Cart content not found');
        }

        // Check if the cart content belongs to the user's cart
        $cart = $cartContent->getCart();
        if ($cart->getUserId()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You do not have permission to update this item');
        }

        $cartContent->setQuantity(max(1, $cartContent->getQuantity() - 1)); // Ensure quantity doesn't go below 1

        $em->persist($cartContent);
        $em->flush();

        $this->addFlash('success', 'Quantité diminuée');

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/checkout', name: 'app_cart_checkout')]
    public function checkout(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $cart = $em->getRepository(Cart::class)->findOneBy(
            ['user_id' => $user->getId(), 'is_paid' => false]
        );

        if (!$cart) {
            throw $this->createNotFoundException('Cart not found');
        }

        $cart->setPaid(true);
        $cart->setAmountPaid($cart->getTotal());
        $cart->setPurchaseDate(new \DateTime());
        $em->persist($cart);
        $em->flush();

        $this->addFlash('success', 'Commande validée');

        return $this->redirectToRoute('app_cart');
    }
}