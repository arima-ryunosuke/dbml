<?php

namespace ryunosuke\Test {

    if (version_compare(PHP_VERSION, '8.1') >= 0) {
        eval(<<<'PHP'
        namespace ryunosuke\Test;
        enum IntEnum: int
        {
            case Int1 = 1;
            case Int2 = 2;
            
            public static function __callStatic(string $name, mixed $arguments): mixed
            {
                return constant("self::$name");
            }
        }

        enum StringEnum: string
        {
            case StringHoge = 'hoge';
            case StringFuga = 'fuga';
            
            public static function __callStatic(string $name, mixed $arguments): mixed
            {
                return constant("self::$name");
            }
        }
        PHP);
    }
    else {
        /**
         * @method static self Int1()
         * @method static self Int2()
         */
        class IntEnum extends \ryunosuke\polyfill\enum\IntBackedEnum
        {
            const Int1 = 1;
            const Int2 = 2;
        }

        /**
         * @method static self StringHoge()
         * @method static self StringFuga()
         */
        class StringEnum extends \ryunosuke\polyfill\enum\StringBackedEnum
        {
            const StringHoge = 'hoge';
            const StringFuga = 'fuga';
        }
    }

    class Mailaddress
    {
        public function __construct(private string $mailaddress)
        {
        }
    }

    class DateTime
    {
        public function __construct(...$args)
        {
        }
    }

    /**
     * テスト用 Database
     *
     * 行を変更したら戻したり SQLServer 用の小細工オーバーライド。
     *
     * @mixin \ryunosuke\Test\dbml\Annotation\Database
     */
    class Database extends \ryunosuke\dbml\Database
    {
        private $is_dirty = true;

        public function clean($callback)
        {
            if ($this->getOriginal()->is_dirty) {
                $callback($this);
                $this->getOriginal()->is_dirty = false;
            }
        }

        public function executeAffect($query, iterable $params = [], ?int $retry = null)
        {
            $this->getOriginal()->is_dirty = true;
            return parent::executeAffect($query, $params, $retry);
        }
    }
}

namespace ryunosuke\Test\Gateway {

    use ryunosuke\dbml\Attribute\VirtualColumn;
    use ryunosuke\dbml\Database;

    /**
     * @mixin \ryunosuke\Test\dbml\Annotation\TableGatewayProvider
     */
    class TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
    {
    }

    class Article extends TableGateway
    {
        protected string $defaultIteration  = 'assoc';
        protected string $defaultJoinMethod = 'left';

        public function __construct(Database $database, $table_name, $entity_name)
        {
            parent::__construct($database, $table_name, $entity_name);

            $this->addScope('scope1', 'NOW()');
            $this->addScope('scope2', function ($id) {
                return [
                    'where' => [
                        'article_id' => $id,
                    ],
                ];
            });
        }

        public function scopeId($id)
        {
            return $this->where(['article_id' => $id]);
        }

        public function virtualTitleChecksColumn($value = null)
        {
            if (func_num_args()) {
                return array_combine(['title', 'checks'], explode(':', $value, 2));
            }
            else {
                return fn($row) => $row['title'] . ':' . $row['checks'];
            }
        }

        /**
         * @lazy true
         */
        public function setUpperTitleColumn($value)
        {
            return [
                'title' => strtoupper($value),
            ];
        }

        #[VirtualColumn(type: "string", implicit: true)]
        public function getStatementColumn()
        {
            return 'UPPER(%s.title)';
        }

        public function getClosureColumn()
        {
            return function ($row) {
                return $row['article_id'] . '-' . $row['title'];
            };
        }

        public function getSelectBuilderColumn(Database $db)
        {
            return $db->subcount('t_comment');
        }

        public function normalize(array $row): array
        {
            if (isset($row['checks'])) {
                $row['checks'] = strtoupper($row['checks']);
            }
            return $row;
        }

        public function invalidColumn(): ?array
        {
            return [
                'delete_at' => fn() => date('Y-m-d H:i:s'),
            ];
        }
    }

    class Comment extends TableGateway
    {
        public function __construct(Database $database, $table_name, $entity_name)
        {
            parent::__construct($database, $table_name, $entity_name);

            $this->addScope('scope1', 'NOW()');
            $this->addScope('scope2', function ($id) {
                return [
                    'where' => [
                        'comment_id' => $id,
                    ],
                ];
            });
        }
    }
}

namespace ryunosuke\Test\Entity {

    /**
     * @mixin \ryunosuke\Test\dbml\Annotation\ArticleEntity
     * @property Comment $comment
     * @property Comment $Comment
     * @property Comment[] $comments
     */
    class Article extends \ryunosuke\dbml\Entity\Entity
    {
    }

    /**
     * @mixin \ryunosuke\Test\dbml\Annotation\CommentEntity
     * @property Article $Article
     */
    class Comment extends \ryunosuke\dbml\Entity\Entity
    {
    }

    /**
     * @mixin \ryunosuke\Test\dbml\Annotation\CommentEntity
     * @property Article $Article
     */
    class ManagedComment extends \ryunosuke\dbml\Entity\Entity
    {
    }
}
