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
use Symfony\Contracts\Translation\TranslatorInterface;

class CartController extends AbstractController
{

    // Cart page with the products in the cart
    #[Route('/cart', name: 'app_cart')]
    public function index(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        // Get the cart of the current user
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

    // Add a product to the cart
    #[Route('/cart/add/{productId}', name: 'app_cart_add')]
    public function add(EntityManagerInterface $em, int $productId, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Get the product
        $product = $em->getRepository(Product::class)->find($productId);

        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // Get the cart of the current user
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

        $this->addFlash('success', $translator->trans('product-added'));

        return $this->redirectToRoute('app_cart');
    }

    // Remove a product from the cart
    #[Route('/cart/remove/{cartContentId}', name: 'app_cart_remove')]
    public function remove(EntityManagerInterface $em, int $cartContentId, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Get the cart content
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

        $this->addFlash('success', $translator->trans('product-removed'));

        return $this->redirectToRoute('app_cart');
    }

    // Increase the quantity of a product in the cart
    #[Route('/cart/increase/{cartContentId}', name: 'app_cart_increase')]
    public function increase(EntityManagerInterface $em, int $cartContentId, TranslatorInterface $translator): Response
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

        $this->addFlash('success', $translator->trans('quantity-increased'));

        return $this->redirectToRoute('app_cart');
    }

    // Decrease the quantity of a product in the cart
    #[Route('/cart/decrease/{cartContentId}', name: 'app_cart_decrease')]
    public function decrease(EntityManagerInterface $em, int $cartContentId, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Get the cart content
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

        $this->addFlash('success', $translator->trans('quantity-decreased'));

        return $this->redirectToRoute('app_cart');
    }

    // Checkout the cart
    #[Route('/cart/checkout', name: 'app_cart_checkout')]
    public function checkout(EntityManagerInterface $em, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Get the cart of the current user
        $cart = $em->getRepository(Cart::class)->findOneBy(
            ['user_id' => $user->getId(), 'is_paid' => false]
        );

        if (!$cart) {
            throw $this->createNotFoundException('Cart not found');
        }

        // Check if there is enough stock for each product in the cart
        foreach ($cart->getCartContents() as $cartContent) {
            $product = $cartContent->getProduct();
            if ($product->getStock() < $cartContent->getQuantity()) {
                $this->addFlash('error', $translator->trans('not-enough-stock') . $product->getName());
                return $this->redirectToRoute('app_cart');
            }
        }

        // Deduct the stock for each product in the cart
        foreach ($cart->getCartContents() as $cartContent) {
            $product = $cartContent->getProduct();
            $product->setStock($product->getStock() - $cartContent->getQuantity());
            $em->persist($product);
        }

        $cart->setPaid(true);
        $cart->setAmountPaid($cart->getTotal());
        $cart->setPurchaseDate(new \DateTime());
        $em->persist($cart);
        $em->flush();

        $this->addFlash('success', $translator->trans('order-confirmed'));

        return $this->redirectToRoute('app_cart');
    }
}