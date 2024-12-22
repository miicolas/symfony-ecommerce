<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProductType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductController extends AbstractController
{

    // Product page with the list of products
    #[Route('/', name: 'app_product')]
    public function index(EntityManagerInterface $em, Request $request, TranslatorInterface $translator): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', $translator->trans('image-upload-error'));
                    return $this->redirectToRoute('app_product');
                }

                $product->setPhoto($newFilename);
            }
            $this->addFlash('success', $translator->trans('product-added-success'));
            $em->persist($product);
            $em->flush();
            return $this->redirectToRoute('app_product');
        }

        $products = $em->getRepository(Product::class)->findAll();
        return $this->render('product/index.html.twig', [
            'products' => $products,
            'form' => $form->createView(),
        ]);
    }

    // Show a product
    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(EntityManagerInterface $em, Product $product): Response
    {
        $product = $em->getRepository(Product::class)->find($product->getId());
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    // Edit a product
    #[Route('/product/delete/{id}', name: 'app_product_delete')]
    public function delete(EntityManagerInterface $em, Request $request, int $id, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException($translator->trans('user-not-found'));
        }

        // Supprime le produit des paniers
        $carts = $user->getCarts();
        foreach ($carts as $cart) {
            foreach ($cart->getCartContents() as $cartContent) {
                if ($cartContent->getProduct()->getId() === $id) {
                    $cart->removeCartContent($cartContent);
                    $em->persist($cart);
                    $em->flush();
                }
            }
            if ($cart->getCartContents()->isEmpty()) {
                $em->remove($cart);
                $em->flush();
            }
        }

        $product = $em->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException($translator->trans('product-not-found'));
        }
        $em->remove($product);
        $em->flush();
        return $this->redirectToRoute('app_product');
    }
}