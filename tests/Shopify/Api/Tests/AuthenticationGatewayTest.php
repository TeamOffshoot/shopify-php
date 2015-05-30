<?php

namespace Shopify\Api\Tests;

class AuthenticationGatewayTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Shopify\Api\AuthenticationGateway
     */
    protected $authenticate;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpClient;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirector;

    public function setUp()
    {

        $this->httpClient = $this->getMock('Shopify\HttpClient');
        $this->redirector = $this->getMock('Shopify\Redirector');

        $this->authenticate = new \Shopify\Api\AuthenticationGateway(
            $this->httpClient, $this->redirector
        );

    }

    public function testInitiatingLogin()
    {

        $shopName = 'shop-name';
        $clientId = 'XXX1234567890';
        $permissions = array('write_products', 'read_orders');
        $redirectUri = 'http://shopify.com/app';

        $authorizeUrl = "https://{$shopName}.myshopify.com"
            . "/admin/oauth/authorize"
            . "?" . http_build_query(array(
                'client_id' => $clientId,
                'scope' => join(',', $permissions),
                'redirect_uri' => $redirectUri
            ));

        $this->redirector->expects($this->once())
                         ->method('redirect')
                         ->with($authorizeUrl)
                         ->will($this->returnValue($redirectUri));

        $this->authenticate->forShopName($shopName)
                           ->usingClientId($clientId)
                           ->withScope($permissions)
                           ->andReturningTo($redirectUri)
                           ->initiateLogin();

        $this->assertEquals(
            $authorizeUrl, $this->authenticate->getAuthenticationUri()
        );

    }

    public function testExchangingToken()
    {

        $shopName = 'shop-name';
        $clientId = 'XXX1234567890';
        $clientSecret = 'ABC123XYZ';
        $temporaryToken = 'TEMP_TOKEN';
        $permanentAccessToken = 'ACCESS_TOKEN';

        $accessUri = "https://{$shopName}.myshopify.com/admin/oauth/access_token";
        $params = array(
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $temporaryToken,
        );

        $response = '{"access_token": "' . $permanentAccessToken . '"}';

        $this->httpClient->expects($this->once())
                         ->method('post')
                         ->with($accessUri, $params)
                         ->will($this->returnValue($response));

        $token = $this->authenticate->forShopName($shopName)
                                    ->usingClientId($clientId)
                                    ->usingClientSecret($clientSecret)
                                    ->toExchange($temporaryToken);

        $this->assertEquals(
            $accessUri, $this->authenticate->getAccessUri()
        );

        $this->assertEquals($permanentAccessToken, $token);

    }

}
