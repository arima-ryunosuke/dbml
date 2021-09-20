<?php

namespace ryunosuke\Test\dbml\Query\Expression;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use ryunosuke\dbml\Metadata\CompatiblePlatform;
use ryunosuke\dbml\Query\Expression\Operator;

class OperatorTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    private static $platform;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$platform = new CompatiblePlatform(new SqlitePlatform);

        Operator::define('MATCH', function ($operand1, $operand2) {
            return [
                'MATCH (' . implode(',', (array) $operand1) . ') AGAINST (?)' => $operand2,
            ];
        });
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        Operator::define('MATCH', null);
    }

    function test___callStatic()
    {
        $actual = Operator::BETWEEN('columnName', [1, 2]);
        $this->assertEquals('columnName BETWEEN ? AND ?', $actual);
        $this->assertEquals([1, 2], $actual->getParams());

        /** @noinspection PhpUndefinedMethodInspection */
        $actual = Operator::MATCH('hoge');
        $actual->lazy('columnName');
        $this->assertEquals('MATCH (columnName) AGAINST (?)', $actual);
        $this->assertEquals(['hoge'], $actual->getParams());

        $actual = Operator::equal('hoge');
        $actual->lazy('columnName');
        $this->assertEquals('columnName = ?', $actual);
        $this->assertEquals(['hoge'], $actual->getParams());

        $this->assertException(new \InvalidArgumentException('length must be greater than 2'), function () {
            /** @noinspection PhpUndefinedMethodInspection */
            Operator::hoge('columnName');
        });
    }

    function test___constructor()
    {
        $operator = new Operator(self::$platform, '!IN', 'hoge', [1, 2, 3]);
        $this->assertOperator('NOT (hoge IN (?,?,?))', [1, 2, 3], $operator);
    }

    function test_op_empty()
    {
        $operator = new Operator(self::$platform, '', 'a', 1);
        $this->assertOperator('a = ?', [1], $operator);
        $this->assertOperator('NOT (a = ?)', [1], $operator->not());

        $operator = new Operator(self::$platform, '', 'a', [1, 2]);
        $this->assertOperator('a IN (?,?)', [1, 2], $operator);
        $this->assertOperator('NOT (a IN (?,?))', [1, 2], $operator->not());

        $operator = new Operator(self::$platform, '', 'a', null);
        $this->assertOperator('a IS NULL', [], $operator);
        $this->assertOperator('NOT (a IS NULL)', [], $operator->not());
    }

    function test_op_default()
    {
        $operator = new Operator(self::$platform, 'def', 'a', 'a');
        $this->assertOperator('a DEF ?', ['a'], $operator);
        $this->assertOperator('NOT (a DEF ?)', ['a'], $operator->not());
        $this->assertOperator('(a,b) DEF', [], new Operator(self::$platform, 'def', '(a,b)', []));
        $this->assertOperator('(a,b) DEF (?,?)', [1, 2], new Operator(self::$platform, 'def', '(a,b)', [1, 2]));
    }

    function test_op_colval()
    {
        // 指定なし＋配列
        $operator = new Operator(self::$platform, Operator::COLVAL, 'hoge', [1, 2, 3]);
        $this->assertOperator('hoge IN (?,?,?)', [1, 2, 3], $operator);

        // eq
        $operator = new Operator(self::$platform, Operator::COLVAL, 'hoge', 'x');
        $this->assertOperator('hoge = ?', ['x'], $operator);

        // NULL
        $operator = new Operator(self::$platform, Operator::COLVAL, 'hoge', null);
        $this->assertOperator('hoge IS NULL', [], $operator);

        // NULL (IN)
        $operator = new Operator(self::$platform, Operator::COLVAL, 'hoge', []);
        $this->assertOperator('hoge IN (NULL)', [], $operator);

        // NULLIN
        $operator = new Operator(self::$platform, Operator::COLVAL, 'hoge', [1, null]);
        $this->assertOperator('hoge IN (?,?) OR hoge IS NULL', [1, null], $operator);

        // 行値式
        $operator = new Operator(self::$platform, Operator::COLVAL, '(hoge, fuga)', [[1, 2], [3, 4]]);
        $this->assertOperator('(hoge, fuga) IN ((?,?),(?,?))', [1, 2, 3, 4], $operator);
    }

    function test_op_raw()
    {
        // 複条件1
        $operator = new Operator(self::$platform, Operator::RAW, 'hoge = ? AND (fuga = ? OR piyo = ?)', [1, 2, 3]);
        $this->assertOperator('hoge = ? AND (fuga = ? OR piyo = ?)', [1, 2, 3], $operator);

        // 複条件2
        $operator = new Operator(self::$platform, Operator::RAW, 'hoge = ? AND (fuga IN (?) OR piyo IN (?))', [1, [2, 3], [4, 5, 6]]);
        $this->assertOperator('hoge = ? AND (fuga IN (?,?) OR piyo IN (?,?,?))', [1, 2, 3, 4, 5, 6], $operator);

        $this->assertException('notfound search string', function () {
            (new Operator(self::$platform, Operator::RAW, 'hoge = ?', [[1, 2, 3], 4]))->getQuery();
        });
    }

    function test_op_spaceship()
    {
        $operator = new Operator(self::$platform, '<=>', 'a', [99]);
        $this->assertOperator('a IS NULL OR a = ?', [99], $operator);
        $this->assertOperator('NOT (a IS NULL OR a = ?)', [99], $operator->not());
        $this->assertException('contains 1 elements', L(new Operator(self::$platform, '<=>', 'a', []))->getQuery());
    }

    function test_op_is_null()
    {
        $operator = new Operator(self::$platform, 'IS NULL', 'a', []);
        $this->assertOperator('a IS NULL', [], $operator);
        $this->assertOperator('NOT (a IS NULL)', [], $operator->not());
    }

    function test_op_between()
    {
        $operator = new Operator(self::$platform, 'BETWEEN', 'a', [1, 2]);
        $this->assertOperator('a BETWEEN ? AND ?', [1, 2], $operator);
        $this->assertOperator('NOT (a BETWEEN ? AND ?)', [1, 2], $operator->not());
        $this->assertException('contains 2 elements', L(new Operator(self::$platform, 'BETWEEN', 'a', 1))->getQuery());
    }

    function test_op_like()
    {
        $operator = new Operator(self::$platform, 'LIKE%', 'a', 'w');
        $this->assertOperator('a LIKE ?', ['w%'], $operator);

        $operator = new Operator(self::$platform, '%LIKE', 'a', 'w');
        $this->assertOperator('a LIKE ?', ['%w'], $operator);

        $operator = new Operator(self::$platform, '%LIKE%', 'a', 'w');
        $this->assertOperator('a LIKE ?', ['%w%'], $operator);

        $operator = new Operator(self::$platform, '%LIKE%', 'a', 'a%_z');
        $this->assertOperator('a LIKE ?', ['%a\\%\\_z%'], $operator);
    }

    function test_op_likein()
    {
        $operator = new Operator(self::$platform, 'LIKEIN%', 'a', 'x');
        $this->assertOperator('a LIKE ?', ['x%'], $operator);

        $operator = new Operator(self::$platform, '%LIKEIN', 'a', ['x']);
        $this->assertOperator('a LIKE ?', ['%x'], $operator);

        $operator = new Operator(self::$platform, '%LIKEIN%', 'a', ['x', 'y']);
        $this->assertOperator('a LIKE ? OR a LIKE ?', ['%x%', '%y%'], $operator);

        $operator = new Operator(self::$platform, '%LIKEIN%', 'a', ['a%z', 'a_z']);
        $this->assertOperator('a LIKE ? OR a LIKE ?', ['%a\\%z%', '%a\\_z%'], $operator);
    }

    function test_op_phrase()
    {
        $operator = new Operator(self::$platform, 'PHRASE', 'a', '-x -y');
        $this->assertOperator('(NOT (a LIKE ?)) AND (NOT (a LIKE ?))', ['%x%', '%y%'], $operator);

        $operator = new Operator(self::$platform, 'PHRASE', 'a', 'x y');
        $this->assertOperator('(a LIKE ?) AND (a LIKE ?)', ['%x%', '%y%'], $operator);

        $operator = new Operator(self::$platform, 'PHRASE', 'a', 'x|z');
        $this->assertOperator('(a LIKE ?) OR (a LIKE ?)', ['%x%', '%z%'], $operator);

        $operator = new Operator(self::$platform, 'PHRASE', 'a', 'x, y');
        $this->assertOperator('(a LIKE ?) OR (a LIKE ?)', ['%x%', '%y%'], $operator);

        $operator = new Operator(self::$platform, 'PHRASE', 'a', '"x|y, z"');
        $this->assertOperator('a LIKE ?', ['%x|y,%z%'], $operator);

        $operator = new Operator(self::$platform, 'PHRASE', 'a', '-x1 y1, x2|y2 z, "x3|y3, z"');
        $this->assertOperator('((NOT (a LIKE ?)) AND (a LIKE ?)) OR (((a LIKE ?) OR (a LIKE ?)) AND (a LIKE ?)) OR (a LIKE ?)', [
            '%x1%',
            '%y1%',
            '%x2%',
            '%y2%',
            '%z%',
            '%x3|y3,%z%',
        ], $operator);
    }

    function test_op_in()
    {
        $operator = new Operator(self::$platform, 'IN', 'a', [1, 2]);
        $this->assertOperator('a IN (?,?)', [1, 2], $operator);
        $this->assertOperator('NOT (a IN (?,?))', [1, 2], $operator->not());
        $this->assertOperator('a IN (NULL)', [], new Operator(self::$platform, 'IN', 'a', []));
    }

    function test_op_nullin()
    {
        $operator = new Operator(self::$platform, 'NULLIN', 'a', [1, 2, null]);
        $this->assertOperator('a IN (?,?,?) OR a IS NULL', [1, 2, null], $operator);
        $this->assertOperator('NOT (a IN (?,?,?) OR a IS NULL)', [1, 2, null], $operator->not());
        $this->assertOperator('a IN (?,?)', [1, 2], new Operator(self::$platform, 'IN', 'a', [1, 2]));
        $this->assertOperator('a IN (NULL)', [], new Operator(self::$platform, 'IN', 'a', []));
    }

    function test_op_range()
    {
        $ops = [
            Operator::OP_RANGE         => ['>', '<'],
            Operator::OP_RANGE_LTE     => ['>=', '<'],
            Operator::OP_RANGE_GTE     => ['>', '<='],
            Operator::OP_RANGE_BETWEEN => ['>=', '<='],
        ];
        foreach ($ops as $ope => $op12) {
            [$ope1, $ope2] = $op12;
            $operator = new Operator(self::$platform, $ope, 'a', [1, 2]);
            $this->assertOperator("a $ope1 ? AND a $ope2 ?", [1, 2], $operator);
            $this->assertOperator("NOT (a $ope1 ? AND a $ope2 ?)", [1, 2], $operator->not());

            $operator = new Operator(self::$platform, $ope, 'a', [1, null]);
            $this->assertOperator("a $ope1 ?", [1], $operator);
            $this->assertOperator("NOT (a $ope1 ?)", [1], $operator->not());

            $operator = new Operator(self::$platform, $ope, 'a', [-1, 0]);
            $this->assertOperator("a $ope1 ? AND a $ope2 ?", [-1, 0], $operator);
            $this->assertOperator("NOT (a $ope1 ? AND a $ope2 ?)", [-1, 0], $operator->not());

            $operator = new Operator(self::$platform, $ope, 'a', ['', 2]);
            $this->assertOperator("a $ope2 ?", [2], $operator);
            $this->assertOperator("NOT (a $ope2 ?)", [2], $operator->not());

            $operator = new Operator(self::$platform, $ope, 'a', [null, '']);
            $this->assertOperator("", [], $operator);
            $this->assertOperator("", [], $operator->not());

            $operator = new Operator(self::$platform, $ope, '(a, b)', [[1, 2], [3, 4]]);
            $this->assertOperator("(a, b) $ope1 (?,?) AND (a, b) $ope2 (?,?)", [1, 2, 3, 4], $operator);
            $this->assertOperator("NOT ((a, b) $ope1 (?,?) AND (a, b) $ope2 (?,?))", [1, 2, 3, 4], $operator->not());

            $operator = new Operator(self::$platform, $ope, '(a, b)', [[1, 2], []]);
            $this->assertOperator("(a, b) $ope1 (?,?)", [1, 2], $operator);
            $this->assertOperator("NOT ((a, b) $ope1 (?,?))", [1, 2], $operator->not());

            $operator = new Operator(self::$platform, $ope, '(a, b)', [null, [3, 4]]);
            $this->assertOperator("(a, b) $ope2 (?,?)", [3, 4], $operator);
            $this->assertOperator("NOT ((a, b) $ope2 (?,?))", [3, 4], $operator->not());

            $this->assertException('array length 2', L(new Operator(self::$platform, $ope, 'a', [1]))->getQuery());
        }
    }

    function test_op_defined()
    {
        $operator = new Operator(self::$platform, 'MATCH', 'a', 'word');
        $this->assertOperator('MATCH (a) AGAINST (?)', ['word'], $operator);
        $this->assertOperator("NOT (MATCH (a) AGAINST (?))", ['word'], $operator->not());
    }

    function test_op_override()
    {
        Operator::define('=', function () { return ['hogera' => []]; });
        $operator = new Operator(self::$platform, '=', 'a', 1);
        $this->assertOperator('hogera', [], $operator);
        $this->assertOperator("NOT (hogera)", [], $operator->not());
        Operator::define('=', null);
    }

    function test_merge()
    {
        $operator = new Operator(self::$platform, '=', 'a', 1);
        $this->assertEquals('a = ?', $operator->merge($params));
        $this->assertEquals([1], $params);
    }

    function assertOperator($string, $params, Operator $operator)
    {
        // カバレッジのためであって、分岐自体に意味は無い
        if ($params) {
            $this->assertEquals($string, (string) $operator);
            $this->assertEquals($params, $operator->getParams());
        }
        else {
            $this->assertEquals($params, $operator->getParams());
            $this->assertEquals($string, (string) $operator);
        }
    }
}
