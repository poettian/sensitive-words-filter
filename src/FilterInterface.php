<?php

namespace Poettian\Filter;

interface FilterInterface
{
    /**
     * 根据敏感词库，构造每种算法对应的数据结构
     *
     * @param  string  $dict 敏感词库文件
     * @return void
     */
    public function build($dict = null);

    /**
     * 增加敏感词 
     * @tudo 批量增加
     *
     * @param  string  $keyword 敏感词
     * @return void
     */
    public function add($keyword);

    /**
     * 删除敏感词
     *
     * @param  string  $keyword 敏感词
     * @return void
     */
    public function delete($keyword);

    /**
     * 执行过滤
     *
     * @param  string  $content 待过滤内容
     * @param  string  $repl 替换敏感词的字符
     * @return string  过滤替换后的内容
     */
    public function run(string $content, $repl = '*');
}