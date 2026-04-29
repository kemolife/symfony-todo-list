<?php

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Clear filesystem cache pools from previous test runs
(new Filesystem())->remove(dirname(__DIR__).'/var/cache/test/pools');

if (str_contains($_SERVER['DATABASE_URL'] ?? '', 'sqlite')) {
    $kernel = new \App\Kernel('test', false);
    $kernel->boot();
    $em = $kernel->getContainer()->get('doctrine')->getManager();
    $metadata = $em->getMetadataFactory()->getAllMetadata();
    $schemaTool = new SchemaTool($em);
    $schemaTool->dropSchema($metadata);
    $schemaTool->createSchema($metadata);
    $kernel->shutdown();
}
