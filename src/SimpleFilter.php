<?php

namespace Poettian\Filter;

class SimpleFilter extends BaseFilter
{

    const SIMPLE_DICT_HASH = 'poettian:simple:dict';

    /**
     * 根据敏感词库，构造每种算法对应的数据结构
     *
     * @param  string  $dict 敏感词库文件
     * @return void
     */
    public function build($dict = null) 
    {
        $dict = $this->dict($dict);
        // 删除旧数据
        $this->redis->del(self::SIMPLE_DICT_HASH);
        $fp = fopen($dict, 'rb');
        if ($fp) {
            while (($keyword = fgets($fp)) !== false) {
                $this->add($keyword);
            }
            fclose($fp);
        }
    }

    /**
     * 增加敏感词
     *
     * @param  string  $keyword 敏感词
     * @return void
     */
    public function add($keyword) 
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return;
        }
        $word_len = mb_strlen($keyword);
        $keywords = json_decode(
            $this->redis->hget(self::SIMPLE_DICT_HASH, $word_len) ?? '[]', true
        );
        $keywords[] = $keyword;
        $this->redis->hset(self::SIMPLE_DICT_HASH, $word_len, json_encode($keywords));
    }

    /**
     * 删除敏感词
     *
     * @param  string  $keyword 敏感词
     * @return void
     */
    public function delete($keyword) {}

    /**
     * 执行过滤
     *
     * @param  string  $content 待过滤内容
     * @param  string  $repl 替换敏感词的字符
     * @return string  过滤替换后的内容
     */
    public function run(string $content, $repl = '*') 
    {
        if (empty($content)) {
            return $content;
        }
        $len_arr = $this->redis->hkeys(self::SIMPLE_DICT_HASH);
        rsort($len_arr);
        foreach ($len_arr as $len) {
            $keywords = json_decode($this->redis->hget(self::SIMPLE_DICT_HASH, $len), true);
            foreach ($keywords as $keyword) {
                $repls = str_repeat($repl, mb_strlen($keyword));
                $content = str_replace($keyword, $repls, $content);
            }
        }

        return $content;
    }
}