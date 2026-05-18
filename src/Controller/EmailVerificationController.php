<?php

namespace App\Controller;

use App\Service\VerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify/email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verify(string $token, VerificationService $verificationService): Response
    {
        $user = $verificationService->verifyByToken($token);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification link.');
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Email verified successfully. You can now sign in.');
        return $this->redirectToRoute('app_login');
    }
}

