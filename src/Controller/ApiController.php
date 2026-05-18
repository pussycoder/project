<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrdersRepository;
use App\Repository\ProductsRepository;
use App\Service\VerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/products', name: 'api_products', methods: ['GET'])]
    public function products(ProductsRepository $productsRepository): JsonResponse
    {
        $products = $productsRepository->findAll();
        $data = array_map(static function ($product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'category' => $product->getCategory()?->getName(),
                'image' => $product->getImage(),
            ];
        }, $products);

        return $this->json([
            'success' => true,
            'message' => 'Products fetched successfully',
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }

    #[Route('/orders', name: 'api_orders', methods: ['GET'])]
    public function orders(OrdersRepository $ordersRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $currentUser = $this->getUser();
        $orders = $this->isGranted('ROLE_ADMIN')
            ? $ordersRepository->findAll()
            : $ordersRepository->findBy(['processedBy' => $currentUser]);

        $data = array_map(static function ($order) {
            return [
                'id' => $order->getId(),
                'customer_name' => $order->getCustomerName(),
                'customer_email' => $order->getCustomerEmail(),
                'status' => $order->getStatus(),
                'total_price' => (float) $order->getTotalPrice(),
                'ordered_products' => $order->getOrderedProducts(),
                'created_at' => $order->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $orders);

        return $this->json([
            'success' => true,
            'message' => 'Orders fetched successfully',
            'data' => $data,
            'meta' => [
                'count' => count($data),
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        VerificationService $verificationService
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON payload'], 400);
        }

        $username = trim((string) ($payload['username'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $fullName = trim((string) ($payload['fullName'] ?? ''));

        if ($username === '' || $email === '' || $password === '') {
            return $this->json([
                'success' => false,
                'message' => 'username, email, and password are required',
            ], 422);
        }

        $existing = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existing) {
            return $this->json(['success' => false, 'message' => 'Username already exists'], 409);
        }

        $emailExists = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($emailExists) {
            return $this->json(['success' => false, 'message' => 'Email already exists'], 409);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setFullName($fullName !== '' ? $fullName : $username);
        $user->setRoles(['ROLE_STAFF']);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setIsVerified(false);

        $entityManager->persist($user);
        $entityManager->flush();

        $verificationService->generateTokenFor($user);
        $verificationService->sendVerificationEmail($user);

        return $this->json([
            'success' => true,
            'message' => 'Registration successful. Verify your email before login.',
            'data' => [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'verified' => $user->isVerified(),
            ],
            'meta' => [
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
        ], 201);
    }
}

