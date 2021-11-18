<?php

namespace ryunosuke\Test\dbml\Annotation;

// this code auto generated.

// @formatter:off

trait TableGatewayProvider
{
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
}

class Database extends \ryunosuke\Test\Database
{
    use TableGatewayProvider;
}

class ArticleTableGateway extends \ryunosuke\Test\Gateway\Article
{
    use TableGatewayProvider;

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function find($variadic_primary, $tableDescriptor = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function findInShare($variadic_primary, $tableDescriptor = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function findForUpdate($variadic_primary, $tableDescriptor = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function findOrThrow($variadic_primary, $tableDescriptor = []) { }

    /** @return ArticleEntity|array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int} */
    public function findForAffect($variadic_primary, $tableDescriptor = []) { }

    /** @return ArticleEntity[]|array<array{article_id: int, title: string, checks: array|string, title2: int, title3: int, title4: int, title5: int, comment_count: array|string, vaffect: int}> */
    public function neighbor($predicates = [], $limit = 1) { }
}

class CommentTableGateway extends \ryunosuke\Test\Gateway\Comment
{
    use TableGatewayProvider;

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function find($variadic_primary, $tableDescriptor = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function findInShare($variadic_primary, $tableDescriptor = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function findForUpdate($variadic_primary, $tableDescriptor = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function findOrThrow($variadic_primary, $tableDescriptor = []) { }

    /** @return CommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function findForAffect($variadic_primary, $tableDescriptor = []) { }

    /** @return CommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function neighbor($predicates = [], $limit = 1) { }
}

class ManagedCommentTableGateway extends \ryunosuke\Test\Gateway\Comment
{
    use TableGatewayProvider;

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function arrayForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function assocForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function tupleForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function find($variadic_primary, $tableDescriptor = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function findInShare($variadic_primary, $tableDescriptor = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function findForUpdate($variadic_primary, $tableDescriptor = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function findOrThrow($variadic_primary, $tableDescriptor = []) { }

    /** @return ManagedCommentEntity|array{comment_id: int, article_id: int, comment: string} */
    public function findForAffect($variadic_primary, $tableDescriptor = []) { }

    /** @return ManagedCommentEntity[]|array<array{comment_id: int, article_id: int, comment: string}> */
    public function neighbor($predicates = [], $limit = 1) { }
}

class ArticleEntity extends \ryunosuke\Test\Entity\Article
{
    /** @var int */
    public $article_id;

    /** @var string */
    public $title;

    /** @var array|string */
    public $checks;

    /** @var int */
    public $title2;

    /** @var int */
    public $title3;

    /** @var int */
    public $title4;

    /** @var int */
    public $title5;

    /** @var array|string */
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
