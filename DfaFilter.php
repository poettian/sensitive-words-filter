<?php

namespace Poettian\Filter;

class DfaFilter extends BaseFilter
{

    const DFA_DICT_HASH = 'poettian:dfa:dict';

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
        $this->redis->del(self::DFA_DICT_HASH);
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
        $first_char = mb_substr($keyword, 0, 1);
        $tree = json_decode(
            $this->redis->hget(self::DFA_DICT_HASH, $first_char) ?? '[]', true
        );
        $raw_tree = &$tree;
        for ($pos = 0, $word_len = mb_strlen($keyword);$pos < $word_len;$pos++) {
            $char = mb_substr($keyword, $pos, 1);
            if (! isset($tree[$char])) {
                $tree[$char] = [];
            }
            $tree = &$tree[$char];
        }
        $tree["\x00"] = 1;

        $this->redis->hset(self::DFA_DICT_HASH, $first_char, json_encode($raw_tree));
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
        $pos = 0;
        $len = mb_strlen($content);
        while ($pos < $len) {
            $next_char = $char = mb_substr($content, $pos, 1);
            $step = $last_step = 0;
            if ($tree = $this->redis->hget(self::DFA_DICT_HASH, $char)) {
                $tree = json_decode($tree, true);
                while (isset($tree[$char])) {
                    $tree = &$tree[$char];
                    $step++;
                    if (isset($tree["\x00"])) { // 记录上一次匹配到敏感词对应的位移
                        $last_step = $step;
                    }
                    $pos_next = $pos + $step;
                    if ($pos_next == $len) { // 已到 $content 结尾
                        break;
                    }
                    $char = mb_substr($content, $pos_next, 1);
                }
            }
            if ($last_step > 0) {
                $pos += $last_step;
                $replace_content .= str_repeat($repl, $last_step);
            } else {
                $pos++;
                $replace_content .= $next_char;
            }
        }

        return $replace_content;
    }
}