<?php

namespace Poettian\Filter;

class ParticipleFilter extends BaseFilter
{

    const PARTICIPLE_DICT_SET = 'poettian:participle:dict';
    const PARTICIPLE_LEN_SORT_SET = 'poettian:participle:len';

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
        $this->redis->del([self::PARTICIPLE_DICT_SET, self::PARTICIPLE_LEN_SORT_SET]);
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
        $this->redis->sadd(self::PARTICIPLE_DICT_SET, $keyword);
        $word_len = mb_strlen($keyword);
        $this->redis->zadd(self::PARTICIPLE_LEN_SORT_SET, $word_len, $word_len);
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
    public function run($content, $repl = '*') 
    {
        if (empty($content)) {
            return $content;
        }
        $replace_content = '';
        $len_arr = $this->redis->zrevrange(self::PARTICIPLE_LEN_SORT_SET, 0, -1);
        $pos = 0;
        $len = mb_strlen($content);
        while ($pos < $len) {
            $flag = false; // 标识是否命中敏感词
            foreach ($len_arr as $word_len) {
                $sub_content = mb_substr($content, $pos, $word_len);
                if ($this->redis->sismember(self::PARTICIPLE_DICT_SET, $sub_content)) {
                    $replace_content .= str_repeat($repl, mb_strlen($sub_content));
                    $pos += $word_len;
                    $flag = true;
                    break;
                }
            }
            if (! $flag) {
                $replace_content .= mb_substr($content, $pos, 1);
                $pos++;
            }
        }

        return $replace_content;
    }
}