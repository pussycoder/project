<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Entity\Products;
use App\Entity\User;
use App\Form\ProductsType;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;


#[Route('/products')]
final class ProductsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }
    #[Route(name: 'app_products_index', methods: ['GET'])]
    public function index(ProductsRepository $productsRepository): Response
    {
        // Require authentication (staff or admin)
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        
        // Admin can see all products, staff only see their own
        if ($this->isGranted('ROLE_ADMIN')) {
            $products = $productsRepository->findAll();
        } else {
            $products = $productsRepository->findBy(['createdBy' => $user]);
        }

        return $this->render('products/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'app_products_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Require authentication (staff or admin)
        $this->denyAccessUnlessGranted('ROLE_USER');

        $product = new Products();
        $product->setCreatedBy($this->getUser());
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload manually
            $imageFile = $form->get('image')->getData();

            // if ($imageFile) {
            //     $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            //     $safeFilename = $slugger->slug($originalFilename);
            //     $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            //     try {
            //         $imageFile->move(
            //             $this->getParameter('images_directory'),
            //             $newFilename
            //         );
            //     } catch (FileException $e) {
            //         $this->addFlash('error', 'Image upload failed: '.$e->getMessage());
            //     }

            //     $product->setImage($newFilename);
            // }
            $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();

                    $imageFile->move(
                        $this->getParameter('images_directory'),  // ⬅️ this needs to exist in services.yaml
                        $newFilename
                    );

                if (!$product->getImage()) {    
                    $product->setImage($newFilename);
                }
            }

            $entityManager->persist($product);
            $entityManager->flush();

            // Log activity
            $this->logActivity('Create', 'Product', $product->getId(), 'Product created: ' . $product->getName());

            return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('products/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/show', name: 'app_products_show', methods: ['GET'])]
    public function show(Products $product): Response
    {
        // Require authentication (staff or admin)
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Staff can only view their own products
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only view your own products.');
        }

        return $this->render('products/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_products_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Products $product,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        // Require authentication (staff or admin)
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Staff can only edit their own products
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only edit your own products.');
        }

        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        $existingImage = $product->getImage(); // Keep old image

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: '.$e->getMessage());
                }

                $product->setImage($newFilename);
            } else {
                // Keep the existing image if no new image uploaded
                $product->setImage($existingImage);
            }

            // ✅ Ensure Doctrine tracks changes and updates correctly
            $entityManager->persist($product);
            $entityManager->flush();

            // Log activity
            $this->logActivity('Update', 'Product', $product->getId(), 'Product updated: ' . $product->getName());

            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_products_index');
        }

        return $this->render('products/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_products_delete', methods: ['POST'])]
    public function delete(Request $request, Products $product, EntityManagerInterface $entityManager): Response
    {
        // Require authentication (staff or admin)
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Staff can only delete their own products
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $user) {
            throw $this->createAccessDeniedException('You can only delete your own products.');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $productName = $product->getName();
            $productId = $product->getId();
            
            // Log activity before deletion
            $this->logActivity('Delete', 'Product', $productId, 'Product deleted: ' . $productName);
            
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
    }

    private function logActivity(string $action, string $entityType, ?int $entityId, ?string $description = null): void
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return;
        }

        $log = new ActivityLog();
        $log->setUser($user);
        
        // Get the primary role
        $roles = $user->getRoles();
        $primaryRole = 'ROLE_USER';
        foreach ($roles as $role) {
            if ($role !== 'ROLE_USER') {
                $primaryRole = $role;
                break;
            }
        }
        
        $log->setRole($primaryRole);
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setDescription($description);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
