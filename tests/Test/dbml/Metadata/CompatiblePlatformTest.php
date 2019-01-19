<?php

namespace ryunosuke\Test\dbml\Metadata;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use ryunosuke\dbml\Metadata\CompatiblePlatform;
use ryunosuke\dbml\Query\Expression\Expression;

class CompatiblePlatformTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * dbml がサポートする platform を全て提供
     */
    public static function providePlatform()
    {
        $platforms = [
            new \ryunosuke\Test\Platforms\SqlitePlatform(),
            new SqlitePlatform(),
            new MySqlPlatform(),
            new PostgreSqlPlatform(),
            new SQLServerPlatform(),
            new OraclePlatform(),
        ];
        return array_map(function (AbstractPlatform $v) {
            return [
                new CompatiblePlatform($v),
                $v,
            ];
        }, $platforms);
    }

    function test___construct()
    {
        $platform = new SqlitePlatform();
        $cplatform = new CompatiblePlatform($platform);
        $this->assertSame($platform, $cplatform->getWrappedPlatform());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getWrappedPlatform($cplatform, $platform)
    {
        $this->assertEquals(spl_object_hash($platform), spl_object_hash($cplatform->getWrappedPlatform()));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsIdentityUpdate($cplatform, $platform)
    {
        $expected = !$platform instanceof SQLServerPlatform;
        $this->assertEquals($expected, $cplatform->supportsIdentityUpdate());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsReplace($cplatform, $platform)
    {
        $expected = $platform instanceof SqlitePlatform || $platform instanceof MySqlPlatform;
        $this->assertEquals($expected, $cplatform->supportsReplace());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsMerge($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform || $platform instanceof PostgreSqlPlatform;
        $this->assertEquals($expected, $cplatform->supportsMerge());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsIgnore($cplatform, $platform)
    {
        $expected = $platform instanceof SqlitePlatform || $platform instanceof MySqlPlatform;
        $this->assertEquals($expected, $cplatform->supportsIgnore());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsZeroAffectedUpdate($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform;
        $this->assertEquals($expected, $cplatform->supportsZeroAffectedUpdate());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsUpdateJoin($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform || $platform instanceof SQLServerPlatform;
        $this->assertEquals($expected, $cplatform->supportsUpdateJoin());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsDeleteJoin($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform || $platform instanceof SQLServerPlatform;
        $this->assertEquals($expected, $cplatform->supportsDeleteJoin());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsUpdateLimit($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform || $platform instanceof SQLServerPlatform;
        $this->assertEquals($expected, $cplatform->supportsUpdateLimit());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsDeleteLimit($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform || $platform instanceof SQLServerPlatform;
        $this->assertEquals($expected, $cplatform->supportsDeleteLimit());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsUnionParentheses($cplatform, $platform)
    {
        $expected = !$platform instanceof SqlitePlatform;
        $this->assertEquals($expected, $cplatform->supportsUnionParentheses());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsResetAutoIncrementOnTruncate($cplatform, $platform)
    {
        $expected = !$platform instanceof SqlitePlatform;
        $this->assertEquals($expected, $cplatform->supportsResetAutoIncrementOnTruncate());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsTableNameAttribute($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform;
        $this->assertEquals($expected, $cplatform->supportsTableNameAttribute());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsRowConstructor($cplatform, $platform)
    {
        $expected = true;
        if ($platform instanceof SqlitePlatform) {
            $expected = version_compare(\SQLite3::version()['versionString'], '3.15.0', '>=');
        }
        if ($platform instanceof SQLServerPlatform) {
            $expected = false;
        }
        $this->assertEquals($expected, $cplatform->supportsRowConstructor());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_quoteIdentifierIfNeeded($cplatform, $platform)
    {
        $this->assertEquals('', $cplatform->quoteIdentifierIfNeeded(''));
        $this->assertEquals('hogera', $cplatform->quoteIdentifierIfNeeded('hogera'));
        $this->assertEquals($platform->quoteSingleIdentifier('WHERE'), $cplatform->quoteIdentifierIfNeeded('WHERE'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_escapeLike($cplatform, $platform)
    {
        $expected = $platform instanceof SQLServerPlatform ? 'w$%o$_r$[d' : 'w$%o$_r[d';
        $this->assertEquals($expected, $cplatform->escapeLike('w%o_r[d', '$'));

        $expected = $platform instanceof SQLServerPlatform ? 'w[%]o[_]r[[]d' : 'w\\%o\\_r[d';
        $this->assertEquals($expected, $cplatform->escapeLike('w%o_r[d'));

        $expected = $platform instanceof SQLServerPlatform ? 'a[%]%m%%x%y' : 'a\\%%m%%x%y';
        $this->assertEquals($expected, $cplatform->escapeLike(['a%', new Expression('m%'), ['x', 'y']]));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getFoundRowsOption($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform ? 'SQL_CALC_FOUND_ROWS' : '';
        $this->assertEquals($expected, $cplatform->getFoundRowsOption());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getFoundRowsQuery($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform ? 'SELECT FOUND_ROWS()' : '';
        $this->assertEquals($expected, $cplatform->getFoundRowsQuery());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getMergeSQL($cplatform, $platform)
    {
        $expected = false;
        if ($platform instanceof MySqlPlatform) {
            $expected = 'INSERT INTO tbl SET c1 = 1, c2 = 2 ON DUPLICATE KEY UPDATE c1 = 3, c2 = 4';
        }
        if ($platform instanceof PostgreSqlPlatform) {
            $expected = 'INSERT INTO tbl (c1, c2) VALUES (1, 2) ON CONFLICT ON CONSTRAINT PK DO UPDATE SET c1 = 3, c2 = 4';
        }
        $this->assertEquals($expected, $cplatform->getMergeSQL('tbl', ['c1' => 1, 'c2' => 2], ['c1' => 3, 'c2' => 4], 'PK'));

        $expected = false;
        if ($platform instanceof MySqlPlatform) {
            $expected = 'INSERT INTO tbl SET c1 = 1, c2 = 2 ON DUPLICATE KEY UPDATE c1 = 3, c2 = 4';
        }
        if ($platform instanceof PostgreSqlPlatform) {
            $expected = 'INSERT INTO tbl (c1, c2) VALUES (1, 2) ON CONFLICT ON CONSTRAINT (col1,col2) DO UPDATE SET c1 = 3, c2 = 4';
        }
        $this->assertEquals($expected, $cplatform->getMergeSQL('tbl', ['c1' => 1, 'c2' => 2], ['c1' => 3, 'c2' => 4], ['col1', 'col2']));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getgetIdentityInsertSQL($cplatform, $platform)
    {
        if ($platform instanceof SQLServerPlatform) {
            $expected = 'SET IDENTITY_INSERT t_table ON';
            $this->assertContains($expected, $cplatform->getIdentityInsertSQL('t_table', true));
        }
        else {
            $expected = new DBALException('is not supported by platform');
            $this->assertException($expected, L($cplatform)->getIdentityInsertSQL('t_table', true));
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getTruncateTableSQL($cplatform, $platform)
    {
        $expected = $platform instanceof PostgreSqlPlatform ? 'RESTART IDENTITY' : 't_table';
        $this->assertContains($expected, $cplatform->getTruncateTableSQL('t_table'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getIdentitySequenceName($cplatform, $platform)
    {
        $expected = $platform instanceof PostgreSqlPlatform ? 'table_id_seq' : null;
        $expected = $platform instanceof OraclePlatform ? 'TABLE_SEQ' : $expected;
        $this->assertEquals($expected, $cplatform->getIdentitySequenceName('table', 'id'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getIndexHintSQL($cplatform, $platform)
    {
        $expected = '';
        if ($platform instanceof MySqlPlatform) {
            $expected = 'IGNORE INDEX (idx_1, idx_2)';
        }
        if ($platform instanceof SQLServerPlatform) {
            $expected = 'WITH (INDEX(idx_1, idx_2))';
        }
        $this->assertEquals($expected, $cplatform->getIndexHintSQL(['idx_1', 'idx_2'], 'IGNORE'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_appendLockSuffix($cplatform, $platform)
    {
        $expected = $platform instanceof SQLServerPlatform ? 'select * from t_table' : 'select * from t_table ' . $platform->getReadLockSQL();
        $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_READ, null));
        $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', null, null));

        $expected = $platform instanceof SQLServerPlatform ? 'select * from t_table' : 'select * from t_table ' . $platform->getWriteLockSQL() . ' hoge';
        $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_WRITE, 'hoge'));
        $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', null, null));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getSpaceshipSyntax($cplatform, $platform)
    {
        $expected = $platform instanceof MySqlPlatform ? '<=>' : 'IS NULL OR';
        $this->assertContains($expected, $cplatform->getSpaceshipSyntax('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getPrimaryCondition($cplatform, $platform)
    {
        $actual = $cplatform->getPrimaryCondition([]);
        $this->assertExpression($actual, '', []);

        $actual = $cplatform->getPrimaryCondition([['id' => 1]]);
        $this->assertExpression($actual, 'id = ?', [1]);

        $actual = $cplatform->getPrimaryCondition([['id' => 1], ['id' => 2]]);
        $this->assertExpression($actual, 'id IN (?, ?)', [1, 2]);

        $actual = $cplatform->getPrimaryCondition([['id' => 1], ['id' => 1]], 'prefix');
        $this->assertExpression($actual, 'prefix.id IN (?, ?)', [1, 1]);

        $actual = $cplatform->getPrimaryCondition([['id' => new Expression('?', 1)], ['id' => 2]]);
        $this->assertExpression($actual, 'id IN (?, ?)', [1, 2]);

        $actual = $cplatform->getPrimaryCondition([['id' => 1, 'seq' => 2], ['id' => 3, 'seq' => 4]]);
        $this->assertExpression($actual, '(id = ? AND seq = ?) OR (id = ? AND seq = ?)', [1, 2, 3, 4]);

        $actual = $cplatform->getPrimaryCondition([['id' => 1, 'seq' => 2], ['id' => 3, 'seq' => 4]], 'prefix');
        $this->assertExpression($actual, '(prefix.id = ? AND prefix.seq = ?) OR (prefix.id = ? AND prefix.seq = ?)', [1, 2, 3, 4]);

        $actual = $cplatform->getPrimaryCondition([['id' => new Expression('?', 1), 'seq' => 2]]);
        $this->assertExpression($actual, '(id = ? AND seq = ?)', [1, 2]);
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getCaseWhenSyntax($cplatform, $platform)
    {
        $expected = "(CASE WHEN id = 1 THEN ? WHEN id = 2 THEN ? END)";
        $actual = $cplatform->getCaseWhenSyntax(null, ['id = 1' => 'hoge', 'id = 2' => 'fuga']);
        $this->assertExpression($actual, $expected, ['hoge', 'fuga']);

        $expected = "(CASE id WHEN ? THEN ? WHEN ? THEN ? END)";
        $actual = $cplatform->getCaseWhenSyntax('id', ['1' => 'hoge', '2' => 'fuga']);
        $this->assertExpression($actual, $expected, [1, 'hoge', 2, 'fuga']);

        $expected = "(CASE NOW(?) WHEN ? THEN ? WHEN ? THEN ? END)";
        $actual = $cplatform->getCaseWhenSyntax(new Expression('NOW(?)', ['time']), ['1' => 'hoge', '2' => 'fuga']);
        $this->assertExpression($actual, $expected, ['time', 1, 'hoge', 2, 'fuga']);

        $expected = "(CASE id WHEN ? THEN ? WHEN ? THEN ? WHEN ? THEN ? ELSE ? END)";
        $actual = $cplatform->getCaseWhenSyntax('id', ['h' => 'hoge', 'f' => 'fuga', '1' => '123'], 'other');
        $this->assertExpression($actual, $expected, ['h', 'hoge', 'f', 'fuga', 1, '123', 'other']);

        $expected = "(CASE id WHEN ? THEN ? WHEN ? THEN ? WHEN ? THEN ? ELSE NOW(?) END)";
        $actual = $cplatform->getCaseWhenSyntax('id', ['h' => 'hoge', 'f' => 'fuga', '1' => '123'], new Expression('NOW(?)', ['time']));
        $this->assertExpression($actual, $expected, ['h', 'hoge', 'f', 'fuga', 1, '123', 'time']);

        // very very complex
        $expected = "(CASE (SELECT t.c FROM t WHERE w = ?) WHEN ? THEN (SELECT t.d FROM t WHERE dw = ?) WHEN ? THEN ADD(?, ?) ELSE NOW(?) END)";
        $actual = $cplatform->getCaseWhenSyntax(
            self::getDummyDatabase()->select('t.c', ['w' => 1]),
            [
                'qb'  => self::getDummyDatabase()->select('t.d', ['dw' => 2]),
                'exp' => new Expression('ADD(?, ?)', [3, 4]),
            ],
            new Expression('NOW(?)', ['time'])
        );
        $this->assertExpression($actual, $expected, [1, 'qb', 2, 'exp', 3, 4, 'time']);
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getGroupConcatSyntax($cplatform, $platform)
    {
        if ($platform instanceof SqlitePlatform) {
            $expected = "GROUP_CONCAT(id)";
            $this->assertEquals($expected, $cplatform->getGroupConcatSyntax('id'));

            $expected = "GROUP_CONCAT(id, '|')";
            $this->assertEquals($expected, $cplatform->getGroupConcatSyntax('id', '|'));
        }
        if ($platform instanceof MySqlPlatform) {
            $expected = "GROUP_CONCAT(id)";
            $actual = $cplatform->getGroupConcatSyntax('id');
            $this->assertEquals($expected, $actual);

            $expected = "GROUP_CONCAT(id SEPARATOR '\"')";
            $actual = $cplatform->getGroupConcatSyntax('id', '"');
            $this->assertEquals($expected, $actual);

            $expected = "GROUP_CONCAT(id ORDER BY hoge ASC SEPARATOR '|')";
            $actual = $cplatform->getGroupConcatSyntax('id', '|', 'hoge');
            $this->assertEquals($expected, $actual);

            $expected = "GROUP_CONCAT(id, seq ORDER BY hoge DESC, fuga ASC SEPARATOR '|')";
            $actual = $cplatform->getGroupConcatSyntax(['id', 'seq'], '|', ['hoge' => 'DESC', 'fuga']);
            $this->assertEquals($expected, $actual);

            $expected = "GROUP_CONCAT(id, seq ORDER BY hoge ASC SEPARATOR '|')";
            $actual = $cplatform->getGroupConcatSyntax(['id', 'seq'], '|', ['hoge' => 'ASC']);
            $this->assertEquals($expected, $actual);
        }
        if ($platform instanceof PostgreSqlPlatform) {
            $expected = "ARRAY_AGG(id)";
            $this->assertEquals($expected, $cplatform->getGroupConcatSyntax('id'));

            $expected = "ARRAY_TO_STRING(ARRAY_AGG(id), '|')";
            $this->assertEquals($expected, $cplatform->getGroupConcatSyntax('id', '|'));
        }
        if ($platform instanceof SQLServerPlatform) {
            $expected = new DBALException('is not supported by platform');
            $this->assertException($expected, L($cplatform)->getGroupConcatSyntax('id'));
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getCountExpression($cplatform, $platform)
    {
        $expected = $platform->getCountExpression('hoge');
        $this->assertEquals($expected, $cplatform->getCountExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getMinExpression($cplatform, $platform)
    {
        $expected = $platform->getMinExpression('hoge');
        $this->assertEquals($expected, $cplatform->getMinExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getMaxExpression($cplatform, $platform)
    {
        $expected = $platform->getMaxExpression('hoge');
        $this->assertEquals($expected, $cplatform->getMaxExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getSumExpression($cplatform, $platform)
    {
        $expected = $platform->getSumExpression('hoge');
        $this->assertEquals($expected, $cplatform->getSumExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getAvgExpression($cplatform, $platform)
    {
        if ($platform instanceof SQLServerPlatform) {
            $expected = $platform->getAvgExpression('CAST(hoge AS float)');
        }
        else {
            $expected = $platform->getAvgExpression('hoge');
        }
        $this->assertEquals($expected, $cplatform->getAvgExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getConcatExpression($cplatform, $platform)
    {
        $this->assertException('greater than', L($cplatform)->getConcatExpression([]));

        $this->assertEquals('id', $cplatform->getConcatExpression('id'));

        $expected = $platform instanceof SQLServerPlatform ? $platform->getConcatExpression('CAST(id1 as varchar)', 'CAST(id2 as varchar)') : $platform->getConcatExpression('id1', 'id2');
        $this->assertEquals($expected, $cplatform->getConcatExpression('id1', 'id2'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getResetSequenceExpression($cplatform, $platform)
    {
        $expected = [
            'sqlite'     => [
                'DELETE FROM sqlite_sequence WHERE name = \'t_table\'',
                'INSERT INTO sqlite_sequence (name, seq) VALUES (\'t_table\', 98)',
            ],
            'mysql'      => [
                'ALTER TABLE t_table AUTO_INCREMENT = 99',
            ],
            'postgresql' => [
                'SELECT setval(\'t_table_c_sol_seq\', 99, false)',
            ],
            'mssql'      => [
                'DBCC CHECKIDENT(t_table, RESEED, 98)',
            ],
        ];

        if (!isset($expected[$platform->getName()])) {
            $this->assertException('is not support', L($cplatform)->getResetSequenceExpression('t_table', 'c_sol', 99));
        }
        else {
            $this->assertEquals($expected[$platform->getName()], $cplatform->getResetSequenceExpression('t_table', 'c_sol', 99));
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getIgnoreSyntax($cplatform, $platform)
    {
        if ($platform instanceof SqlitePlatform) {
            $this->assertEquals('OR IGNORE', $cplatform->getIgnoreSyntax());
        }
        elseif ($platform instanceof MySqlPlatform) {
            $this->assertEquals('IGNORE', $cplatform->getIgnoreSyntax());
        }
        else {
            $this->assertException('is not supported', L($cplatform)->getIgnoreSyntax());
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_commentize($cplatform, $platform)
    {
        $s = $platform->getSqlCommentStartString();
        $e = $platform->getSqlCommentEndString();
        $this->assertEquals("$s select 1 $e", $cplatform->commentize('select 1'));

        $s = $platform->getSqlCommentStartString();
        $e = $platform->getSqlCommentEndString();
        $this->assertEquals("$s hoge fuga $e", $cplatform->commentize("hoge\nfuga"));

        $this->assertEquals("/* hoge\nfuga */", $cplatform->commentize("hoge\nfuga", true));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_convertMergeData($cplatform, $platform)
    {
        $expected = ['id' => 9];
        $this->assertEquals($expected, $cplatform->convertMergeData(['id' => 1], ['id' => 9]));

        $expected = ['id' => 1];
        if ($platform instanceof MySqlPlatform) {
            $expected = ['id' => new Expression('VALUES(id)')];
        }
        $this->assertEquals($expected, $cplatform->convertMergeData(['id' => 1], []));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_convertSelectExistsQuery($cplatform, $platform)
    {
        $expected = $platform instanceof SQLServerPlatform ? 'CASE WHEN (select 1) THEN 1 ELSE 0 END' : 'select 1';
        $this->assertExpression($cplatform->convertSelectExistsQuery('select 1'), $expected, []);

        $expected = $platform instanceof SQLServerPlatform ? 'CASE WHEN (select ?) THEN 1 ELSE 0 END' : 'select ?';
        $this->assertExpression($cplatform->convertSelectExistsQuery(new Expression('select ?', 1)), $expected, [1]);
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_isRetryableException($cplatform, $platform)
    {
        $pdoexception = function ($state, $code) {
            $pe = new \PDOException();
            $pe->errorInfo = [$state, $code];
            return $pe;
        };
        if ($platform instanceof MySqlPlatform) {
            $dataset = [
                [true, new DriverException('', new PDOException($pdoexception('', 1205)))],
                [true, new DriverException('', new PDOException($pdoexception('', 1213)))],
                [true, new DriverException('', new PDOException($pdoexception('', 2006)))],
                [false, new DriverException('', new PDOException($pdoexception('', 9999)))],
                [false, new \Exception()],
            ];
        }
        else {
            $dataset = [
                [true, new PDOException($pdoexception('', 0))],
                [false, new \Exception()],
            ];
        }
        foreach ($dataset as $n => $data) {
            $this->assertEquals($data[0], $cplatform->isRetryableException($data[1]), "$n is failed.");
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_convertUpdateQuery($cplatform, $platform)
    {
        $builder = self::getDummyDatabase()->select('test');
        $this->assertEquals('UPDATE test SET key = ?', $cplatform->convertUpdateQuery($builder, 'key = ?'));

        $builder = self::getDummyDatabase()->select('test T');
        if ($platform instanceof SQLServerPlatform) {
            $this->assertException(new \DomainException('not supported'), L($cplatform)->convertUpdateQuery($builder, 'key = ?'));
        }
        else {
            $this->assertEquals('UPDATE test T SET key = ?', $cplatform->convertUpdateQuery($builder, 'key = ?'));
        }

        $builder = self::getDummyDatabase()->select('foreign_c1 C, foreign_p');
        if ($platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform || $platform instanceof MySqlPlatform) {
            $this->assertEquals('UPDATE foreign_c1 C, foreign_p SET key = ?', $cplatform->convertUpdateQuery($builder, 'key = ?'));
        }
        elseif ($platform instanceof SQLServerPlatform) {
            $this->assertEquals('UPDATE C SET key = ? FROM foreign_c1 C, foreign_p', $cplatform->convertUpdateQuery($builder, 'key = ?'));
        }
        else {
            $this->assertException(new \DomainException('not supported'), L($cplatform)->convertUpdateQuery($builder, 'key = ?'));
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_convertDeleteQuery($cplatform, $platform)
    {
        $builder = self::getDummyDatabase()->select('test');
        $this->assertEquals('DELETE FROM test', $cplatform->convertDeleteQuery($builder, []));

        $builder = self::getDummyDatabase()->select('test T');
        if ($platform instanceof MySqlPlatform || $platform instanceof SQLServerPlatform) {
            $this->assertEquals('DELETE T FROM test T', $cplatform->convertDeleteQuery($builder, []));
        }
        else {
            $this->assertEquals('DELETE FROM test T', $cplatform->convertDeleteQuery($builder, []));
        }

        $builder = self::getDummyDatabase()->select('foreign_c1 C, foreign_p');
        if ($platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform || $platform instanceof MySqlPlatform) {
            $this->assertEquals('DELETE C FROM foreign_c1 C, foreign_p', $cplatform->convertDeleteQuery($builder, []));
        }
        elseif ($platform instanceof SQLServerPlatform) {
            $this->assertEquals('DELETE C FROM foreign_c1 C, foreign_p', $cplatform->convertDeleteQuery($builder, []));
        }
        else {
            $this->assertException(new \DomainException('not supported'), L($cplatform)->convertDeleteQuery($builder, []));
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_convertDeleteQuery_multi($cplatform, $platform)
    {
        $builder = self::getDummyDatabase()->select('foreign_c1 C, foreign_p P');
        if ($platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform || $platform instanceof MySqlPlatform) {
            $this->assertEquals('DELETE C, P FROM foreign_c1 C, foreign_p P', $cplatform->convertDeleteQuery($builder, ['C', 'P']));
        }
        else {
            $this->assertException(new \DomainException('not supported'), L($cplatform)->convertDeleteQuery($builder, ['C', 'P']));
        }
    }

    function assertExpression(Expression $expr, $expectedQuery, array $expectedparams)
    {
        $this->assertEquals($expectedQuery, "$expr");
        $this->assertEquals($expectedparams, $expr->getParams());
    }
}
