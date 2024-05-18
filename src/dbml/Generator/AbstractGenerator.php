<?php

namespace ryunosuke\dbml\Generator;

/**
 * 行ごとに何らかの変換を通して返す抽象クラス
 */
abstract class AbstractGenerator
{
    protected const BOM = "\xEF\xBB\xBF";

    protected array $config;

    public function __construct(array $config = [])
    {
        $config = array_replace([
            // mysql のバッファーモード値。 null で変更しない
            'buffered' => null,
            // 行配列に対してのコールバック（function(array $row) {}）。null で何もしない
            'callback' => null,
        ], $config);

        $this->config = $config;
    }

    /**
     * 出力場所とデータプロバイダを与えて出力する
     *
     * @param string|resource $location 出力場所。null を与えると標準出力、文字列を与えるとファイルになる
     * @param iterable $provider 行を1行ずつ返す行プロバイダ
     * @return int 書き込みバイト数
     */
    final public function generate($location, iterable $provider)
    {
        $resource = $location;
        $resource_flg = true;

        // null は標準出力
        if ($location === null) {
            $resource = fopen('php://output', 'w');
        }

        // パス文字ならリソース化
        if (is_string($location)) {
            $resource_flg = false;
            $resource = @fopen($location, 'w');
            if ($resource === false) {
                throw new \RuntimeException("failed to open '$location'.");
            }
        }

        // プロバイダを初期化
        if ($this->config['buffered'] !== null && $provider instanceof Yielder) {
            $provider->setBufferMode($this->config['buffered']);
        }
        $this->initProvider($provider);

        $bytelength = 0;

        // ヘッダ部書き出し
        $bytelength += $this->generateHead($resource);

        // ボディ部書き出し
        $first_flg = true;
        foreach ($provider as $key => $value) {
            if ($this->config['callback']) {
                $value = $this->config['callback']($value);
            }
            $bytelength += $this->generateBody($resource, $key, $value, $first_flg);
            $first_flg = false;
        }

        // フッタ部書き出し
        $bytelength += $this->generateTail($resource);

        // 自前で開いたリソースなら閉じる
        if (!$resource_flg) {
            fclose($resource);
        }

        return $bytelength;
    }

    /**
     * プロバイダの初期設定を行う
     *
     * @ignoreinherit
     *
     * @param iterable $provider 行を1行ずつ返す行プロバイダ
     */
    abstract protected function initProvider(iterable $provider);

    /**
     * head を出力する
     *
     * 例えば csv ならヘッダ行、 json なら [{ などの開きブレースを出力する。
     *
     * @ignoreinherit
     *
     * @param resource $resource 書き込み先リソース
     */
    abstract protected function generateHead($resource);

    /**
     * body を出力する
     *
     * 例えば csv なら "a,b,c"、 json なら "{a: 1, b: 2, c: 3}" のようなデータ本体を出力する。
     *
     * @ignoreinherit
     *
     * @param resource $resource 書き込み先リソース
     * @param int|string $key 行のキー
     * @param array $value 行
     * @param bool $first_flg 初回ループ（最初の出力）なら true
     */
    abstract protected function generateBody($resource, $key, $value, $first_flg);

    /**
     * tail を出力する
     *
     * json なら ]} などの閉じブレースを出力する。
     *
     * @ignoreinherit
     *
     * @param resource $resource 書き込み先リソース
     */
    abstract protected function generateTail($resource);
}
