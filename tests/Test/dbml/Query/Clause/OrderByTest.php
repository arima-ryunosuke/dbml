<?php

namespace ryunosuke\Test\dbml\Query\Clause;

use ryunosuke\dbml\Query\Clause\OrderBy;
use function ryunosuke\dbml\sql_format;

class OrderByTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_random_method()
    {
        $db = self::getDummyDatabase();

        $db->truncate('test');
        $db->insertArray('test', [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
            ['id' => 5],
            ['id' => 6],
            ['id' => 7],
            ['id' => 8],
            ['id' => 9],
        ]);

        $builder = $db->createSelectBuilder();

        $random = $builder->getDatabase()->getCompatiblePlatform()->getRandomExpression(null);

        $this->assertEqualsSQL(<<<SQL
        SELECT test.*
        FROM test
        ORDER BY $random
        SQL, $builder->reset()->column('test')->orderBy(OrderBy::randomSuitably()));

        $this->assertEqualsSQL(<<<SQL
        SELECT test.*
        FROM test
        WHERE test.id IN(?,?)
        ORDER BY $random
        LIMIT 1
        SQL, $builder->reset()->column('test')->limit(1)->orderBy(OrderBy::randomSuitably()));

        $this->assertEqualsSQL(<<<SQL
        WITH RECURSIVE __dbml_cte_table AS (
          SELECT test.*,
            test.id AS __dbml_auto_column_depend_cte_test_id
          FROM test
          WHERE id > ?
        )
        SELECT * FROM __dbml_cte_table
        WHERE __dbml_auto_column_depend_cte_test_id IN (
          SELECT * FROM (SELECT __dbml_auto_column_depend_cte_test_id
            FROM __dbml_cte_table
            ORDER BY $random
            LIMIT 1
          ) __dbml_cte_table_alias
        )
        ORDER BY $random
        SQL, $builder->reset()->column('test')->where(['id > ?' => 5])->limit(1)->orderBy(OrderBy::randomSuitably()));

        $this->assertEqualsSQL(<<<SQL
        SELECT test.*
        FROM test
        WHERE test.id IN (?,?,?,?,?,?,?,?,?)
        ORDER BY $random
        SQL, $builder->reset()->column('test')->orderBy(OrderBy::randomPKMinMax()));

        $this->assertEqualsSQL(<<<SQL
        SELECT test.*
        FROM test
        WHERE (id > ?) AND (test.id IN (?,?,?,?))
        ORDER BY $random
        SQL, $builder->reset()->column('test')->where(['id > ?' => 5])->orderBy(OrderBy::randomPKMinMax()));

        $this->assertEqualsSQL(<<<SQL
        SELECT test.*
        FROM test
        WHERE (id > ?) AND (test.id IN (?,?))
        ORDER BY $random
        LIMIT 1
        SQL, $builder->reset()->column('test')->where(['id > ?' => 5])->limit(1)->orderBy(OrderBy::randomPKMinMax()));

        $this->assertEqualsSQL(<<<SQL
        WITH RECURSIVE __dbml_cte_table AS (
          SELECT test.*,
            test.id AS __dbml_auto_column_depend_cte_test_id
          FROM test
        )
        SELECT * FROM __dbml_cte_table
        WHERE __dbml_auto_column_depend_cte_test_id IN (
          SELECT __dbml_auto_column_depend_cte_test_id
          FROM __dbml_cte_table
          ORDER BY $random
        )
        ORDER BY $random
        SQL, $builder->reset()->column('test')->orderBy(OrderBy::randomPK()));

        $this->assertEqualsSQL(<<<SQL
        WITH RECURSIVE __dbml_cte_table AS (
          SELECT test.*,
            test.id AS __dbml_auto_column_depend_cte_test_id
          FROM test
          WHERE id > ?
        )
        SELECT * FROM __dbml_cte_table
        WHERE __dbml_auto_column_depend_cte_test_id IN (
          SELECT __dbml_auto_column_depend_cte_test_id
          FROM __dbml_cte_table
          ORDER BY $random
        )
        ORDER BY $random
        SQL, $builder->reset()->column('test')->where(['id > ?' => 5])->orderBy(OrderBy::randomPK()));

        $this->assertEqualsSQL(<<<SQL
        WITH RECURSIVE __dbml_cte_table AS (
          SELECT test.*,
            test.id AS __dbml_auto_column_depend_cte_test_id
          FROM test
          WHERE id > ?
        )
        SELECT * FROM __dbml_cte_table
        WHERE __dbml_auto_column_depend_cte_test_id IN (
          SELECT * FROM (SELECT __dbml_auto_column_depend_cte_test_id
            FROM __dbml_cte_table
            ORDER BY $random
            LIMIT 3
          ) __dbml_cte_table_alias
        )
        ORDER BY $random
        SQL, $builder->reset()->column('test')->where(['id > ?' => 5])->limit(3)->orderBy(OrderBy::randomPK()));

        $this->assertEqualsSQL(<<<SQL
        SELECT test.*
        FROM test
        WHERE (id > ?) AND ($random <= (? / ?))
        ORDER BY $random
        LIMIT 3
        SQL, $builder->reset()->column('test')->where(['id > ?' => 5])->limit(3)->orderBy(OrderBy::randomWhere()));

        srand(24);
        $this->assertEqualsSQL(<<<SQL
        WITH RECURSIVE __dbml_cte_table AS (
          SELECT test.*
          FROM test
          WHERE id > ?
        )
        SELECT * FROM (
          SELECT * FROM __dbml_cte_table LIMIT 1 OFFSET 2 UNION
          SELECT * FROM __dbml_cte_table LIMIT 1 OFFSET 3 UNION
          SELECT * FROM __dbml_cte_table LIMIT 1 OFFSET 1
        ) __dbml_union_table
        ORDER BY $random
        SQL, $builder->reset()->column('test')->where(['id > ?' => 5])->limit(3)->orderBy(OrderBy::randomOffset()));

        $this->assertEqualsSQL(<<<SQL
        WITH RECURSIVE __dbml_cte_table AS (
          SELECT test.*
          FROM test
          WHERE id >= ?
        )
        SELECT * FROM (
          SELECT * FROM __dbml_cte_table WHERE id >= ? LIMIT 1 UNION
          SELECT * FROM __dbml_cte_table WHERE id >= ? LIMIT 1 UNION
          SELECT * FROM __dbml_cte_table WHERE id >= ? LIMIT 1
        ) __dbml_union_table
        ORDER BY $random
        SQL, $builder->reset()->column('test')->where(['id >= ?' => 1])->limit(3)->orderBy(OrderBy::randomPKMinMax2()));
    }

    public static function assertEqualsSQL($expected, $actual, $message = '')
    {
        $options = [
            'highlight' => false,
            'nestlevel' => 0,
        ];
        self::assertEquals(trim(sql_format($expected, $options)), trim(sql_format($actual, $options)), $message);
    }
}
