<?php

namespace {

    /**
     * @template T
     * @param T $object
     * @return T
     */
    function L($object)
    {
        return new class($object) {
            private $object;

            public function __construct($object)
            {
                $this->object = $object;
            }

            public function __get($name)
            {
                return function () use ($name) {
                    return $this->object->$name;
                };
            }

            public function __call($name, $args)
            {
                return function () use ($name, $args) {
                    $params = [];
                    for ($i = 0, $l = count($args); $i < $l; $i++) {
                        $params[$i] = &$args[$i];
                    }
                    return call_user_func_array([$this->object, $name], $params);
                };
            }
        };
    }
}

namespace ryunosuke\Test {

    use Doctrine\DBAL\Platforms\SQLServerPlatform;
    use ryunosuke\polyfill\enum\IntBackedEnum;
    use ryunosuke\polyfill\enum\StringBackedEnum;

    /**
     * @method static self Int1()
     * @method static self Int2()
     */
    class IntEnum extends IntBackedEnum
    {
        const Int1 = 1;
        const Int2 = 2;
    }

    /**
     * @method static self StringHoge()
     * @method static self StringFuga()
     */
    class StringEnum extends StringBackedEnum
    {
        const StringHoge = 'hoge';
        const StringFuga = 'fuga';
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
            if ($this->is_dirty) {
                $callback($this);
                $this->is_dirty = false;
            }
        }

        public function executeAffect($query, iterable $params = [], ?int $retry = null)
        {
            $this->is_dirty = true;
            return parent::executeAffect($query, $params, $retry);
        }

        /**
         * SQLServer は AUTO_INCREMENT なカラムを明示指定できないので小細工する
         *
         * @inheritdoc
         */
        public function insert($tableName, $data)
        {
            if ($this->getPlatform() instanceof SQLServerPlatform && is_string($tableName) && strpos($tableName, ' ') === false && strpos($tableName, '.') === false && strpos($tableName, ',') === false) {
                $tableName2 = $this->convertTableName($tableName);
                $pcols = $this->getSchema()->getTablePrimaryColumns($tableName2);
                $specified_id = count($pcols) === 1 && reset($pcols)->getAutoincrement() && ($data[key($pcols)] ?? '') !== '';

                if ($specified_id) {
                    $this->getConnection()->executeStatement($this->getCompatiblePlatform()->getIdentityInsertSQL($tableName2, true));
                }

                try {
                    $result = parent::insert(...func_get_args());
                }
                finally {
                    if ($specified_id) {
                        $this->getConnection()->executeStatement($this->getCompatiblePlatform()->getIdentityInsertSQL($tableName2, false));
                    }
                }

                return $result;
            }

            return parent::insert(...func_get_args());
        }

        /**
         * SQLServer は AUTO_INCREMENT なカラムを明示指定できないので小細工する
         *
         * @inheritdoc
         */
        public function duplicate($targetTable, array $overrideData = [], $where = [], $sourceTable = null)
        {
            if ($this->getPlatform() instanceof SQLServerPlatform) {
                $targetTable2 = $this->convertTableName($targetTable);
                $sourceTable2 = $this->convertTableName($sourceTable);
                $pcols1 = $this->getSchema()->getTablePrimaryColumns($targetTable2);
                $pcols2 = $this->getSchema()->getTablePrimaryColumns($sourceTable2 ?: $targetTable2);
                $specified_id = (count($pcols1) === 1 && reset($pcols1)->getAutoincrement() && array_key_exists(key($pcols1), $overrideData));
                if ($sourceTable2) {
                    $specified_id = $specified_id || isset($pcols2[key($pcols1)]);
                }

                if ($specified_id) {
                    $this->getConnection()->executeStatement($this->getCompatiblePlatform()->getIdentityInsertSQL($targetTable2, true));
                }

                $result = parent::duplicate(...func_get_args());

                if ($specified_id) {
                    $this->getConnection()->executeStatement($this->getCompatiblePlatform()->getIdentityInsertSQL($targetTable2, false));
                }

                return $result;
            }

            return parent::duplicate(...func_get_args());
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
        protected $defaultIteration  = 'assoc';
        protected $defaultJoinMethod = 'left';

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
            $this->where(['article_id' => $id]);
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

        public function getQueryBuilderColumn(Database $db)
        {
            return $db->subcount('t_comment');
        }

        public function normalize($row)
        {
            if (isset($row['checks'])) {
                $row['checks'] = strtoupper($row['checks']);
            }
            return $row;
        }

        public function invalidColumn()
        {
            return [
                'delete_at' => fn() => date('Y-m-d H:i:s'),
            ];
        }
    }

    class Comment extends TableGateway
    {
        public function __construct(Database $database, $table_name)
        {
            parent::__construct($database, $table_name);

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

namespace ryunosuke\Test\Platforms {

    /**
     * テスト用 Platform
     */
    class SqlitePlatform extends \Doctrine\DBAL\Platforms\SqlitePlatform
    {
        public function getReadLockSQL()
        {
            return '/* lock for read */';
        }

        public function getWriteLockSQL()
        {
            return '/* lock for write */';
        }
    }
}
