<?php

namespace ryunosuke\dbml\Generator;

use ryunosuke\dbml\Database;

/**
 * 行ごとに CSV 化する出力クラス
 */
class CsvGenerator extends AbstractGenerator
{
    /** @var string 出力エンコーディング */
    private $mb_internal_encoding;

    public function __construct(array $config = [])
    {
        // 設定配列の正規化
        $config = array_replace([
            // BOM を出力するか（出力すると Excel でわりかし便利）
            'bom'       => false,
            // 'SJIS-WIN' のような文字列。 null で何もしない。true で SELECT 列をそのまま出す
            'encoding'  => null,
            // 1行目のヘッダ(['columnName' => 'headerName'])。null でヘッダを出力しない
            'headers'   => null,
            // fputcsv 引数群
            'delimiter' => ',',
            'enclosure' => '"',
            'escape'    => '\\',
        ], $config);

        $this->mb_internal_encoding = mb_internal_encoding();

        parent::__construct($config);
    }

    private function _write($resource, $fields)
    {
        if ($this->config['encoding']) {
            mb_convert_variables($this->config['encoding'], $this->mb_internal_encoding, $fields);
        }

        return fputcsv($resource, $fields, $this->config['delimiter'], $this->config['enclosure'], $this->config['escape']);
    }

    protected function initProvider($provider)
    {
        if ($provider instanceof Yielder) {
            $provider->setFetchMethod(Database::METHOD_ARRAY);
        }
    }

    protected function generateHead($resource)
    {
        $bytelength = 0;

        // BOM 出力
        if ($this->config['bom']) {
            $bytelength += fwrite($resource, self::BOM);
        }

        // ヘッダ出力
        if (is_array($this->config['headers'])) {
            $headers = array_values($this->config['headers']);
            $bytelength += $this->_write($resource, $headers);
        }

        return $bytelength;
    }

    protected function generateBody($resource, $key, $value, $first_flg)
    {
        static $keys = null;
        $bytelength = 0;

        // 最初のループのみ共通ヘッダを定義
        if ($first_flg) {
            if ($this->config['headers'] === null) {
                // null はヘッダ行を出力しないが、カラムが入り乱れていると CSV としての体裁を保てないので
                // 後述の array_intersect_key のために代入だけはしておく（fetchArray なのでほぼあり得ないが…）
                $keys = $value;
            }
            elseif ($this->config['headers'] === true) {
                $keys = $value;
                $bytelength += $this->_write($resource, array_keys($value));
            }
            else {
                $keys = $this->config['headers'];
            }
        }

        // headers との共通項を出力
        $row = array_intersect_key($value, $keys);
        $bytelength += $this->_write($resource, $row);

        return $bytelength;
    }

    protected function generateTail($resource)
    {
        // CSV に後処理はない
        return 0;
    }
}
