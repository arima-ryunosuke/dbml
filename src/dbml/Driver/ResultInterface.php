<?php

namespace ryunosuke\dbml\Driver;

interface ResultInterface
{
    /**
     * NativeType から DoctrineType への変換
     *
     * ※ この抽象メソッドはこのコメントを書きたいゆえの宣言であり、インターフェースとしての意味はない
     *
     * 各々の driver から得られる native type がメチャクチャすぎて観測範囲内での最低限の実装となっている。
     * というのもこれらの型が活きるのは now() などの datetime と精々 json くらいで他の型を活かす機会がそもそもない。
     * もっと言うと json をリテラルで書くような状況もないため、実質的に datetime 専用となっている。
     * （正味 now() で DateTime インスタンスを得たいだけ）。
     *
     * なお Type::convertToPHPValue を使いたいだけなので型のサイズはあまり重要ではない。
     * 重要なのは型そのもので、特に TEXT/BLOB 等はストリーム化されたりするので注意。
     *
     * ので原則として互換性破壊の担保に入れない。
     * そもそも native type の網羅が大変すぎるのでかなり気軽に追加する可能性がある。
     *
     * @param int|string $nativeType NativeType
     * @return ?string DoctrineType
     */
    public static function doctrineType(int|string $nativeType): ?string;

    public function setSameCheckMethod(string $method);

    public function getMetadata(): array;
}
