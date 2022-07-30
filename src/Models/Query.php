<?php


namespace Jtar\Elasticsearch\Models;

use Jtar\Elasticsearch\Models\Responses\BatchUpdatedResponse;
use Jtar\Elasticsearch\Models\Responses\BulkResponse;
use Jtar\Elasticsearch\Models\Responses\CreatedResponse;
use Jtar\Elasticsearch\Models\Responses\DeletedResponse;
use Jtar\Elasticsearch\Models\Responses\FindIdResponse;
use Jtar\Elasticsearch\Models\Responses\ListResponse;
use Jtar\Elasticsearch\Models\Responses\UpdatedResponse;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Hyperf\Utils\Context;

class Query
{
    /**
     * @var ElasticsearchModel
     */
    protected $model;

    public function __construct(ElasticsearchModel $model)
    {
        $this->model = $model;
    }

    /**
     * 新增文档.
     *
     * @param array $attributes
     *
     * @return CreatedResponse
     */
    public function insert(array $attributes, $index = null)
    {
        $id = $attributes['_id'] ?? null;
        unset($attributes['_id']);

        $opt = $this->prepareParams(
            [
                'index' => $index ?: $this->model->getIndex(),
                'body'  => $attributes,
            ]
        );

        if ($id) {
            $opt['id'] = $id;
        }

        return new CreatedResponse($this->getClient()->index($opt));
    }

    /**
     * 批量新增.
     *
     * @param array $rows
     *
     * @return BulkResponse
     */
    public function batchInsert(array $rows, $index = null): BulkResponse
    {
        if (! $rows) {
            return new BulkResponse([]);
        }

        $params = ['body' => []];
        $index = $index ?: $this->model->getIndex();

        foreach ($rows as &$row) {
            $id = $row['_id'] ?? null;
            unset($row['_id']);

            $body = $this->prepareParams(
                [
                    '_index' => $index,
                ],
                '_type'
            );

            if ($id) {
                $body['_id'] = $id;
            }

            $params['body'][] = [
                'index' => $body,
            ];

            $params['body'][] = $row instanceof self ? $row->toArray() : $row;
        }

        return new BulkResponse($this->getClient()->bulk($params));
    }


    /**
     * Delete data by id.
     *
     * @param mixed $id ID
     * @return DeletedResponse
     */
    public function deleteById($id, $index = null): DeletedResponse
    {
        if (! $id) {
            return new DeletedResponse([]);
        }
        try {
            return new DeletedResponse(
                $this->getClient()->delete(
                    $this->prepareParams(
                        [
                            'index' => $index ?: $this->model->getIndex(),
                            'id'    => $id,
                        ]
                    )
                )
            );
        } catch (Missing404Exception $e) {
            return new DeletedResponse([]);
        }
    }

    /**
     * Delete by ids.
     *
     * @param array $ids
     *
     * @return DeletedResponse
     */
    public function deleteByIds(array $ids, $index = null): DeletedResponse
    {
        if (! $ids) {
            return new DeletedResponse($ids);
        }

        return $this->deleteByQuery([
            'bool' => [
                'filter' => [
                    'terms' => [
                        '_id' => array_values($ids),
                    ],
                ],
            ],

        ], $index);
    }

    /**
     * 根据条件删除数据.
     *
     * @param array $condition
     * @return DeletedResponse
     */
    public function deleteByQuery(array $condition, $index = null): DeletedResponse
    {
        return new DeletedResponse(
            $this->getClient()->deleteByQuery(
                $this->prepareParams(
                    [
                        'index' => $index ?: $this->model->getIndex(),
                        'body'  => [
                            'query' => $condition,
                        ],
                    ]
                )
            )
        );
    }



    /**
     * 根据ID查找数据.
     *
     * @param $id
     * @param mixed $fields
     * @param array $options
     * @param string|null $index
     *
     * @return array
     */
    public function findById($id, $fields = null, array $options = [], $index = null)
    {
        if (! $id) {
            return [];
        }

        $params = $this->prepareParams(
            [
                'index' => $index ?: $this->model->getIndex(),
                'id'    => $id,
            ]
        );

        if ($fields) {
            $params['_source'] = $fields;
        }

        if ($options) {
            $params = array_merge($params, $options);
        }

        try {
            $data = (new FindIdResponse($this->getClient()->get($params), $this->model))->toArray();

        } catch (Missing404Exception $e) {
            return [];
        }

        if (! $data) {
            return [];
        }

        $data['_id'] = $id;

        return $data;
    }


    /**
     * 查找单行数据.
     *
     * @param array $condition
     * @param null $fields
     * @param null $sort
     * @param array $options
     * @param string|null $index
     *
     * @return array
     */
    public function first(array $condition = [], $fields = null, $sort = null, array $options = [], $index = null)
    {
        $data = $this->find($condition, $fields, $sort, null, 1, $options, $index)->toArray();

        return $data[0] ?? [];
    }

    /**
     * @param array  $params
     * @param string $key
     *
     * @return array
     */
    protected function prepareParams(array $params, string $key = 'type'): array
    {
        if ($type = $this->model->getType()) {
            $params[$key] = $type;
        }

        return $params;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->model->getClient();
    }


    /**
     * 查找数据.
     *
     * @param array $condition
     * @param string|null $fields
     * @param array|null $sort
     * @param int|null $from
     * @param int|null $size
     * @param array $opts
     *
     * @return ListResponse
     */
    public function find(
        array $condition,
        $fields = null,
        $sort = [],
        $from = null,
        $size = null,
        $opts = [],
        $index = null
    ) {
        $params = $this->prepareParams(
            [
                'index' => $index ?: $this->model->getIndex(),
                'body'  => [],
            ]
        );

        if (! $condition) {
            $condition = [
                'bool' => [
                    'must'     => [],
                    'must_not' => [],
                    'should'   => [['match_all' => (object) []]],
                ],
            ];
        }

        if ($sort !== null) {
            $params['body']['sort'] = $sort;
        }

        if ($fields !== null) {
            $params['body']['_source'] = is_array($fields) ? $fields : explode(',', trim($fields));
        }

        if ($from !== null) {
            $params['body']['from'] = $from;
        }
        if ($size !== null) {
            $params['body']['size'] = $size;
        }
        if ($condition) {
            $params['body']['query'] = &$condition;
        }

        $params['body'] = array_merge($params['body'], $opts);

        if (Context::get('preference')){
            $params['preference'] = Context::get('preference');
        }

        return new ListResponse($this->getClient()->search($params));
    }



    /**
     * 查找所有数据.
     *
     * @param null $fields
     * @param array $sort
     * @param int|null $from
     * @param int|null $size
     * @param array|null $opts
     * @param string|null $index
     *
     * @return ListResponse
     */
    public function all(
        $fields = null,
        array $sort = [],
        ?int $from = null,
        ?int $size = null,
        ?array $opts = [],
        string $index = null
    ): ListResponse
    {
        return $this->find(
            [],
            $fields,
            $sort,
            $from,
            $size,
            $opts,
            $index
        );
    }


    /**
     * 聚合.
     *
     * @param array $aggs
     * @param array $condition
     * @param null $fields
     * @param null $sort
     * @param int|null $from
     * @param int|null $size
     * @return ListResponse
     */
    public function aggregate(
        array $aggs,
        array $condition = [],
        $fields = null,
        $sort = null,
        $from = null,
        $size = null,
        $index = null
    ): ListResponse
    {
        $params = $this->prepareParams(
            [
                'index' => $index ?: $this->model->getIndex(),
                'body'  => [],
            ]
        );

        if ($sort) {
            $params['body']['sort'] = $sort;
        }

        if ($fields === null) {
            $params['body']['_source'] = false;
        }

        if ($from !== null) {
            $params['body']['from'] = $from;
        }
        if ($size !== null) {
            $params['body']['size'] = $size;
        }
        $params['body']['aggs'] = &$aggs;
        if ($condition) {
            $params['body']['query'] = &$condition;
        }

        return new ListResponse($this->getClient()->search($params));
    }



    /**
     * 统计数量.
     *
     * @param array $condition 不传则查询所有数据
     *
     * @return int
     */
    public function count(array $condition = null, $index = null): int
    {
        $params = $this->prepareParams(
            [
                'index' => $index ?: $this->model->getIndex(),
                'body'  => [],
            ]
        );

        if ($condition === null) {
            $condition = [
                'bool' => [
                    'must' => [],
                    'must_not' => [],
                    'should' => [['match_all' => (object) []]],
                ],
            ];
        }

        if ($condition) {
            $params['body']['query'] = &$condition;
        }
        $data = $this->getClient()->count($params);

        return $data ? (int) $data['count'] : 0;
    }


    /**
     * 根据条件批量修改接口.
     *
     * @param array $attributes
     * @param array $condition
     * @return BatchUpdatedResponse
     */
    public function updateByQuery(array $attributes, array $condition, $index = null): BatchUpdatedResponse
    {
        $params = $this->prepareParams(
            [
                'index' => $index ?: $this->model->getIndex(),
                'body' => [
                    'query' => &$condition,
                    'script' => [
                        'source' => $this->getUpdateScript($attributes),
                    ],
                ],
            ]
        );

        return new BatchUpdatedResponse($this->getClient()->updateByQuery($params));
    }


    /**
     * @param array $attributes
     *
     * @return string
     */
    protected  function getUpdateScript(array $attributes)
    {
        $source = [];
        foreach ($attributes as $k => &$v) {
            if (is_string($v)) {
                $v = str_replace("'", "\\'", $v);
                $v = str_replace('"', '\\"', $v);
                $v = "'$v'";
            }
            $source[] = "ctx._source.$k=$v";
        }

        return implode(';', $source);
    }


    /**
     * 根据id批量删除.
     * @deprecated
     * @param array $rows
     * @param array $ids
     * @return BatchUpdatedResponse
     */
    protected function batchUpdate(array $rows, $index = null): BatchUpdatedResponse
    {
        if (! $rows) {
            return new BatchUpdatedResponse([]);
        }

        $params = ['body' => []];
        foreach ($rows as &$row) {
            $id = $row['_id'] ?? null;
            if (! $id) {
                throw new \InvalidArgumentException('批量修改必须设置“_id”字段');
            }
            unset($row['_id']);

            $params['body'][] = [
                'update' => $this->prepareParams(
                    [
                        '_index' => $index ?: $this->model->getIndex(),
                        '_id'    => $id,
                    ],
                    '_type'
                ),
            ];
            $params['body'][]['doc'] = $row;
        }

        return new BatchUpdatedResponse($this->getClient()->bulk($params));
    }

    /**
     * 根据id批量删除.
     * @deprecated
     * @param array|static $attributes
     * @param array $ids
     * @return BatchUpdatedResponse
     */
    public function updateByIds($attributes, array $ids, $index = null): BatchUpdatedResponse
    {
        if (! $ids) {
            return new BatchUpdatedResponse([]);
        }

        $rows = [];
        foreach ($ids as &$id) {
            $attributes['_id'] = $id;

            $rows[] = $attributes;
        }

        return $this->batchUpdate($rows, $index);
    }


    /**
     * 修改文档内容.
     *
     * @param array $attributes
     * @param string $id
     * @param null $index
     * @return UpdatedResponse
     */
    public function updateById(array $attributes, $id, $index = null)
    {
        if (! $id) {
            throw new \InvalidArgumentException('id不能为空');
        }

        return new UpdatedResponse(
            $this->getClient()->update(
                $this->prepareParams(
                    [
                        'index' => $index ?: $this->model->getIndex(),
                        'id'    => $id,

                        'body' => [
                            'doc' => $attributes,
                        ],
                    ]
                )
            )
        );
    }

    /**
     * 嵌套对象内容追加.
     *
     * @param array $ids ES的id
     * @param string $column 嵌套对象字段名称
     * @param array $content 嵌套对象内容
     * @return UpdatedResponse
     */
    public function appendToEmbeddedByIds(array $ids, string $column, array $content, $index = null): UpdatedResponse
    {
        if (! $ids) {
            return new UpdatedResponse([]);
        }

        $params = ['body' => []];
        foreach ($ids as &$id) {
            $params['body'][] = [
                'update' => $this->prepareParams(
                    [
                        '_index' => $index ?: $this->model->getIndex(),
                        '_id'    => $id,
                    ],
                    '_type'
                ),
            ];
            $params['body'][] = [
                'script' => [
                    'inline' => "if (ctx._source.$column == null) { ctx._source.$column = params.$column } else { ctx._source.{$column}.add(params.{$column}) }",
                    'params' => [
                        $column => &$content,
                    ],
                ],

            ];
        }

        return new UpdatedResponse($this->getClient()->bulk($params));
    }
}