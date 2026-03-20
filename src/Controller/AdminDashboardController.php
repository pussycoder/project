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
        ]);
    }
}
