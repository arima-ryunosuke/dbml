<?php

namespace ryunosuke\Test\dbml\Metadata;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use ryunosuke\dbml\Metadata\CompatiblePlatform;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Queryable;
use function ryunosuke\dbml\date_convert;

class CompatiblePlatformTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * dbml がサポートする platform を全て提供
     */
    public static function providePlatform()
    {
        $platforms = [
            'sqlite'      => [new SqlitePlatform(), "3.31.1"],
            'mysql8.0.18' => [new MySQLPlatform(), "8.0.18"],
            'mysql8.0.19' => [new MySQLPlatform(), "8.0.19"],
            'postgresql'  => [new PostgreSQLPlatform(), "15.4.1"],
            'sqlserver'   => [new SQLServerPlatform(), "14.00.3460"],
            'sqlserver16' => [new SQLServerPlatform(), "16.0"],
            'oracle'      => [new OraclePlatform(), "1.0"],
        ];
        return array_map(function ($v) {
            return [
                new CompatiblePlatform(...$v),
                $v[0],
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
    function test_setOptions($cplatform, $platform)
    {
        $current = $cplatform->setOptions([
            'mysql.reference' => null,
        ]);

        $this->assertEquals([
            "mysql.reference" => "excluded",
        ], $current);

        if ($platform instanceof MySQLPlatform) {
            $this->assertEquals('VALUES(name)', $cplatform->getReferenceSyntax('name'));
        }

        $current = $cplatform->setOptions($current);

        $this->assertEquals([
            "mysql.reference" => null,
        ], $current);
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
    function test_getName($cplatform, $platform)
    {
        $expected = 'oracle';
        if ($platform instanceof SqlitePlatform) {
            $expected = 'sqlite';
        }
        if ($platform instanceof MySQLPlatform) {
            $expected = 'mysql';
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = 'postgresql';
        }
        if ($platform instanceof SQLServerPlatform) {
            $expected = 'mssql';
        }
        $this->assertEquals($expected, $cplatform->getName());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsAbortTransaction($cplatform, $platform)
    {
        $expected = $platform instanceof SqlitePlatform || $platform instanceof PostgreSQLPlatform || $platform instanceof SQLServerPlatform;
        $this->assertEquals($expected, $cplatform->supportsAbortTransaction());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsIdentityNullable($cplatform, $platform)
    {
        $expected = $platform instanceof SqlitePlatform || $platform instanceof MySQLPlatform;
        $this->assertEquals($expected, $cplatform->supportsIdentityNullable());
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
    function test_supportsIdentityAutoUpdate($cplatform, $platform)
    {
        $expected = !($platform instanceof PostgreSQLPlatform || $platform instanceof OraclePlatform);
        $this->assertEquals($expected, $cplatform->supportsIdentityAutoUpdate());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsInsertSet($cplatform, $platform)
    {
        $expected = $platform instanceof MySQLPlatform;
        $this->assertEquals($expected, $cplatform->supportsInsertSet());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsReplace($cplatform, $platform)
    {
        $expected = $platform instanceof SqlitePlatform || $platform instanceof MySQLPlatform;
        $this->assertEquals($expected, $cplatform->supportsReplace());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsMerge($cplatform, $platform)
    {
        $expected = $platform instanceof SqlitePlatform || $platform instanceof MySQLPlatform || $platform instanceof PostgreSQLPlatform;
        $this->assertEquals($expected, $cplatform->supportsMerge());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsBulkMerge($cplatform, $platform)
    {
        $expected = $platform instanceof SqlitePlatform || $platform instanceof MySQLPlatform || $platform instanceof PostgreSQLPlatform;
        $this->assertEquals($expected, $cplatform->supportsBulkMerge());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsIgnore($cplatform, $platform)
    {
        $expected = $platform instanceof SqlitePlatform || $platform instanceof MySQLPlatform;
        $this->assertEquals($expected, $cplatform->supportsIgnore());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsUpdateLimit($cplatform, $platform)
    {
        $expected = $platform instanceof MySQLPlatform || $platform instanceof SQLServerPlatform;
        $this->assertEquals($expected, $cplatform->supportsUpdateLimit());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsDeleteLimit($cplatform, $platform)
    {
        $expected = $platform instanceof MySQLPlatform || $platform instanceof SQLServerPlatform;
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
    function test_supportsRowConstructor($cplatform, $platform)
    {
        $expected = true;
        if ($platform instanceof SQLServerPlatform) {
            $expected = false;
        }
        elseif ($platform instanceof SqlitePlatform) {
            $expected = false;
        }
        $this->assertEquals($expected, $cplatform->supportsRowConstructor());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsCompatibleCharAndBinary($cplatform, $platform)
    {
        $expected = true;
        if ($platform instanceof SQLServerPlatform) {
            $expected = false;
        }
        $this->assertEquals($expected, $cplatform->supportsCompatibleCharAndBinary());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_supportsRedundantOrderBy($cplatform, $platform)
    {
        $expected = true;
        if ($platform instanceof SQLServerPlatform) {
            $expected = false;
        }
        $this->assertEquals($expected, $cplatform->supportsRedundantOrderBy());
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

        if ($platform instanceof PostgreSQLPlatform) {
            $this->assertEquals('aaa', $cplatform->quoteIdentifierIfNeeded('aaa'));
            $this->assertEquals($platform->quoteSingleIdentifier('AAA'), $cplatform->quoteIdentifierIfNeeded('AAA'));
        }
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

        $expected = $platform instanceof SQLServerPlatform ? 'w\\%o\\_r\\[d' : 'w\\%o\\_r[d';
        $this->assertEquals($expected, $cplatform->escapeLike('w%o_r[d'));

        $this->assertEquals('m%m', $cplatform->escapeLike(new Expression('m%m')));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_truncateString($cplatform, $platform)
    {
        $cstring = new Column('dummy', Type::getType('string'), ['length' => 4]);
        $cbinary = new Column('dummy', Type::getType('binary'), ['length' => 12]);
        $ctext = new Column('dummy', Type::getType('text'), ['length' => 12]);
        $cblob = new Column('dummy', Type::getType('blob'), ['length' => 12]);

        $expected = $platform instanceof MySQLPlatform ? 'あいうえ' : 'あいうえお';
        $this->assertEquals('あいうえお', $cplatform->truncateString('あいうえお', new Column('dummy', Type::getType('string'))));
        $this->assertEquals('あいうえお', $cplatform->truncateString('あいうえお', new Column('dummy', Type::getType('binary'))));
        $this->assertEquals('あいうえお', $cplatform->truncateString('あいうえお', $cstring));
        $this->assertEquals($expected, $cplatform->truncateString('あいうえお', $cstring->setPlatformOption('charset', 'utf8mb3')));
        $this->assertEquals($expected, $cplatform->truncateString('あいうえお', $cbinary));
        $this->assertEquals($expected, $cplatform->truncateString('あいうえお', $ctext));
        $this->assertEquals($expected, $cplatform->truncateString('あいうえお', $cblob));

        if ($platform instanceof MySQLPlatform) {
            that($cplatform)->truncateString('dummy', new Column('dummy', Type::getType('integer')))->wasThrown('integer is not supported');
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getMergeSyntax($cplatform, $platform)
    {
        $expected = false;
        if ($platform instanceof SqlitePlatform) {
            $expected = 'ON CONFLICT(col) DO UPDATE SET';
        }
        if ($platform instanceof MySQLPlatform) {
            if (version_compare($cplatform->getVersion(), '8.0.19') >= 0) {
                $expected = 'AS excluded ON DUPLICATE KEY UPDATE';
            }
            else {
                $expected = 'ON DUPLICATE KEY UPDATE';
            }
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = 'ON CONFLICT(col) DO UPDATE SET';
        }
        $this->assertEquals($expected, $cplatform->getMergeSyntax(['col']));

        $expected = false;
        if ($platform instanceof SqlitePlatform) {
            $expected = 'ON CONFLICT(col1,col2) DO UPDATE SET';
        }
        if ($platform instanceof MySQLPlatform) {
            if (version_compare($cplatform->getVersion(), '8.0.19') >= 0) {
                $expected = 'AS excluded ON DUPLICATE KEY UPDATE';
            }
            else {
                $expected = 'ON DUPLICATE KEY UPDATE';
            }
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = 'ON CONFLICT(col1,col2) DO UPDATE SET';
        }
        $this->assertEquals($expected, $cplatform->getMergeSyntax(['col1', 'col2']));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getReferenceSyntax($cplatform, $platform)
    {
        $expected = false;
        if ($platform instanceof SqlitePlatform) {
            $expected = 'excluded.name';
        }
        if ($platform instanceof MySQLPlatform) {
            if (version_compare($cplatform->getVersion(), '8.0.19') >= 0) {
                $expected = 'excluded.name';
            }
            else {
                $expected = 'VALUES(name)';
            }
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = 'EXCLUDED.name';
        }
        $this->assertEquals($expected, $cplatform->getReferenceSyntax('name'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getInsertSelectSyntax($cplatform, $platform)
    {
        $expected = 'SELECT 1, hoge WHERE 1 AND 2';
        if ($platform instanceof MySQLPlatform) {
            if (version_compare($cplatform->getVersion(), '8.0.19') >= 0) {
                $expected = 'SELECT * FROM (SELECT 1 AS id, hoge AS name WHERE 1 AND 2)';
            }
        }

        $this->assertEquals($expected, $cplatform->getInsertSelectSyntax(['id' => 1, 'name' => 'hoge'], '1 AND 2'));
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
            $this->assertStringContainsString($expected, $cplatform->getIdentityInsertSQL('t_table', true));
        }
        else {
            that($cplatform)->getIdentityInsertSQL('t_table', true)->wasThrown('is not supported');
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getTruncateTableSQL($cplatform, $platform)
    {
        $expected = $platform instanceof PostgreSQLPlatform ? 'RESTART IDENTITY' : 't_table';
        $this->assertStringContainsString($expected, $cplatform->getTruncateTableSQL('t_table'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getIdentitySequenceName($cplatform, $platform)
    {
        $expected = $platform instanceof PostgreSQLPlatform ? 'table_id_seq' : null;
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
        if ($platform instanceof MySQLPlatform) {
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
        if ($platform instanceof SqlitePlatform) {
            $expected = 'select * from t_table /* lock for read */';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_READ, ''));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));

            $expected = 'select * from t_table /* lock for write */ hoge';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_WRITE, 'hoge'));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));
        }
        if ($platform instanceof MySQLPlatform) {
            $expected = 'select * from t_table LOCK IN SHARE MODE';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_READ, ''));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));

            $expected = 'select * from t_table FOR SHARE hoge';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_READ, 'hoge'));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));

            $expected = 'select * from t_table FOR UPDATE hoge';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_WRITE, 'hoge'));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = 'select * from t_table FOR SHARE';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_READ, ''));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));

            $expected = 'select * from t_table FOR UPDATE hoge';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_WRITE, 'hoge'));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));
        }
        if ($platform instanceof SQLServerPlatform) {
            $expected = 'select * from t_table';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_READ, ''));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));

            $expected = 'select * from t_table';
            $this->assertEquals($expected, $cplatform->appendLockSuffix('select * from t_table', LockMode::PESSIMISTIC_WRITE, 'hoge'));
            $this->assertEquals('t_table', $cplatform->appendLockSuffix('t_table', LockMode::NONE, ''));
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getSpaceshipSyntaxExpression($cplatform, $platform)
    {
        $expected = null;
        if ($platform instanceof SqlitePlatform) {
            $expected = 'hoge IS ?';
        }
        if ($platform instanceof MySQLPlatform) {
            $expected = 'hoge <=> ?';
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = 'hoge IS NOT DISTINCT FROM ?';
        }
        if ($platform instanceof SQLServerPlatform && version_compare($cplatform->getVersion(), "16") >= 0) {
            $expected = 'hoge IS NOT DISTINCT FROM ?';
        }

        if ($expected === null) {
            $this->assertExpression($cplatform->getSpaceshipExpression('hoge', 1), '(hoge IS NULL AND ? IS NULL) OR hoge = ?', [1, 1]);
        }
        else {
            $this->assertExpression($cplatform->getSpaceshipExpression('hoge', 1), $expected, [1]);
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getDateTimeTzFormats($cplatform, $platform)
    {
        $expected = '2009-02-14 08:31:30.123456 +09:00';
        if ($platform instanceof SqlitePlatform) {
            $expected = '2009-02-14 08:31:30';
        }
        if ($platform instanceof MySQLPlatform) {
            $expected = '2009-02-14 08:31:30.123456';
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = '2009-02-14 08:31:30.123456 +0900';
        }
        if ($platform instanceof SQLServerPlatform) {
            $expected = '2009-02-14 08:31:30.123456 +09:00';
        }

        $formats = array_filter($cplatform->getDateTimeTzFormats(), 'strlen');
        $datestring = date_convert(implode(' ', $formats), 1234567890.123456);
        $this->assertEquals($expected, $datestring);
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

        $actual = $cplatform->getPrimaryCondition([['id' => new Expression('?', 1), 'seq' => 2]]);
        $this->assertExpression($actual, '(id = ? AND seq = ?)', [1, 2]);

        if ($cplatform->supportsRowConstructor()) {
            $actual = $cplatform->getPrimaryCondition([['id' => 1, 'seq' => 2], ['id' => 3, 'seq' => 4]]);
            $this->assertExpression($actual, '(id, seq) IN ((?, ?), (?, ?))', [1, 2, 3, 4]);

            $actual = $cplatform->getPrimaryCondition([['id' => 1, 'seq' => 2], ['id' => 3, 'seq' => 4]], 'prefix');
            $this->assertExpression($actual, '(prefix.id, prefix.seq) IN ((?, ?), (?, ?))', [1, 2, 3, 4]);

            $actual = $cplatform->getPrimaryCondition([['id' => new Expression('?', 1), 'seq' => 2], ['id' => 3, 'seq' => new Expression('?', 4)]]);
            $this->assertExpression($actual, '(id, seq) IN ((?, ?), (?, ?))', [1, 2, 3, 4]);
        }
        else {
            $actual = $cplatform->getPrimaryCondition([['id' => 1, 'seq' => 2], ['id' => 3, 'seq' => 4]]);
            $this->assertExpression($actual, '(id = ? AND seq = ?) OR (id = ? AND seq = ?)', [1, 2, 3, 4]);

            $actual = $cplatform->getPrimaryCondition([['id' => 1, 'seq' => 2], ['id' => 3, 'seq' => 4]], 'prefix');
            $this->assertExpression($actual, '(prefix.id = ? AND prefix.seq = ?) OR (prefix.id = ? AND prefix.seq = ?)', [1, 2, 3, 4]);

            $actual = $cplatform->getPrimaryCondition([['id' => new Expression('?', 1), 'seq' => 2], ['id' => 3, 'seq' => new Expression('?', 4)]]);
            $this->assertExpression($actual, '(id = ? AND seq = ?) OR (id = ? AND seq = ?)', [1, 2, 3, 4]);
        }
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
        if ($platform instanceof MySQLPlatform) {
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
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = "ARRAY_AGG(id)";
            $this->assertEquals($expected, $cplatform->getGroupConcatSyntax('id'));

            $expected = "ARRAY_TO_STRING(ARRAY_AGG(id), '|')";
            $this->assertEquals($expected, $cplatform->getGroupConcatSyntax('id', '|'));
        }
        if ($platform instanceof SQLServerPlatform) {
            that($cplatform)->getGroupConcatSyntax('id')->wasThrown('is not supported');
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getWithRecursiveSyntax($cplatform, $platform)
    {
        $expected = 'WITH';
        if (!$platform instanceof SQLServerPlatform) {
            $expected .= ' RECURSIVE';
        }
        $this->assertEquals($expected, $cplatform->getWithRecursiveSyntax());
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getCountExpression($cplatform, $platform)
    {
        $this->assertEquals("COUNT(hoge)", $cplatform->getCountExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getMinExpression($cplatform, $platform)
    {
        $this->assertEquals("MIN(hoge)", $cplatform->getMinExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getMaxExpression($cplatform, $platform)
    {
        $this->assertEquals("MAX(hoge)", $cplatform->getMaxExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getSumExpression($cplatform, $platform)
    {
        $this->assertEquals("SUM(hoge)", $cplatform->getSumExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getAvgExpression($cplatform, $platform)
    {
        if ($platform instanceof SQLServerPlatform) {
            $expected = 'AVG(CAST(hoge AS float))';
        }
        else {
            $expected = 'AVG(hoge)';
        }
        $this->assertEquals($expected, $cplatform->getAvgExpression('hoge'));
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getJsonObjectExpression($cplatform, $platform)
    {
        $this->trapThrowable('is not supported');

        $jsonObject = $cplatform->getJsonObjectExpression([
            'id'  => 'id',
            'idN' => new Expression('id * ?', 3),
        ]);
        if ($platform instanceof SqlitePlatform) {
            $this->assertExpression($jsonObject, 'JSON_OBJECT(?, id, ?, id * ?)', ['id', 'idN', 3]);
        }
        if ($platform instanceof MySQLPlatform) {
            $this->assertExpression($jsonObject, 'JSON_OBJECT(?, id, ?, id * ?)', ['id', 'idN', 3]);
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $this->assertExpression($jsonObject, 'JSON_BUILD_OBJECT(CAST(? AS TEXT), id, CAST(? AS TEXT), id * ?)', ['id', 'idN', 3]);
        }
        if ($platform instanceof SQLServerPlatform) {
            $this->assertExpression($jsonObject, 'JSON_OBJECT(?: id, ?: id * ?)', ['id', 'idN', 3]);
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getJsonAggExpression($cplatform, $platform)
    {
        $this->trapThrowable('is not supported');

        $jsonObject = $cplatform->getJsonAggExpression([
            'id'  => 'id',
            'idN' => new Expression('id * ?', 3),
        ]);
        if ($platform instanceof SqlitePlatform) {
            $this->assertExpression($jsonObject, 'JSON_GROUP_ARRAY(JSON_OBJECT(?, id, ?, id * ?))', ['id', 'idN', 3]);
        }
        if ($platform instanceof MySQLPlatform) {
            $this->assertExpression($jsonObject, 'JSON_ARRAYAGG(JSON_OBJECT(?, id, ?, id * ?))', ['id', 'idN', 3]);
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $this->assertExpression($jsonObject, 'JSON_AGG(JSON_BUILD_OBJECT(CAST(? AS TEXT), id, CAST(? AS TEXT), id * ?))', ['id', 'idN', 3]);
        }
        if ($platform instanceof SQLServerPlatform) {
            $this->assertExpression($jsonObject, 'JSON_ARRAYAGG(JSON_OBJECT(?: id, ?: id * ?))', ['id', 'idN', 3]);
        }

        $jsonObject = $cplatform->getJsonAggExpression([
            'id'  => 'id',
            'idN' => new Expression('id * ?', 3),
        ], new Expression('id * ?', 9));
        if ($platform instanceof SqlitePlatform) {
            $this->assertExpression($jsonObject, 'JSON_GROUP_OBJECT(id * ?, JSON_OBJECT(?, id, ?, id * ?))', [9, 'id', 'idN', 3]);
        }
        if ($platform instanceof MySQLPlatform) {
            $this->assertExpression($jsonObject, 'JSON_OBJECTAGG(id * ?, JSON_OBJECT(?, id, ?, id * ?))', [9, 'id', 'idN', 3]);
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $this->assertExpression($jsonObject, 'JSON_OBJECT_AGG(CAST(id * ? AS TEXT), JSON_BUILD_OBJECT(CAST(? AS TEXT), id, CAST(? AS TEXT), id * ?))', [9, 'id', 'idN', 3]);
        }
        if ($platform instanceof SQLServerPlatform) {
            $this->assertExpression($jsonObject, 'JSON_OBJECTAGG(id * ?, JSON_OBJECT(?: id, ?: id * ?))', [9, 'id', 'idN', 3]);
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getConcatExpression($cplatform, $platform)
    {
        that($cplatform)->getConcatExpression()->wasThrown('greater than');

        $this->assertExpression($cplatform->getConcatExpression('id'), 'id', []);

        $expected = $platform instanceof SQLServerPlatform ? $platform->getConcatExpression('CAST(id1 as varchar)', 'CAST(id2 as varchar)') : $platform->getConcatExpression('id1', 'id2');
        $this->assertEquals($expected, $cplatform->getConcatExpression('id1', 'id2'));

        if ($platform instanceof SqlitePlatform) {
            $this->assertExpression($cplatform->getConcatExpression(new Expression('?', ['hoge']), 'id2'), '? || id2', ['hoge']);
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getRegexpExpression($cplatform, $platform)
    {
        if ($platform instanceof SqlitePlatform) {
            $this->assertExpression($cplatform->getRegexpExpression('column', '.*'), "column REGEXP ?", ['.*']);
        }
        elseif ($platform instanceof MySQLPlatform) {
            $this->assertExpression($cplatform->getRegexpExpression('column', '.*'), "REGEXP_LIKE(column, ?, 'i')", ['.*']);
        }
        elseif ($platform instanceof PostgreSQLPlatform) {
            $this->assertExpression($cplatform->getRegexpExpression('column', '.*'), "column ~* ?", ['.*']);
        }
        else {
            that($cplatform)->getRegexpExpression('column', '.*')->wasThrown('is not supported');
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getBinaryExpression($cplatform, $platform)
    {
        if ($platform instanceof SQLServerPlatform) {
            $this->assertExpression($cplatform->getBinaryExpression('hoge'), 'CAST(? as VARBINARY(MAX))', ['hoge']);
        }
        else {
            $this->assertExpression($cplatform->getBinaryExpression('hoge'), 'hoge', []);
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getNowExpression($cplatform, $platform)
    {
        if ($platform instanceof SqlitePlatform) {
            $this->assertExpression($cplatform->getNowExpression(0), "strftime('%Y-%m-%d %H:%M:%S', datetime('now', 'localtime'))", []);
            $this->assertExpression($cplatform->getNowExpression(3), "strftime('%Y-%m-%d %H:%M:%f', datetime('now', 'localtime'))", []);
        }
        elseif ($platform instanceof MySQLPlatform) {
            $this->assertExpression($cplatform->getNowExpression(0), "NOW(0)", []);
            $this->assertExpression($cplatform->getNowExpression(3), "NOW(3)", []);
        }
        elseif ($platform instanceof PostgreSQLPlatform) {
            $this->assertExpression($cplatform->getNowExpression(0), "LOCALTIMESTAMP(0)", []);
            $this->assertExpression($cplatform->getNowExpression(3), "LOCALTIMESTAMP(3)", []);
        }
        elseif ($platform instanceof SQLServerPlatform) {
            $this->assertExpression($cplatform->getNowExpression(0), "CAST(FORMAT(GETDATE(), 'yyyy-MM-dd HH:mm:ss') as DATETIME)", []);
            $this->assertExpression($cplatform->getNowExpression(3), "CAST(FORMAT(GETDATE(), 'yyyy-MM-dd HH:mm:ss.fff') as DATETIME)", []);
        }
        else {
            $this->assertExpression($cplatform->getNowExpression(99), 'NOW()', []);
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getSleepExpression($cplatform, $platform)
    {
        if ($platform instanceof MySQLPlatform) {
            $this->assertExpression($cplatform->getSleepExpression(10), 'SLEEP(?)', [10]);
        }
        elseif ($platform instanceof PostgreSQLPlatform) {
            $this->assertExpression($cplatform->getSleepExpression(10), 'pg_sleep(?)', [10]);
        }
        else {
            that($cplatform)->getSleepExpression(99)->wasThrown('is not supported');
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getRandomExpression($cplatform, $platform)
    {
        if ($platform instanceof SqlitePlatform) {
            $this->assertExpression($cplatform->getRandomExpression(null), '(0.5 - RANDOM() / CAST(-9223372036854775808 AS REAL) / 2)', []);
            $this->assertExpression($cplatform->getRandomExpression(1234), '(0.5 - RANDOM() / CAST(-9223372036854775808 AS REAL) / 2)', []);
        }
        elseif ($platform instanceof MySQLPlatform) {
            $this->assertExpression($cplatform->getRandomExpression(null), 'RAND()', []);
            $this->assertExpression($cplatform->getRandomExpression(1234), 'RAND(?)', [1234]);
        }
        elseif ($platform instanceof PostgreSQLPlatform) {
            $this->assertExpression($cplatform->getRandomExpression(null), 'random()', []);
            $this->assertExpression($cplatform->getRandomExpression(1234), 'random()', []);
        }
        elseif ($platform instanceof SQLServerPlatform) {
            $this->assertExpression($cplatform->getRandomExpression(null), 'RAND(CHECKSUM(NEWID()))', []);
            $this->assertExpression($cplatform->getRandomExpression(1234), 'RAND(CHECKSUM(NEWID()))', []);
        }
        else {
            that($cplatform)->getRandomExpression(null)->wasThrown('is not supported');
        }
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

        if (!isset($expected[$cplatform->getName()])) {
            that($cplatform)->getResetSequenceExpression('t_table', 'c_sol', 99)->wasThrown('is not supported');
        }
        else {
            $this->assertEquals($expected[$cplatform->getName()], $cplatform->getResetSequenceExpression('t_table', 'c_sol', 99));
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_getSwitchForeignKeyExpression($cplatform, $platform)
    {
        if ($platform instanceof SqlitePlatform) {
            $this->assertEquals(['PRAGMA foreign_keys = false'], $cplatform->getSwitchForeignKeyExpression(false));
            $this->assertEquals(['PRAGMA foreign_keys = true'], $cplatform->getSwitchForeignKeyExpression(true));
        }
        elseif ($platform instanceof MySQLPlatform) {
            $this->assertEquals(['SET SESSION foreign_key_checks = 0'], $cplatform->getSwitchForeignKeyExpression(false));
            $this->assertEquals(['SET SESSION foreign_key_checks = 1'], $cplatform->getSwitchForeignKeyExpression(true));
        }
        elseif ($platform instanceof PostgreSQLPlatform) {
            $this->assertEquals(['SET CONSTRAINTS fkname DEFERRED'], $cplatform->getSwitchForeignKeyExpression(false, null, 'fkname'));
            $this->assertEquals(['SET CONSTRAINTS fkname IMMEDIATE'], $cplatform->getSwitchForeignKeyExpression(true, null, 'fkname'));
        }
        elseif ($platform instanceof SQLServerPlatform) {
            $this->assertEquals(['ALTER TABLE tablename NOCHECK CONSTRAINT fkname'], $cplatform->getSwitchForeignKeyExpression(false, 'tablename', 'fkname'));
            $this->assertEquals(['ALTER TABLE tablename WITH CHECK CHECK CONSTRAINT fkname'], $cplatform->getSwitchForeignKeyExpression(true, 'tablename', 'fkname'));
            $this->assertEquals(['ALTER TABLE tablename NOCHECK CONSTRAINT ALL'], $cplatform->getSwitchForeignKeyExpression(false, 'tablename'));
            $this->assertEquals(['ALTER TABLE tablename WITH CHECK CHECK CONSTRAINT ALL'], $cplatform->getSwitchForeignKeyExpression(true, 'tablename'));
        }
        else {
            $this->assertEquals([], $cplatform->getSwitchForeignKeyExpression(false));
            $this->assertEquals([], $cplatform->getSwitchForeignKeyExpression(true));
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
        elseif ($platform instanceof MySQLPlatform) {
            $this->assertEquals('IGNORE', $cplatform->getIgnoreSyntax());
        }
        else {
            that($cplatform)->getIgnoreSyntax()->wasThrown('is not supported');
        }
    }

    /**
     * @dataProvider providePlatform
     * @param CompatiblePlatform $cplatform
     * @param AbstractPlatform $platform
     */
    function test_commentize($cplatform, $platform)
    {
        $this->assertEquals("-- hoge fuga \n", $cplatform->commentize("hoge\nfuga"));

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
        if ($platform instanceof SqlitePlatform) {
            $expected = ['id' => new Expression('excluded.id')];
        }
        if ($platform instanceof MySQLPlatform) {
            if (version_compare($cplatform->getVersion(), '8.0.19') >= 0) {
                $expected = ['id' => new Expression('excluded.id')];
            }
            else {
                $expected = ['id' => new Expression('VALUES(id)')];
            }
        }
        if ($platform instanceof PostgreSQLPlatform) {
            $expected = ['id' => new Expression('EXCLUDED.id')];
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

    function assertExpression(Queryable $expr, $expectedQuery, array $expectedparams)
    {
        $this->assertEquals($expectedQuery, (string) $expr);
        $this->assertEquals($expectedparams, $expr->getParams());
    }
}
