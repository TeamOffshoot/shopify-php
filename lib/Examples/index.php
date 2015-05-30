<?php

include 'autoload.php';

use \Shopify\Api\Client;
use \Shopify\Api\AuthenticationGateway;
use \Shopify\HttpClient\CurlHttpClient;
use \Shopify\Redirector\HeaderRedirector;

$settings=json_decode(file_get_contents('settings.json'));

if (!empty($settings->shopName) && !empty($_GET['code'])) {
    Client::doValidateSignature($settings->clientSecret, $_GET) or die('Signature validation failed');
    $auth = new AuthenticationGateway(new CurlHttpClient(null, false, false), new HeaderRedirector());
    $token = $auth->forShopName($settings->shopName)
        ->usingClientId($settings->clientId)
        ->usingClientSecret($settings->clientSecret)
        ->toExchange($_GET['code']);
    if ($token) {
        $settings->accessToken = $token;
        file_put_contents('settings.json', json_encode($settings, JSON_PRETTY_PRINT));
        HeaderRedirector::go($settings->redirectUri);
    } else {
        die('toExchange failed');
    }
}

if (empty($settings->accessToken)) die('not authenticated, use install.php first');

$client = new Client(new CurlHttpClient(null, false, false), $settings);
$result = $client->get('/admin/pages.json');
var_dump($result);

