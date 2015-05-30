<?php

if (empty($_GET['shopName'])) die('shopName not provided');

include 'autoload.php';

use \Shopify\Api\AuthenticationGateway;
use \Shopify\HttpClient\CurlHttpClient;
use \Shopify\Redirector\HeaderRedirector;

$settings=json_decode(file_get_contents('settings.json'));
$settings->shopName = $_GET['shopName'];
$settings->redirectUri = "http://{$_SERVER['HTTP_HOST']}".dirname($_SERVER['REQUEST_URI']).'/';

file_put_contents('settings.json', json_encode($settings, JSON_PRETTY_PRINT));

$auth = new AuthenticationGateway(new CurlHttpClient(null, false, false), new HeaderRedirector());
$auth->forShopName($settings->shopName)
    ->usingClientId($settings->clientId)
    ->withScope($settings->permissions)
    ->andReturningTo($settings->redirectUri)
    ->initiateLogin();
