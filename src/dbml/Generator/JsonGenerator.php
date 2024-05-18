<?php

namespace ryunosuke\dbml\Generator;

use ryunosuke\dbml\Database;

/**
 * 行ごとに JSON 化する出力クラス
 */
class JsonGenerator extends AbstractGenerator
{
    private int $pretty;

    private string $indent;

    public function __construct(array $config = [])
    {
        // 設定配列の正規化
        $config = array_replace([
            // 一番外側がオブジェクトか。 true だと {} でラップされ、 false だと [] でラップされる
            'assoc'  => false,
            // いわゆる json_encode のオプション。ビット的なマージはされないので指定する場合はすべて指定する
            'option' => JSON_UNESCAPED_UNICODE,
        ], $config);

        $this->pretty = $config['option'] & JSON_PRETTY_PRINT;
        $this->indent = str_repeat(' ', 4);

        parent::__construct($config);
    }

    protected function initProvider(iterable $provider)
    {
        if ($provider instanceof Yielder) {
            if ($this->config['assoc']) {
                $provider->setFetchMethod(Database::METHOD_ASSOC);
            }
            else {
                $provider->setFetchMethod(Database::METHOD_ARRAY);
            }
        }
    }

    protected function generateHead($resource)
    {
        $char = $this->config['assoc'] ? '{' : '[';
        if ($this->pretty) {
            $char .= "\n";
        }
        return fwrite($resource, $char);
    }

    protected function generateBody($resource, $key, $value, $first_flg)
    {
        $bytelength = 0;

        // 初回ループ以外はケツカンマを振る
        if (!$first_flg) {
            $comma = ",";
            if ($this->pretty) {
                $comma .= "\n";
            }
            $bytelength += fwrite($resource, $comma);
        }

        // データブロック（assoc モードはキーも付与）
        $id = '';
        if ($this->config['assoc']) {
            $id = json_encode((string) $key, $this->config['option']) . ':';
            if ($this->pretty) {
                $id .= ' ';
            }
        }
        $data = $id . json_encode($value, $this->config['option']);

        // JSON_PRETTY_PRINT ならインデントを加える（json は RFC 的に改行が許されないので単純な置換でOK）
        if ($this->pretty) {
            $data = $this->indent . str_replace("\n", "\n" . $this->indent, $data);
        }
        $bytelength += fwrite($resource, $data);

        return $bytelength;
    }

    protected function generateTail($resource)
    {
        $char = $this->config['assoc'] ? '}' : ']';
        if ($this->pretty) {
            $char = "\n" . $char;
        }
        return fwrite($resource, $char);
    }
}
