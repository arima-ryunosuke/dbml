<?php

namespace ryunosuke\Test\dbml\Query;

use ryunosuke\dbml\Query\Parser;

class ParserTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_raiseMismatchParameter()
    {
        $errors = [];
        set_error_handler(function ($no, $message) use (&$errors) {
            $errors[] = $message;
        });
        Parser::raiseMismatchParameter(123, Parser::ERROR_MODE_SILENT);
        Parser::raiseMismatchParameter(123, Parser::ERROR_MODE_WARNING);
        restore_error_handler();

        $this->assertCount(1, $errors);
        $this->assertEquals("parameter 123 does not have a bound value.", $errors[0]);
    }

    function test_convertPartialSQL()
    {
        $parser = new Parser(false);

        $this->assertEquals([], $parser->convertPartialSQL(''));
        $this->assertEquals(['?', ', ', '?'], $parser->convertPartialSQL('?, ?'));
        $this->assertEquals(['?', ', ', '?', ', ', ':named'], $parser->convertPartialSQL('?, ?, :named'));
        $this->assertEquals(['select sleep(', '?', '), ', ':named1', ' * ', ':named2', ', hoge'], $parser->convertPartialSQL('select sleep(?), :named1 * :named2, hoge'));
    }

    function test_convertNamedSQL()
    {
        $parser = new Parser(false);

        $params = [9 => 1, 'named' => 2, 3];
        $sql = $parser->convertNamedSQL('select ?, :named, :named, ?', $params);
        $this->assertEquals("select :__dbml_auto_bind0, :named, :named, :__dbml_auto_bind1", $sql);
        $this->assertEquals([
            "__dbml_auto_bind0" => 1,
            "named"             => 2,
            "__dbml_auto_bind1" => 3,
        ], $params);
        $this->assertEquals('select 1, 2, 2, 3', $parser->convertQuotedSQL($sql, $params, fn($v) => $v));

        $params = ['named' => 1, 'named2' => 2, 3, 4];
        $sql = $parser->convertNamedSQL('select ?, :named, :named2, ?, :named9', $params);
        $this->assertEquals("select :__dbml_auto_bind0, :named, :named2, :__dbml_auto_bind1, :named9", $sql);
        $this->assertEquals([
            "__dbml_auto_bind0" => 3,
            "named"             => 1,
            "named2"            => 2,
            "__dbml_auto_bind1" => 4,
        ], $params);
        $this->assertEquals('select 3, 1, 2, 4, 9', $parser->convertQuotedSQL($sql, $params + ['named9' => 9], fn($v) => $v));

        $params = [1];
        $this->assertException('does not have', L($parser)->convertNamedSQL('select ?, :named, :named, ?', $params));

        $params = [1, 2, 3];
        $this->assertException('length is long', L($parser)->convertNamedSQL('select ?, :named, :named, ?', $params));
    }

    function test_convertPositionalSQL()
    {
        $parser = new Parser(false);

        $params = [9 => 1, 'named' => 2, 3];
        $sql = $parser->convertPositionalSQL('select ?, :named, :named, ?', $params, $map);
        $this->assertEquals("select ?, ?, ?, ?", $sql);
        $this->assertEquals([
            1,
            2,
            2,
            3,
        ], $params);
        $this->assertEquals([
            0,
            'named',
            'named',
            1,
        ], $map);
        $this->assertEquals('select 1, 2, 2, 3', $parser->convertQuotedSQL($sql, $params, fn($v) => $v));

        $params = ['named' => 1, 'named2' => 2, 3, 4];
        $sql = $parser->convertPositionalSQL('select ?, :named, :named2, ?, ?', $params, $map);
        $this->assertEquals("select ?, ?, ?, ?, ?", $sql);
        $this->assertEquals([
            3,
            1,
            2,
            4,
        ], $params);
        $this->assertEquals([
            0,
            'named',
            'named2',
            1,
            2,
        ], $map);
        $this->assertEquals('select 3, 1, 2, 4, 9', $parser->convertQuotedSQL($sql, $params + [4 => 9], fn($v) => $v));

        $params = ['named9' => 9];
        $this->assertException('does not have', L($parser)->convertPositionalSQL('select ?, :named, :named, ?', $params));

        $params = ['named' => 1, 'named9' => 9];
        $this->assertException('length is long', L($parser)->convertPositionalSQL('select ?, :named, :named, ?', $params));
    }

    function test_convertDollarSQL()
    {
        $parser = new Parser(false);

        $params = [9 => 1, 'named' => 2, 3];
        $sql = $parser->convertDollarSQL('select ?, :named, :named, ?', $params, $map);
        $this->assertEquals("select $1, $2, $3, $4", $sql);
        $this->assertEquals([
            1,
            2,
            2,
            3,
        ], $params);
        $this->assertEquals([
            0,
            'named',
            'named',
            1,
        ], $map);

        $params = ['named' => 1, 'named2' => 2, 3, 4];
        $sql = $parser->convertDollarSQL('select ?, :named, :named2, ?', $params, $map);
        $this->assertEquals("select $1, $2, $3, $4", $sql);
        $this->assertEquals([
            3,
            1,
            2,
            4,
        ], $params);
        $this->assertEquals([
            0,
            'named',
            'named2',
            1,
        ], $map);
    }

    function test_convertQuotedSQL()
    {
        $parser = new Parser(false, Parser::ERROR_MODE_SILENT);
        $sql = $parser->convertQuotedSQL('?, ?, :named', [1], fn($v) => $v);
        $this->assertEquals("1, undefined(1), undefined(named)", $sql);

        $parser = new Parser(false);

        $sql = $parser->convertQuotedSQL('select ?, :named, :named, ?', [9 => null, 'named' => false, 'x'], fn($v) => "'$v'");
        $this->assertEquals("select NULL, 0, 0, 'x'", $sql);

        $params = [1, 2, 3];
        $this->assertException('does not have', L($parser)->convertQuotedSQL('select ?, :named, ?', $params, fn($v) => $v));

        $params = [1];
        $this->assertException('does not have', L($parser)->convertQuotedSQL('select ?, :named, ?', $params, fn($v) => $v));

        $params = ['named' => 1];
        $this->assertException('does not have', L($parser)->convertQuotedSQL('select ?, :named, ?', $params, fn($v) => $v));

        $params = ['named' => 1, 2];
        $this->assertException('does not have', L($parser)->convertQuotedSQL('select ?, :named, ?', $params, fn($v) => $v));

        $params = ['named' => 1, 2, 3, 4];
        $this->assertException('length is long', L($parser)->convertQuotedSQL('select ?, :named, ?', $params, fn($v) => $v));
    }
}
