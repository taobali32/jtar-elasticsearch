<?php

namespace Jtar\Elasticsearch\Models\Responses;


class CreatedResponse extends Response
{
    /**
     * 获取新增id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->content['_id'] ?? '';
    }

    /**
     * 判断是否成功
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        $created = $this->content['result'] ?? false;

        return $created === 'created' || $created === 'updated';
    }
}