<?php

namespace Poettian\Filter;

use Predis\Client;

class BaseFilter
{
    /**
     * redis connection
     */
    protected $redis;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    /**
     * 取敏感词库文件地址
     *
     * @param  mixed  敏感词库文件
     * @return string
     */
    protected function dict($dict = null)
    {
        if (is_null($dict)) {
            return __DIR__ . '/data/sensitive_dict';
        }
        if (! \file_exists($dict)) {
            throw new \Exception(sprintf(
                'Not found dict file [%s].', $dict
            ));
        }
        
        return $dict;
    }
}