<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/activity-logs')]
final class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'app_activity_log_index', methods: ['GET'])]
    public function index(
        ActivityLogRepository $activityLogRepository,
        UserRepository $userRepository,
        Request $request
    ): Response {
        // Check if user has ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $action = $request->query->get('action');
        $userId = $request->query->get('user');
        $date = $request->query->get('date');
        $limit = (int) ($request->query->get('limit', 100));

        // Parse date filter
        $startDate = null;
        $endDate = null;
        if ($date) {
            try {
                $startDate = new \DateTime($date . ' 00:00:00');
                $endDate = new \DateTime($date . ' 23:59:59');
            } catch (\Exception $e) {
                // Invalid date, ignore
            }
        }

        // Get all users for filter dropdown
        $users = $userRepository->findAll();

        // Apply filters
        $logs = $activityLogRepository->findWithFilters(
            $action ?: null,
            $userId ? (int) $userId : null,
            $startDate,
            $endDate,
            $limit
        );

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'users' => $users,
            'currentAction' => $action,
            'currentUser' => $userId ? (int) $userId : null,
            'currentDate' => $date,
        ]);
    }
}

