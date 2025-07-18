<?php

namespace ryunosuke\Test\dbml\Gateway;

use ryunosuke\dbml\Gateway\Scope;

class ScopeTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_flags()
    {
        $scope = new Scope(fn() => null);

        that($scope)->isClosure()->isTrue();
        that($scope)->selective()->isTrue();
        that($scope)->affective()->isTrue();

        $scope->selective(false);
        $scope->affective(false);

        that($scope)->selective()->isFalse();
        that($scope)->affective()->isFalse();
    }

    function test_resolve()
    {
        $scope = new Scope(['now' => 'NOW()']);

        that($scope)->resolve($this)->is([
            "column"  => [
                "now" => "NOW()",
            ],
            "where"   => [],
            "orderBy" => [],
            "limit"   => [],
            "groupBy" => [],
            "having"  => [],
            "set"     => [],
        ]);

        $scope = new Scope(fn($id) => ['where' => ['id' => $id]]);

        that($scope)->resolve($this, 2)->is([
            "where" => [
                "id" => 2,
            ],
        ]);

        $that = new class() {
            private $id = 3;
        };
        $scope = new Scope(fn() => ['where' => ['id' => $this->id]]);

        that($scope)->resolve($that)->is([
            "where" => [
                "id" => 3,
            ],
        ]);
    }

    function test_bind()
    {
        $scope = new Scope(fn($a, $b, $c) => ['where' => ['a' => $a, 'b' => $b, 'c' => $c]]);

        that($scope)->bind([0 => 'A', 2 => 'C'])->resolve($this, 1, 2)->is([
            "where" => [
                "a" => 1,
                "b" => 2,
                "c" => "C",
            ],
        ]);

        that($scope)->bind(['c' => 'C'])->resolve($this, 1, 2)->is([
            "where" => [
                "a" => 1,
                "b" => 2,
                "c" => "C",
            ],
        ]);

        that($scope)->bind(['a' => 'A'])->resolve($this, b: 2, c: 3)->is([
            "where" => [
                "a" => "A",
                "b" => 2,
                "c" => 3,
            ],
        ]);
    }

    function test_merge()
    {
        $scope = new Scope();

        $scope = $scope->merge([]);

        that($scope)->resolve($this)->is([
            "column"  => [],
            "where"   => [],
            "orderBy" => [],
            "limit"   => [],
            "groupBy" => [],
            "having"  => [],
            "set"     => [],
        ]);

        $scope = $scope->merge(['column' => 'id1', 'limit' => 1]);

        that($scope)->resolve($this)->is([
            "column"  => ["id1"],
            "where"   => [],
            "orderBy" => [],
            "limit"   => [1],
            "groupBy" => [],
            "having"  => [],
            "set"     => [],
        ]);

        $scope = $scope->merge(['column' => 'id2', 'limit' => 2]);

        that($scope)->resolve($this)->is([
            "column"  => ["id1", "id2"],
            "where"   => [],
            "orderBy" => [],
            "limit"   => [2],
            "groupBy" => [],
            "having"  => [],
            "set"     => [],
        ]);

        $scope = $scope->merge(['column' => 'id3', 'limit' => [2 => 3]]);

        that($scope)->resolve($this)->is([
            "column"  => ["id1", "id2", "id3"],
            "where"   => [],
            "orderBy" => [],
            "limit"   => [2 => 3],
            "groupBy" => [],
            "having"  => [],
            "set"     => [],
        ]);

        $scope = $scope->merge(['column' => 'id4'], ['column' => 'id5', 'where' => ['id' => 1]], ['where' => ['id' => 2]]);

        that($scope)->resolve($this)->is([
            "column"  => ["id1", "id2", "id3", "id4", "id5"],
            "where"   => ["id" => [1, 2]],
            "orderBy" => [],
            "limit"   => [2 => 3],
            "groupBy" => [],
            "having"  => [],
            "set"     => [],
        ]);
    }
}
