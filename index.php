<?php

use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;

use MongoDB\Client;
use MongoDB\BSON\Regex;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;


include 'Product.php';

include 'bootstrap.php';

require __DIR__ . '/vendor/autoload.php';


$access_data = include 'params.php'; // in root folder for this project only






/**
 * Instantiate App
 *
 * In order for the factory to work you need to ensure you have installed
 * a supported PSR-7 implementation of your choice e.g.: Slim PSR-7 and a supported
 * ServerRequest creator (included with Slim PSR-7)
 */
$app = AppFactory::create();
$app->setBasePath('/SimpleRestAPI');









/////////////////////// Check header for X-token
$beforeMiddleware = function (Request $request, RequestHandler $handler) {

    GLOBAL $access_data;
    $token_name = 'X-token';
    
    if ( !$request->hasHeader($token_name) || $request->getHeaderLine($token_name) != $access_data->SimpleRestAPI[$token_name] ) {
        $response = new Response();
        $response->getBody()->write( json_encode(['message' => 'unauthorized'], JSON_PRETTY_PRINT) );

        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    } else{
        try {
            $response = $handler->handle($request);
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write( json_encode(['message' => $e->getMessage()], JSON_PRETTY_PRINT) );
            $response->withStatus($e->getCode());
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }


};
$app->add($beforeMiddleware);








/**
 * Add Error Handling Middleware
 *
 * @param bool $displayErrorDetails -> Should be set to false in production
 * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool $logErrorDetails -> Display error details in error log
 * which can be replaced by a callable of your choice.
 
 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);








////////////////////// Define app routes


$app->post('/create', function (Request $request, Response $response, array $args) {
    $success = true;
    $message = '';

    $input = file_get_contents('php://input');
    $contents = json_decode($input, true);
    
    if (empty($contents)) {
        $response->getBody()->write( json_encode(['message' => 'bad_request'], JSON_PRETTY_PRINT) );
        return $response->withStatus(400);
    }

    // check if one or multiple products will be created
    $is_one_product_submitted = false;
    foreach ($contents as $key => $value) {
        if (!is_array($value)) {
            $is_one_product_submitted = true;
        }
    }
    reset($contents);


    $config = new Configuration();
    $config->setProxyDir(__DIR__ . '/Proxies');
    $config->setProxyNamespace('Proxies');
    $config->setHydratorDir(__DIR__ . '/Hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setDefaultDB('product_db');
    $config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/Products'));
    
        
    $client = new Client('mongodb://localhost', [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
    $dm = DocumentManager::create($client, $config);
  

  
    GLOBAL $access_data;
    $message = '';
    $if_error = false;
    
    if ($is_one_product_submitted) {

        $message = findCreateUpdate($contents, $access_data, $dm);

    } else {
        foreach ($contents as $item) { 
            $message = findCreateUpdate($item, $access_data, $dm);
            if ($message == 'error') {
                $if_error = true;
            }
        }

    }
    
    if ($if_error)
        $message = 'error';
    else
        $dm->flush();
    
    $response->getBody()->write(json_encode( ['message' => $message], JSON_PRETTY_PRINT) );
    
    if ($message == 'created') {
        return $response->withStatus(201);
    } else if ($message == 'created') {
        return $response->withStatus(200);
    } else {
        return $response->withStatus(400);
    }
});










$app->get('/search', function (Request $request, Response $response, array $args) {

    $query_params = $request->getQueryParams();


    $config = new Configuration();
    $config->setProxyDir(__DIR__ . '/Proxies');
    $config->setProxyNamespace('Proxies');
    $config->setHydratorDir(__DIR__ . '/Hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setDefaultDB('product_db');
    $config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/Products'));
    
        
    $client = new Client('mongodb://localhost', [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
    $dm = DocumentManager::create($client, $config);



    $qb = $dm->createQueryBuilder(Product::class);

        
    
    foreach ($query_params as $key => $value) {
        
        if ($key == 'color')
            $key = 'colors';
        else if ($key == 'size')
            $key = 'sizes';

        $qb->field($key)->equals( ['$regex' => '^'.$value] );
    }

    //$qb->field('colors')->equals( ['$regex' => '^y'] );
    $query = $qb->getQuery();
    $products = $query->execute();


    $final = array();
    foreach ($products as $value) {
        $final[] = $value->getArray();
    }
    
    $response->getBody()->write( json_encode( $final, JSON_PRETTY_PRINT) );


    //$response->getBody()->write( json_encode($products->getArray(), JSON_PRETTY_PRINT) );
    return $response->withStatus(200);
});








$app->run();










///        library

function object_to_array($data)
{
    if (is_array($data) || is_object($data))
    {
        $result = array();
        foreach ($data as $key => $value)
        {
            $result[$key] = object_to_array($value);
        }
        return $result;
    }
    return $data;
}



function areRequiredPresent(array $product_data) {
    $required = (new Product())->getRequiredArray();

    foreach ($required as $value) {             // !!!!!!!!!!! doesn't work for first item in group array . I don't know why.
        if ( empty($product_data[$value]) ) {
            return false;
        }
    }

    return true;
}




function findCreateUpdate(array $product_data, object $access_data, DocumentManager $dm) {

    if (!areRequiredPresent($product_data)) {   
        return 'error: missing input property';
    } else {

        $product_rep = $dm->getRepository(Product::class)->findOneBy(['product_id' => $product_data['product_id']]);
        
        $product = new Product();

        if (!empty($product_rep))
            $product = $product_rep;

        // check and update Product data
        $message = $product->setDataArray($product_data);

        if ($message) {
            // fetch from external api for item product_id
            $product->updateFromAPI($access_data->PromoProductsAPI);
            // create or update db
            $dm->persist($product);
            
            if (empty($product_rep)) {
                return 'created';
            } else
                return 'updated';
        } else {
            return 'error';
        }
    }
}


