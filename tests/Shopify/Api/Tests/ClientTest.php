<?php

namespace Shopify\Api\Tests;

class ClientTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {

        $this->shopName = 'mycoolshop';
        $this->clientSecret = 'ABC123XYZ';
        $this->permanentAccessToken = '0987654321';
        $this->shopUri = "https://{$this->shopName}.myshopify.com";

        $this->httpClient = $this->getMock('Shopify\HttpClient');

        $this->api = new \Shopify\Api\Client($this->httpClient);
        $this->api->setShopName($this->shopName);
        $this->api->setClientSecret($this->clientSecret);
        $this->api->setAccessToken($this->permanentAccessToken);

    }

    public function testGetRequest()
    {

        $this->assertEquals($this->shopUri, $this->api->getShopUri());

        $productUri = '/admin/product/632910392.json';
        $productResponse = $this->getProductResponse();
        $product = json_decode($productResponse);

        $this->httpClient->expects($this->once())
                         ->method('get')
                         ->with($this->shopUri . $productUri)
                         ->will($this->returnValue($productResponse));

        // retrieve a single product
        // @see http://docs.shopify.com/api/product#show
        $this->assertEquals($product, $this->api->get($productUri));

    }

    public function testPostRequest()
    {

        $ordersUri = '/admin/orders.json';
        $ordersRequest = $this->getOrdersRequest();
        $ordersResponse = $this->getOrdersResponse();
        $order = json_decode($ordersResponse);

        $this->httpClient->expects($this->once())
                         ->method('post')
                         ->with(
                            $this->shopUri . $ordersUri,
                            json_encode($ordersRequest)
                         )
                         ->will($this->returnValue($ordersResponse));

        // create a new order
        // @see http://docs.shopify.com/api/order#create
        $this->assertEquals($order, $this->api->post(
            $ordersUri, $ordersRequest
        ));

    }

    public function testRequestValidation()
    {

        $this->api->setClientSecret('hush');

        $signature = "31b9fcfbd98a3650b8523bcc92f8c5d2";

        // Assume we have the query parameters in a hash
        $params = array(
            'shop' => "some-shop.myshopify.com",
            'code' => "a94a110d86d2452eb3e2af4cfb8a3828",
            'timestamp' => "1337178173", // 2012-05-16 14:22:53
            'signature' => $signature,
            'admin' => 1,
        );

        $this->assertEquals($signature, $this->api->generateSignature($params));

        $paramsWithSignature = $params;
        $paramsWithSignature['signature'] = $signature;

        $this->assertTrue($this->api->validateSignature($paramsWithSignature));

        // request is older than 1 day, expect false
        $this->assertFalse($this->api->isValidRequest($paramsWithSignature));

    }

    public function testCallLimits()
    {

        $callsMade = 10;
        $callLimit = 100;
        $callsRemaining = $callLimit - $callsMade;

        $headers = array(
            \Shopify\Api\Client::SHOP_API_CALL_LIMIT => '10/100'
        );

        $this->assertEquals(
            $callsMade, $this->api->getNumberOfCallsMade($headers)
        );

        $this->assertEquals(
            $callLimit, $this->api->getCallLimit($headers)
        );

        $this->assertEquals(
            $callsRemaining, $this->api->getNumberOfCallsRemaining($headers)
        );

    }

    protected function getOrdersRequest()
    {
        return array(
            'order' => array(
                'line_items' => array(
                    0 => array(
                        'grams' => 1300,
                        'price' => 74.99,
                        'quantity' => 3,
                        'title' => "Big Brown Bear Boots",
                    ),
                ),
                'tax_lines' => array(
                    0 => array(
                        'price' => 29.25,
                        'rate' => 0.13,
                        'title' => "HST",
                    ),
                ),
                'transactions' => array(
                    0 => array(
                        'amount' => 254.22,
                        'kind' => "sale",
                        'status' => "success",
                    )
                ),
                'total_tax' => 29.25,
                'currency' => "CAD",
            )
        );
    }

    protected function getOrdersResponse()
    {
        return <<<JSON
{
  "order": {
    "buyer_accepts_marketing": false,
    "cancel_reason": null,
    "cancelled_at": null,
    "cart_token": null,
    "checkout_token": null,
    "closed_at": null,
    "confirmed": true,
    "created_at": "2014-01-24T16:30:55-05:00",
    "currency": "EUR",
    "email": "",
    "financial_status": "paid",
    "fulfillment_status": null,
    "gateway": "",
    "id": 1073459962,
    "landing_site": null,
    "location_id": null,
    "name": "#1002",
    "note": null,
    "number": 2,
    "reference": null,
    "referring_site": null,
    "source": "api",
    "subtotal_price": "224.97",
    "taxes_included": false,
    "test": false,
    "token": "2703ec3adcf7d6f9998fbc5e5a57d83d",
    "total_discounts": "0.00",
    "total_line_items_price": "224.97",
    "total_price": "238.47",
    "total_price_usd": "340.54",
    "total_tax": "13.50",
    "total_weight": 0,
    "updated_at": "2014-01-24T16:30:55-05:00",
    "user_id": null,
    "browser_ip": null,
    "landing_site_ref": null,
    "order_number": 1002,
    "discount_codes": [

    ],
    "note_attributes": [

    ],
    "processing_method": null,
    "checkout_id": null,
    "source_name": "api",
    "tax_lines": [
      {
        "price": "13.50",
        "rate": 0.06,
        "title": "State tax"
      }
    ],
    "line_items": [
      {
        "fulfillment_service": "manual",
        "fulfillment_status": null,
        "grams": 1300,
        "id": 1071823172,
        "price": "74.99",
        "product_id": null,
        "quantity": 3,
        "requires_shipping": true,
        "sku": null,
        "title": "Big Brown Bear Boots",
        "variant_id": null,
        "variant_title": null,
        "vendor": null,
        "name": "Big Brown Bear Boots",
        "variant_inventory_management": null,
        "properties": [

        ],
        "product_exists": false
      }
    ],
    "shipping_lines": [

    ],
    "fulfillments": [

    ]
  }
}
JSON;
    }

    protected function getProductResponse()
    {
        return <<<JSON
{
  "product": {
    "body_html": "<p>It's the small iPod with one very big idea: Video. Now the world's most popular music player, available in 4GB and 8GB models, lets you enjoy TV shows, movies, video podcasts, and more. The larger, brighter display means amazing picture quality. In six eye-catching colors, iPod nano is stunning all around. And with models starting at just $149, little speaks volumes.</p>",
    "created_at": "2014-01-24T16:30:53-05:00",
    "handle": "ipod-nano",
    "id": 632910392,
    "product_type": "Cult Products",
    "published_at": "2007-12-31T19:00:00-05:00",
    "published_scope": "global",
    "template_suffix": null,
    "title": "IPod Nano - 8GB",
    "updated_at": "2014-01-24T16:30:53-05:00",
    "vendor": "Apple",
    "tags": "Emotive, Flash Memory, MP3, Music",
    "variants": [
      {
        "barcode": "1234_pink",
        "compare_at_price": null,
        "created_at": "2014-01-24T16:30:53-05:00",
        "fulfillment_service": "manual",
        "grams": 200,
        "id": 808950810,
        "inventory_management": "shopify",
        "inventory_policy": "continue",
        "option1": "Pink",
        "option2": null,
        "option3": null,
        "position": 1,
        "price": "199.00",
        "product_id": 632910392,
        "requires_shipping": true,
        "sku": "IPOD2008PINK",
        "taxable": true,
        "title": "Pink",
        "updated_at": "2014-01-24T16:30:53-05:00",
        "inventory_quantity": 10
      }
    ],
    "options": [
      {
        "id": 594680422,
        "name": "Title",
        "position": 1,
        "product_id": 632910392
      }
    ],
    "images": [
      {
        "created_at": "2014-01-24T16:30:53-05:00",
        "id": 850703190,
        "position": 1,
        "product_id": 632910392,
        "updated_at": "2014-01-24T16:30:53-05:00",
        "src": "http://cdn.shopify.com/s/files/1/0006/9093/3842/products/ipod-nano.png?v=1390599053"
      },
      {
        "created_at": "2014-01-24T16:30:53-05:00",
        "id": 562641783,
        "position": 2,
        "product_id": 632910392,
        "updated_at": "2014-01-24T16:30:53-05:00",
        "src": "http://cdn.shopify.com/s/files/1/0006/9093/3842/products/ipod-nano-2.png?v=1390599053"
      }
    ],
    "image": {
      "created_at": "2014-01-24T16:30:53-05:00",
      "id": 850703190,
      "position": 1,
      "product_id": 632910392,
      "updated_at": "2014-01-24T16:30:53-05:00",
      "src": "http://cdn.shopify.com/s/files/1/0006/9093/3842/products/ipod-nano.png?v=1390599053"
    }
  }
}
JSON;
    }
}
