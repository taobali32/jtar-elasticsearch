<?php


namespace Jtar\Elasticsearch;


use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Hyperf\Guzzle\RingPHP\PoolHandler;
use Swoole\Coroutine;

class BuilderFactory
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        $builder = ClientBuilder::create();

        if (Coroutine::getCid() > 0) {
            $handler = make(PoolHandler::class, [
                'option' => [
                    'max_connections' => 100,
                ],
            ]);
            $builder->setHandler($handler);
        }

        //  https://username:password@es-8jqtzuee.public.tencentelasticsearch.com:9200'
        $this->client = $builder->setHosts([env('ES_HOST')])->build();

        return $this->client;
    }

}