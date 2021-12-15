<?php


namespace Jtar\Elasticsearch\Models;

use Elasticsearch\Client;
use Jtar\Elasticsearch\BuilderFactory;

class ElasticsearchModel
{
    /**
     * 索引名称（如果索引定义了别名，此处也可以使用别名代替）.
     *
     * @var string
     */
    protected $index;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var Client
     */
    protected $client;

    /**
     * 获取索引名称.
     *
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * @param string $index
     *
     * @return void
     */
    public function setIndex(string $index)
    {
        $this->index = $index;
    }

    /**
     * @param string $type
     *
     * @return void
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Query
     */
    public function query(): Query
    {
        return new Query($this);
    }

    /**
     * @param mixed $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * 获取ES客户端.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client ?: ($this->client = make(BuilderFactory::class)->getClient());
    }

    public function __call($method, array $args = [])
    {
        return $this->query()->$method(...$args);
    }

}