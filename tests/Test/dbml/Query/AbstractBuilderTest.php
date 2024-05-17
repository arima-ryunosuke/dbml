<?php

namespace ryunosuke\Test\dbml\Query;

use ryunosuke\dbml\Query\AbstractBuilder;
use ryunosuke\dbml\Query\Clause\Where;
use ryunosuke\dbml\Query\Expression\Expression;

class AbstractBuilderTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function provideAbstractBuilder()
    {
        return array_map(function ($v) {
            return [
                new class($v[0]) extends AbstractBuilder {
                    public static function getDefaultOptions(): array { return []; }

                    public function __toString(): string
                    {
                        return __CLASS__;
                    }
                },
                $v[0],
            ];
        }, parent::provideDatabase());
    }

    /**
     * @dataProvider provideAbstractBuilder
     * @param AbstractBuilder $builder
     */
    function test_precondition($builder)
    {
        $precondition = self::forcedCallize($builder, '_precondition');

        $actual = Where::build($builder->getDatabase(), $precondition(['P' => 'foreign_p'], [$builder->getDatabase()->subexists('foreign_c1')]));
        $this->assertEquals(['(EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = P.id))'], $actual);

        $db = $builder->getDatabase();

        $actual = Where::build($builder->getDatabase(), $precondition(['foreign_p' => 'foreign_p'], ['' => 1]));
        $this->assertEquals(['foreign_p.id = ?'], $actual);

        $actual = Where::build($builder->getDatabase(), $precondition(['multiprimary' => 'multiprimary'], ['' => [1, 2]]));
        $this->assertEquals(['(multiprimary.mainid = ? AND multiprimary.subid = ?)'], $actual);

        $actual = Where::build($builder->getDatabase(), $precondition(['multiprimary' => 'multiprimary'], ['' => [[1, 2], [2, 3]]]));
        if ($db->getCompatiblePlatform()->supportsRowConstructor()) {
            $this->assertEquals(['(multiprimary.mainid, multiprimary.subid) IN ((?, ?), (?, ?))'], $actual);
        }
        else {
            $this->assertEquals(['(multiprimary.mainid = ? AND multiprimary.subid = ?) OR (multiprimary.mainid = ? AND multiprimary.subid = ?)'], $actual);
        }

        $actual = Where::build($builder->getDatabase(), $precondition(['foreign_p' => 'foreign_p'], [$db->subexists('foreign_c1', ['seq > ?' => 0])]));
        $this->assertEquals(['(EXISTS (SELECT * FROM foreign_c1 WHERE (seq > ?) AND (foreign_c1.id = foreign_p.id)))'], $actual);

        // ネストしててもOKのはず
        $actual = Where::build($builder->getDatabase(), $precondition(['g_ancestor' => 'g_ancestor'], [$db->subexists('g_parent', $db->subexists('g_child'))]));
        $this->assertEquals(['(EXISTS (SELECT * FROM g_parent WHERE ((EXISTS (SELECT * FROM g_child WHERE g_child.parent_id = g_parent.parent_id))) AND (g_parent.ancestor_id = g_ancestor.ancestor_id)))'], $actual);

        try {
            $precondition(['foreign_p' => 'foreign_p'], ['' => [[1, 2]]]);
        }
        catch (\Throwable $t) {
            $this->assertStringContainsString('is not match primary columns', $t->getMessage());
        }
    }

    /**
     * @dataProvider provideAbstractBuilder
     * @param AbstractBuilder $builder
     */
    function test_bindInto($builder)
    {
        $params = [];
        $bind = $builder->bindInto(['colA' => 1, 'colB' => 2], $params);
        $this->assertEquals(['colA' => '?', 'colB' => '?'], $bind);
        $this->assertEquals([1, 2], $params);

        $params = [];
        $bind = $builder->bindInto(['colA' => 1, 'colB' => new Expression('FUNC(99)')], $params);
        $this->assertEquals(['colA' => '?', 'colB' => 'FUNC(99)'], $bind);
        $this->assertEquals([1], $params);

        $params = [];
        $bind = $builder->bindInto(['colA' => 1, 'colB' => new Expression('FUNC(?)', [99])], $params);
        $this->assertEquals(['colA' => '?', 'colB' => 'FUNC(?)'], $bind);
        $this->assertEquals([1, 99], $params);

        $params = [];
        $subquery = $builder->getDatabase()->select('test', ['id' => 1]);
        $bind = $builder->bindInto(['colA' => new Expression('FUNC(?)', [99]), 'colB' => $subquery], $params);
        $this->assertEquals(['colA' => 'FUNC(?)', 'colB' => "($subquery)"], $bind);
        $this->assertEquals([99, 1], $params);
    }
}
