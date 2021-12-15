<?php


namespace Jtar\Elasticsearch;
use Hyperf\Database\Model\Events\Deleted;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\Database\Model\Events\Updated;

trait EsCache
{
    protected function getCacheModel($model)
    {
        $caches = config('es_cache');

        foreach ($caches as $k => $v){
            if ($model instanceof $k){
                return $v;
            }
        }

        return null;
    }

    public function updated(Updated $event)
    {
        $model = $event->getModel();

        $cache = $this->getCacheModel($model);

        if ($cache){
            $cache = new $cache();

            if (method_exists($cache,'createOrUpdate')){
                $cache->createOrUpdate($model);
            }
        }
    }

    public function saved(Saved $event)
    {
        $model = $event->getModel();

        $cache = $this->getCacheModel($model);

        if ($cache){
            $cache = new $cache();

            if (method_exists($cache,'createOrUpdate')){
                $cache->createOrUpdate($model);
            }
        }
    }


    public function deleted(Deleted $event)
    {

        $model = $event->getModel();

        $cache = $this->getCacheModel($model);

        if ($cache){
            $cache = new $cache();

            if (method_exists($cache,'delete')){
                $cache->delete($model);
            }
        }
    }
}