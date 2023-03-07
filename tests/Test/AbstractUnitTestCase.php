<?php

namespace ryunosuke\Test;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\Error\Error;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\TestCase;
use ryunosuke\dbml\Logging\LoggerChain;
use ryunosuke\dbml\Logging\Middleware;
use ryunosuke\PHPUnit\TestCaseTrait;
use function ryunosuke\dbml\cacheobject;
use function ryunosuke\dbml\class_shorten;
use function ryunosuke\dbml\try_null;

abstract class AbstractUnitTestCase extends TestCase
{
    use TestCaseTrait;

    /** @var Connection[] */
    private static $connections = [];

    /** @var Database[] */
    private static $databases = [];

    /** @var Database 接続が必要なときに使う汎用インスタンス */
    protected static $database;

    public static function createConnection($dbms, $init = false)
    {
        $getconst = function ($cname) {
            if (!defined($cname)) {
                throw new SkippedTestError("$cname is not defined.");
            }
            return constant($cname);
        };

        $prefix = strtoupper($dbms);
        $config = ['url' => $getconst("{$prefix}_URL")];
        $middlewares = [];

        if ($init) {
            $mparam = DriverManager::getConnection($config)->getParams();
            $dbname = isset($mparam['dbname']) ? $mparam['dbname'] : (isset($mparam['path']) ? $mparam['path'] : '');
            unset($mparam['url'], $mparam['dbname'], $mparam['path']);
            $schemaManager = DriverManager::getConnection($mparam)->createSchemaManager();
            try_null(fn() => $schemaManager->dropDatabase($dbname));
            $schemaManager->createDatabase($dbname);
        }

        if ($dbms === 'sqlite') {
            $config['platform'] = new \ryunosuke\Test\Platforms\SqlitePlatform();
            $middlewares[] = new EnableForeignKeys();
        }
        if ($dbms === 'mysql') {
            $config['driverOptions'] = [
                \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            ];
        }

        $configuration = new Configuration();
        $configuration->setMiddlewares([new Middleware(new LoggerChain()), ...$middlewares]);
        $connection = DriverManager::getConnection($config, $configuration);
        $connection->connect();

        return $connection;
    }

    public static function provideConnection()
    {
        $getconst = function ($cname) {
            if (!defined($cname)) {
                throw new SkippedTestError("$cname is not defined.");
            }
            return constant($cname);
        };

        $rdbms = array_map('trim', explode(',', getenv('RDBMS') ?: $getconst('RDBMS')));
        foreach ($rdbms as $dbms) {
            if (isset(self::$connections[$dbms])) {
                continue;
            }

            try {
                $connection = self::createConnection($dbms, true);

                self::createTables(
                    $connection,
                    [
                        new Table('test',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                                new Column('data', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('test1',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name1', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true), new Index('SECONDARY1', ['id'])]
                        ),
                        new Table('test2',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name2', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true), new Index('SECONDARY2', ['id'])]
                        ),
                        new Table('auto',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                        ),
                        new Table('noauto',
                            [
                                new Column('id', Type::getType('string'), ['length' => 32]),
                                new Column('name', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('paging',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('aggregate',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32]),
                                new Column('group_id1', Type::getType('integer')),
                                new Column('group_id2', Type::getType('integer')),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('oprlog',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('category', Type::getType('string'), ['length' => 32]),
                                new Column('primary_id', Type::getType('integer')),
                                new Column('log_date', Type::getType('date')),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('noprimary',
                            [
                                new Column('id', Type::getType('integer')),
                            ]
                        ),
                        new Table('nullable',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32, 'notnull' => false]),
                                new Column('cint', Type::getType('integer'), ['notnull' => false]),
                                new Column('cfloat', Type::getType('float'), ['notnull' => false]),
                                new Column('cdecimal', Type::getType('decimal'), ['notnull' => false, 'scale' => 2, 'precision' => 3]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('notnulls',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32, 'notnull' => true, 'default' => '']),
                                new Column('cint', Type::getType('integer'), ['notnull' => true, 'default' => 1]),
                                new Column('cfloat', Type::getType('float'), ['notnull' => true, 'default' => 2.3]),
                                new Column('cdecimal', Type::getType('decimal'), ['notnull' => true, 'scale' => 2, 'precision' => 3, 'default' => 4.56]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('multiprimary',
                            [
                                new Column('mainid', Type::getType('integer')),
                                new Column('subid', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['mainid', 'subid'], true, true)]
                        ),
                        new Table('multiunique',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('uc_s', Type::getType('string')),
                                new Column('uc_i', Type::getType('integer')),
                                new Column('uc1', Type::getType('string')),
                                new Column('uc2', Type::getType('integer')),
                            ],
                            [
                                new Index('PRIMARY', ['id'], true, true),
                                new Index('uk1', ['uc_s'], true),
                                new Index('uk2', ['uc_i'], true),
                                new Index('uk3', ['uc1', 'uc2'], true),
                            ]
                        ),
                        new Table('misctype',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('pid', Type::getType('integer'), ['notnull' => false]),
                                new Column('cint', Type::getType('integer')),
                                new Column('cfloat', Type::getType('float')),
                                new Column('cdecimal', Type::getType('decimal')),
                                new Column('cdate', Type::getType('date')),
                                new Column('cdatetime', Type::getType('datetime')),
                                new Column('cstring', Type::getType('string')),
                                new Column('ctext', Type::getType('text')),
                                new Column('cbinary', Type::getType('binary'), ['notnull' => false]),
                                new Column('cblob', Type::getType('blob'), ['notnull' => false]),
                                new Column('carray', Type::getType('simple_array'), ['notnull' => false]),
                                new Column('cjson', Type::getType('json'), ['notnull' => false]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true), new Index('IDX_MISCTYPE1', ['pid'], true)]
                        ),
                        new Table('misctype_child',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('cid', Type::getType('integer')),
                                new Column('cint', Type::getType('integer')),
                                new Column('cfloat', Type::getType('float')),
                                new Column('cdecimal', Type::getType('decimal')),
                                new Column('cdate', Type::getType('date')),
                                new Column('cdatetime', Type::getType('datetime')),
                                new Column('cstring', Type::getType('string')),
                                new Column('ctext', Type::getType('text')),
                                new Column('cbinary', Type::getType('binary'), ['notnull' => false]),
                                new Column('cblob', Type::getType('blob'), ['notnull' => false]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('foreign_p',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('foreign_c1',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('seq', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id', 'seq'], true, true)],
                            [],
                            [new ForeignKeyConstraint(['id'], 'foreign_p', ['id'], 'fk_parentchild1')]
                        ),
                        new Table('foreign_c2',
                            [
                                new Column('cid', Type::getType('integer')),
                                new Column('seq', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['cid', 'seq'], true, true)],
                            [],
                            [new ForeignKeyConstraint(['cid'], 'foreign_p', ['id'], 'fk_parentchild2')]
                        ),
                        new Table('foreign_d1',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('d2_id', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('foreign_d2',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        function (Connection $connection) {
                            $fk1 = new ForeignKeyConstraint(['d2_id'], 'foreign_d2', ['id'], 'fk_dd12');
                            $fk2 = new ForeignKeyConstraint(['id'], 'foreign_d1', ['id'], 'fk_dd21');
                            $connection->createSchemaManager()->createForeignKey($fk1, 'foreign_d1');
                            $connection->createSchemaManager()->createForeignKey($fk2, 'foreign_d2');
                        },
                        new Table('foreign_s',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('foreign_sc',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                                new Column('s_id1', Type::getType('integer')),
                                new Column('s_id2', Type::getType('integer'), ['notnull' => false]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [
                                new ForeignKeyConstraint(['s_id1'], 'foreign_s', ['id'], 'fk_sc1'),
                                new ForeignKeyConstraint(['s_id2'], 'foreign_s', ['id'], 'fk_sc2'),
                            ]
                        ),
                        new Table('g_ancestor',
                            [
                                new Column('ancestor_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('ancestor_name', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['ancestor_id'], true, true)]
                        ),
                        new Table('g_parent',
                            [
                                new Column('parent_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('parent_name', Type::getType('string'), ['length' => 32]),
                                new Column('ancestor_id', Type::getType('integer'), []),
                            ],
                            [
                                new Index('PRIMARY', ['parent_id'], true, true),
                                new Index('SECONDARY10', ['parent_id', 'ancestor_id'], true),
                            ],
                            [],
                            [
                                new ForeignKeyConstraint(['ancestor_id'], 'g_ancestor', ['ancestor_id'], 'fkey_generation1', [
                                    'onDelete' => 'CASCADE',
                                ]),
                            ]
                        ),
                        new Table('g_child',
                            [
                                new Column('child_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('child_name', Type::getType('string'), ['length' => 32]),
                                new Column('parent_id', Type::getType('integer'), []),
                            ],
                            [new Index('PRIMARY', ['child_id'], true, true)],
                            [],
                            [
                                new ForeignKeyConstraint(['parent_id'], 'g_parent', ['parent_id'], 'fkey_generation2', [
                                    'onDelete' => 'CASCADE',
                                ]),
                            ]
                        ),
                        new Table('g_grand1',
                            [
                                new Column('grand_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('parent_id', Type::getType('integer'), []),
                                new Column('ancestor_id', Type::getType('integer'), []),
                                new Column('grand1_name', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['grand_id'], true, true)],
                            [],
                            [
                                new ForeignKeyConstraint(['ancestor_id'], 'g_ancestor', ['ancestor_id'], 'fkey_generation3_1', []),
                                new ForeignKeyConstraint(['parent_id'], 'g_parent', ['parent_id'], 'fkey_generation3_2', [
                                    'onDelete' => 'CASCADE',
                                ]),
                            ]
                        ),
                        function (Connection $connection) {
                            // 謎のエラーが出るのでさしあたり除外
                            if ($connection->getDatabasePlatform() instanceof SQLServerPlatform) {
                                return;
                            }
                            $connection->createSchemaManager()->createTable(new Table('g_grand2',
                                [
                                    new Column('grand_id', Type::getType('integer'), ['autoincrement' => true]),
                                    new Column('parent_id', Type::getType('integer'), []),
                                    new Column('ancestor_id', Type::getType('integer'), []),
                                    new Column('grand2_name', Type::getType('string'), ['length' => 32]),
                                ],
                                [new Index('PRIMARY', ['grand_id'], true, true)],
                                [],
                                [
                                    new ForeignKeyConstraint(['parent_id', 'ancestor_id'], 'g_parent', ['parent_id', 'ancestor_id'], 'fkey_generation3', [
                                        'onDelete' => 'CASCADE',
                                    ]),
                                ]
                            ));
                        },
                        new Table('horizontal1',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string')),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('horizontal2',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('summary', Type::getType('string')),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [new ForeignKeyConstraint(['id'], 'horizontal1', ['id'], 'fkey_horizontal')]
                        ),
                        new Table('heavy',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('data', Type::getType('text')),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)]
                        ),
                        new Table('t_article',
                            [
                                new Column('article_id', Type::getType('integer')),
                                new Column('title', Type::getType('string')),
                                new Column('checks', Type::getType('string')),
                            ],
                            [
                                new Index('PRIMARY', ['article_id'], true, true),
                                new Index('secondary', ['title']),
                            ]
                        ),
                        new Table('t_comment',
                            [
                                new Column('comment_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('article_id', Type::getType('integer')),
                                new Column('comment', Type::getType('text')),
                            ],
                            [new Index('PRIMARY', ['comment_id'], true, true)],
                            [],
                            [
                                new ForeignKeyConstraint(['article_id'], 't_article', ['article_id'], 'fk_articlecomment', [
                                    'onUpdate' => 'CASCADE',
                                    'onDelete' => 'CASCADE',
                                ]),
                            ]
                        ),
                        new View('v_blog', '
                            SELECT
                              A.article_id,
                              A.title,
                              C.comment_id,
                              C.comment
                            FROM t_article A
                            JOIN t_comment C ON A.article_id = C.article_id
                        '),
                    ]
                );
            }
            catch (\Exception $ex) {
                echo $ex->getMessage(), PHP_EOL;
                $connection = false;
            }

            self::$connections[$dbms] = $connection;
        }

        return array_map(function ($v) {
            return [$v];
        }, array_filter(self::$connections));
    }

    public static function provideDatabase()
    {
        return self::$databases ?: self::$databases = array_map(function ($v) {
            $database = new Database($v[0], [
                'convertBoolToInt' => true,
                'modifyAutoSelect' => false,
                'tableMapper'      => static function ($tablename) {
                    if ($tablename === 't_article') {
                        return [
                            'Article' => [
                                'entityClass'  => \ryunosuke\Test\Entity\Article::class,
                                'gatewayClass' => \ryunosuke\Test\Gateway\Article::class,
                            ],
                        ];
                    }
                    if ($tablename === 't_comment') {
                        return [
                            'Comment'        => [
                                'entityClass'  => \ryunosuke\Test\Entity\Comment::class,
                                'gatewayClass' => \ryunosuke\Test\Gateway\Comment::class,
                            ],
                            'ManagedComment' => [
                                'entityClass'  => \ryunosuke\Test\Entity\ManagedComment::class,
                                'gatewayClass' => \ryunosuke\Test\Gateway\Comment::class,
                            ],
                        ];
                    }
                    return $tablename;
                },
            ]);
            $database->declareVirtualTable('v_article_comment', [
                't_article' => [
                    'article_id',
                    '<t_comment C' => [
                        'comment_id',
                    ],
                ],
            ], [
                'C.comment_id IS NOT NULL',
            ], [
                'C.article_id',
                'C.comment_id',
            ]);
            return [$database];
        }, self::provideConnection());
    }

    public static function getConnections()
    {
        return array_filter(self::$connections);
    }

    public static function getDummyDatabase()
    {
        if (self::$database === null) {
            $configuration = new Configuration();
            $configuration->setMiddlewares([new Middleware(new LoggerChain())]);
            $connection = DriverManager::getConnection([
                'url'      => 'sqlite:///:memory:',
                'platform' => new \ryunosuke\Test\Platforms\SqlitePlatform(),
            ], $configuration);
            self::createTables($connection, [
                new Table('t',
                    [
                        new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                    ],
                    [new Index('PRIMARY', ['id'], true, true)]
                ),
                new Table('test',
                    [
                        new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                    ],
                    [new Index('PRIMARY', ['id'], true, true)]
                ),
                new Table('foreign_p',
                    [
                        new Column('id', Type::getType('integer')),
                        new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                    ],
                    [new Index('PRIMARY', ['id'], true, true)]
                ),
                new Table('foreign_c1',
                    [
                        new Column('id', Type::getType('integer')),
                        new Column('seq', Type::getType('integer')),
                        new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                    ],
                    [new Index('PRIMARY', ['id', 'seq'], true, true)],
                    [],
                    [new ForeignKeyConstraint(['id'], 'foreign_p', ['id'], 'fk_parentchild1')]
                ),
                new Table('foreign_c2',
                    [
                        new Column('cid', Type::getType('integer')),
                        new Column('seq', Type::getType('integer')),
                        new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                    ],
                    [new Index('PRIMARY', ['cid', 'seq'], true, true)],
                    [],
                    [new ForeignKeyConstraint(['cid'], 'foreign_p', ['id'], 'fk_parentchild2')]
                ),
            ]);
            self::$database = new Database($connection, [
                'cacheProvider' => cacheobject(sys_get_temp_dir() . '/dbml-dummy'),
            ]);
        }
        return self::$database;
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::readyRecord();
    }

    public static function readyRecord()
    {
        /** @var Database $db */
        foreach (array_column(self::provideDatabase(), 0) as $db) {
            $db->clean(function (Database $db) {
                // http://blogs.wankuma.com/naka/archive/2005/10/10/18641.aspx
                if ($db->getPlatform() instanceof SQLServerPlatform) {
                    $db->delete('t_comment');
                    $db->delete('t_article');
                    $db->insert('t_article', ['article_id' => 1, 'title' => '', 'checks' => '']);
                    $db->insert('t_comment', ['article_id' => 1, 'comment' => '']);
                    $db->delete('t_comment');
                    $db->delete('t_article');

                    $db->delete('g_child');
                    $db->delete('g_parent');
                    $db->delete('g_ancestor');
                    $db->insert('g_ancestor', ['ancestor_name' => '']);
                    $db->insert('g_parent', ['parent_name' => '', 'ancestor_id' => $db->getLastInsertId()]);
                    $db->insert('g_child', ['child_name' => '', 'parent_id' => $db->getLastInsertId()]);
                    $db->delete('g_child');
                    $db->delete('g_parent');
                    $db->delete('g_ancestor');
                }
                $db->truncate('test');
                $db->truncate('test1');
                $db->truncate('test2');
                $db->truncate('noauto');
                $db->truncate('paging');
                $db->truncate('aggregate');
                $db->truncate('oprlog');
                $db->truncate('noprimary');
                $db->truncate('nullable');
                $db->truncate('multiprimary');
                $db->truncate('multiunique');
                $db->truncate('misctype');
                $db->truncate('misctype_child');
                $db->delete('foreign_c1');
                $db->delete('foreign_c2');
                $db->delete('foreign_p');
                $db->delete('g_child');
                $db->delete('g_parent');
                $db->delete('g_ancestor');
                $db->delete('t_comment');
                $db->delete('t_article');
                $db->resetAutoIncrement('t_comment');
                $db->resetAutoIncrement('g_ancestor');
                $db->resetAutoIncrement('g_parent');
                $db->resetAutoIncrement('g_child');

                $db->transact(function (Database $db) {
                    for ($i = 0, $char = 'a'; $i < 10; $i++) {
                        $db->insert('test', [
                            'name' => $char++,
                        ]);
                    }
                    for ($i = 0, $char = 'a'; $i < 10; $i++) {
                        $db->insert('test1', [
                            'name1' => $char++,
                        ]);
                    }
                    for ($i = 0, $char = 'A'; $i < 20; $i++) {
                        $db->insert('test2', [
                            'name2' => $char++,
                        ]);
                    }
                    for ($i = 0, $char = 'a'; $i < 100; $i++) {
                        $db->insert('paging', [
                            'name' => $char++,
                        ]);
                    }
                    for ($i = 0, $char = 'a'; $i < 10; $i++) {
                        $db->insert('aggregate', [
                            'name'      => $char++,
                            'group_id1' => floor($i / 2) + 1,
                            'group_id2' => (floor($i / 5) + 1) * 10,
                        ]);
                    }
                    foreach (range(1, 9) as $i) {
                        foreach (range(1, $i) as $j) {
                            foreach (range(1, $j) as $k) {
                                $db->insert('oprlog', [
                                    'category'   => "category-$i",
                                    'primary_id' => $j,
                                    'log_date'   => date('Y-m-d', strtotime("200$i-$j-$k")),
                                ]);
                            }
                        }
                    }
                    for ($i = 1, $char = 'a'; $i <= 10; $i++) {
                        $db->insert('nullable', [
                            'id'       => $i,
                            'name'     => $char++,
                            'cint'     => ($i % 2 === 0) ? null : $i - 5,
                            'cfloat'   => ($i % 3 === 0) ? null : $i / 2 - 5,
                            'cdecimal' => ($i % 5 === 0) ? null : $i / 3 - 5,
                        ]);
                    }
                    for ($i = 1, $char = 'a'; $i <= 10; $i++) {
                        $db->insert('multiprimary', [
                            'mainid' => ceil($i / 5),
                            'subid'  => $i,
                            'name'   => $char++,
                        ]);
                    }
                    for ($i = 1, $char = 'a'; $i <= 10; $i++) {
                        $db->insert('multiunique', [
                            'id'   => $i,
                            'uc_s' => $char,
                            'uc_i' => $i * 10,
                            'uc1'  => "$char,$char",
                            'uc2'  => $i * 100,
                        ]);
                        $char++;
                    }
                    $db->insert('t_article', [
                        'article_id' => 1,
                        'title'      => 'タイトルです',
                        'checks'     => '',
                    ]);
                    $db->insert('t_article', [
                        'article_id' => 2,
                        'title'      => 'コメントのない記事です',
                        'checks'     => '',
                    ]);
                    for ($i = 1; $i <= 3; $i++) {
                        $db->insert('t_comment', [
                            'article_id' => 1,
                            'comment'    => "コメント{$i}です",
                        ]);
                    }
                });
            });
        }
    }

    public function setUp(): void
    {
        parent::setUp();

        /** @var Database $db */
        foreach (array_column(self::provideDatabase(), 0) as $db) {
            $db->overrideColumns([
                't_article' => [
                    'title'         => [
                        'anywhere' => [
                            'enable'  => true,
                            'collate' => 'utf8_bin',
                        ],
                    ],
                    'title2'        => 'UPPER(%s.title)',
                    'title3'        => [
                        'select' => static function ($v) {
                            return "a {$v['title']} z";
                        },
                    ],
                    'title4'        => static function () {
                        return function ($prefix) {
                            /** @noinspection PhpUndefinedFieldInspection */
                            return $prefix . $this->title;
                        };
                    },
                    'title5'        => [
                        'select'   => 'UPPER(%s.title)',
                        'implicit' => true,
                    ],
                    'checks'        => [
                        'type' => Type::getType('simple_array'),
                    ],
                    'comment_count' => [
                        'select' => $db->subcount('t_comment'),
                    ],
                    'vaffect'       => [
                        'affect' => function ($value, $row) {
                            $parts = explode(':', $value);
                            return [
                                'title'  => array_shift($parts),
                                'checks' => $parts,
                            ];
                        },
                    ],
                ],
            ]);
        }

        self::readyRecord();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        gc_collect_cycles();
    }

    public static function createTables(Connection $connection, $tableorviews)
    {
        /** @var AbstractAsset $tableorview */

        $schemeManager = $connection->createSchemaManager();

        // 外部キーのつらみがあるので逆順で drop
        foreach (array_reverse($tableorviews) as $tableorview) {
            if ($tableorview instanceof \Closure) {
                continue;
            }
            $tablename = $tableorview->getQuotedName($connection->getDatabasePlatform());
            if ($schemeManager->tablesExist($tablename)) {
                $method = 'drop' . class_shorten($tableorview);
                $schemeManager->$method($tablename);
            }
        }
        // そのあと create
        foreach ($tableorviews as $tableorview) {
            if ($tableorview instanceof \Closure) {
                $tableorview($connection);
                continue;
            }
            $method = 'create' . class_shorten($tableorview);
            $schemeManager->$method($tableorview);
        }
    }

    public static function supportSyntax(Database $database, $syntax)
    {
        try {
            $database->executeSelect("$syntax");
            return true;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    public static function assertException($e, $callback)
    {
        $callback = self::forcedCallize($callback);

        try {
            $callback(...array_slice(func_get_args(), 2));
        }
        catch (Error $ex) {
            throw $ex;
        }
        catch (Exception $ex) {
            throw $ex;
        }
        catch (\Throwable $ex) {
            $check_code = true;
            if (is_string($e)) {
                $check_code = false;
                if (class_exists($e)) {
                    $e = (new \ReflectionClass($e))->newInstanceWithoutConstructor();
                }
                else {
                    if ($ex instanceof \Exception) {
                        $e = new \Exception($e);
                    }
                    if ($ex instanceof \Error) {
                        $e = new \Error($e);
                    }
                }
            }

            self::assertInstanceOf(get_class($e), $ex);
            if ($check_code) {
                self::assertEquals($e->getCode(), $ex->getCode());
            }
            if (strlen($e->getMessage()) > 0) {
                self::assertStringContainsString($e->getMessage(), $ex->getMessage());
            }
            return;
        }
        self::fail(get_class($e) . ' is not thrown.');
    }

    public static function assertStringIgnoreBreak($expected, $actual, $message = '')
    {
        $expected = preg_replace('/[\r\n]/', ' ', trim($expected, "\r\n"));
        $actual = preg_replace('/[\r\n]/', ' ', trim($actual, "\r\n"));
        self::assertEquals($expected, $actual);
    }

    public static function assertArrayStartsWith($expected, $actual, $message = '')
    {
        foreach ($expected as $k => $v) {
            self::assertArrayHasKey($k, $actual);
            self::assertStringStartsWith($v, $actual[$k]);
        }
    }

    public static function forcedCallize($callable, $method = null)
    {
        if (func_num_args() == 2) {
            $callable = func_get_args();
        }

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $parts = explode('::', $callable);
            $method = new \ReflectionMethod($parts[0], $parts[1]);
            if (!$method->isPublic() && $method->isStatic()) {
                $method->setAccessible(true);
                return function () use ($method) {
                    return $method->invokeArgs(null, func_get_args());
                };
            }
        }

        if (is_array($callable) && count($callable) === 2) {
            try {
                $method = new \ReflectionMethod($callable[0], $callable[1]);
                if (!$method->isPublic()) {
                    $method->setAccessible(true);
                    return function () use ($callable, $method) {
                        return $method->invokeArgs($method->isStatic() ? null : $callable[0], func_get_args());
                    };
                }
            }
            catch (\ReflectionException $ex) {
                // __call を考慮するとどうしようもない
            }
        }

        return $callable;
    }

    public static function forcedRead($object, $property)
    {
        $class = get_class($object);
        while (true) {
            try {
                $refprop = new \ReflectionProperty($class, $property);
                break;
            }
            catch (\ReflectionException $ex) {
                $class = get_parent_class($class);
                if ($class == false) {
                    throw $ex;
                }
            }
        }
        $refprop->setAccessible(true);
        return $refprop->getValue($object);
    }

    public static function forcedWrite($object, $property, $value)
    {
        $class = get_class($object);
        while (true) {
            try {
                $refprop = new \ReflectionProperty($class, $property);
                break;
            }
            catch (\ReflectionException $ex) {
                $class = get_parent_class($class);
                if ($class == false) {
                    throw $ex;
                }
            }
        }
        $refprop->setAccessible(true);
        $current = $refprop->getValue($object);
        $refprop->setValue($object, $value);
        return $current;
    }

    public static function scopeManager(callable $initializer)
    {
        return new class($initializer) {
            private $finalizer;

            public function __construct($initializer)
            {
                $this->finalizer = $initializer();
            }

            public function __destruct()
            {
                ($this->finalizer)();
                unset($this->finalizer);
                gc_collect_cycles();
            }
        };
    }
}
