<?php

apf_require_class('APF_Cache_Factory');

/**
 * 奇怪的队列
 *
 * 1 包含有很多单独的队列，但是它的来源统一，里面的值不重复，但自己不检查重复性，需要外部的程序保持
 * 2 为了上面说的存在的重复问题，有一个key 记录这些队列的最大值。
 *
 *
 */

class Seocrawler_Queue
{

    protected static $instance;

    protected $redis;

    const QUEUE_PREFIX = 'crawler_que';

    const QUEUE_TOP = 'crawler_top';


    private function __construct()
    {
        $this->redis = APF_Cache_Factory::get_instance()->get_redis('crawler_redis');
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Seocrawler_Queue();
        }

        return self::$instance;
    }


    public function getTopId()
    {
        return intval($this->redis->get(self::QUEUE_TOP));
    }


    public function setTopId($id)
    {
        $this->redis->set(self::QUEUE_TOP, $id);
    }


    public function push($site_id, $batch_id, $id)
    {
        $id = intval($id);
        $this->setTopId($id);
        $this->redis->rpush($this->key($site_id, $batch_id), $id);
    }


    public function pop($site_id, $batch_id)
    {
        $result = $this->redis->lpop($this->key($site_id, $batch_id));

        return $result;
    }

    protected function key($site_id, $batch_id)
    {
        $key = self::QUEUE_PREFIX . '_' . $site_id . '_' . $batch_id;
        return $key;
    }

}
