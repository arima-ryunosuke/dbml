<?php

// documentize の名前空間ドキュメント用ファイル

/**
 * 特定のクラスに依存しない全体仕様を以下に記す。
 *
 * ### データベーススキーマ
 *
 * まず大前提として**別スキーマへのクエリは一切サポートしない**。
 * ただし、 {@link TableGateway} や {@link SelectBuilder} の話であり、 {@link Database::fetchArray} などで直接クエリを投げる場合はこの限りではない。
 *
 * スキーマ情報はキャッシュに保存される。
 * このキャッシュは {@link Database::__construct()} のオプションで指定する。
 * 結構な勢いでスキーマを漁るので、何らかの永続キャッシュ（FilesystemCache や ApcuCache など）を使用したほうが良い。
 *
 * ### テーブル記法
 *
 * 各クラスの各メソッドで引数名が `$tableDescriptor` となっているものは下記の記法を受け入れる（{@link TableDescriptor} から抜粋）。
 *
 * - base: `'(joinsign)tablename(pkval)@scope:fkeyname[condition]<groupby>+order-by#offset-limit AS Alias.col1, col2 AS C2'`
 *
 * | 要素               | 必須 | 説明
 * |:--                 |:--:  |:--
 * | joinsign           | 任意 | JOIN する場合に結合方法を表す記号を置く（'*':CROSS, '+':INNER, '<':LEFT, '>':RIGHT, '~':AUTO, ',':FROM）
 * | tablename          | 必須 | 取得するテーブル名を指定する
 * | (pkval)            | 任意 | 主キーの値を指定する
 * | @scope             | 任意 | 対応する Gateway がありかつ `scope` というスコープが定義されているならそのスコープを当てる（複数可）
 * | :fkeyname          | 任意 | JOIN に使用する外部キー名を指定する
 * | [condition]        | 任意 | JOIN に使用する結合条件を yaml で指定する（where 記法）
 * | {condition}        | 任意 | JOIN に使用する結合条件を yaml で指定する（カラム結合）
 * | &lt;groupby&gt;    | 任意 | GROUP BY を指定する
 * | +order-by          | 任意 | ORDER BY を指定する
 * | #offset-limit      | 任意 | LIMIT, OFFSET を指定する
 * | AS Alias           | 任意 | テーブルエイリアスを指定する
 * | .col1, col2 AS C2  | 任意 | 取得するカラムを指定する
 *
 * 上記を base としてさらに JOIN や子供行取得を含めると下記のようになる。
 *
 * - auto join: `'base~base'`
 * - inner join: `'base+base'`
 * - left join: `'base<base'`
 * - subselect: `'base/base'`
 *
 * この記法を**テーブル記法**と呼称する。
 * 端的に言えば SQL の「SELECT 句」「FROM 句」「JOIN 句」「WHERE 句」「GROUP 句」「ORDER 句」「LIMIT 句」をすべてひっくるめて文字列で指定できるイメージである。
 *
 * いくつかの例を以下に記す。
 *
 * #### base
 *
 * ```php
 * # tablename 以外の省略可能なものを省略した最もシンプルな例
 * $db->select('t_article');
 * // SELECT * FROM t_article
 *
 * # @scope でスコープを適用する例（t_article に active というスコープ（where: delete_flg=0）があることが前提）
 * $db->select('t_article@active');
 * // SELECT * FROM t_article WHERE t_article.delete_flg = 0
 *
 * # @scope を続けることで複数のスコープを当てられる（t_article に latest というスコープ（order: create_time DESC）があることが前提）
 * $db->select('t_article@active@latest');
 * // SELECT * FROM t_article WHERE t_article.delete_flg = 0 ORDER BY t_article.create_time DESC
 *
 * # yaml 記法のハッシュの配列で複数条件を指定する（Database::whereInto と同じ）
 * $db->select('t_article@active@latest[flag1: 1, "flag2 < ?": 2]');
 * // SELECT * FROM t_article WHERE (t_article.delete_flg = 0) AND ((t_article.flag = 1) AND (t_article.flag2 < 2)) ORDER BY t_article.create_time DESC
 *
 * # (pkval) で主キーを指定する例
 * $db->select('t_article(1)');
 * // SELECT * FROM t_article WHERE article_id = 1
 *
 * # #offset-limit で取得件数を指定する例
 * $db->select('t_article#40-60');
 * // SELECT * FROM t_article LIMIT 20 OFFSET 40
 *
 * # AS alias でテーブルのエイリアスを指定する
 * $db->select('t_article@active@latest[flag1: 1, "flag2 < ?": 2] AS A');
 * // SELECT * FROM t_article AS A WHERE (A.delete_flg = 0) AND ((A.flag = 1) AND (A.flag2 < 2)) ORDER BY A.create_time DESC
 *
 * # .（ドット）以降で取得カラムを指定する
 * $db->select('t_article@active@latest[flag1: 1, "flag2 < ?": 2] AS A.article_id');
 * // SELECT A.article_id FROM t_article AS A WHERE (A.delete_flg = 0) AND ((A.flag = 1) AND (A.flag2 < 2)) ORDER BY A.create_time DESC
 *
 * # 取得カラムは,（カンマ）区切りで複数指定でき、エイリアスも指定できる
 * $db->select('t_article@active@latest[flag1: 1, "flag2 < ?": 2] AS A.article_id, title AS T');
 * // SELECT A.article_id, A.title AS T FROM t_article AS A WHERE (A.delete_flg = 0) AND ((A.flag = 1) AND (A.flag2 < 2)) ORDER BY A.create_time DESC
 * ```
 *
 * #### join
 *
 * ```php
 * # :fkeyname で JOIN 時の外部キーを指定する（t_article と t_comment に fkeyAC: article_id があることが前提）
 * $db->select('t_article<t_comment:fkeyAC');
 * $db->select('t_article<t_comment'); // 同じ。テーブル間外部キーが一つなら指定は省略できる
 * // SELECT t_article.*, t_comment.* FROM t_article LEFT JOIN t_comment ON (t_comment.article_id = t_article.article_id)
 *
 * # [condition] で JOIN 時の結合条件を指定する（yaml の配列記法は where 的な動作になる）
 * $db->select('t_article<t_comment[delete_flg=0]');
 * // SELECT t_article.*, t_comment.* FROM t_article LEFT JOIN t_comment ON (t_comment.article_id = t_article.article_id) AND (delete_flg=0)
 *
 * # 各テーブル要素には base の記法がほぼすべて使える
 * $db->select('t_article@active[article_id=9] AS A.article_id+t_comment:fkeyAC AS C.comment');
 * // SELECT A.article_id, C.comment FROM t_article AS A INNER JOIN t_comment AS C ON (C.article_id = T.article_id) WHERE article_id=9
 * ```
 *
 * #### subselect
 *
 * ```php
 * # 親子の階層で取得する
 * $db->selectArray('t_article/t_comment');
 * // SELECT t_article.*, t_article.article_id FROM t_article
 * // SELECT t_comment.comment_id AS comment_id, t_comment.* FROM t_comment WHERE t_comment.article_id IN ('1', '2', ...)
 *
 * # 各テーブル要素には base の記法がほぼすべて使える
 * $db->selectArray('t_article@latest[delete_flg=0] AS A.article_id/t_comment AS C.comment');
 * // SELECT A.article_id FROM t_article AS A WHERE delete_flg=0
 * // SELECT C.comment_id AS __dbml_auto_pk, C.comment, C.article_id AS __dbml_auto_ck FROM t_comment AS C WHERE C.article_id IN ('1', '2', ...)
 * ```
 *
 * カラムの修飾はテーブルにエイリアスが貼られている場合はエイリアスで、無いならテーブル名で、と可能な限り正しく修飾される。
 * ただし、明示的に修飾されている場合は一切修飾を行わない。
 * 素のカラム名が現れたときは直近のテーブル名（エイリアス）で修飾される。
 *
 * ただし、構文解析などはしておらず、文字列ではパースに限界があるので、テーブル・カラムレベルでは配列でバラして指定することもできる。
 * 実用上はこっちの指定の方がはるかに多い。
 *
 * ```php
 * # base の最も複雑なものをバラして配列で指定する
 * $db->select([
 *     't_article@active@latest[flag1: 1, "flag2 < ?": 2] AS A' => [
 *         'article_id',
 *         'T' => 'title',
 *     ],
 * ]);
 * // 右記と同じ: $db->select('t_article@active@latest[flag1: 1, "flag2 < ?": 2] AS A.article_id, title AS T');
 *
 * # join の最も複雑なものをバラして配列で指定する
 * $db->select([
 *     't_article@active[article_id=9] AS A' => [
 *         'article_id',
 *         // JOIN 記号のあるキーはカラム取得ではなく JOIN と解釈される
 *         '+t_comment:fkeyAC AS C' => [
 *             // このようにキーなしの配列を与えるとそれは ON 条件になる
 *             // ['on1' => 1, 'on2' => 2],
 *             'comment',
 *         ],
 *     ],
 * ]);
 * // 右記と同じ: $db->select('t_article@active[article_id=9] AS A.article_id+t_comment:fkeyAC AS C.comment');
 *
 * # subselect の最も複雑なものをバラして配列で指定する
 * $db->selectArray([
 *     't_article@latest[delete_flg=0] AS A' => [
 *         'article_id',
 *         // JOIN 記号のないキーはカラム取得ではなく subselect と解釈される
 *         't_comment AS C' => [
 *             'comment',
 *         ],
 *     ],
 * ]);
 * // 右記と同じ: $db->selectArray('t_article@latest[delete_flg=0] AS A.article_id/t_comment AS C.comment');
 * ```
 *
 * さらに、このような取得をするのであれば、実際には `:fkeyname` や `[condition]` は 第2引数の $where で渡したほうが分かりやすい。
 * JOIN のときもテーブル記法として書くよりは上記のようにキーなしで php の構文として指定したほうがはるかにミスが少ない。
 * もっと単純ならそもそも `$db->select` ではなく Gateway で取得したほうが手っ取り早いこともある。
 * 以下に例を記す。
 *
 * ```php
 * # base の最も複雑なものを引数で指定する
 * $db->select([
 *     't_article@active@latest A' => [
 *         'article_id',
 *         'T' => 'title',
 *     ]
 * ], [
 *     'flag2'     => 1,
 *     'flag2 < ?' => 2,
 * ]);
 * # base の最も複雑なものを Gateway で指定する
 * $db->t_article->as('A')->scope('active latest')->column([
 *     'article_id',
 *     'T' => 'title',
 * ])->where([
 *     'flag2'     => 1,
 *     'flag2 < ?' => 2,
 * ])->select();
 * // 2つとも右記と同じ: $db->select('t_article@active@latest[flag1: 1, "flag2 < ?": 2] AS A.article_id, title AS T');
 *
 * # join の最も複雑なものを引数で指定する
 * $db->select([
 *     't_article@active A' => [
 *         'article_id',
 *         '+t_comment C' => [
 *             'comment',
 *         ],
 *     ]
 * ], [
 *     'article_id' => 9,
 * ]);
 * # join の最も複雑なものを Gateway で指定する
 * $db->t_article->as('A')->scope('active')->column([
 *     'article_id',
 *     '+t_comment' => $db->t_comment->as('C')->foreign('fkeyAC')->column('comment'),
 * ])->where([
 *     'article_id' => 9,
 * ])->select();
 * // 2つとも右記と同じ: $db->select('t_article@active[article_id=9] AS A.article_id+t_comment:fkeyAC AS C.comment');
 *
 * # subselect の最も複雑なものを引数で指定する
 * $db->selectArray([
 *     't_article@latest A' => [
 *         'article_id',
 *         't_comment C' => [
 *             'comment',
 *         ],
 *     ]
 * ], [
 *     'delete_flg' => 0,
 * ]);
 * # subselect の最も複雑なものを Gateway で指定する
 * $db->t_article->as('A')->scope('latest')->column([
 *     'article_id',
 *     't_comment' => $db->t_comment->as('C')->column('comment'),
 * ])->where([
 *     'delete_flg' => 0,
 * ])->array();
 * // 2つとも右記と同じ: $db->selectArray('t_article@latest[delete_flg=0] AS A.article_id/t_comment AS C.comment');
 * ```
 *
 * ここまで来ると文字列ではなくほぼ php 構文になるのでかなり直感的になる。
 * テーブル記法については {@link SelectBuilder::column()} や {@link TableDescriptor} にも記載があるのでそちらも参照。
 *
 * ### テーブル名 ⇔ エンティティ名の自動変換
 *
 * ```php
 * $db = new Database($connection, [
 *     // テーブル名 <=> エンティティの変換を行うクロージャを指定する
 *     'tableMapper'    => function ($tablename) {
 *         $entityname = ucfirst(preg_replace('#^t_#', '', $tablename));
 *         return [
 *             // キーが単一のエンティティ名として使用され、要素として使用されるエンティティクラスやゲートウェイクラスを指定できる
 *             $entityname => [
 *                 'entityClass'  => 'vendor\\Entity\\' . $entityname,
 *                 'gatewayClass' => 'vendor\\Gateway\\' . $entityname,
 *             ],
 *             // 複数返しても良い。その場合「テーブル名 => エンティティ」が1対多の関係になる
 *         ];
 *     },
 *     // デフォルトのエンティティやゲートウェイを使うなら文字列を返しても良い
 *     'tableMapper'    => function ($tablename) {
 *         return ucfirst(preg_replace('#^t_#', '', $tablename));
 *     },
 * ]);
 * ```
 *
 * このような設定を行うと、内部で「DB テーブル名 ⇔ エンティティクラス名」の相互変換を行うことができる。
 * これによって「"t_article" テーブルは php 内で "Article" として扱う」のようなことが可能になる。
 *
 * この設定の効果として、具体的には下記のコードが等価になる。
 *
 * ```php
 * $db->selectArray('Article.*');
 * $db->selectArray('t_article.*');
 *
 * $db->insert('Article', $dataarray);
 * $db->insert('t_article', $dataarray);
 * ```
 *
 * つまり、 SELECT 系クエリで引っ張るときに t_article は Article とみなされ、更新系クエリのときは Article は t_article とみなされるようになる。
 * 結果的に「t_article テーブルから引っ張ったら自動で Article キーになってた」「Article キーで更新したら自動で t_article が更新されてた」という動作になる。
 * が、下記の制限から「アプリレイヤーによる自動エイリアス」と考えても差し支えない。端的に言えば「SQL レベルでエイリアスを明示しなくても "t_article" でも "Article" でもどっちでも認識する」ということになる。
 *
 * 変換は、変換前と変換後で決して同じ名前があってはならない。さらに、変換した結果が同じ文字列になるのも厳禁。
 * 要するにエンティティ名 => テーブル名が曖昧になってはならない。
 *
 * この機能はオンにしたところで「DB テーブル名 ⇔ エンティティクラス名の自動読み替え」程度の違いしか生まれず、その他の箇所にほとんど影響しない。
 * 実装上、影響がある箇所は下記のみ。
 *
 * | 影響箇所                                               | 説明
 * |:--                                                     |:--
 * | t_article で select すると キーが Article で返ってくる | 単に返ってくるキーの違いであり、 自動でエンティティ化したりはしない。エンティティで欲しい場合は常に `cast()` を呼ぶ
 * | t_ancestor.*** で取得する子供列がエンティティ名になる  | 上記の派生。テーブル名を指定できない以上、エンティティで返すしかないため
 * | `$qb->cast(null)` するとエンティティインスタンスを返す | {@link SelectBuilder::cast()} を参照
 * | `$db->Article` が取得可能になる                        | `Article` にあたるものは本来テーブル名だが、エンティティ名でも TableGateway が取得できるようになる
 *
 * 最後の項目について補足すると t_article ⇔ Article というマッピングが存在するとして、
 *
 * - `$db->t_article`
 * - `$db->Article`
 *
 * のどちらでも Gateway が得られるようになる。
 * メソッド体系などは全て同じだが、 array, tuple などの取得系メソッドの返り値の型が異なってくる。
 *
 * - `$db->t_article->tuple('*', ['id' => 1]);`
 * - `$db->Article->tuple('*', ['id' => 1]);`
 *
 * 上は**プレーンな配列**で1行を返すが、下は **エンティティインスタンス**で1行を返す。
 * その「エンティティインスタンス」とは tableMapper で指定した完全修飾クラス名である。
 *
 * ### テーブル名 ⇔ ビュー名の自動変換
 *
 * tableMapper は前述に加えてテーブルとビューのマッピングも指定できる。
 *
 * ```php
 * $db = new Database($connection, [
 *     'tableMapper'    => function ($tablename) {
 *         $entityname = ucfirst(preg_replace('#^t_#', '', $tablename));
 *         return [
 *             $entityname => [
 *                 'selectView' => preg_replace('#^t_#', 'v_', $tablename),
 *             ],
 *         ];
 *     },
 * ]);
 * ```
 *
 * このような設定を行うと、内部で「ビュー名 ⇒ テーブル名」の自動変換が行われ、view は table の外部キーやインデックスを引き継ぐようになる。
 * これによって「取得時は view, 更新時は table」のようなことが透過的に可能になる。
 * さらに view は存在しなくてもよい。存在しない場合テーブル名が代理で使用される。
 *
 * この設定の効果として、具体的には下記のようなコードが書ける。
 *
 * ```php
 * // v_article と t_comment は外部キーで繋がっていないが、 t_article の外部キーで接続される
 * $db->selectArray('v_article+t_comment');
 *
 * // v_article は view なので本来更新できないが、t_article への insert と読み替えられる
 * $db->insert('v_article', $dataarray);
 * ```
 *
 * つまり・・・
 * SELECT 系を実行するとき v_article は t_article の外部キーを引き継ぐため、あたかも t_article であるかのように JOIN できる。
 * AFFECT 系を実行するとき内部的には単純に t_article への更新とみなされる。
 *
 * v_article に子テーブルの件数や exists などの SELECT, 論理削除などの WHERE を設定しておけば「便利な t_article」として使用できる。
 *
 * ### 外部キーの扱いについて
 *
 * TableA ⇔ TableBのような相互参照外部キーの場合は例外が飛ぶ。
 * したがってそのような場合は外部キー自動指定はできず、明示的にカラムを指定する必要がある。
 * （これは安全性のため。外部キーの定義順で動作が異なるような現象は気づきにくいバグの温床になる）。
 *
 * TableA ⇔ TableBのような相互参照外部キーは早々無いだろうが、今後外部キーが追加されることを考えてただ通るだけのテストでもいいので何かしらで担保したほうが良い。
 *
 * また、外部キーによるリレーションは「辿れる限り辿る」実装になっている。
 * 下記のようなテーブルがあると仮定する。
 *
 * ```text
 * 関連α
 * tableA           tableB           tableC
 *                                    PK(auto)
 *                   PK(auto) <-----  FK
 *  PK(auto) <-----  FK
 *
 * 関連β
 * tableA           tableB           tableC
 *  PK1      <-----  PK1 & FK <-----  PK1 & FK
 *                   PK2      <-----  PK2 & FK
 *                                    PK3
 * ```
 *
 * 関連αはいわゆる「サロゲートキー構成」で、関連βはいわゆる「ナチュラルキー構成」である。
 * 関連αにおいて tableA と tableC に直接的な相関はない。相関を持たせるには必ず tableB を経由する必要がある。
 * 一方、関連βは複合主キーがあり、 tableB を介さずとも tableA と tableC に関連がある、とみなすことができる。
 *
 * この関連βのとき、 tableA と tableC は「外部キーによる相関がある」とみなされる。
 * つまり・・・
 *
 * ```php
 * // 下記のコードを実行すると・・・
 * $db->tableA()->tableC()->array();
 *
 * // 関連αの場合はエラー（tableA と tableC は相関がないので JOIN できない）
 * // 関連βの場合は辿れる（SELECT tableA.*,tableC.* FROM tableA INNER JOIN tableC ON tableA.PK1 = tableC.PK1）
 * ```
 *
 * となる。
 * この機構は外部キーを見る箇所全てで有効である（join, subselect, subexists など）。
 *
 * ### 自動カラムキャスト
 *
 * autoCastType オプションを設定すると DB の型を活かして `Doctrine\DBAL\Types\Type` で変換して取得する。
 *
 * ```php
 * // 具体的にはソースのコメントを参考
 * $db->setAutoCastType([
 *     // DATETIME 型は「取得時は変換」「更新時はそのまま」となるように設定
 *     Type::DATETIME => [
 *         'select' => true,
 *         'affect' => false,
 *     ],
 *     // SARRAY 型は「取得時も更新時も変換」となるように設定
 *     Type::SIMPLE_ARRAY => true,
 * ]);
 * $row = $db->selectTuple('t_article', ['id' => 1]);
 * // t_article に public_time: DATETIME が定義されているとすると・・・
 * var_dump($row['public_time']);
 * // results: このようになぜか DateTime インスタンスで返してくれている
 * class DateTime#2 (3) {
 *   public $date =>
 *   string(26) "2017-12-07 21:42:56.000000"
 *   public $timezone_type =>
 *   int(3)
 *   public $timezone =>
 *   string(10) "Asia/Tokyo"
 * }
 *
 * // t_article に public_option: SARRAY(実態は TEXT) が定義されているとすると・・・
 * var_dump($row['public_option']);
 * // results: このようになぜか配列で返してくれている
 * array(3) {
 *   [0] =>
 *   string(2) "10"
 *   [1] =>
 *   string(2) "30"
 *   [2] =>
 *   string(2) "50"
 * }
 * // さらに SARRAY は更新時も有効なので・・・
 * $db->insert('t_article', [
 *     'article_id'    => 1,
 *     'public_option' => ['10', '30', '50'],
 * ]);
 * // このように insert に直接配列を突っ込むことができる（「Array to string conversion」などと怒られたりはしない）。
 * ```
 *
 * 実際のところとんでもないほど強力な機能だが、その分パフォーマンスは劣化する（得られた全行全列に対して実行するので）。
 * また、 mysql のみ完全対応で、その他の DBMS はオマケのような位置付けになっている（一応それなりには動きはする）。
 * ただし、現在のところほぼグローバル設定で動作し、「テーブルごとに個別設定」のような動作は不可能。
 * これについては Gateway や Entity を利用して順次改善していく見込み。
 * （例えば Gateway のスコープに型情報を持たせたり、エンティティのフィールドの型でワイヤリングしたりなど）。
 *
 * ### 仮想カラム
 *
 * {@link Database::overrideColumns()} で「あたかもテーブルにあるかのように振る舞うカラム」を定義できる。
 *
 * ありがちな例だが「姓」「名」を持つテーブルに対して `overrideColumns(['usertable' => ['fullname' => Expression::new('CONCAT(sei, mei)')]]);` すると、あたかもフルネームカラムがあるように振る舞わせることができる。
 * 使用例などはメソッドを参照。
 *
 * 原則として仮想カラムを引っ張るためには明示的な指定が必要で、 `*` や `!ignore` で引っ張ったとしても取得列に含まれることはない。
 * ただし追加するときに `implicit` を true にすると取得列に含まれるようになる（それでも `*` は不可）。
 * 仮想カラムを含めた全てを取得したい場合は '!' とする（{@link SelectBuilder::column()} を参照）。
 *
 * 明示使用の場合でも今のところ select, where, having でのみ使用可能。
 * orderBy は select に含めて指定すれば実現できるし、having も mysql であれば（設定次第で）直接式を指定することができるので、実質的には select, where でのみの使用となることが多いはず。
 *
 * ### 拡張クラス
 *
 * 一部のクラスは {@link FactoryTrait} を use しており、これらのクラスは継承した拡張クラスで insteadof を呼ぶことでそのクラスが代わりに使われるようになる。
 *
 * ```php
 * class MySelectBuilder extends SelectBuilder
 * {
 *     public function hoge()
 *     {
 *         // something
 *     }
 * }
 * MySelectBuilder::insteadof();                     // これをすると SelectBuilder の代わりに MySelectBuilder が使用されるようになる
 * SelectBuilder::insteadof(MySelectBuilder::class); // これでもよい（一応こっちが本来の使い方）
 *
 * $db->select('t_table')->hoge(); // 呼べる
 * ```
 *
 * 乱用は禁物だし、ドラスティックな変更はできないがちょっとした拡張ポイントのように使うことができる。
 * ただし、これで拡張した場合互換性担保の対称にならないため注意。
 */
namespace ryunosuke\dbml {

    // ドキュメント内の名前解決用に use しているだけで深い意味はない
    use ryunosuke\dbml\Gateway\TableGateway;
    use ryunosuke\dbml\Metadata\Schema;
    use ryunosuke\dbml\Query\SelectBuilder;
    use ryunosuke\dbml\Query\TableDescriptor;
    use ryunosuke\dbml\Mixin\FactoryTrait;

    assert(class_exists(TableGateway::class));
    assert(class_exists(Schema::class));
    assert(class_exists(TableDescriptor::class));
    assert(class_exists(SelectBuilder::class));
    assert(trait_exists(FactoryTrait::class));
}
