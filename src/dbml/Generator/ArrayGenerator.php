<?php

namespace ryunosuke\dbml\Generator;

use ryunosuke\dbml\Database;

/**
 * 行ごとに php 配列化する出力クラス
 */
class ArrayGenerator extends AbstractGenerator
{
    public function __construct(array $config = [])
    {
        // 設定配列の正規化
        $config = array_replace([
            // 最初の列をキーとして使用するか
            'assoc' => false,
        ], $config);

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
        return fwrite($resource, /** @lang text */ "<?php return array(\n");
    }

    protected function generateBody($resource, $key, $value, $first_flg)
    {
        $id = '';
        if ($this->config['assoc']) {
            $id = var_export($key, true) . ' => ';
        }
        return fwrite($resource, $id . var_export($value, true) . ",\n");
    }

    protected function generateTail($resource)
    {
        return fwrite($resource, ");");
    }
}
