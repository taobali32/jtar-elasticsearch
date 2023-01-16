<?php

/*
//#[GetMapping]
public function add10()
{
    for ($i = 0; $i <= 1000; $i++) {
        $num = mt_rand(1, 100);
        $create = AppBalanceLog::query()->create([
            'user_id' => 1,
            'num' => $num,
            'before' => 1,
            'after' => 1,
            'type' => mt_rand(0, 10),
            'memo' => 'change金额变动:' . $num
        ]);

        $this->serivce->instestData($create->id);
    }

    jtarGetRedis()->set('cache_1', Carbon::now()->toDateTimeString(), 180);
}

//    #[GetMapping]
public function setcahce()
{
    jtarGetRedis()->set('cache_1', Carbon::now()->toDateTimeString(), 180);
}

//    #[GetMapping]
public function clearscrollid()
{
    $qBuilder = new ElasticsearchModel();
    $qBuilder->setIndex((new TestListIndex)->getName());

    $scroll_id = $this->request->input('scroll_id', '');

    if ($scroll_id) $qBuilder->query()->clearScrollId($scroll_id);

}

//#[GetMapping]
public function page2()
{
    $size = (int)$this->request->input('size', 10);
    $page = (int)$this->request->input('page', 1);

    $qType = 0;

    $scroll_id = $this->request->input('scroll_id', '');

    //  当改变订单状态等的时候这个前缀改一下!
    $cache_prefix = 'cache_' . 1;

    if (jtarGetRedis()->get($cache_prefix)) {
        //  数据库
        $data = AppBalanceLog::query()->where('type', 0)
            ->orderBy('id', 'desc')
            ->page2($size, $page);

        list($count, $item) = $data;

        $data = $item;
        jtarContextSet('source', 'database');
        $scroll_id = '';

    } else {
        $qBuilder = new ElasticsearchModel();
        $qBuilder->setIndex((new TestListIndex)->getName());

        if ($scroll_id) {
            $params = [
                'scroll_id' => $scroll_id,
                'scroll' => '5m',
            ];
            $result = $qBuilder->query()->nextScrollQuery($params);
        } else {
            $result = $qBuilder->query()->find(
                [
                    'bool' => [
                        'filter' => [
                            'term' => [
                                'type' => $qType
                            ]
                        ]
                    ],
                ],
                null,
                [
                    'id' => [
                        "order" => "desc",
                    ]
                ], null, $size,
                [],
                null,
                ['scroll' => '5m']
            );
        }

        jtarContextSet('source', 'es');

        $scroll_id = $result->getContent()['_scroll_id'] ?? '';
        $data = $result->toArray();

    }

    return $this->success('余额记录', ['item' => $data, 'scroll_id' => $scroll_id]);
}

*/