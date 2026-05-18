<?php

namespace App\Controller;

use App\Repository\ProductsRepository;
use App\Repository\OrdersRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        ProductsRepository $productsRepository,
        OrdersRepository $ordersRepository,
        UserRepository $userRepository
    ): Response {
        // Check if user has ROLE_ADMIN
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get real data from database
        $products = $productsRepository->findAll();
        $orders = $ordersRepository->findAll();
        $users = $userRepository->findAll();
        
        // Calculate statistics
        $totalProducts = count($products);
        $totalOrders = count($orders);
        $totalUsers = count($users);
        
        // Count users by role
        $totalAdmins = count(array_filter($users, fn($u) => in_array('ROLE_ADMIN', $u->getRoles(), true)));
        $totalStaff = count(array_filter($users, fn($u) => in_array('ROLE_STAFF', $u->getRoles(), true)));
        
        // Calculate total revenue
        $totalRevenue = 0;
        foreach ($orders as $order) {
            $totalRevenue += (float) $order->getTotalPrice();
        }
        
        // Get recent orders (last 5)
        $recentOrders = $ordersRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Get orders by status
        $pendingOrders = $ordersRepository->findBy(['status' => 'Pending']);
        $completedOrders = $ordersRepository->findBy(['status' => 'Completed']);

        // Build last-7-days revenue data for chart
        $today = new \DateTimeImmutable('today');
        $salesLabels = [];
        $salesData = [];
        $dailyRevenue = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->modify("-{$i} days");
            $key = $date->format('Y-m-d');
            $salesLabels[] = $date->format('D');
            $dailyRevenue[$key] = 0.0;
        }

        foreach ($orders as $order) {
            $createdAt = $order->getCreatedAt();
            if (!$createdAt) {
                continue;
            }

            $dayKey = $createdAt->format('Y-m-d');
            if (array_key_exists($dayKey, $dailyRevenue)) {
                $dailyRevenue[$dayKey] += (float) $order->getTotalPrice();
            }
        }

        foreach ($dailyRevenue as $amount) {
            $salesData[] = round($amount, 2);
        }
        
        return $this->render('admin_dashboard/index.html.twig', [
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalUsers' => $totalUsers,
            'totalAdmins' => $totalAdmins,
            'totalStaff' => $totalStaff,
            'totalRevenue' => $totalRevenue,
            'recentOrders' => $recentOrders,
            'pendingOrders' => count($pendingOrders),
            'completedOrders' => count($completedOrders),
            'salesLabels' => $salesLabels,
            'salesData' => $salesData,
        ]);
    }
}
