<?php
require __DIR__ . '/vendor/autoload.php';
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$env = [
    'DEFAULT_URI' => 'http://localhost',
    'APP_ENV' => 'test',
    'DATABASE_URL' => 'sqlite:///data_test.db',
    'LOCK_DSN' => 'flock',
    'REDIS_URL' => 'redis://127.0.0.1:6379',
];
foreach ($env as $k => $v) {
    putenv("$k=$v");
    $_SERVER[$k] = $v;
}

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
$em = $container->get('doctrine')->getManager();
$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
$metadata = $em->getMetadataFactory()->getAllMetadata();
$schemaTool->dropSchema($metadata);
$schemaTool->createSchema($metadata);

$source = new App\Entity\Account('USD', '200.00');
$dest = new App\Entity\Account('USD', '25.00');
$em->persist($source);
$em->persist($dest);
$em->flush();

$request = Request::create('/api/transfers', 'POST', [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json', 'CONTENT_TYPE' => 'application/json'], json_encode([
    'fromAccountId' => $source->getId(),
    'toAccountId' => $dest->getId(),
    'amount' => '80.00',
    'currency' => 'USD',
]));

try {
    $response = $kernel->handle($request);
    echo $response->getStatusCode() . "\n";
    echo $response->getContent() . "\n";
} catch (\Throwable $e) {
    echo get_class($e) . "\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
