<?php

namespace ryunosuke\Test\dbml\Metadata;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Type;
use ryunosuke\dbml\Metadata\Schema;
use ryunosuke\Test\Database;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Psr16Cache;
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
            'SELECT * FROM metasample'
        ));

        return [
            [self::getDummyDatabase()->getSchema(), self::getDummyDatabase()],
        ];
    }

    function setUp(): void
    {
        parent::setUp();

        self::getDummyDatabase()->getSchema()->refresh();
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

        $this->assertException(SchemaException::tableAlreadyExists('metatest'), L($schema)->addTable($this->getDummyTable('metatest')));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_hasTable($schema)
    {
        $this->assertTrue($schema->hasTable('metasample'));
        $this->assertFalse($schema->hasTable('metahoge'));

        // VIEW も含まれる
        $this->assertTrue($schema->hasTable('viewsample'));
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
        $this->assertContains('viewsample', $schema->getTableNames());

        $schema->refresh();

        $this->assertNotContains('metatest', $schema->getTableNames());
        $this->assertContains('metasample', $schema->getTableNames());
        $this->assertContains('viewsample', $schema->getTableNames());
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTable($schema)
    {
        $this->assertStringContainsString('Table', get_class($schema->getTable('metasample')));

        $schema->refresh();

        $this->assertStringContainsString('Table', get_class($schema->getTable('metasample')));

        $this->assertException(SchemaException::tableDoesNotExist('hogera'), L($schema)->getTable('hogera'));
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
            't',
            'test',
            'viewsample',
        ], array_keys($schema->getTables('!foreign_c?')));
        $this->assertEquals([
            'foreign_p',
            'metasample',
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
        $this->assertIsArray($schema->getTableColumns('viewsample'));

        $this->assertException(SchemaException::tableDoesNotExist('hogera'), L($schema)->getTableColumns('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTablePrimaryKey($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));

        $this->assertStringContainsString('Index', get_class($schema->getTablePrimaryKey('metatest')));

        $this->assertException(SchemaException::tableDoesNotExist('hogera'), L($schema)->getTablePrimaryKey('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     */
    function test_getTablePrimaryKeyColumns($schema)
    {
        $schema->addTable($this->getDummyTable('metatest'));

        $this->assertEquals(array_intersect_key($schema->getTableColumns('metatest'), ['id' => '']), $schema->getTablePrimaryColumns('metatest'));
        $this->assertEquals([], $schema->getTablePrimaryColumns('viewsample'));

        $this->assertException(SchemaException::tableDoesNotExist('hogera'), L($schema)->getTablePrimaryKey('hogera'));
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

        $this->assertException(SchemaException::tableDoesNotExist('hogera'), L($schema)->getTableForeignKeys('hogera'));
    }

    /**
     * @dataProvider provideSchema
     * @param Schema $schema
     * @param Database $database
     */
    function test_setTableColumn($schema, $database)
    {
        // 実カラムの上書き
        $column = $schema->setTableColumn('metasample', 'id', ['type' => 'string']);
        $this->assertEquals([
            'virtual'  => false,
            'implicit' => true,
        ], $column->getPlatformOptions());
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertEquals('string', $schema->getTableColumns('metasample')['id']->getType()->getName());

        // 仮想カラムの追加
        $column = $schema->setTableColumn('metasample', 'dummy', [
            'type'     => 'integer',
            'select'   => 'NOW()',
            'implicit' => false,
            'others'   => [
                'hoge' => 'HOGE',
            ],
        ]);
        $this->assertEquals([
            'virtual'  => true,
            'select'   => 'NOW()',
            'implicit' => false,
            'others'   => [
                'hoge' => 'HOGE',
            ],
        ], $column->getPlatformOptions());
        $this->assertEquals('integer', $column->getType()->getName());
        $this->assertEquals('integer', $schema->getTableColumns('metasample')['dummy']->getType()->getName());

        // 仮想カラムの上書き
        $column = $schema->setTableColumn('metasample', 'dummy', [
            'type'     => 'string',
            'select'   => 'NOW()',
            'implicit' => true,
            'others'   => [
                'fuga' => 'FUGA',
            ],
        ]);
        $this->assertEquals([
            'virtual'  => true,
            'select'   => 'NOW()',
            'implicit' => true,
            'others'   => [
                'fuga' => 'FUGA',
            ],
        ], $column->getPlatformOptions());
        $this->assertEquals('string', $column->getType()->getName());
        $this->assertEquals('string', $schema->getTableColumns('metasample')['dummy']->getType()->getName());

        // キャッシュが効いていないか担保しておく
        $this->assertEquals('SELECT metasample.id, NOW() AS dummy FROM metasample', $database->select('metasample.!')->queryInto());

        // 仮想カラムの削除
        $column = $schema->setTableColumn('metasample', 'dummy', null);
        $this->assertNull($column);
        $this->assertEquals(['id'], array_keys($schema->getTableColumns('metasample')));
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

        $this->assertException(SchemaException::tableDoesNotExist('hogera'), L($schema)->getTablePrimaryKey('hogera'));
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
        $schema->addTable($this->getDummyTable('metatest'));
        $foreign1 = $this->getDummyTable('foreign1');
        $foreign1->addForeignKeyConstraint('metatest', ['id'], ['foreign1'], [], 'FK_1');
        $foreign2 = $this->getDummyTable('foreign2');
        $foreign2->addForeignKeyConstraint('metatest', ['id'], ['foreign2'], [], 'FK_2');
        $schema->addTable($foreign1);
        $schema->addTable($foreign2);

        $this->assertEquals(['id' => 'foreign1'], $schema->getForeignColumns('metatest', 'foreign1'));
        $this->assertEquals(['id' => 'foreign2'], $schema->getForeignColumns('metatest', 'foreign2'));
        $this->assertEquals(['foreign1' => 'id'], $schema->getForeignColumns('foreign1', 'metatest'));
        $this->assertEquals(['foreign2' => 'id'], $schema->getForeignColumns('foreign2', 'metatest'));
        $this->assertEquals([], $schema->getForeignColumns('foreign1', 'foreign2'));
        $this->assertEquals(['id' => 'foreign1'], $schema->getForeignColumns('metatest', 'foreign1', 'FK_1'));
        $this->assertException('is not exists', L($schema)->getForeignColumns('metatest', 'foreign1', 'FK_2'));
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
        $this->assertEquals(['id' => 'd2_id'], $schema->getForeignColumns('foreign_d1', 'foreign_d2', 'fk_dd12'));

        $this->assertEquals(['id' => 'id'], $schema->getForeignColumns('foreign_c1', 'foreign_p', null, $direction));
        $this->assertSame(true, $direction);
        $this->assertEquals(['id' => 'id'], $schema->getForeignColumns('foreign_p', 'foreign_c1', null, $direction));
        $this->assertSame(false, $direction);

        $this->assertException('ambiguous foreign keys', L($schema)->getForeignColumns('foreign_d2', 'foreign_d1'));
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

        $this->assertException('localTable is not set', L($schema)->addForeignKey($newFK(null, 'id', 'foreign2', 'id'), null));
        $this->assertException('already defined same', L($schema)->addForeignKey($newFK('fk_hogera', 'id', 'foreign2', 'id'), 'foreign1'));
        $this->assertException('column for foreign1', L($schema)->addForeignKey($newFK(null, 'foreign2', 'foreign2', 'foreign2'), 'foreign1'));
        $this->assertException('column for foreign1', L($schema)->addForeignKey($newFK(null, 'foreign2', 'foreign1', 'foreign2'), 'foreign2'));
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

        $this->assertException('undefined foreign key', L($schema)->ignoreForeignKey('undefined'));
        $this->assertException('localTable is not set', L($schema)->ignoreForeignKey($newFK(null, 'id', 'foreign2', 'id'), null));
        $this->assertException('matched foreign key', L($schema)->ignoreForeignKey($newFK(null, 'notfound', 'metatest', 'notfound'), 'foreign'));
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
        $this->assertEquals(['foreign_d2/foreign_d1' => 'id'], $schema->followColumnName('foreign_d1', 'foreign_d2', 'id'));
        $this->assertEquals([], $schema->followColumnName('foreign_d1', 'foreign_d2', 'd2_id'));
        $this->assertEquals([], $schema->followColumnName('foreign_d2', 'foreign_d1', 'id'));
        $this->assertEquals(['foreign_d1/foreign_d2' => 'id'], $schema->followColumnName('foreign_d2', 'foreign_d1', 'd2_id'));
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

        $schema = new Schema(self::getDummyDatabase()->getConnection()->createSchemaManager(), new Psr16Cache(new NullAdapter()));

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
        $this->assertEquals([
            'root_id' => 'leaf_root_id',
            'seq'     => 'leaf_root_seq',
        ], $schema->getForeignColumns('t_leaf', 't_root', null, $direction));
        $this->assertSame(true, $direction);
        $this->assertEquals([
            'leaf_root_id'  => 'root_id',
            'leaf_root_seq' => 'seq',
        ], $schema->getForeignColumns('t_root', 't_leaf', null, $direction));
        $this->assertSame(false, $direction);
    }
}
