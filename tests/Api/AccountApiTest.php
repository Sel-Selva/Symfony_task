<?php

namespace App\Tests\Api;

use App\Entity\Account;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountApiTest extends WebTestCase
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

    public function testCreateAndShowAccount(): void
    {

        $this->client->request(
            'POST',
            '/api/accounts',
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            json_encode(['currency' => 'USD', 'initialBalance' => '120.00'])
        );

        $response = $this->client->getResponse();
        $this->assertSame(201, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $payload);
        $this->assertSame('USD', $payload['currency']);
        $this->assertSame('120.00', $payload['balance']);

        $this->client->request('GET', '/api/accounts/' . $payload['id']);
        $showResponse = $this->client->getResponse();
        $this->assertSame(200, $showResponse->getStatusCode());

        $showPayload = json_decode($showResponse->getContent(), true);
        $this->assertSame('120.00', $showPayload['balance']);
        $this->assertSame($payload['id'], $showPayload['id']);
    }
}
