<?php

namespace Dialogflow\tests;

use RuntimeException;

use PHPUnit\Framework\TestCase;
use Dialogflow\WebhookClient;

class WebhookClientTest extends TestCase
{
    protected $agentv1google;
    protected $agentv1facebook;
    protected $agentv1web;
    protected $agentv2google;
    protected $agentv2facebook;
    protected $agentv2web;

    protected function setUp()
    {
        $data_v1_google = json_decode(file_get_contents(__DIR__ . '/stubs/request-v1-google.json'), true);
        $this->agentv1google = new WebhookClient($data_v1_google);

        $data_v1_facebook = json_decode(file_get_contents(__DIR__ . '/stubs/request-v1-facebook.json'), true);
        $this->agentv1facebook = new WebhookClient($data_v1_facebook);

        $data_v1_web = json_decode(file_get_contents(__DIR__ . '/stubs/request-v1-web.json'), true);
        $this->agentv1web = new WebhookClient($data_v1_web);

        $data_v2_google = json_decode(file_get_contents(__DIR__ . '/stubs/request-v2-google.json'), true);
        $this->agentv2google = new WebhookClient($data_v2_google);

        $data_v2_facebook = json_decode(file_get_contents(__DIR__ . '/stubs/request-v2-facebook.json'), true);
        $this->agentv2facebook = new WebhookClient($data_v2_facebook);
    }

    public function testConstruct()
    {
        $this->assertInstanceOf('\Dialogflow\WebhookClient', $this->agentv1google);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testException()
    {
        $request = new WebhookClient([]);
    }

    public function testAgentVersion()
    {
        $this->assertEquals(1, $this->agentv1google->getAgentVersion());
        $this->assertEquals(1, $this->agentv1facebook->getAgentVersion());
        $this->assertEquals(1, $this->agentv1web->getAgentVersion());
        $this->assertEquals(2, $this->agentv2facebook->getAgentVersion());
        $this->assertEquals(2, $this->agentv2google->getAgentVersion());
    }

    public function testIntent()
    {
        $this->assertEquals('prayer.time', $this->agentv1google->getIntent());
    }

    public function testAction()
    {
        $this->assertEquals('prayer.time', $this->agentv1google->getAction());
    }

    public function testSession()
    {
        $this->assertEquals('1525478176609', $this->agentv1google->getSession());
    }

    public function testParameters()
    {
        $expectedParameters = [
            'date' => null,
            'kota' => '1470',
            'propinsi' => null,
            'shalat' => 'isha'
        ];

        $this->assertEquals($expectedParameters, $this->agentv1google->getParameters());
    }

    public function testContexts()
    {
        $contexts = $this->agentv1google->getContexts();

        $this->assertInternalType('array', $contexts);

        if (count($contexts)>0) {
            $context = $contexts[0];

            $this->assertInstanceOf('\Dialogflow\Context', $context);

            $this->assertEquals('google_assistant_welcome', $context->getName());
            $this->assertEquals(0, $context->getLifespan());

            $expectedParameters = [
                "date" => null,
                "propinsi" => null,
                "kota.original" => "jakarta utara",
                "kota" => "1470",
                "shalat.original" => "isya",
                "date.original" => null,
                "shalat" => "isha",
                "propinsi.original" => null
            ];

            $this->assertEquals($expectedParameters, $context->getParameters());
        }
    }

    public function testRequestSource()
    {
        $this->assertEquals('google', $this->agentv1google->getRequestSource());
        $this->assertEquals('agent', $this->agentv1web->getRequestSource());
    }

    public function testOriginalRequest()
    {
        $originalRequest = $this->agentv1google->getOriginalRequest();

        $this->assertInternalType('array', $originalRequest);
    }

    public function testQuery()
    {
        $this->assertEquals('kapan waktu shalat isya di jakarta utara', $this->agentv1google->getQuery());
    }

    public function testLocale()
    {
        $this->assertEquals('id', $this->agentv1google->getLocale());
    }

    public function testReplyV1GoogleSimple()
    {
        $this->agentv1google->reply('Welcome');

        $this->assertEquals([
            'messages' => [
                [
                    'type' => 'simple_response',
                    'platform' => 'google',
                    'textToSpeech' => 'Welcome',
                    'displayText' => 'Welcome'
                ]
            ]
        ], $this->agentv1google->render());
    }

    public function testReplyV2GoogleSimple()
    {
        $this->agentv2google->reply('Welcome');

        $this->assertEquals([
            'fulfillmentMessages' => [
                [
                    'platform' => 'ACTIONS_ON_GOOGLE',
                    'simpleResponses' => [
                        'simpleResponses' => [
                            [
                                'textToSpeech' => 'Welcome',
                                'displayText' => 'Welcome'
                            ]
                        ]
                    ]
                ]
            ]
        ], $this->agentv2google->render());
    }

    public function testReplyV1FacebookSimple()
    {
        $this->agentv1facebook->reply('Welcome');

        $this->assertEquals([
            'messages' => [
                [
                    'type' => 0,
                    'platform' => 'facebook',
                    'speech' => 'Welcome'
                ]
            ]
        ], $this->agentv1facebook->render());
    }

    public function testReplyV2FacebookSimple()
    {
        $this->agentv2facebook->reply('Welcome');

        $this->assertEquals([
            'fulfillmentMessages' => [
                [
                    'text' => [
                        'text' => [
                            'Welcome'
                        ]
                    ],
                    'platform' => 'FACEBOOK'
                ]
            ]
        ], $this->agentv2facebook->render());
    }

    public function testReplyV1WebSimple()
    {
        $this->agentv1web->reply('Welcome');

        $this->assertEquals([
            'messages' => [
                [
                    'type' => 0,
                    'speech' => 'Welcome'
                ]
            ],
            'speech' => 'Welcome'
        ], $this->agentv1web->render());
    }

    public function testReplyV1GoogleText()
    {
        $text = \Dialogflow\RichMessage\Text::create()
            ->text('Welcome')
            ->ssml('Hi, welcome')
        ;

        $this->agentv1google->reply($text);

        $array = $this->agentv1google->render();
        $expectedArray = [
            'messages' => [
                [
                    'type' => 'simple_response',
                    'platform' => 'google',
                    'textToSpeech' => 'Welcome',
                    'displayText' => 'Welcome'
                ]
            ]
        ];

        $this->assertEquals($expectedArray, $array);
    }

    public function testReplyV2GoogleText()
    {
        $text = \Dialogflow\RichMessage\Text::create()
            ->text('Welcome')
            ->ssml('Hi, welcome')
        ;

        $this->agentv2google->reply($text);

        $this->assertEquals([
            'fulfillmentMessages' => [
                [
                    'platform' => 'ACTIONS_ON_GOOGLE',
                    'simpleResponses' => [
                        'simpleResponses' => [
                            [
                                'ssml' => 'Hi, welcome',
                                'displayText' => 'Welcome'
                            ]
                        ]
                    ]
                ]
            ]
        ], $this->agentv2google->render());
    }
}
