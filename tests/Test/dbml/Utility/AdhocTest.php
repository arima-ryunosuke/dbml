<?php

namespace ryunosuke\Test\dbml\Utility;

use Doctrine\DBAL\ParameterType;
use ryunosuke\dbml\Utility\Adhoc;
use ryunosuke\Test\IntEnum;
use ryunosuke\Test\StringEnum;
use function ryunosuke\dbml\cacheobject;

class AdhocTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_parseParams()
    {
        $params = [
            'driver'        => 'sql',
            'serverVersion' => '1.0.0',
            'host'          => 'localhost',
            'port'          => 9999,
            'user'          => 'user',
            'password'      => 'pass',
            'dbname'        => 'example',
            'charset'       => 'utf8',
            'driverOptions' => [
                \PDO::ATTR_TIMEOUT          => 1234,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];

        // no url
        $this->assertEquals($params, Adhoc::parseParams($params));

        // only url
        $this->assertEquals([
            "driver"        => "mysql",
            "serverVersion" => "8.1.2",
            "host"          => "127.0.0.1",
            "port"          => 3306,
            "user"          => "U",
            "password"      => "P",
            "dbname"        => "dbname",
            "charset"       => "utfmb4",
            "socket"        => "tmp.socket",
            "driverOptions" => [
                \PDO::ATTR_TIMEOUT => "2345",
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ],
        ], Adhoc::parseParams(['url' => 'mysql+8.1.2://U:P@127.0.0.1:3306/dbname?charset=utfmb4&socket=tmp.socket#PDO::ATTR_TIMEOUT=2345&PDO::ATTR_ERRMODE=PDO::ERRMODE_EXCEPTION']));

        // merge url
        $this->assertEquals([
            "driver"        => "sql",
            "serverVersion" => "1.0.0",
            "host"          => "localhost",
            "port"          => 9999,
            "user"          => "user",
            "password"      => "pass",
            "dbname"        => "example",
            "charset"       => "utf8",
            "socket"        => "tmp.socket",
            "driverOptions" => [
                \PDO::ATTR_TIMEOUT          => 1234,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION,
            ],
        ], Adhoc::parseParams(['url' => 'mysql+8.1.2://U:P@127.0.0.1:3306/dbname?charset=utfmb4&socket=tmp.socket#PDO::ATTR_TIMEOUT=2345&PDO::ATTR_ERRMODE=PDO::ERRMODE_EXCEPTION'] + $params));
    }

    function test_cacheByHash()
    {
        $cacheobject = cacheobject(sys_get_temp_dir() . '/cacheByHash');
        $cacheobject->clear();
        $called = 0;
        $provider = static function () use (&$called) {
            return ++$called;
        };
        $this->assertEquals(1, Adhoc::cacheByHash($cacheobject, 'hoge:1', $provider, 1));
        $this->assertEquals(1, Adhoc::cacheByHash($cacheobject, 'hoge:1', $provider, 1));
        sleep(2);
        $this->assertEquals(2, Adhoc::cacheByHash($cacheobject, 'hoge:1', $provider, 1));
        $this->assertEquals(3, Adhoc::cacheByHash($cacheobject, 'hoge:2', $provider, 1));

        $this->assertEquals(null, Adhoc::cacheByHash($cacheobject, 'null', static fn() => null));
        $this->assertCount(2, $cacheobject->keys());
    }

    function test_is_empty()
    {
        $this->assertTrue(Adhoc::is_empty([]));
        $this->assertTrue(Adhoc::is_empty(null));
        $this->assertTrue(Adhoc::is_empty(''));

        $this->assertFalse(Adhoc::is_empty([1]));
        $this->assertFalse(Adhoc::is_empty('0'));
        $this->assertFalse(Adhoc::is_empty(0));
        $this->assertFalse(Adhoc::is_empty(false));

        $this->assertFalse(Adhoc::is_empty(self::getDummyDatabase()->select('t', ['id' => 1])));
        $this->assertFalse(Adhoc::is_empty(self::getDummyDatabase()->select('t', ['!id' => 1])));
        $this->assertTrue(Adhoc::is_empty(self::getDummyDatabase()->select('t', ['!id' => null])));
        $this->assertTrue(Adhoc::is_empty(self::getDummyDatabase()->select('t', ['id' => 1, '!id' => null])));
    }

    function test_wrapParentheses()
    {
        // シンプル配列
        $this->assertEquals([], Adhoc::wrapParentheses([]));
        $this->assertEquals(['hoge'], Adhoc::wrapParentheses(['hoge']));
        $this->assertEquals(['x' => 'hoge'], Adhoc::wrapParentheses(['x' => 'hoge']));
        $this->assertEquals(['(hoge)', '(fuga)'], Adhoc::wrapParentheses(['hoge', 'fuga']));
        $this->assertEquals(['x' => '(hoge)', 'y' => '(fuga)'], Adhoc::wrapParentheses(['x' => 'hoge', 'y' => 'fuga']));

        // 入れ子配列
        $this->assertEquals([
            '(hoge)',
            'x' => [
                '(fuga)',
                'y' => [
                    '(piyo)',
                    'z' => ['zzz'],
                ],
            ],
        ], Adhoc::wrapParentheses([
            'hoge',
            'x' => [
                'fuga',
                'y' => [
                    'piyo',
                    'z' => ['zzz'],
                ],
            ],
        ]));
    }

    function test_containQueryable()
    {
        $e = self::getDummyDatabase()->raw('column');
        $this->assertFalse(Adhoc::containQueryable(null));
        $this->assertFalse(Adhoc::containQueryable('hoge'));
        $this->assertFalse(Adhoc::containQueryable([]));
        $this->assertFalse(Adhoc::containQueryable(['hoge']));
        $this->assertFalse(Adhoc::containQueryable($e));
        $this->assertTrue(Adhoc::containQueryable([$e]));
        $this->assertTrue(Adhoc::containQueryable([$e, $e]));
        $this->assertTrue(Adhoc::containQueryable([null, $e, 'hoge']));
    }

    function test_modifier()
    {
        $this->assertEquals(['column'], Adhoc::modifier('', [], ['column']));
        $this->assertEquals(['c' => 'column'], Adhoc::modifier('', [], ['c' => 'column']));

        $this->assertEquals(['column'], Adhoc::modifier('T', [], ['column']));
        $this->assertEquals(['UPPER(column)' => 'hoge'], Adhoc::modifier('T', [], ['UPPER(column)' => 'hoge']));
        $this->assertEquals(['!T.c' => 'column'], Adhoc::modifier('T', ['c' => true], ['!c' => 'column']));
        $this->assertEquals(['T.c' => 'column'], Adhoc::modifier('T', ['c' => true], ['c' => 'column']));
        $this->assertEquals(['!T.c' => 'column'], Adhoc::modifier('T', ['c' => true], ['!c' => 'column']));
        $this->assertEquals(['-T.a <> ?' => 'column', '+T.b = ?' => 'column'], Adhoc::modifier('T', ['a' => true, 'b' => true], ['-a <> ?' => 'column', '+b = ?' => 'column']));
        $this->assertEquals(['T.a' => 'columnA', ['T.b' => 'columnB'], 'c' => 'columnC'], Adhoc::modifier('T', ['a' => true, 'b' => true], ['a' => 'columnA', ['b' => 'columnB'], 'c' => 'columnC']));

        $qb = self::getDummyDatabase()->subexists('t', ['id' => 1]);
        $this->assertEquals(['c' => $qb], Adhoc::modifier('T', ['c' => true], ['c' => $qb]));

        $qb = self::getDummyDatabase()->subquery('t', ['id' => 1]);
        $this->assertEquals(['T.c' => $qb], Adhoc::modifier('T', ['c' => true], ['c' => $qb]));

        $e = self::getDummyDatabase()->raw('column');
        $this->assertEquals(['T.c' => $e], Adhoc::modifier('T', ['c' => true], ['c' => $e]));
        $this->assertEquals(['c' => [$e]], Adhoc::modifier('T', ['c' => true], ['c' => [$e]]));
    }

    function test_stringifyParameter()
    {
        $quoter = fn($v) => $v;
        $this->assertSame('NULL', Adhoc::stringifyParameter(fn() => null, $quoter));
        $this->assertSame('123', Adhoc::stringifyParameter(fn() => 123, $quoter));
        $this->assertSame('NULL', Adhoc::stringifyParameter(null, $quoter));
        $this->assertSame('0', Adhoc::stringifyParameter(false, $quoter));
        $this->assertSame('1', Adhoc::stringifyParameter(IntEnum::Int1(), $quoter));
        $this->assertSame('hoge', Adhoc::stringifyParameter(StringEnum::StringHoge(), $quoter));
        $this->assertSame('string', Adhoc::stringifyParameter(new class() {
            public function __toString() { return 'string'; }
        }, $quoter));
        $this->assertSame('invoke', Adhoc::stringifyParameter(new class() {
            public function __invoke() { return 'invoke'; }
        }, $quoter));
        $this->assertSame('string', Adhoc::stringifyParameter(new class() {
            public function __toString() { return 'string'; }

            public function __invoke() { return 'invoke'; }
        }, $quoter));
    }

    function test_bindableParameters()
    {
        $this->assertSame([
            'fn-null'     => null,
            'fn-int'      => 123,
            'null'        => null,
            'false'       => 0,
            'true'        => 1,
            'int-enum'    => 1,
            'string-enum' => "hoge",
            "datetime"    => "2014-12-24 12:34:56.789",
        ], Adhoc::bindableParameters([
            'fn-null'     => fn() => null,
            'fn-int'      => fn() => 123,
            'null'        => null,
            'false'       => false,
            'true'        => true,
            'int-enum'    => IntEnum::Int1(),
            'string-enum' => StringEnum::StringHoge(),
            'datetime'    => new \DateTime('2014/12/24 12:34:56.789'),
        ], "Y-m-d H:i:s.v"));

        $resource = tmpfile();
        fwrite($resource, "the resource");
        rewind($resource);

        $splinfo = new \SplFileInfo(__FILE__);

        $splfile = new \SplTempFileObject();
        $splfile->fwrite("the splfile");
        $splfile->rewind();

        $generator = (function () {
            yield "the";
            yield " ";
            yield "generator";
        })();

        $actual = Adhoc::bindableParameters([
            'resource'  => $resource,
            'splinfo'   => $splinfo,
            'splfile'   => $splfile,
            'generator' => $generator,
        ], "Y-m-d H:i:s.v");

        that(stream_get_contents($actual['resource']))->is('the resource');
        that(stream_get_contents($actual['splinfo']))->is(file_get_contents(__FILE__));
        that(stream_get_contents($actual['splfile']))->is('the splfile');
        that(stream_get_contents($actual['generator']))->is('the generator');
    }

    function test_bindableTypes()
    {
        $this->assertSame([
            "null"     => ParameterType::NULL,
            "false"    => ParameterType::BOOLEAN,
            "true"     => ParameterType::BOOLEAN,
            "int"      => ParameterType::INTEGER,
            "float"    => ParameterType::STRING,
            "string"   => ParameterType::STRING,
            "resource" => ParameterType::LARGE_OBJECT,
        ], Adhoc::bindableTypes([
            'null'     => null,
            'false'    => false,
            'true'     => true,
            'int'      => 123,
            'float'    => 3.14,
            'string'   => 'string',
            'resource' => tmpfile(),
        ]));
    }
}
