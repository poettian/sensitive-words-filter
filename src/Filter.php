<?php

namespace Poettian\Filter;

class Filter
{
    /**
     * filter driver: simple, index, participle, dfa
     */
    protected $driver;

    /**
     * config data
     */
    protected $config;

    /**
     * 构造函数
     *
     * @param  array  $redis_config  Predis连接参数
     * @param  string  $driver 使用的算法
     */
    public function __construct(array $redis_config, $driver = 'dfa')
    {
        $this->config = array_merge(
            $redis_config, 
            ['driver' => $driver],
        );
    }

    /**
     * 创建具体的算法驱动，执行添加和过滤敏感词等动作
     *
     * @return  FilterInterface  具体的算法驱动
     */
    protected function createDriver()
    {
        $driver_class = \ucfirst(strtolower($this->config['driver'])) . 'Filter';
        $class = "\\Poettian\\Filter\\{$driver_class}";
        if (! class_exists($class)) {
            throw new \Exception(sprintf(
                'Unable to resolve driver for [%s].', $this->config['driver']
            ));
        }
        unset($this->config['driver']);
        $redis = new \Predis\Client($this->config);

        return new $class($redis);
    }

    /**
     * 取得算法驱动
     *
     * @return void
     */
    protected function driver()
    {
        if (is_null($this->driver)) {
            $this->driver = $this->createDriver();
        }

        return $this->driver;
    }

    /**
     * 动作转发
     */
    public function __call($name, $arguments)
    {
        return $this->driver()->$name(...$arguments);
    }
}