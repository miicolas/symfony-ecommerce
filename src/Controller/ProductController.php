<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProductType;
use Symfony\Component\HttpFoundation\Request;


class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
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
                    $this->addFlash('error', 'Impossible de télécharger l\'image');
                    return $this->redirectToRoute('app_product');
                }

                $product->setPhoto($newFilename);
            }
            $this->addFlash('success', 'Produit ajouté avec succès');
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

    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(EntityManagerInterface $em, Product $product): Response
    {
        $product = $em->getRepository(Product::class)->find($product->getId());
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/product/delete/{id}', name: 'app_product_delete')]
    public function delete(EntityManagerInterface $em, Request $request, int $id): Response
    {

        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException('User not found');
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
            throw $this->createNotFoundException('Product not found');
        }
        $em->remove($product);
        $em->flush();
        return $this->redirectToRoute('app_product');
    }
}
