<?php

namespace ryunosuke\Test\dbml\Annotation;

// this code auto generated.

// @formatter:off

trait TableGatewayProvider
{
    /** @var aggregateTableGateway */
    public $aggregate;

    /** @var autoTableGateway */
    public $auto;

    /** @var foreign_c1TableGateway */
    public $foreign_c1;

    /** @var foreign_c2TableGateway */
    public $foreign_c2;

    /** @var foreign_d1TableGateway */
    public $foreign_d1;

    /** @var foreign_d2TableGateway */
    public $foreign_d2;

    /** @var foreign_pTableGateway */
    public $foreign_p;

    /** @var foreign_sTableGateway */
    public $foreign_s;

    /** @var foreign_scTableGateway */
    public $foreign_sc;

    /** @var g_ancestorTableGateway */
    public $g_ancestor;

    /** @var g_childTableGateway */
    public $g_child;

    /** @var g_grand1TableGateway */
    public $g_grand1;

    /** @var g_grand2TableGateway */
    public $g_grand2;

    /** @var g_parentTableGateway */
    public $g_parent;

    /** @var heavyTableGateway */
    public $heavy;

    /** @var horizontal1TableGateway */
    public $horizontal1;

    /** @var horizontal2TableGateway */
    public $horizontal2;

    /** @var master_tableTableGateway */
    public $master_table;

    /** @var misctypeTableGateway */
    public $misctype;

    /** @var misctype_childTableGateway */
    public $misctype_child;

    /** @var multifkeyTableGateway */
    public $multifkey;

    /** @var multiprimaryTableGateway */
    public $multiprimary;

    /** @var multiuniqueTableGateway */
    public $multiunique;

    /** @var noautoTableGateway */
    public $noauto;

    /** @var noprimaryTableGateway */
    public $noprimary;

    /** @var notnullsTableGateway */
    public $notnulls;

    /** @var nullableTableGateway */
    public $nullable;

    /** @var oprlogTableGateway */
    public $oprlog;

    /** @var pagingTableGateway */
    public $paging;

    /** @var ArticleTableGateway */
    public $t_article;

    /** @var ArticleTableGateway */
    public $Article;

    /** @var CommentTableGateway */
    public $t_comment;

    /** @var CommentTableGateway */
    public $Comment;

    /** @var ManagedCommentTableGateway */
    public $ManagedComment;

    /** @var testTableGateway */
    public $test;

    /** @var test1TableGateway */
    public $test1;

    /** @var test2TableGateway */
    public $test2;

    /** @var tran_table1TableGateway */
    public $tran_table1;

    /** @var tran_table2TableGateway */
    public $tran_table2;

    /** @var tran_table3TableGateway */
    public $tran_table3;

    /** @return aggregateTableGateway */
    public function aggregate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return autoTableGateway */
    public function auto($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return foreign_c1TableGateway */
    public function foreign_c1($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return foreign_c2TableGateway */
    public function foreign_c2($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return foreign_d1TableGateway */
    public function foreign_d1($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return foreign_d2TableGateway */
    public function foreign_d2($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return foreign_pTableGateway */
    public function foreign_p($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return foreign_sTableGateway */
    public function foreign_s($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return foreign_scTableGateway */
    public function foreign_sc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return g_ancestorTableGateway */
    public function g_ancestor($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return g_childTableGateway */
    public function g_child($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return g_grand1TableGateway */
    public function g_grand1($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return g_grand2TableGateway */
    public function g_grand2($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return g_parentTableGateway */
    public function g_parent($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return heavyTableGateway */
    public function heavy($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return horizontal1TableGateway */
    public function horizontal1($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return horizontal2TableGateway */
    public function horizontal2($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return master_tableTableGateway */
    public function master_table($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return misctypeTableGateway */
    public function misctype($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return misctype_childTableGateway */
    public function misctype_child($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return multifkeyTableGateway */
    public function multifkey($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return multiprimaryTableGateway */
    public function multiprimary($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return multiuniqueTableGateway */
    public function multiunique($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return noautoTableGateway */
    public function noauto($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return noprimaryTableGateway */
    public function noprimary($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return notnullsTableGateway */
    public function notnulls($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return nullableTableGateway */
    public function nullable($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return oprlogTableGateway */
    public function oprlog($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return pagingTableGateway */
    public function paging($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleTableGateway */
    public function t_article($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleTableGateway */
    public function Article($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentTableGateway */
    public function t_comment($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentTableGateway */
    public function Comment($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentTableGateway */
    public function ManagedComment($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return testTableGateway */
    public function test($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return test1TableGateway */
    public function test1($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return test2TableGateway */
    public function test2($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return tran_table1TableGateway */
    public function tran_table1($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return tran_table2TableGateway */
    public function tran_table2($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return tran_table3TableGateway */
    public function tran_table3($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class Database extends \ryunosuke\Test\Database
{
    use TableGatewayProvider;
}

class aggregateTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string, group_id1: int, group_id2: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string, group_id1: int, group_id2: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $dataarray
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, group_id1: int, group_id2: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $insertData
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<aggregateEntity>|array<array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<aggregateEntity|array{id: int, name: string, group_id1: int, group_id2: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class autoTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $dataarray
     * @param array{id: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<autoEntity>|array<array{id: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $insertData
     * @param autoEntity|array{id: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param autoEntity|array{id: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<autoEntity>|array<array{id: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return autoEntity|array{id: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return autoEntity|array{id: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return autoEntity|array{id: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return autoEntity|array{id: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return autoEntity|array{id: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<autoEntity|array{id: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<autoEntity|array{id: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class foreign_c1TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, seq: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, seq: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $dataarray
     * @param array{id: int, seq: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     * @param array{id: int, seq: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     * @param array{id: int, seq: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     * @param array{id: int, seq: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     * @param array{id: int, seq: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     * @param array{id: int, seq: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, seq: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $insertData
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param foreign_c1Entity|array{id: int, seq: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c1Entity>|array<array{id: int, seq: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c1Entity|array{id: int, seq: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c1Entity|array{id: int, seq: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c1Entity|array{id: int, seq: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c1Entity|array{id: int, seq: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c1Entity|array{id: int, seq: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_c1Entity|array{id: int, seq: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_c1Entity|array{id: int, seq: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class foreign_c2TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{cid: int, seq: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{cid: int, seq: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $dataarray
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     * @param array{cid: int, seq: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     * @param array{cid: int, seq: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     * @param array{cid: int, seq: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     * @param array{cid: int, seq: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{cid: int, seq: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $insertData
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param foreign_c2Entity|array{cid: int, seq: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_c2Entity>|array<array{cid: int, seq: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c2Entity|array{cid: int, seq: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c2Entity|array{cid: int, seq: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c2Entity|array{cid: int, seq: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c2Entity|array{cid: int, seq: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_c2Entity|array{cid: int, seq: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_c2Entity|array{cid: int, seq: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_c2Entity|array{cid: int, seq: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class foreign_d1TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, d2_id: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, d2_id: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $dataarray
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     * @param array{id: int, d2_id: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     * @param array{id: int, d2_id: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     * @param array{id: int, d2_id: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     * @param array{id: int, d2_id: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, d2_id: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $insertData
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param foreign_d1Entity|array{id: int, d2_id: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d1Entity>|array<array{id: int, d2_id: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d1Entity|array{id: int, d2_id: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d1Entity|array{id: int, d2_id: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d1Entity|array{id: int, d2_id: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d1Entity|array{id: int, d2_id: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d1Entity|array{id: int, d2_id: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_d1Entity|array{id: int, d2_id: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_d1Entity|array{id: int, d2_id: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class foreign_d2TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $dataarray
     * @param array{id: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<foreign_d2Entity>|array<array{id: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $insertData
     * @param foreign_d2Entity|array{id: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param foreign_d2Entity|array{id: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_d2Entity>|array<array{id: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d2Entity|array{id: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d2Entity|array{id: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d2Entity|array{id: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d2Entity|array{id: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_d2Entity|array{id: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_d2Entity|array{id: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_d2Entity|array{id: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class foreign_pTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $dataarray
     * @param array{id: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<foreign_pEntity>|array<array{id: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $insertData
     * @param foreign_pEntity|array{id: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param foreign_pEntity|array{id: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_pEntity>|array<array{id: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_pEntity|array{id: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_pEntity|array{id: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_pEntity|array{id: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_pEntity|array{id: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_pEntity|array{id: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_pEntity|array{id: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_pEntity|array{id: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class foreign_sTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $dataarray
     * @param array{id: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<foreign_sEntity>|array<array{id: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $insertData
     * @param foreign_sEntity|array{id: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param foreign_sEntity|array{id: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_sEntity>|array<array{id: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_sEntity|array{id: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_sEntity|array{id: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_sEntity|array{id: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_sEntity|array{id: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_sEntity|array{id: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_sEntity|array{id: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_sEntity|array{id: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class foreign_scTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, s_id1: int, s_id2: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, s_id1: int, s_id2: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $dataarray
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, s_id1: int, s_id2: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $insertData
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<foreign_scEntity>|array<array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<foreign_scEntity|array{id: int, s_id1: int, s_id2: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class g_ancestorTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $dataarray
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{ancestor_id: int, ancestor_name: string, delete_at: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $insertData
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_ancestorEntity>|array<array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_ancestorEntity|array{ancestor_id: int, ancestor_name: string, delete_at: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class g_childTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $dataarray
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{child_id: int, parent_id: int, child_name: string, delete_at: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $insertData
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_childEntity>|array<array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_childEntity|array{child_id: int, parent_id: int, child_name: string, delete_at: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class g_grand1TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $dataarray
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $insertData
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand1Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_grand1Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand1_name: string, delete_at: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class g_grand2TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $dataarray
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $insertData
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_grand2Entity>|array<array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_grand2Entity|array{grand_id: int, parent_id: int, ancestor_id: int, grand2_name: string, delete_at: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class g_parentTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $dataarray
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $insertData
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<g_parentEntity>|array<array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<g_parentEntity|array{parent_id: int, ancestor_id: int, parent_name: string, delete_at: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class heavyTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $data
     * @param array{id: int, data: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $data
     * @param array{id: int, data: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, data: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, data: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $dataarray
     * @param array{id: int, data: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<heavyEntity>|array<array{id: int, data: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     * @param array{id: int, data: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     * @param array{id: int, data: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     * @param array{id: int, data: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     * @param array{id: int, data: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     * @param array{id: int, data: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, data: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $insertData
     * @param heavyEntity|array{id: int, data: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param heavyEntity|array{id: int, data: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<heavyEntity>|array<array{id: int, data: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return heavyEntity|array{id: int, data: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return heavyEntity|array{id: int, data: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return heavyEntity|array{id: int, data: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return heavyEntity|array{id: int, data: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return heavyEntity|array{id: int, data: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<heavyEntity|array{id: int, data: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<heavyEntity|array{id: int, data: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class horizontal1TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $dataarray
     * @param array{id: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<horizontal1Entity>|array<array{id: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $insertData
     * @param horizontal1Entity|array{id: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param horizontal1Entity|array{id: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal1Entity>|array<array{id: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal1Entity|array{id: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal1Entity|array{id: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal1Entity|array{id: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal1Entity|array{id: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal1Entity|array{id: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<horizontal1Entity|array{id: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<horizontal1Entity|array{id: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class horizontal2TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $data
     * @param array{id: int, summary: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $data
     * @param array{id: int, summary: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, summary: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, summary: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $dataarray
     * @param array{id: int, summary: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<horizontal2Entity>|array<array{id: int, summary: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     * @param array{id: int, summary: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     * @param array{id: int, summary: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     * @param array{id: int, summary: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     * @param array{id: int, summary: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     * @param array{id: int, summary: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, summary: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $insertData
     * @param horizontal2Entity|array{id: int, summary: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param horizontal2Entity|array{id: int, summary: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<horizontal2Entity>|array<array{id: int, summary: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal2Entity|array{id: int, summary: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal2Entity|array{id: int, summary: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal2Entity|array{id: int, summary: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal2Entity|array{id: int, summary: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return horizontal2Entity|array{id: int, summary: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<horizontal2Entity|array{id: int, summary: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<horizontal2Entity|array{id: int, summary: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class master_tableTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $data
     * @param array{category: string, subid: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $data
     * @param array{category: string, subid: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{category: string, subid: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{category: string, subid: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $dataarray
     * @param array{category: string, subid: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<master_tableEntity>|array<array{category: string, subid: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     * @param array{category: string, subid: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     * @param array{category: string, subid: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     * @param array{category: string, subid: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     * @param array{category: string, subid: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     * @param array{category: string, subid: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{category: string, subid: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $insertData
     * @param master_tableEntity|array{category: string, subid: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param master_tableEntity|array{category: string, subid: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<master_tableEntity>|array<array{category: string, subid: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return master_tableEntity|array{category: string, subid: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return master_tableEntity|array{category: string, subid: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return master_tableEntity|array{category: string, subid: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return master_tableEntity|array{category: string, subid: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return master_tableEntity|array{category: string, subid: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<master_tableEntity|array{category: string, subid: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<master_tableEntity|array{category: string, subid: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class misctypeTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $dataarray
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $insertData
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctypeEntity>|array<array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<misctypeEntity|array{id: int, pid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: \DateTimeImmutable, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string, carray: array|string, cjson: array|string, eint: \ryunosuke\Test\IntEnum, estring: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class misctype_childTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $dataarray
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $insertData
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<misctype_childEntity>|array<array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<misctype_childEntity|array{id: int, cid: int, cint: int, cfloat: float, cdecimal: float|string, cdate: string, cdatetime: string, cstring: string, ctext: string, cbinary: string, cblob: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class multifkeyTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, mainid: int, subid: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, mainid: int, subid: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $dataarray
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     * @param array{id: int, mainid: int, subid: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     * @param array{id: int, mainid: int, subid: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     * @param array{id: int, mainid: int, subid: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     * @param array{id: int, mainid: int, subid: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, mainid: int, subid: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $insertData
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param multifkeyEntity|array{id: int, mainid: int, subid: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multifkeyEntity>|array<array{id: int, mainid: int, subid: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multifkeyEntity|array{id: int, mainid: int, subid: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multifkeyEntity|array{id: int, mainid: int, subid: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multifkeyEntity|array{id: int, mainid: int, subid: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multifkeyEntity|array{id: int, mainid: int, subid: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multifkeyEntity|array{id: int, mainid: int, subid: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<multifkeyEntity|array{id: int, mainid: int, subid: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<multifkeyEntity|array{id: int, mainid: int, subid: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class multiprimaryTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{mainid: int, subid: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{mainid: int, subid: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $dataarray
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     * @param array{mainid: int, subid: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     * @param array{mainid: int, subid: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     * @param array{mainid: int, subid: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     * @param array{mainid: int, subid: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{mainid: int, subid: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $insertData
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param multiprimaryEntity|array{mainid: int, subid: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiprimaryEntity>|array<array{mainid: int, subid: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiprimaryEntity|array{mainid: int, subid: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiprimaryEntity|array{mainid: int, subid: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiprimaryEntity|array{mainid: int, subid: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiprimaryEntity|array{mainid: int, subid: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiprimaryEntity|array{mainid: int, subid: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<multiprimaryEntity|array{mainid: int, subid: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<multiprimaryEntity|array{mainid: int, subid: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class multiuniqueTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $dataarray
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $insertData
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<multiuniqueEntity>|array<array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<multiuniqueEntity|array{id: int, uc_s: string, uc_i: int, uc1: string, uc2: int, groupkey: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class noautoTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $data
     * @param array{id: string, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $data
     * @param array{id: string, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: string, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: string, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $dataarray
     * @param array{id: string, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<noautoEntity>|array<array{id: string, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     * @param array{id: string, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     * @param array{id: string, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     * @param array{id: string, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     * @param array{id: string, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     * @param array{id: string, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: string, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $insertData
     * @param noautoEntity|array{id: string, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param noautoEntity|array{id: string, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noautoEntity>|array<array{id: string, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noautoEntity|array{id: string, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noautoEntity|array{id: string, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noautoEntity|array{id: string, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noautoEntity|array{id: string, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noautoEntity|array{id: string, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<noautoEntity|array{id: string, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<noautoEntity|array{id: string, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class noprimaryTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $data
     * @param array{id: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $data
     * @param array{id: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $dataarray
     * @param array{id: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<noprimaryEntity>|array<array{id: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     * @param array{id: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int} $where
     * @param array{id: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int} $where
     * @param array{id: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int} $where
     * @param array{id: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     * @param array{id: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $insertData
     * @param noprimaryEntity|array{id: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param noprimaryEntity|array{id: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<noprimaryEntity>|array<array{id: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noprimaryEntity|array{id: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noprimaryEntity|array{id: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noprimaryEntity|array{id: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noprimaryEntity|array{id: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return noprimaryEntity|array{id: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<noprimaryEntity|array{id: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<noprimaryEntity|array{id: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class notnullsTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $dataarray
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<notnullsEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<notnullsEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class nullableTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $dataarray
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $insertData
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<nullableEntity>|array<array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<nullableEntity|array{id: int, name: string, cint: int, cfloat: float, cdecimal: float|string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class oprlogTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, category: string, primary_id: int, log_date: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, category: string, primary_id: int, log_date: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $dataarray
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     * @param array{id: int, category: string, primary_id: int, log_date: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     * @param array{id: int, category: string, primary_id: int, log_date: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     * @param array{id: int, category: string, primary_id: int, log_date: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     * @param array{id: int, category: string, primary_id: int, log_date: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, category: string, primary_id: int, log_date: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $insertData
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<oprlogEntity>|array<array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<oprlogEntity|array{id: int, category: string, primary_id: int, log_date: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class pagingTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $data
     * @param array{id: int, name: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $dataarray
     * @param array{id: int, name: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<pagingEntity>|array<array{id: int, name: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     * @param array{id: int, name: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     * @param array{id: int, name: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $insertData
     * @param pagingEntity|array{id: int, name: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param pagingEntity|array{id: int, name: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<pagingEntity>|array<array{id: int, name: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return pagingEntity|array{id: int, name: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return pagingEntity|array{id: int, name: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return pagingEntity|array{id: int, name: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return pagingEntity|array{id: int, name: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return pagingEntity|array{id: int, name: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<pagingEntity|array{id: int, name: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<pagingEntity|array{id: int, name: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class ArticleTableGateway extends \ryunosuke\Test\Gateway\Article
{
    use TableGatewayProvider;

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $dataarray
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $insertData
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ArticleEntity>|array<array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<ArticleEntity|array{article_id: int, title: string, checks: array|string, delete_at: string, title2: int, title3: int, title4: int, title5: int, comment_count: int, vaffect: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class CommentTableGateway extends \ryunosuke\Test\Gateway\Comment
{
    use TableGatewayProvider;

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{comment_id: int, article_id: int, comment: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{comment_id: int, article_id: int, comment: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $dataarray
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     * @param array{comment_id: int, article_id: int, comment: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     * @param array{comment_id: int, article_id: int, comment: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     * @param array{comment_id: int, article_id: int, comment: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     * @param array{comment_id: int, article_id: int, comment: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param CommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<CommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return CommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return CommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return CommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return CommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return CommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<CommentEntity|array{comment_id: int, article_id: int, comment: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<CommentEntity|array{comment_id: int, article_id: int, comment: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class ManagedCommentTableGateway extends \ryunosuke\Test\Gateway\Comment
{
    use TableGatewayProvider;

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{comment_id: int, article_id: int, comment: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{comment_id: int, article_id: int, comment: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $dataarray
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     * @param array{comment_id: int, article_id: int, comment: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     * @param array{comment_id: int, article_id: int, comment: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     * @param array{comment_id: int, article_id: int, comment: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     * @param array{comment_id: int, article_id: int, comment: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{comment_id: int, article_id: int, comment: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $insertData
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<ManagedCommentEntity>|array<array{comment_id: int, article_id: int, comment: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class testTableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string, data: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name: string, data: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $dataarray
     * @param array{id: int, name: string, data: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<testEntity>|array<array{id: int, name: string, data: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     * @param array{id: int, name: string, data: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     * @param array{id: int, name: string, data: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     * @param array{id: int, name: string, data: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     * @param array{id: int, name: string, data: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     * @param array{id: int, name: string, data: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name: string, data: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $insertData
     * @param testEntity|array{id: int, name: string, data: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param testEntity|array{id: int, name: string, data: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<testEntity>|array<array{id: int, name: string, data: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return testEntity|array{id: int, name: string, data: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return testEntity|array{id: int, name: string, data: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return testEntity|array{id: int, name: string, data: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return testEntity|array{id: int, name: string, data: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return testEntity|array{id: int, name: string, data: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<testEntity|array{id: int, name: string, data: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<testEntity|array{id: int, name: string, data: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class test1TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $data
     * @param array{id: int, name1: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $data
     * @param array{id: int, name1: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name1: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name1: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $dataarray
     * @param array{id: int, name1: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<test1Entity>|array<array{id: int, name1: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     * @param array{id: int, name1: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     * @param array{id: int, name1: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     * @param array{id: int, name1: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     * @param array{id: int, name1: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     * @param array{id: int, name1: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name1: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $insertData
     * @param test1Entity|array{id: int, name1: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param test1Entity|array{id: int, name1: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test1Entity>|array<array{id: int, name1: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test1Entity|array{id: int, name1: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test1Entity|array{id: int, name1: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test1Entity|array{id: int, name1: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test1Entity|array{id: int, name1: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test1Entity|array{id: int, name1: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<test1Entity|array{id: int, name1: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<test1Entity|array{id: int, name1: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class test2TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $data
     * @param array{id: int, name2: string} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $data
     * @param array{id: int, name2: string} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name2: string}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, name2: string}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $dataarray
     * @param array{id: int, name2: string} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<test2Entity>|array<array{id: int, name2: string}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     * @param array{id: int, name2: string} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     * @param array{id: int, name2: string} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     * @param array{id: int, name2: string} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     * @param array{id: int, name2: string} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     * @param array{id: int, name2: string} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, name2: string} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $insertData
     * @param test2Entity|array{id: int, name2: string} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param test2Entity|array{id: int, name2: string} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<test2Entity>|array<array{id: int, name2: string}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test2Entity|array{id: int, name2: string}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test2Entity|array{id: int, name2: string}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test2Entity|array{id: int, name2: string}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test2Entity|array{id: int, name2: string}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return test2Entity|array{id: int, name2: string}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<test2Entity|array{id: int, name2: string}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<test2Entity|array{id: int, name2: string}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class tran_table1TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, master_id: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, master_id: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $dataarray
     * @param array{id: int, master_id: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<tran_table1Entity>|array<array{id: int, master_id: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $insertData
     * @param tran_table1Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param tran_table1Entity|array{id: int, master_id: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table1Entity>|array<array{id: int, master_id: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table1Entity|array{id: int, master_id: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table1Entity|array{id: int, master_id: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table1Entity|array{id: int, master_id: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table1Entity|array{id: int, master_id: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table1Entity|array{id: int, master_id: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<tran_table1Entity|array{id: int, master_id: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<tran_table1Entity|array{id: int, master_id: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class tran_table2TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, master_id: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, master_id: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $dataarray
     * @param array{id: int, master_id: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<tran_table2Entity>|array<array{id: int, master_id: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $insertData
     * @param tran_table2Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param tran_table2Entity|array{id: int, master_id: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table2Entity>|array<array{id: int, master_id: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table2Entity|array{id: int, master_id: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table2Entity|array{id: int, master_id: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table2Entity|array{id: int, master_id: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table2Entity|array{id: int, master_id: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table2Entity|array{id: int, master_id: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<tran_table2Entity|array{id: int, master_id: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<tran_table2Entity|array{id: int, master_id: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class tran_table3TableGateway extends \ryunosuke\dbml\Gateway\TableGateway
{
    use TableGatewayProvider;

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function neighbor(array $predicates = [], int $limit = 1):array { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArray($data, ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArrayAndPrimary($data, ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $data
     */
    public function insertArrayOrThrow($data, ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateArray($data, $where = [], ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateArrayAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param array<array{id: int, master_id: int}> $where
     */
    public function deleteArray($where = [], ...$opt) { }

    /**
     * @param array<array{id: int, master_id: int}> $where
     */
    public function deleteArrayAndBefore($where = [], ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArrayAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyArrayAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $dataarray
     * @param array{id: int, master_id: int} $where
     */
    public function changeArray($dataarray, $where, $uniquekey = "PRIMARY", $returning = [], ...$opt) { }

    /**
     * @param array<tran_table3Entity>|array<array{id: int, master_id: int}> $dataarray
     */
    public function affectArray($dataarray, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     */
    public function save($data, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     */
    public function insert($data, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     */
    public function insertAndPrimary($data, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     */
    public function insertOrThrow($data, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function update($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function updateOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function delete($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function deleteOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalid($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidAndPrimary($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidAndBefore($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     * @param array{id: int, master_id: int} $invalid_columns
     */
    public function invalidOrThrow($where = [], ?array $invalid_columns = null, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function revise($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function reviseOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgrade($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeAndPrimary($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeAndBefore($data, $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     * @param array{id: int, master_id: int} $where
     */
    public function upgradeOrThrow($data, $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function remove($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function removeOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroy($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyAndPrimary($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyAndBefore($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function destroyOrThrow($where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduceAndBefore($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param array{id: int, master_id: int} $where
     */
    public function reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function upsert($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertAndPrimary($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertAndBefore($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function upsertOrThrow($insertData, $updateData = [], ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function modify($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyAndPrimary($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyAndBefore($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $insertData
     * @param tran_table3Entity|array{id: int, master_id: int} $updateData
     */
    public function modifyOrThrow($insertData, $updateData = [], $uniquekey = "PRIMARY", ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     */
    public function replace($data, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     */
    public function replaceAndPrimary($data, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     */
    public function replaceAndBefore($data, ...$opt) { }

    /**
     * @param tran_table3Entity|array{id: int, master_id: int} $data
     */
    public function replaceOrThrow($data, ...$opt) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return array<tran_table3Entity>|array<array{id: int, master_id: int}>
     */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table3Entity|array{id: int, master_id: int}
     */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table3Entity|array{id: int, master_id: int}
     */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table3Entity|array{id: int, master_id: int}
     */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table3Entity|array{id: int, master_id: int}
     */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return tran_table3Entity|array{id: int, master_id: int}
     */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<tran_table3Entity|array{id: int, master_id: int}>
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /**
     * @return iterable<tran_table3Entity|array{id: int, master_id: int}>
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }
}

class aggregateEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int */
    public $group_id1;

    /** @var int */
    public $group_id2;
}

class autoEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;
}

class foreign_c1Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $seq;

    /** @var string */
    public $name;
}

class foreign_c2Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $cid;

    /** @var int */
    public $seq;

    /** @var string */
    public $name;
}

class foreign_d1Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $d2_id;

    /** @var string */
    public $name;
}

class foreign_d2Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;
}

class foreign_pEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;
}

class foreign_sEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;
}

class foreign_scEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $s_id1;

    /** @var int */
    public $s_id2;

    /** @var string */
    public $name;
}

class g_ancestorEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $ancestor_id;

    /** @var string */
    public $ancestor_name;

    /** @var string */
    public $delete_at;
}

class g_childEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $child_id;

    /** @var int */
    public $parent_id;

    /** @var string */
    public $child_name;

    /** @var string */
    public $delete_at;
}

class g_grand1Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $grand_id;

    /** @var int */
    public $parent_id;

    /** @var int */
    public $ancestor_id;

    /** @var string */
    public $grand1_name;

    /** @var string */
    public $delete_at;
}

class g_grand2Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $grand_id;

    /** @var int */
    public $parent_id;

    /** @var int */
    public $ancestor_id;

    /** @var string */
    public $grand2_name;

    /** @var string */
    public $delete_at;
}

class g_parentEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $parent_id;

    /** @var int */
    public $ancestor_id;

    /** @var string */
    public $parent_name;

    /** @var string */
    public $delete_at;
}

class heavyEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $data;
}

class horizontal1Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;
}

class horizontal2Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $summary;
}

class master_tableEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var string */
    public $category;

    /** @var int */
    public $subid;
}

class misctypeEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $pid;

    /** @var int */
    public $cint;

    /** @var float */
    public $cfloat;

    /** @var float|string */
    public $cdecimal;

    /** @var \DateTimeImmutable */
    public $cdate;

    /** @var string */
    public $cdatetime;

    /** @var string */
    public $cstring;

    /** @var string */
    public $ctext;

    /** @var string */
    public $cbinary;

    /** @var string */
    public $cblob;

    /** @var array|string */
    public $carray;

    /** @var array|string */
    public $cjson;

    /** @var \ryunosuke\Test\IntEnum */
    public $eint;

    /** @var string */
    public $estring;
}

class misctype_childEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $cid;

    /** @var int */
    public $cint;

    /** @var float */
    public $cfloat;

    /** @var float|string */
    public $cdecimal;

    /** @var string */
    public $cdate;

    /** @var string */
    public $cdatetime;

    /** @var string */
    public $cstring;

    /** @var string */
    public $ctext;

    /** @var string */
    public $cbinary;

    /** @var string */
    public $cblob;
}

class multifkeyEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $mainid;

    /** @var int */
    public $subid;
}

class multiprimaryEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $mainid;

    /** @var int */
    public $subid;

    /** @var string */
    public $name;
}

class multiuniqueEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $uc_s;

    /** @var int */
    public $uc_i;

    /** @var string */
    public $uc1;

    /** @var int */
    public $uc2;

    /** @var int */
    public $groupkey;
}

class noautoEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;
}

class noprimaryEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;
}

class notnullsEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int */
    public $cint;

    /** @var float */
    public $cfloat;

    /** @var float|string */
    public $cdecimal;
}

class nullableEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int */
    public $cint;

    /** @var float */
    public $cfloat;

    /** @var float|string */
    public $cdecimal;
}

class oprlogEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $category;

    /** @var int */
    public $primary_id;

    /** @var string */
    public $log_date;
}

class pagingEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;
}

class ArticleEntity extends \ryunosuke\Test\Entity\Article
{
    /** @var int */
    public $article_id;

    /** @var string */
    public $title;

    /** @var array|string */
    public $checks;

    /** @var string */
    public $delete_at;

    /** @var int */
    public $title2;

    /** @var int */
    public $title3;

    /** @var int */
    public $title4;

    /** @var int */
    public $title5;

    /** @var int */
    public $comment_count;

    /** @var int */
    public $vaffect;
}

class CommentEntity extends \ryunosuke\Test\Entity\Comment
{
    /** @var int */
    public $comment_id;

    /** @var int */
    public $article_id;

    /** @var string */
    public $comment;
}

class ManagedCommentEntity extends \ryunosuke\Test\Entity\ManagedComment
{
    /** @var int */
    public $comment_id;

    /** @var int */
    public $article_id;

    /** @var string */
    public $comment;
}

class testEntity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $data;
}

class test1Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name1;
}

class test2Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var string */
    public $name2;
}

class tran_table1Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $master_id;
}

class tran_table2Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $master_id;
}

class tran_table3Entity extends \ryunosuke\dbml\Entity\Entity
{
    /** @var int */
    public $id;

    /** @var int */
    public $master_id;
}
