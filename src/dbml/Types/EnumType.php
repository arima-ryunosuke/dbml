<?php /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

namespace ryunosuke\dbml\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * php の enum と db の型を紐づけるクラス
 *
 * 他の型のように個別クラスで扱うのではなく、インスタンス単位で扱う。
 * ちなみに DB 側のいわゆる ENUM 型は一切関係ない（別に使っても良いが）。
 *
 * register しておけば select/affect 時に自動で enum との変換が行われるようになる。
 */
class EnumType extends AbstractType
{
    private string $name;

    /** @var class-string<\BackedEnum> */
    private string $enumClass;

    /**
     * 型名と EnumClass をまとめて登録する
     *
     * ```php
     * // こうしておけば AutoCastType とかパイプ修飾とかで enum の値で返されるようになる
     * EnumType::register($db->getPlatform(), [
     *     'article_status' => ArticleStatus::class,
     *     'public_status'  => PublicStatus::class,
     * ]);
     * ```
     *
     * @param bool $throw 重複登録で例外を投げるか？（基本的にテスト・デバッグ目的であり明示指定は非推奨）
     * @return static[] 登録した Type オブジェクトを返すが特に用途はない（getTypeRegistry 経由で取得可能）
     */
    public static function register(AbstractPlatform $platform, array $typeMap, bool $throw = true): array
    {
        $result = [];
        foreach ($typeMap as $typeName => $enumClass) {
            assert(is_subclass_of($enumClass, \BackedEnum::class));

            $type = new static();
            $type->name = $typeName;
            $type->enumClass = ltrim($enumClass, '\\');

            $registry = Type::getTypeRegistry();
            if ($throw || !$registry->has($typeName)) {
                $registry->register($typeName, $type);
            }
            if ($throw || !$platform->hasDoctrineTypeMappingFor($typeName)) {
                $platform->registerDoctrineTypeMapping($typeName, $typeName);
            }

            $result[$typeName] = $registry->get($typeName);
        }
        return $result;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEnum(): string
    {
        return $this->enumClass;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }
        if (!$value instanceof \BackedEnum) {
            // 有効値チェック
            $value = $this->enumClass::from($value);
        }
        return $value->value;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?\BackedEnum
    {
        if ($value === null || $value instanceof \BackedEnum) {
            return $value;
        }
        return $this->enumClass::from($value);
    }
}
