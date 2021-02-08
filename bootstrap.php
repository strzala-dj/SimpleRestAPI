<?php


use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use MongoDB\Client;


if ( ! file_exists($file = __DIR__.'/vendor/autoload.php')) {
    throw new RuntimeException('Install dependencies to run this script.');
}

$loader = require_once $file;
$loader->add('Products', __DIR__); // do sprawdzenia, czy na pewno ten katalof

AnnotationRegistry::registerLoader([$loader, 'loadClass']);


$config = new Configuration();
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');
$config->setHydratorDir(__DIR__ . '/Hydrators');
$config->setHydratorNamespace('Hydrators');
$config->setDefaultDB('product_db');
$config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/Products'));



$client = new Client('mongodb://localhost', [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
$dm = DocumentManager::create($client, $config);

spl_autoload_register($config->getProxyManagerConfiguration()->getProxyAutoloader()); /// ??????? usunąć?