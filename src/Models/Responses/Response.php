<?php

namespace Jtar\Elasticsearch\Models\Responses;

abstract class Response
{
    /**
     * @var array
     */
    protected $content = [];

    public function __construct(array $content)
    {
        $this->content = $content;
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }
}