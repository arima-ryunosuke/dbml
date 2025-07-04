<?php

namespace ryunosuke\Test;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware\EnableForeignKeys;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\TestCase;
use ryunosuke\dbml\Logging\LoggerChain;
use ryunosuke\dbml\Logging\Middleware;
use ryunosuke\dbml\Types\EnumType;
use ryunosuke\dbml\Utility\Adhoc;
use ryunosuke\PHPUnit\TestCaseTrait;
use ryunosuke\SimpleLogger\Plugins\LevelUnsetPlugin;
use ryunosuke\SimpleLogger\Plugins\SuppressPlugin;
use ryunosuke\SimpleLogger\StreamLogger;
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
        $params = Adhoc::parseParams(['url' => $getconst("{$prefix}_URL")]);
        $middlewares = [];

        if ($init) {
            $mparam = DriverManager::getConnection($params)->getParams();
            $dbname = isset($mparam['dbname']) ? $mparam['dbname'] : (isset($mparam['path']) ? $mparam['path'] : '');
            unset($mparam['url'], $mparam['dbname'], $mparam['path']);
            $schemaManager = DriverManager::getConnection($mparam)->createSchemaManager();
            try_null(fn() => $schemaManager->dropDatabase($dbname));
            try_null(fn() => $schemaManager->createDatabase($dbname));
        }

        if (strpos($dbms, 'sqlite') !== false) {
            $middlewares[] = new EnableForeignKeys();
        }

        $configuration = new Configuration();
        $configuration->setMiddlewares([new Middleware(new LoggerChain()), ...$middlewares]);
        $connection = DriverManager::getConnection($params, $configuration);

        $connection->setNestTransactionsWithSavepoints(true);

        $native = $connection->getNativeConnection();
        if ($native instanceof \PDO && strpos($dbms, 'sqlite') !== false) {
            $native->sqliteCreateFunction('REGEXP', fn($pattern, $value) => (int) mb_eregi($pattern, $value), 2);
        }
        if ($native instanceof \SQLite3) {
            $native->createFunction('REGEXP', fn($pattern, $value) => (int) mb_eregi($pattern, $value), 2);
        }

        EnumType::register($connection->getDatabasePlatform(), [
            'enum_int'    => IntEnum::class,
            'enum_string' => StringEnum::class,
        ], false);

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
                                new Column('data', Type::getType('string'), ['length' => 255, 'default' => '', 'notnull' => false]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('test1',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name1', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true), new Index('SECONDARY1', ['id'])],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('test2',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name2', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true), new Index('SECONDARY2', ['id'])],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('auto',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('noauto',
                            [
                                new Column('id', Type::getType('string'), ['length' => 32]),
                                new Column('name', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('paging',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('aggregate',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32]),
                                new Column('group_id1', Type::getType('integer')),
                                new Column('group_id2', Type::getType('integer')),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('oprlog',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('category', Type::getType('string'), ['length' => 32]),
                                new Column('primary_id', Type::getType('integer')),
                                new Column('log_date', Type::getType('date')),
                                new Column('message', Type::getType('text'), ['length' => 65535]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('noprimary',
                            [
                                new Column('id', Type::getType('integer')),
                            ],
                            [],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('nullable',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32, 'notnull' => false]),
                                new Column('cint', Type::getType('integer'), ['notnull' => false]),
                                new Column('cfloat', Type::getType('float'), ['notnull' => false]),
                                new Column('cdecimal', Type::getType('decimal'), ['notnull' => false, 'scale' => 2, 'precision' => 3]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('notnulls',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 32, 'notnull' => true, 'default' => '']),
                                new Column('cint', Type::getType('integer'), ['notnull' => true, 'default' => 1]),
                                new Column('cfloat', Type::getType('float'), ['notnull' => true, 'default' => 2.3]),
                                new Column('cdecimal', Type::getType('decimal'), ['notnull' => true, 'scale' => 2, 'precision' => 3, 'default' => 4.56]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('multiprimary',
                            [
                                new Column('mainid', Type::getType('integer')),
                                new Column('subid', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['mainid', 'subid'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('multiunique',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('uc_s', Type::getType('string'), ['length' => 255]),
                                new Column('uc_i', Type::getType('integer')),
                                new Column('uc1', Type::getType('string'), ['length' => 255]),
                                new Column('uc2', Type::getType('integer')),
                                new Column('groupkey', Type::getType('integer')),
                            ],
                            [
                                new Index('PRIMARY', ['id'], true, true),
                                new Index('uk1', ['uc_s'], true),
                                new Index('uk2', ['uc_i'], true),
                                new Index('uk3', ['uc1', 'uc2'], true),
                            ],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('multifkey',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('mainid', Type::getType('integer')),
                                new Column('subid', Type::getType('integer')),
                            ],
                            [
                                new Index('PRIMARY', ['id'], true, true),
                            ],
                            [],
                            [
                                new ForeignKeyConstraint(['mainid', 'subid'], 'multiprimary', ['mainid', 'subid'], 'fk_multifkey1', []),
                            ],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('multifkey2',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('fcol1', Type::getType('integer')),
                                new Column('fcol2', Type::getType('integer')),
                                new Column('fcol9', Type::getType('integer')),
                            ],
                            [
                                new Index('PRIMARY', ['id'], true, true),
                            ],
                            [],
                            [
                                new ForeignKeyConstraint(['fcol1'], 'multiunique', ['id'], 'fk_multifkeys1', []),
                                new ForeignKeyConstraint(['fcol2'], 'multiunique', ['id'], 'fk_multifkeys2', []),
                                new ForeignKeyConstraint(['fcol9'], 'multiunique', ['id'], 'fk_multifkeys91', []),
                                new ForeignKeyConstraint(['fcol9'], 'multifkey', ['id'], 'fk_multifkeys92', []),
                            ],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('multicolumn',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 255]),
                                new Column('flag1', Type::getType('integer')),
                                new Column('title1', Type::getType('string')),
                                new Column('value1', Type::getType('string')),
                                new Column('flag2', Type::getType('integer')),
                                new Column('title2', Type::getType('string')),
                                new Column('value2', Type::getType('string')),
                                new Column('flag3', Type::getType('integer')),
                                new Column('title3', Type::getType('string')),
                                new Column('value3', Type::getType('string')),
                                new Column('flag4', Type::getType('integer')),
                                new Column('title4', Type::getType('string')),
                                new Column('value4', Type::getType('string')),
                                new Column('flag5', Type::getType('integer')),
                                new Column('title5', Type::getType('string')),
                                new Column('value5', Type::getType('string')),
                            ],
                            [
                                new Index('PRIMARY', ['id'], true, true),
                            ],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('misctype',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('pid', Type::getType('integer'), ['notnull' => false]),
                                new Column('cint', Type::getType('integer')),
                                new Column('cfloat', Type::getType('float')),
                                new Column('cdecimal', Type::getType('decimal'), ['scale' => 2, 'precision' => 3]),
                                new Column('cdate', Type::getType('date')),
                                new Column('cdatetime', Type::getType('datetime')),
                                new Column('cstring', Type::getType('string'), ['length' => 8]),
                                new Column('ctext', Type::getType('text'), ['length' => 255]),
                                new Column('cbinary', Type::getType('binary'), ['length' => 24, 'notnull' => false]),
                                new Column('cblob', Type::getType('blob'), ['length' => 255, 'notnull' => false]),
                                new Column('carray', Type::getType('simple_array'), ['notnull' => false]),
                                new Column('cjson', Type::getType('json'), ['notnull' => false]),
                                new Column('eint', Type::getType('integer')),
                                new Column('estring', Type::getType('string'), ['length' => 8]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true), new Index('IDX_MISCTYPE1', ['pid'], true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('misctype_child',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('cid', Type::getType('integer')),
                                new Column('cint', Type::getType('integer')),
                                new Column('cfloat', Type::getType('float')),
                                new Column('cdecimal', Type::getType('decimal'), ['scale' => 2, 'precision' => 3]),
                                new Column('cdate', Type::getType('date')),
                                new Column('cdatetime', Type::getType('datetime')),
                                new Column('cstring', Type::getType('string'), ['length' => 255]),
                                new Column('ctext', Type::getType('text')),
                                new Column('cbinary', Type::getType('binary'), ['length' => 255, 'notnull' => false]),
                                new Column('cblob', Type::getType('blob'), ['notnull' => false]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('foreign_p',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('foreign_c1',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('seq', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id', 'seq'], true, true)],
                            [],
                            [
                                new ForeignKeyConstraint(['id'], 'foreign_p', ['id'], 'fk_parentchild1', [
                                    'deferrable' => true,
                                    'deferred'   => true,
                                ]),
                            ],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('foreign_c2',
                            [
                                new Column('cid', Type::getType('integer')),
                                new Column('seq', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['cid', 'seq'], true, true)],
                            [],
                            [
                                new ForeignKeyConstraint(['cid'], 'foreign_p', ['id'], 'fk_parentchild2', [
                                    'deferrable' => true,
                                    'deferred'   => true,
                                ]),
                            ],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('foreign_d1',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('d2_id', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('foreign_d2',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
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
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
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
                            ],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('g_ancestor',
                            [
                                new Column('ancestor_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('ancestor_name', Type::getType('string'), ['length' => 32]),
                                new Column('delete_at', Type::getType('datetime'), ['notnull' => false, 'default' => null]),
                            ],
                            [new Index('PRIMARY', ['ancestor_id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('g_parent',
                            [
                                new Column('parent_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('parent_name', Type::getType('string'), ['length' => 32]),
                                new Column('ancestor_id', Type::getType('integer'), []),
                                new Column('delete_at', Type::getType('datetime'), ['notnull' => false, 'default' => null]),
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
                            ],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('g_child',
                            [
                                new Column('child_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('child_name', Type::getType('string'), ['length' => 32]),
                                new Column('parent_id', Type::getType('integer'), []),
                                new Column('delete_at', Type::getType('datetime'), ['notnull' => false, 'default' => null]),
                            ],
                            [new Index('PRIMARY', ['child_id'], true, true)],
                            [],
                            [
                                new ForeignKeyConstraint(['parent_id'], 'g_parent', ['parent_id'], 'fkey_generation2', [
                                    'onDelete' => 'CASCADE',
                                ]),
                            ],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('g_grand1',
                            [
                                new Column('grand_id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('parent_id', Type::getType('integer'), []),
                                new Column('ancestor_id', Type::getType('integer'), []),
                                new Column('grand1_name', Type::getType('string'), ['length' => 32]),
                                new Column('delete_at', Type::getType('datetime'), ['notnull' => false, 'default' => null]),
                            ],
                            [new Index('PRIMARY', ['grand_id'], true, true)],
                            [],
                            [
                                new ForeignKeyConstraint(['ancestor_id'], 'g_ancestor', ['ancestor_id'], 'fkey_generation3_1', []),
                                new ForeignKeyConstraint(['parent_id'], 'g_parent', ['parent_id'], 'fkey_generation3_2', [
                                    'onDelete' => 'CASCADE',
                                ]),
                            ],
                            ['collation' => 'utf8mb3_bin'],
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
                                    new Column('delete_at', Type::getType('datetime'), ['notnull' => false, 'default' => null]),
                                ],
                                [new Index('PRIMARY', ['grand_id'], true, true)],
                                [],
                                [
                                    new ForeignKeyConstraint(['parent_id', 'ancestor_id'], 'g_parent', ['parent_id', 'ancestor_id'], 'fkey_generation3', [
                                        'onDelete' => 'CASCADE',
                                    ]),
                                ],
                                ['collation' => 'utf8mb3_bin'],
                            ));
                        },
                        new Table('horizontal1',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('name', Type::getType('string'), ['length' => 255]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('horizontal2',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('summary', Type::getType('string'), ['length' => 255]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [new ForeignKeyConstraint(['id'], 'horizontal1', ['id'], 'fkey_horizontal')],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('master_table',
                            [
                                new Column('category', Type::getType('string'), ['length' => 255]),
                                new Column('subid', Type::getType('integer')),
                            ],
                            [new Index('PRIMARY', ['category', 'subid'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('tran_table1',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('master_id', Type::getType('integer'), ['notnull' => false]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('tran_table2',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('master_id', Type::getType('integer'), ['notnull' => false]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('tran_table3',
                            [
                                new Column('id', Type::getType('integer')),
                                new Column('master_id', Type::getType('integer'), ['notnull' => false]),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                        ),
                        new Table('heavy',
                            [
                                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                                new Column('data', Type::getType('text')),
                            ],
                            [new Index('PRIMARY', ['id'], true, true)],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new Table('t_article',
                            [
                                new Column('article_id', Type::getType('integer')),
                                new Column('title', Type::getType('string'), ['length' => 255]),
                                new Column('checks', Type::getType('string'), ['length' => 255]),
                                new Column('delete_at', Type::getType('datetime'), ['notnull' => false, 'default' => null]),
                            ],
                            [
                                new Index('PRIMARY', ['article_id'], true, true),
                                new Index('secondary', ['title']),
                            ],
                            [],
                            [],
                            ['collation' => 'utf8mb3_bin'],
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
                            ],
                            ['collation' => 'utf8mb3_bin'],
                        ),
                        new View('v_article', <<<SQL
                            SELECT
                                t_article.*,
                                (SELECT COUNT(*) FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS comment_count
                            FROM
                                t_article
                        SQL,),
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
                'convertBoolToInt'         => true,
                'convertNumericToDatetime' => true,
                'truncateString'           => true,
                'tableMapper'              => static function ($tablename) {
                    if ($tablename === 't_article') {
                        return [
                            'Article'        => [
                                'entityClass'  => \ryunosuke\Test\Entity\Article::class,
                                'gatewayClass' => \ryunosuke\Test\Gateway\Article::class,
                            ],
                            'ManagedArticle' => [
                                'selectView'   => 'v_article',
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
                                'selectView'   => 'v_comment',
                                'entityClass'  => \ryunosuke\Test\Entity\ManagedComment::class,
                                'gatewayClass' => \ryunosuke\Test\Gateway\Comment::class,
                            ],
                        ];
                    }
                    return $tablename;
                },
                'debugLogger'              => $v[0]->getDatabasePlatform() instanceof SqlitePlatform ? (new StreamLogger(__DIR__ . '/../debug-log.jsonl', [
                    'mode' => 'wb',
                ]))->appendPlugin(new SuppressPlugin(3600), new LevelUnsetPlugin()) : null,
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

    public static function getDummyDatabase(array $options = [])
    {
        if (self::$database === null) {
            $configuration = new Configuration();
            $configuration->setMiddlewares([
                new Middleware(new LoggerChain()),
            ]);

            $parser = new DsnParser();
            $params = $parser->parse('sqlite3:///:memory:');

            $connection = DriverManager::getConnection($params, $configuration);
            self::createTables($connection, [
                new Table('t',
                    [
                        new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                    ],
                    [new Index('PRIMARY', ['id'], true, true)],
                    [],
                    [],
                    ['collation' => 'utf8mb3_bin'],
                ),
                new Table('test',
                    [
                        new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                    ],
                    [new Index('PRIMARY', ['id'], true, true)],
                    [],
                    [],
                    ['collation' => 'utf8mb3_bin'],
                ),
                new Table('foreign_p',
                    [
                        new Column('id', Type::getType('integer')),
                        new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                    ],
                    [new Index('PRIMARY', ['id'], true, true)],
                    [],
                    [],
                    ['collation' => 'utf8mb3_bin'],
                ),
                new Table('foreign_c1',
                    [
                        new Column('id', Type::getType('integer')),
                        new Column('seq', Type::getType('integer')),
                        new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                    ],
                    [new Index('PRIMARY', ['id', 'seq'], true, true)],
                    [],
                    [new ForeignKeyConstraint(['id'], 'foreign_p', ['id'], 'fk_parentchild1')],
                    ['collation' => 'utf8mb3_bin'],
                ),
                new Table('foreign_c2',
                    [
                        new Column('cid', Type::getType('integer')),
                        new Column('seq', Type::getType('integer')),
                        new Column('name', Type::getType('string'), ['length' => 32, 'default' => '']),
                    ],
                    [new Index('PRIMARY', ['cid', 'seq'], true, true)],
                    [],
                    [new ForeignKeyConstraint(['cid'], 'foreign_p', ['id'], 'fk_parentchild2')],
                    ['collation' => 'utf8mb3_bin'],
                ),
                new Table('misctype',
                    [
                        new Column('id', Type::getType('integer'), ['autoincrement' => true]),
                        new Column('pid', Type::getType('integer'), ['notnull' => false]),
                        new Column('cint', Type::getType('integer')),
                        new Column('cfloat', Type::getType('float')),
                        new Column('cdecimal', Type::getType('decimal'), ['scale' => 2, 'precision' => 3]),
                        new Column('cdate', Type::getType('date')),
                        new Column('cdatetime', Type::getType('datetime')),
                        new Column('cstring', Type::getType('string'), ['length' => 8]),
                        new Column('ctext', Type::getType('text'), ['length' => 255]),
                        new Column('cbinary', Type::getType('binary'), ['length' => 24, 'notnull' => false]),
                        new Column('cblob', Type::getType('blob'), ['length' => 255, 'notnull' => false]),
                        new Column('carray', Type::getType('simple_array'), ['notnull' => false]),
                        new Column('cjson', Type::getType('json'), ['notnull' => false]),
                    ],
                    [new Index('PRIMARY', ['id'], true, true), new Index('IDX_MISCTYPE1', ['pid'], true)],
                    [],
                    [],
                    ['collation' => 'utf8mb3_bin'],
                ),
            ]);
            self::$database = new Database($connection, array_replace([
                'cacheProvider' => cacheobject(sys_get_temp_dir() . '/dbml-dummy'),
            ], $options));
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
                $db->truncate('multicolumn');
                $db->truncate('misctype');
                $db->truncate('misctype_child');
                $db->truncate('master_table');
                $db->truncate('tran_table1');
                $db->truncate('tran_table2');
                $db->truncate('tran_table3');
                $db->truncate('multifkey2');
                $db->delete('multifkey');
                $db->delete('multiprimary');
                $db->delete('multiunique');
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
                                    'message'    => "message:$i-$j-$k",
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
                            'id'       => $i,
                            'uc_s'     => $char,
                            'uc_i'     => $i * 10,
                            'uc1'      => "$char,$char",
                            'uc2'      => $i * 100,
                            'groupkey' => ceil($i / 5),
                        ]);
                        $char++;
                    }
                    for ($i = 1, $char = 'a'; $i <= 10; $i++) {
                        $db->insert('multicolumn', [
                            'id'     => $i,
                            'name'   => $char,
                            'flag1'  => $i === 1 ? 1 : 0,
                            'title1' => "Title$i-1",
                            'value1' => "$i-1",
                            'flag2'  => $i === 2 ? 1 : 0,
                            'title2' => "Title$i-2",
                            'value2' => "$i-2",
                            'flag3'  => $i === 3 ? 1 : 0,
                            'title3' => "Title$i-3",
                            'value3' => "$i-3",
                            'flag4'  => $i === 4 ? 1 : 0,
                            'title4' => "Title$i-4",
                            'value4' => "$i-4",
                            'flag5'  => $i === 5 ? 1 : 0,
                            'title5' => "Title$i-5",
                            'value5' => "$i-5",
                        ]);
                        $char++;
                    }
                    foreach ([1, 2, 3] as $category) {
                        for ($i = 1; $i <= 10; $i++) {
                            $db->insert('master_table', [
                                'category' => "tran$category",
                                'subid'    => $i * 10,
                            ]);
                            if (in_array($category, [1, 2])) {
                                foreach (range(1, 2) as $j) {
                                    $db->insert("tran_table$category", [
                                        'id'        => $i + $j * 100,
                                        'master_id' => $i * 10,
                                    ]);
                                }
                            }
                        }
                    }
                    $db->insert("tran_table3", [
                        'id'        => 1,
                        'master_id' => 100,
                    ]);
                    $db->insert("tran_table3", [
                        'id'        => 2,
                        'master_id' => 100,
                    ]);
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
            if ($schemeManager->tablesExist([$tablename])) {
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
        catch (\Exception) {
            return false;
        }
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
}
