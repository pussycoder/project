<?php

namespace App\Command;

use App\Entity\Orders;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-order',
    description: 'Create a test order',
)]
class CreateOrderCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $order = new Orders();
        $order->setCustomerName('John Doe');
        $order->setCustomerEmail('john@example.com');
        $order->setShippingAddress('123 Main St, City, State');
        $order->setStatus('Pending');
        $order->setTotalPrice('99.99');

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $io->success('Test order created with ID: ' . $order->getId());

        return Command::SUCCESS;
    }
}
