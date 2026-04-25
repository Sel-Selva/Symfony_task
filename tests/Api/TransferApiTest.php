<?php

namespace App\Tests\Api;

use App\Entity\Account;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected static function getKernelClass(): string
    {
        return 'App\\Kernel';
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testFundTransferBetweenAccounts(): void
    {
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        $source = new Account('USD', '200.00');
        $destination = new Account('USD', '25.00');
        $entityManager->persist($source);
        $entityManager->persist($destination);
        $entityManager->flush();

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fromAccountId' => $source->getId(),
                'toAccountId' => $destination->getId(),
                'amount' => '80.00',
                'currency' => 'USD',
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $entityManager->refresh($source);
        $entityManager->refresh($destination);

        $this->assertSame('120.00', $source->getBalance());
        $this->assertSame('105.00', $destination->getBalance());
    }

    public function testTransferFailsWithInsufficientFunds(): void
    {
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        $source = new Account('USD', '30.00');
        $destination = new Account('USD', '10.00');
        $entityManager->persist($source);
        $entityManager->persist($destination);
        $entityManager->flush();

        $this->client->request(
            'POST',
            '/api/transfers',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fromAccountId' => $source->getId(),
                'toAccountId' => $destination->getId(),
                'amount' => '123.45',
                'currency' => 'USD',
            ])
        );

        $response = $this->client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true);
        $this->assertSame('Account has insufficient funds.', $payload['error']);
    }
}
