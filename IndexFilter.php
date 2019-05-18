<?php

namespace Poettian\Filter;

class IndexFilter extends BaseFilter
{

    const INDEX_DICT_HASH = 'poettian:index:dict';

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
        $this->redis->del(self::INDEX_DICT_HASH);
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
        $char = mb_substr($keyword, 0, 1);
        $keywords = json_decode(
            $this->redis->hget(self::INDEX_DICT_HASH, $char) ?? '[]', true
        );
        $word_len = mb_strlen($keyword);
        $keywords[$word_len][] = $keyword;
        krsort($keywords);
        $this->redis->hset(self::INDEX_DICT_HASH, $char, json_encode($keywords));
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
        $replace_content = '';
        $pos = 0;
        $len = mb_strlen($content);
        while ($pos < $len) {
            $char = mb_substr($content, $pos, 1);
            $flag = false; // 标识是否命中敏感词
            if ($keywords = $this->redis->hget(self::INDEX_DICT_HASH, $char)) {
                $keywords = json_decode($keywords, true);
                foreach ($keywords as $word_len => $words) {
                    foreach ($words as $keyword) {
                        if ($keyword == mb_substr($content, $pos, $word_len)) {
                            $replace_content .= str_repeat($repl, $word_len);
                            $pos += $word_len;
                            $flag = true;
                            break 2;
                        }
                    }
                }
            }
            if (! $flag) {
                $pos++;
                $replace_content .= $char;
            }
        }

        return $replace_content;
    }
}