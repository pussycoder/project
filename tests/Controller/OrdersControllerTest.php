<?php

namespace App\Tests\Controller;

use App\Entity\Orders;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrdersControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $orderRepository;
    private string $path = '/orders/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->orderRepository = $this->manager->getRepository(Orders::class);

        foreach ($this->orderRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Order index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'order[customerName]' => 'Testing',
            'order[customerEmail]' => 'Testing',
            'order[shippingAddress]' => 'Testing',
            'order[status]' => 'Testing',
            'order[totalPrice]' => 'Testing',
            'order[createdAt]' => 'Testing',
            'order[updatedAt]' => 'Testing',
            'order[processedBy]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->orderRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Orders();
        $fixture->setCustomerName('My Title');
        $fixture->setCustomerEmail('My Title');
        $fixture->setShippingAddress('My Title');
        $fixture->setStatus('My Title');
        $fixture->setTotalPrice('My Title');
        $fixture->setCreatedAt('My Title');
        $fixture->setUpdatedAt('My Title');
        $fixture->setProcessedBy('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Order');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Orders();
        $fixture->setCustomerName('Value');
        $fixture->setCustomerEmail('Value');
        $fixture->setShippingAddress('Value');
        $fixture->setStatus('Value');
        $fixture->setTotalPrice('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setUpdatedAt('Value');
        $fixture->setProcessedBy('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'order[customerName]' => 'Something New',
            'order[customerEmail]' => 'Something New',
            'order[shippingAddress]' => 'Something New',
            'order[status]' => 'Something New',
            'order[totalPrice]' => 'Something New',
            'order[createdAt]' => 'Something New',
            'order[updatedAt]' => 'Something New',
            'order[processedBy]' => 'Something New',
        ]);

        self::assertResponseRedirects('/orders/');

        $fixture = $this->orderRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getCustomerName());
        self::assertSame('Something New', $fixture[0]->getCustomerEmail());
        self::assertSame('Something New', $fixture[0]->getShippingAddress());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getTotalPrice());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());
        self::assertSame('Something New', $fixture[0]->getUpdatedAt());
        self::assertSame('Something New', $fixture[0]->getProcessedBy());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Orders();
        $fixture->setCustomerName('Value');
        $fixture->setCustomerEmail('Value');
        $fixture->setShippingAddress('Value');
        $fixture->setStatus('Value');
        $fixture->setTotalPrice('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setUpdatedAt('Value');
        $fixture->setProcessedBy('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/orders/');
        self::assertSame(0, $this->orderRepository->count([]));
    }
}
