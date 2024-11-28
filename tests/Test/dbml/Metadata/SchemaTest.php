<?php

namespace ryunosuke\Test\dbml\Metadata;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Exception as SchemaException;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Type;
use ryunosuke\dbml\Metadata\Schema;
use ryunosuke\dbml\Utility\Adhoc;
use ryunosuke\SimpleCache\NullCache;
use ryunosuke\Test\Database;
use function ryunosuke\dbml\try_return;

class SchemaTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function provideSchema()
    {
        $schmer = self::getDummyDatabase()->getConnection()->createSchemaManager();
        try_return([$schmer, 'dropTable'], 'metasample');
        $schmer->createTable(new Table(
            'metasample',
            [
                new Column('id', Type::getType('integer'), ['autoincrement' => true]),
            ],
            [new Index('PRIMARY', ['id'], true, true)]
        ));
        try_return([$schmer, 'dropView'], 'viewsample');
        $schmer->createView(new View(
            'viewsample',
            'SELECT *, 1 AS dummy FROM metasample WHERE id >= 0',
        ));

        return [
            [self::getDummyDatabase()->getSchema(), self::getDummyDatabase()],
        ];
    }

    function setUp(): void
    {
        parent::setUp();

        $schema = self::getDummyDatabase()->getSchema();
        $schema->refresh();
        $schema->setViewSource([
            'viewsample' => 'metasample',
            'v_dummy'    => 'metasample',
        ]);
    }

    function getDummyTable($name)
    {
        return new Table($name,
            [
                new Column('id', Type::getType('integer')),
                new Column($name, Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['id'], true, true)]
        );
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_refresh($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));
        $this->assertTrue($schema->hasTable('metatest'));

        $schema->refresh();

        $this->assertFalse($schema->hasTable('metatest'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_addTable($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));

        $this->assertStringContainsString('Table', get_class($schema->getTable('metatest')));

        that($schema)->addTable($this->getDummyTable('metatest'))->wasThrown(SchemaException\TableDoesNotExist::new('metatest'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_hasTable($schema)
    {
        $this->assertTrue($schema->hasTable('metasample'));
        $this->assertFalse($schema->hasTable('metahoge'));
        $this->assertFalse($schema->hasTable('v_dummy'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTableNames($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));

        $this->assertContains('metasample', $schema->getTableNames());
        $this->assertContains('metatest', $schema->getTableNames());

        $schema->refresh();

        $this->assertNotContains('metatest', $schema->getTableNames());
        $this->assertContains('metasample', $schema->getTableNames());
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTable($schema)
    {
        $this->assertStringContainsString('Table', get_class($schema->getTable('metasample')));
        $this->assertStringContainsString('Table', get_class($schema->getTable('viewsample')));

        $schema->refresh();

        $this->assertStringContainsString('Table', get_class($schema->getTable('metasample')));

        that($schema)->getTable('hogera')->wasThrown(SchemaException\tableDoesNotExist::new('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getViewAsTable($schema)
    {
        $this->assertStringContainsString('Table', get_class($schema->getTable('viewsample')));
        $this->assertEquals(['id', 'dummy'], array_keys($schema->getTableColumns('viewsample')));
        $this->assertEquals(['id'], array_keys($schema->getTablePrimaryColumns('viewsample')));

        // proxy to metasample
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('v_dummy')));
        $this->assertEquals(['id'], array_keys($schema->getTablePrimaryColumns('v_dummy')));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTables($schema)
    {
        $this->assertEquals($schema->getTableNames(), array_keys($schema->getTables()));

        $this->assertEquals([
            'foreign_c1',
            'foreign_c2',
            'foreign_p',
        ], array_keys($schema->getTables('foreign_*')));
        $this->assertEquals([
            'foreign_c1',
            'foreign_c2',
            't',
            'test',
        ], array_keys($schema->getTables(['foreign_c?', 't*'])));

        $this->assertEquals([
            'foreign_p',
            'metasample',
            'misctype',
            't',
            'test',
            'viewsample',
        ], array_keys($schema->getTables('!foreign_c?')));
        $this->assertEquals([
            'foreign_p',
            'metasample',
            'misctype',
            'viewsample',
        ], array_keys($schema->getTables(['!foreign_c?', '!t*'])));

        $this->assertEquals([
            'foreign_c1',
            'foreign_c2',
        ], array_keys($schema->getTables(['foreign_*', '!foreign_p'])));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTableColumns($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));

        $this->assertIsArray($schema->getTableColumns('metatest'));

        that($schema)->getTableColumns('hogera')->wasThrown(SchemaException\tableDoesNotExist::new('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTablePrimaryKey($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));

        $this->assertStringContainsString('Index', get_class($schema->getTablePrimaryKey('metatest')));

        that($schema)->getTablePrimaryKey('hogera')->wasThrown(SchemaException\tableDoesNotExist::new('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTablePrimaryKeyColumns($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));

        $this->assertEquals(array_intersect_key($schema->getTableColumns('metatest'), ['id' => '']), $schema->getTablePrimaryColumns('metatest'));

        that($schema)->getTablePrimaryKey('hogera')->wasThrown(SchemaException\tableDoesNotExist::new('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTableUniqueColumns($schema)
    {
        $schema->addTable(new Table('uniquetable',
            [
                new Column('id', Type::getType('integer')),
                new Column('uid1', Type::getType('integer')),
                new Column('uid2', Type::getType('integer')),
                new Column('uidx', Type::getType('integer')),
            ],
            [
                new Index('PRIMARY', ['id'], true, true),
                new Index('UNIQUEKEY1', ['uid1', 'uid2'], true, false),
                new Index('UNIQUEKEY2', ['uidx'], true, false),
            ]
        ));

        $this->assertEquals(array_intersect_key($schema->getTableColumns('uniquetable'), ['id' => '']), $schema->getTableUniqueColumns('uniquetable', 'primary'));
        $this->assertEquals(array_intersect_key($schema->getTableColumns('uniquetable'), ['uid1' => '', 'uid2' => '']), $schema->getTableUniqueColumns('uniquetable', ''));
        $this->assertEquals(array_intersect_key($schema->getTableColumns('uniquetable'), ['uid1' => '', 'uid2' => '']), $schema->getTableUniqueColumns('uniquetable'));
        $this->assertEquals(array_intersect_key($schema->getTableColumns('uniquetable'), ['uidx' => '']), $schema->getTableUniqueColumns('uniquetable', 'UNIQUEKEY2'));

        that($schema)->getTableUniqueColumns('metasample')->wasThrown('is not found');
        that($schema)->getTableUniqueColumns('uniquetable', 'undefined')->wasThrown('does not exist');
        that($schema)->getTablePrimaryKey('hogera')->wasThrown(SchemaException\tableDoesNotExist::new('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTableAutoIncrement($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));
        $schema->addTable(new Table('noprimary', [new Column('id', Type::getType('integer'))]));

        $column = $schema->getTableAutoIncrement('metasample');
        $this->assertInstanceOf(Column::class, $column);
        $this->assertEquals('id', $column->getName());
        $this->assertTrue(true, $column->getAutoincrement());

        // 無いなら null
        $this->assertNull($schema->getTableAutoIncrement('metatest'));
        $this->assertNull($schema->getTableAutoIncrement('noprimary'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTableForeignKeys($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));
        $foreign = $this->getDummyTable('foreign');
        $foreign->addForeignKeyConstraint('metatest', ['id'], ['id']);
        $schema->addTable($foreign);

        $this->assertIsArray($schema->getTableForeignKeys('metatest'));

        that($schema)->getTableForeignKeys('hogera')->wasThrown(SchemaException\tableDoesNotExist::new('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     * @param Database $database
     */
    function test_setTableColumn($schema, $database)
    {
        $schema->setViewSource([
            'viewsample' => 'metasample',
        ]);

        // 実カラムの上書き
        $schema->setTableColumn('metasample', 'id', ['type' => 'string']);
        $column = $schema->getTableColumns('metasample')['id'];
        $this->assertEquals([
            "lazy"     => false,
            "select"   => null,
            "affect"   => null,
            "virtual"  => false,
            "implicit" => true,
        ], $column->getPlatformOptions());
        $this->assertEquals('string', Adhoc::typeName($column->getType()));
        $this->assertEquals('string', Adhoc::typeName($schema->getTableColumns('metasample')['id']->getType()));
        $this->assertEquals('string', Adhoc::typeName($schema->getTableColumns('viewsample')['id']->getType()));
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('metasample')));

        // 仮想カラムの追加
        $schema->setTableColumn('metasample', 'dummy', [
            'type'     => 'integer',
            'select'   => 'NOW()',
            'implicit' => false,
            'others'   => [
                'hoge' => 'HOGE',
            ],
        ]);
        $column = $schema->getTableColumns('metasample')['dummy'];
        $this->assertEquals([
            "select"   => "NOW()",
            "implicit" => false,
            "others"   => [
                "hoge" => "HOGE",
            ],
            "lazy"     => false,
            "affect"   => null,
            "virtual"  => true,
        ], $column->getPlatformOptions());
        $this->assertEquals('integer', Adhoc::typeName($column->getType()));
        $this->assertEquals('integer', Adhoc::typeName($schema->getTableColumns('metasample')['dummy']->getType()));
        $this->assertEquals('integer', Adhoc::typeName($schema->getTableColumns('viewsample')['dummy']->getType()));
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('metasample', Schema::COLUMN_UPDATABLE)));
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('metasample', Schema::COLUMN_REAL)));

        // 仮想カラムの上書き
        $schema->setTableColumn('metasample', 'dummy', [
            'type'       => 'string',
            'select'     => 'NOW()',
            'affect'     => $affect = static fn($value, $row) => $value,
            'implicit'   => true,
            'generation' => ['expression' => 'concat("a", "b")'],
            'others'     => [
                'fuga' => 'FUGA',
            ],
        ]);
        $column = $schema->getTableColumns('metasample')['dummy'];
        $this->assertEquals([
            "select"     => "NOW()",
            "implicit"   => true,
            "others"     => [
                "fuga" => "FUGA",
            ],
            "lazy"       => false,
            "affect"     => $affect,
            "virtual"    => true,
            "generation" => [
                "expression" => "concat(\"a\", \"b\")",
            ],
        ], $column->getPlatformOptions());
        $this->assertEquals('string', Adhoc::typeName($column->getType()));
        $this->assertEquals('string', Adhoc::typeName($schema->getTableColumns('metasample')['dummy']->getType()));
        $this->assertEquals('string', Adhoc::typeName($schema->getTableColumns('viewsample')['dummy']->getType()));
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('metasample', Schema::COLUMN_UPDATABLE)));
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('metasample', Schema::COLUMN_REAL)));

        // 仮想カラムの上書き2
        $schema->setTableColumn('metasample', 'dummy', [
            'generation' => null,
        ]);
        $column = $schema->getTableColumns('metasample')['dummy'];
        $this->assertEquals([
            "select"     => "NOW()",
            "implicit"   => true,
            "others"     => [
                "fuga" => "FUGA",
            ],
            "virtual"    => true,
            "lazy"       => false,
            "affect"     => $affect,
            "generation" => null,
        ], $column->getPlatformOptions());
        $this->assertEquals('string', Adhoc::typeName($column->getType()));
        $this->assertEquals('string', Adhoc::typeName($schema->getTableColumns('metasample')['dummy']->getType()));
        $this->assertEquals('string', Adhoc::typeName($schema->getTableColumns('viewsample')['dummy']->getType()));
        $this->assertEquals(['id', 'dummy'], array_keys($schema->getTableColumns('metasample', Schema::COLUMN_UPDATABLE)));
        $this->assertEquals(['id', 'dummy'], array_keys($schema->getTableColumns('viewsample', Schema::COLUMN_UPDATABLE)));
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('metasample', Schema::COLUMN_REAL)));

        // キャッシュが効いていないか担保しておく
        $this->assertEquals('SELECT metasample.id, NOW() AS dummy FROM metasample', $database->select('metasample.!')->queryInto());

        // 仮想カラムの削除
        $schema->setTableColumn('metasample', 'dummy', null);
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('metasample', fn() => true)));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     * @param Database $database
     */
    function test_getTableColumnExpression($schema, $database)
    {
        $schema->setTableColumn('metasample', 'dummy', [
            'lazy'   => true,
            'select' => function ($arg) { return strtoupper($arg); },
        ]);
        $this->assertEquals(true, $schema->getTableColumns('metasample')['dummy']->getPlatformOption('lazy')); // この時点では true
        $this->assertEquals('HOGE', $schema->getTableColumnExpression('metasample', 'dummy', 'select', 'hoge'));
        $this->assertEquals('HOGE', $schema->getTableColumnExpression('metasample', 'dummy', 'select', 'fuga'));    // キャッシュされるのでコールバックされない
        $this->assertEquals(false, $schema->getTableColumns('metasample')['dummy']->getPlatformOption('lazy'));     // 呼ばれたので false
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getForeignKeys($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));
        $foreign1 = $this->getDummyTable('foreign1');
        $foreign1->addForeignKeyConstraint('metatest', ['id'], ['id'], [], 'FK_1');
        $foreign2 = $this->getDummyTable('foreign2');
        $foreign2->addForeignKeyConstraint('metatest', ['id'], ['id'], [], 'FK_2');
        $schema->addTable($foreign1);
        $schema->addTable($foreign2);

        $this->assertEquals(['FK_1', 'FK_2'], array_keys($schema->getForeignKeys('metatest')));

        that($schema)->getTablePrimaryKey('hogera')->wasThrown(SchemaException\tableDoesNotExist::new('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getForeignTable($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));
        $foreign1 = $this->getDummyTable('foreign1');
        $foreign1->addForeignKeyConstraint('metatest', ['id'], ['id'], [], 'FK_1');
        $foreign2 = $this->getDummyTable('foreign2');
        $foreign2->addForeignKeyConstraint('metatest', ['id'], ['id'], [], 'FK_2');
        $schema->addTable($foreign1);
        $schema->addTable($foreign2);

        $this->assertEquals(['foreign1' => 'metatest'], $schema->getForeignTable('FK_1'));
        $this->assertEquals(['foreign2' => 'metatest'], $schema->getForeignTable('FK_2'));
        $this->assertEquals([], $schema->getForeignTable('fk_X'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getForeignColumns($schema)
    {
        $schema->setViewSource([
            'v_foreign1' => 'foreign1',
            'v_foreign2' => 'foreign2',
        ]);

        $schema->addTable($this->getDummyTable('metatest'));
        $foreign1 = $this->getDummyTable('foreign1');
        $foreign1->addForeignKeyConstraint('metatest', ['id'], ['foreign1'], [], 'FK_1');
        $foreign2 = $this->getDummyTable('foreign2');
        $foreign2->addForeignKeyConstraint('metatest', ['id'], ['foreign2'], [], 'FK_2');
        $schema->addTable($foreign1);
        $schema->addTable($foreign2);

        $fkey = null;
        $this->assertEquals(['id' => 'foreign1'], $schema->getForeignColumns('metatest', 'foreign1', $fkey));
        $this->assertEquals('FK_1', $fkey->getName());

        $fkey = null;
        $this->assertEquals(['id' => 'foreign1'], $schema->getForeignColumns('metatest', 'v_foreign1', $fkey));
        $this->assertEquals('FK_1', $fkey->getName());

        $fkey = null;
        $this->assertEquals(['id' => 'foreign2'], $schema->getForeignColumns('metatest', 'foreign2', $fkey));
        $this->assertEquals('FK_2', $fkey->getName());

        $fkey = null;
        $this->assertEquals(['id' => 'foreign2'], $schema->getForeignColumns('metatest', 'v_foreign2', $fkey));
        $this->assertEquals('FK_2', $fkey->getName());

        $this->assertEquals(['foreign1' => 'id'], $schema->getForeignColumns('foreign1', 'metatest'));
        $this->assertEquals(['foreign2' => 'id'], $schema->getForeignColumns('foreign2', 'metatest'));
        $this->assertEquals(['foreign1' => 'id'], $schema->getForeignColumns('v_foreign1', 'metatest'));
        $this->assertEquals(['foreign2' => 'id'], $schema->getForeignColumns('v_foreign2', 'metatest'));
        $this->assertEquals([], $schema->getForeignColumns('foreign1', 'foreign2'));

        $fkey = 'FK_1';
        $this->assertEquals(['id' => 'foreign1'], $schema->getForeignColumns('metatest', 'foreign1', $fkey));
        $this->assertEquals('FK_1', $fkey->getName());

        $fkey = 'FK_2';
        that($schema)->getForeignColumns('metatest', 'foreign1', $fkey)->wasThrown('is not exists');
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_getForeignColumns_misc($database)
    {
        $schema = $database->getSchema();
        $this->assertEquals([], $schema->getForeignColumns('notfound', 'foreign_d1'));
        $this->assertEquals([], $schema->getForeignColumns('foreign_d1', 'notfound'));
        $fkey = 'fk_dd12';
        $this->assertEquals(['id' => 'd2_id'], $schema->getForeignColumns('foreign_d1', 'foreign_d2', $fkey));
        $this->assertEquals('fk_dd12', $fkey->getName());

        $fkey = null;
        $this->assertEquals(['id' => 'id'], $schema->getForeignColumns('foreign_c1', 'foreign_p', $fkey, $direction));
        $this->assertSame(true, $direction);
        $fkey = null;
        $this->assertEquals(['id' => 'id'], $schema->getForeignColumns('foreign_p', 'foreign_c1', $fkey, $direction));
        $this->assertSame(false, $direction);

        // 普通に呼ぶと曖昧だが・・・
        $schema->refresh();
        that($schema)->getForeignColumns('foreign_d2', 'foreign_d1')->wasThrown('ambiguous foreign keys');

        // 一方をデフォルトじゃなくすと関連が取れる
        $schema->refresh();
        $schema->setForeignKeyMetadata('fk_dd12', ['joinable' => false]);
        $schema->setForeignKeyMetadata('fk_dd21', ['joinable' => true]);
        $fkey = null;
        $this->assertEquals(['id' => 'id'], $schema->getForeignColumns('foreign_d2', 'foreign_d1', $fkey, $direction));
        $this->assertEquals('fk_dd21', $fkey->getName());
        $this->assertSame(true, $direction);

        // 他方だと逆
        $schema->refresh();
        $schema->setForeignKeyMetadata('fk_dd12', ['joinable' => true]);
        $schema->setForeignKeyMetadata('fk_dd21', ['joinable' => false]);
        $fkey = null;
        $this->assertEquals(['d2_id' => 'id'], $schema->getForeignColumns('foreign_d2', 'foreign_d1', $fkey, $direction));
        $this->assertEquals('fk_dd12', $fkey->getName());
        $this->assertSame(false, $direction);

        // 両方だと取れない
        $schema->refresh();
        $schema->setForeignKeyMetadata('fk_dd12', ['joinable' => false]);
        $schema->setForeignKeyMetadata('fk_dd21', ['joinable' => false]);
        that($schema)->getForeignColumns('foreign_d2', 'foreign_d1')->wasThrown('joinable foreign key');

        // 設定できないなら例外
        that($schema)->setForeignKeyMetadata('hogefuga', [])->wasThrown('undefined foreign key');

        $schema->refresh();
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_addForeignKeyLazy($schema)
    {
        $schema->addTable($this->getDummyTable('foreign1'));
        $schema->addTable($this->getDummyTable('foreign2'));

        $this->assertEquals('fk_hogera', $schema->addForeignKeyLazy('foreign1', 'foreign2', 'id', 'fk_hogera'));
        $this->assertEquals('foreign1_foreign2_1', $schema->addForeignKeyLazy('foreign1', 'foreign2', ['foreign1' => 'foreign2']));
        $this->assertEquals(['fk_hogera', 'foreign1_foreign2_1'], array_keys($schema->getTableForeignKeys('foreign1')));
        $schema->refresh();
        $schema->addTable($this->getDummyTable('foreign1'));
        $schema->addTable($this->getDummyTable('foreign2'));
        $this->assertEquals([], array_keys($schema->getTableForeignKeys('foreign1')));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_addFsssoreignKey($schema)
    {
        $newFK = function ($name, $lc, $ft, $fc) use ($schema) {
            return new ForeignKeyConstraint((array) $lc, $ft, (array) $fc, $name);
        };

        $schema->addTable($this->getDummyTable('foreign1'));
        $schema->addTable($this->getDummyTable('foreign2'));

        $this->assertEquals('fk_hogera', $schema->addForeignKey($newFK('fk_hogera', 'id', 'foreign2', 'id'), 'foreign1')->getName());

        that($schema)->addForeignKey($newFK('', 'id', 'foreign2', 'id'), null)->wasThrown('localTable is not set');
        that($schema)->addForeignKey($newFK('fk_hogera', 'id', 'foreign2', 'id'), 'foreign1')->wasThrown('already defined same');
        that($schema)->addForeignKey($newFK('', 'foreign2', 'foreign2', 'foreign2'), 'foreign1')->wasThrown('column for foreign1');
        that($schema)->addForeignKey($newFK('', 'foreign2', 'foreign1', 'foreign2'), 'foreign2')->wasThrown('column for foreign1');
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_ignoreForeignKey($schema)
    {
        $newFK = function ($name, $lc, $ft, $fc) use ($schema) {
            return new ForeignKeyConstraint((array) $lc, $ft, (array) $fc, $name);
        };

        $schema->addTable($this->getDummyTable('metatest'));
        $foreign = $this->getDummyTable('foreign');
        $foreign->addForeignKeyConstraint('metatest', ['id'], ['id'], [], 'fk_hogera');
        $schema->addTable($foreign);

        $schema->ignoreForeignKey('fk_hogera');
        $this->assertEmpty($schema->getTableForeignKeys('foreign'));

        that($schema)->ignoreForeignKey('undefined')->wasThrown('undefined foreign key');
        that($schema)->ignoreForeignKey($newFK('', 'id', 'foreign2', 'id'), null)->wasThrown('localTable is not set');
        that($schema)->ignoreForeignKey($newFK('', 'notfound', 'metatest', 'notfound'), 'foreign')->wasThrown('matched foreign key');
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_getIndirectlyColumns($database)
    {
        $schema = $database->getSchema();
        $this->assertEquals(['id' => 'id'], $schema->getIndirectlyColumns('foreign_d1', 'foreign_d2'));
        $this->assertEquals(['d2_id' => 'id'], $schema->getIndirectlyColumns('foreign_d2', 'foreign_d1'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_followColumnName($database)
    {
        $schema = $database->getSchema();
        $schema->setViewSource([
            'v_article' => 't_article',
        ]);
        $this->assertEquals(['foreign_d2/foreign_d1' => 'id'], $schema->followColumnName('foreign_d1', 'foreign_d2', 'id'));
        $this->assertEquals([], $schema->followColumnName('foreign_d1', 'foreign_d2', 'd2_id'));
        $this->assertEquals([], $schema->followColumnName('foreign_d2', 'foreign_d1', 'id'));
        $this->assertEquals(['foreign_d1/foreign_d2' => 'id'], $schema->followColumnName('foreign_d2', 'foreign_d1', 'd2_id'));

        $this->assertEquals([], $schema->followColumnName('t_comment', 'v_article', 'article_id'));
        $this->assertEquals(['t_comment/v_article' => 'article_id'], $schema->followColumnName('v_article', 't_comment', 'article_id'));
    }

    function test_relation()
    {
        $schmer = self::getDummyDatabase()->getConnection()->createSchemaManager();
        try_return([$schmer, 'dropTable'], 't_root');
        $schmer->createTable(new Table(
            't_root',
            [
                new Column('root_id', Type::getType('integer')),
                new Column('seq', Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['root_id', 'seq'], true, true)]
        ));
        try_return([$schmer, 'dropTable'], 't_inner1');
        $schmer->createTable(new Table(
            't_inner1',
            [
                new Column('inner1_id', Type::getType('integer')),
                new Column('root1_id', Type::getType('integer')),
                new Column('root1_seq', Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['inner1_id'], true, true)],
            [],
            [new ForeignKeyConstraint(['root1_id', 'root1_seq'], 't_root', ['root_id', 'seq'], 'fk_inner1')]
        ));
        try_return([$schmer, 'dropTable'], 't_inner2');
        $schmer->createTable(new Table(
            't_inner2',
            [
                new Column('inner2_id', Type::getType('integer')),
                new Column('root2_id', Type::getType('integer')),
                new Column('root2_seq', Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['inner2_id'], true, true)],
            [],
            [new ForeignKeyConstraint(['root2_id', 'root2_seq'], 't_root', ['root_id', 'seq'], 'fk_inner2')]
        ));
        try_return([$schmer, 'dropTable'], 't_leaf');
        $schmer->createTable(new Table(
            't_leaf',
            [
                new Column('leaf_id', Type::getType('integer')),
                new Column('leaf_inner1_id', Type::getType('integer')),
                new Column('leaf_inner2_id', Type::getType('integer')),
                new Column('leaf_root_id', Type::getType('integer')),
                new Column('leaf_root_seq', Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['leaf_id'], true, true)],
            [],
            [
                new ForeignKeyConstraint(['leaf_inner1_id', 'leaf_root_id', 'leaf_root_seq'], 't_inner1', ['inner1_id', 'root1_id', 'root1_seq'], 'fk_leaf1'),
                new ForeignKeyConstraint(['leaf_inner2_id', 'leaf_root_id', 'leaf_root_seq'], 't_inner2', ['inner2_id', 'root2_id', 'root2_seq'], 'fk_leaf2'),
            ]
        ));

        $schema = new Schema(self::getDummyDatabase()->getConnection()->createSchemaManager(), [], new NullCache());

        // 2つの経路がある
        $this->assertEquals([
            't_inner1/t_root' => 'root_id',
            't_inner2/t_root' => 'root_id',
        ], $schema->followColumnName('t_root', 't_leaf', 'leaf_root_id'));

        // 中間テーブルを介さず辿れる
        $this->assertEquals([
            'leaf_root_id'  => 'root_id',
            'leaf_root_seq' => 'seq',
        ], $schema->getIndirectlyColumns('t_root', 't_leaf'));

        // t_root -> t_leaf は辿れない
        $this->assertEquals([], $schema->getIndirectlyColumns('t_leaf', 't_root'));

        // getForeignColumns は代替される
        $fkey = null;
        $this->assertEquals([
            'root_id' => 'leaf_root_id',
            'seq'     => 'leaf_root_seq',
        ], $schema->getForeignColumns('t_leaf', 't_root', $fkey, $direction));
        $this->assertSame(true, $direction);
        $fkey = null;
        $this->assertEquals([
            'leaf_root_id'  => 'root_id',
            'leaf_root_seq' => 'seq',
        ], $schema->getForeignColumns('t_root', 't_leaf', $fkey, $direction));
        $this->assertSame(false, $direction);
    }

    function test_event()
    {
        $schema = new Schema(self::getDummyDatabase()->getConnection()->createSchemaManager(), [
            'onIntrospectTable' => function (Table $table) {
                if ($table->getName() === 'tabletest') {
                    $table->addColumn('hoge', 'integer');
                    $table->setComment('modify-comment');
                }
                if ($table->getName() === 'test') {
                    $table->addColumn('fuga', 'integer');
                    $table->setComment('modify-comment');
                }
            },
        ], new NullCache());

        $table = $this->getDummyTable('tabletest');
        $this->assertFalse($table->hasColumn('hoge'));
        $schema->addTable($table);
        $this->assertTrue($table->hasColumn('hoge'));
        $this->assertEquals('modify-comment', $table->getComment());

        $table = $schema->getTable('test');
        $this->assertTrue($table->hasColumn('fuga'));
        $this->assertEquals('modify-comment', $table->getComment());
    }
}
