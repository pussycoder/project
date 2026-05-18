<?php

namespace App\Controller;

use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends AbstractController
{
    #[Route('/page', name: 'app_page')]
    public function home(): Response
    {
        return $this->render('page/index.html.twig');
    }

    #[Route('/about', name: 'app_page_about')]
    public function about(): Response
    {
        return $this->render('page/about.html.twig');
    }

    #[Route('/contact', name: 'app_page_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('contact', $token)) {
                $this->addFlash('error', 'Invalid form token. Please try again.');
                return $this->redirectToRoute('app_page_contact');
            }

            $name = trim((string) $request->request->get('name', ''));
            $fromEmail = trim((string) $request->request->get('email', ''));
            $topic = trim((string) $request->request->get('topic', ''));
            $message = trim((string) $request->request->get('message', ''));

            if ($name === '' || $fromEmail === '' || $message === '') {
                $this->addFlash('error', 'Please fill in your name, email, and message.');
                return $this->redirectToRoute('app_page_contact');
            }

            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Please enter a valid email address.');
                return $this->redirectToRoute('app_page_contact');
            }

            // Use env overrides when present (easy testing to your Gmail).
            // Note: Brevo may reject unverified "From" addresses, so Reply-To is always the user.
            $to = $_ENV['CONTACT_TO_EMAIL'] ?? 'support@skadoosh.com';
            $from = $_ENV['CONTACT_FROM_EMAIL'] ?? 'a56f02001@smtp-brevo.com';

            $email = (new Email())
                ->from($from)
                ->to($to)
                ->replyTo($fromEmail)
                ->subject('SKADOOSH Contact: ' . ($topic !== '' ? $topic : 'Message'))
                ->text(
                    "Name: {$name}\n" .
                    "Email: {$fromEmail}\n" .
                    "Topic: " . ($topic !== '' ? $topic : '—') . "\n\n" .
                    $message . "\n"
                );

            try {
                $mailer->send($email);
                return $this->redirectToRoute('app_page_contact', ['sent' => 1]);
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('error', 'Email could not be sent. Check MAILER_DSN (Brevo) configuration.');
                return $this->redirectToRoute('app_page_contact');
            }
        }

        return $this->render('page/contact.html.twig');
    }

    #[Route('/accessories', name: 'app_page_accessories')]
    public function accessories(): Response
    {
        return $this->render('page/shirts.html.twig');
    }

    #[Route('/shop', name: 'app_shop')]
    public function shop(ProductsRepository $productsRepository): Response
    {
        // Public catalog: show newest items (at least 5 when available)
        $products = $productsRepository->findBy([], ['id' => 'DESC'], 24);

        return $this->render('page/shop.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/shop/{id}', name: 'app_shop_product', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function shopProduct(int $id, Request $request, ProductsRepository $productsRepository): Response
    {
        $product = $productsRepository->find($id);
        if (!$product) {
            throw new NotFoundHttpException('Product not found.');
        }

        // Uniqlo-like query params
        $colorCode = (string) $request->query->get('colorCode', 'COL09');
        $sizeCode = (string) $request->query->get('sizeCode', 'SMA003');

        // Simple option sets (UI-only). If you later add real variants, replace these arrays.
        $colors = [
            ['code' => 'COL09', 'name' => 'Black', 'hex' => '#111827'],
            ['code' => 'COL01', 'name' => 'White', 'hex' => '#f8fafc'],
            ['code' => 'COL68', 'name' => 'Navy', 'hex' => '#0b1220'],
            ['code' => 'COL25', 'name' => 'Burgundy', 'hex' => '#7a0000'],
            ['code' => 'COL52', 'name' => 'Olive', 'hex' => '#3f4f2a'],
            ['code' => 'COL62', 'name' => 'Sky', 'hex' => '#60a5fa'],
        ];

        $sizes = [
            ['code' => 'SMA002', 'label' => 'XS'],
            ['code' => 'SMA003', 'label' => 'S'],
            ['code' => 'SMA004', 'label' => 'M'],
            ['code' => 'SMA005', 'label' => 'L'],
            ['code' => 'SMA006', 'label' => 'XL'],
            ['code' => 'SMA007', 'label' => 'XXL'],
        ];

        return $this->render('page/product.html.twig', [
            'product' => $product,
            'colorCode' => $colorCode,
            'sizeCode' => $sizeCode,
            'colors' => $colors,
            'sizes' => $sizes,
        ]);
    }
}  
