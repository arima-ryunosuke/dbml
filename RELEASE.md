# RELEASE

バージョニングはセマンティックバージョニングでは**ありません**。

| バージョン   | 説明
|:--           |:--
| メジャー     | 大規模な仕様変更の際にアップします（クラス構造・メソッド体系などの根本的な変更）。<br>メジャーバージョンアップ対応は多大なコストを伴います。
| マイナー     | 小規模な仕様変更の際にアップします（中機能追加・メソッドの追加など）。<br>マイナーバージョンアップ対応は1日程度の修正で終わるようにします。
| パッチ       | バグフィックス・小機能追加の際にアップします（基本的には互換性を維持するバグフィックス）。<br>パッチバージョンアップは特殊なことをしてない限り何も行う必要はありません。

なお、下記の一覧のプレフィックスは下記のような意味合いです。

- change: 仕様変更
- feature: 新機能
- fixbug: バグ修正
- refactor: 内部動作の変更
- `*` 付きは互換性破壊

## x.y.z

- Entity 消したい。使わない…
- phpstorm と相性が悪いのでマジックメソッドを撲滅したい

## 3.1.11

- [fixbug] $data や $where は多様な型が来うるので mixed を混ぜないと IDE のエラーが出ることがある
- [fixbug] php8.2 での warning
- [fixbug] scope 処理の notice を修正
- Merge tag 'v2.1.27'

## 3.1.10

- [feature] debug フラグを新設
- [feature] affect(insert|modify)Select でエイリアスをカラムとして使用できる機能
- [feature] modifySelect を追加
- [feature] CompatiblePlatform に設定を持たせる
- [change] マジックカラムの登場で $tableDescriptor を指定しないこともあるのでデフォルト引数に変更
- [feature] クエリビルダーに生クエリ機能を追加
- [feature] virtualColumn と同様に Scope 属性でパラメータを指定できる機能
- [feature] Scope を別クラスに分割
- [feature] 行セット全体のコールバック機能
- [feature] 子供がいるかを返す using/subusing を追加
- [refactor] 外部キー系3兄弟を Affect から Abstract に格上げ
- [feature] yield 系メソッドに chunk を渡せるように修正
- [fixbug] トランザクション内で insertOrUpdate がコケる不具合
- [fixbug] デフォルトスコープが定義できない不具合
- [tests] リネーム分のテスト
- [change] InShare を ForShare にリネーム
- [change] upsert を insertOrUpdate にリネーム
- [change] 外部キーが伴う affect 系メソッドをリネーム
- [composer] update
  - dbal: 3.10.0
- Merge tag 'v2.1.26'
- Merge tag 'v2.1.25'

## 3.1.9

- [feature] TableGateway の CTE 機能
- [feature] 外部キー名の代わりにカラム名で JOIN できる機能

## 3.1.8

- [fixbug] echoAnnotation で第1カラムが消える不具合

## 3.1.7

- [feature] paginate の countperpage を nullable 化
- [fixbug] 自動生成された Gateway の空行スペースを削除

## 3.1.6

- [feature] マルチカラム Where
- [feature] マルチカラム Select
- [feature] エイリアスの指定文字で入れ子にする機能
- [refactor] 試行錯誤のコードが残っていたので除去
- [refactor] getSpaceshipSyntax 改め getSpaceshipExpression
- [fixbug] Operator::lazy で再生成されない
- [fixbug] LockWaitTimeoutException が投げられない
- [fixbug] autoCast が有効じゃないと明示指定してもキャストが効かない
- Merge tag 'v2.1.24'
- Merge tag 'v2.1.23'
- Merge tag 'v2.1.22'

## 3.1.5

- [refactor] token_get_all を PhpToken::tokenize に変更
- [feature] SAVEPOINT でもログをインデントする

## 3.1.4

- [fixbug] pk したときに AndPrimary が動かない不具合
- [feature] IteratorTrait に apply を追加
- [feature] 特殊なメソッドの返り値の型の対応

## 3.1.3

- [feature] カラムにクラス名を指定するとオブジェクトが得られる機能
- [feature] select 句自体のコメントに対応
- [feature] テーブル記法のコメントに対応
- [fixbug] 一度キャッシュすると通常クエリもキャッシュされてしまう不具合
- [feature] TableGateway に immutable モードを追加
- [feature] TableGateway の get/set でカラムアクセスできる機能
- [feature] OptionTrait に shallow モードを追加
- [feature] generate 機能の強化

## 3.1.2

- [tests] sqlite の memory までキャッシュされているので明確に消す
- [feature] 未知のオプションを与えていると notice を出す
- [feature] コンストラクタで文字列指定の場合は URL とみなす

## 3.1.1

- [feature] 接続のリトライ機能
- [feature] 全体として更新したかを返す isAffected メソッド
- [fixbug] 暗黙のコミット/ロールバックで正常フローに乗らない不具合
- [change] savepoint の切り替え機能を非推奨化
- [change] ドライバスキームに統一感がないのを修正

## 3.1.0

- [refactor] Builder の整合性のないところを修正
- [*feature] OrderBy 周りを改善
- [*feature] Window 機能を強化
- [*feature] view の更新機能と table 情報の継承
- [*change] patch も 3/4 の両対応しなければならない
- [*change] dbal4.* の対応
- [refactor] php8.2 でも最低限動くように修正
- [refactor] code format, fix inspection
- [refactor] echoAnnotation のヒントを属性化
- [fixbug] Database を経由しない invoke が効いていない不具合
- [*fixbug] SelectBuilder の OrderBy の順番がおかしい不具合
- [*fixbug] TableDescriptor 経由の orderBy,range が効かない不具合
- [*change] 非互換・非推奨の削除
- [*change] setTableColumn の遅延化
- [*change] 1つでもメソッドエイリアスされるトレイトの可視性を private に変更
- [*change] 歴史的経緯でメチャクチャだった opt を正規化
- [*change] 隠し引数ではなくなったため chunk が引数で指定可能になった
- [*change] 隠し引数 opt の撲滅
- [feature] chunk の min/max 固定化機能
- [feature] Gateway/Entity クラスを出力するechoTableClass を実装
- [feature] EnumType を追加
- [feature] mysql8.0.20 から VALUES が非推奨になってたので対応
- [feature] insert/modifyArray の andPrimary 対応
- [feature] JSON 機能の強化
- [feature] JSON 集約機能を追加
- [feature] MEDIAN 集約機能を追加
- Merge tag 'v2.1.22'

## 3.0.3

- [feature] デバッグログ機能
- [feature] 複合型を JSON にするオプションを追加
- [feature] 元のレコードを返す AndBefore 機能
- [change] duml 表を削除
- [fixbug] エイリアスを付けたゲートウェイで更新系クエリを実行すると例外になる不具合
- [fixbug] exists/notExists を複数回呼ぶとエラーになる不具合
- [fixbug] テーブルエイリアスがテーブル記法として反応していない不具合
- [fixbug] assert が暴発している不具合
- [refactor] 妙な goto があったので除去

## 3.0.2

- [composer] update

## 3.0.1

- [feature] FactoryTrait を導入して外部から多少拡張できるようになった
- [feature] トランザクションログをインデントする機能
- [feature] deleteArray を実装

## 3.0.0

- [change] php>=8.0
- [*change] 古い仕様・互換用の仕様を廃止
  - 一部のクラス名・メソッド名を変更
  - 暗黙の外部キーの affected rows は結果に含めない
  - update,delete,invalid が完全に外部キーを見るようになった
  - upsert が「存在チェックしてから insert/update」という挙動から「insert してダメなら update」という挙動になった
  - dryrun は常に文字列配列を返すようになった
  - insertArray が主キー配列を返せるようになった
  - orderByPrimary -> OrderBy::primary()
  - orderBySecure -> OrderBy::secure()
  - orderByRandom -> OrderBy::random()
  - Conditionally トレイトの廃止
  - Prepare トレイトの廃止
  - クエリビルダ書き換えによる型キャストを廃止
  - mysql の FoundRows サポートを廃止
  - 存在するカラムの時は単一値クロージャになる仕様を廃止
  - PDO の特別扱いを廃止
  - yamlParser を廃止
  - getAnnotation の廃止
  - injectCallStack を廃止
  - anywhere を廃止
  - autoOrder を廃止
  - orderByPhp の仕様を廃止
  - QueryBuilder を affect 系の SET や JOIN に流用する仕様を廃止
  - column 以外のスラッシュネスト仕様を廃止
  - Paginator が0件の時の first/last を null に変更
  - Sequencer の双方向サポートを廃止
  - Sequencer の負数降順を廃止
  - Sequencer の has メソッド改名

## 2.1.27

- [feature] chunk(sequence) のマルチカラム対応
- [fixbug] chunk で同じ連番が出現する不具合
- [fixbug] トランザクション内の paginate/sequence は即時コールする

## 2.1.26

- おかしな分岐になっていたので空タグ

## 2.1.25

- [fixbug] 精度付き DATETIME に float を与えても効果がない不具合

## 2.1.24

- [package] dbal 3.9

## 2.1.23

- [fixbug] BackedEnum の判定にポリフィルも追加
- [feature] orderBy に配列を与えると任意順にできる機能

## 2.1.22

- [feature] echoAnnotation で affect 系の補完も出す機能

## 2.1.21

- [composer] update

## 2.1.20

- [feature] 強制的に値埋め込みを行う dynamicPlaceholder オプションを追加

## 2.1.19

- [fixbug] mysql の phrase が照合順序に依存していた不具合を修正

## 2.1.18

- [feature] 外部キーにメタデータを設定できる機能

## 2.1.17

- [feature] slave のランダム化と serverVersion や driverOptions などに対応した url を実装
- [feature] checkSameKey オプションを追加
- [feature] truncate の cascade を pgsql 以外でも対応
- [feature] 外部キーをよしなにする UPDATE を実装
- [feature] 外部キーの有効無効切り替え機能
- [fixbug] 主キーが含まれていない場合にも例外が飛んでいたのを修正
- [fixbug] bind parameter の型が渡らない不具合を修正

## 2.1.16

- [feature] トランザクションのリトライ処理を改善
- [feature] executeAffect のリトライを実装
- [feature] executeSelect のクエリキャッシュを実装
- [refactor] 諸々修正
- [refactor] affect のバリエーションでコード補完が ...$args になる件の改善
- [refactor] select のバリエーションを trait 化
- [change] Driver 周りの整理
- [fixbug] echoAnnotation のクラス名が完全修飾になっていない不具合を修正
- [fixbug] replace で与えていないデータが null エラーになる不具合を修正
- [fixbug] 仮想カラムの where に配列以外が来ると Warning が出る不具合を修正
- [fixbug] CTE があると orderBySecure が例外を吐く不具合を修正
- [fixbug] ForUpdate/InShare で arrayFetch オプションが効かない不具合を修正
- [fixbug] 結合条件が違うにも関わらず同じテーブルとして統合されてしまう不具合を修正
- [fixbug] phrase 検索の不具合を修正
- [fixbug] spaceship 演算子で column が NULL だと常に TRUE になっていた不具合を修正
- [feature] バインドパラメータ対応型を拡張
- [feature] ランダム機能を実装
- [feature] getTableColumns で特定条件を伏せる機能
- [feature] CompatiblePlatform にバージョンを導入
- [feature] modify(Array) の updateData に * キーを与えると insertData の値で埋められる機能
- [feature] TableGateway の特殊メソッドのキャッシュ化と属性化
- [feature] キャッシュのウォームアップメソッドを追加
- [feature] debugInfo の整理

## 2.1.15

- [fixbug] affectArray に affected rows の入れ忘れ

## 2.1.14

- [fixbug] insert/modifyArray で AUTOINCREMENT を混ぜるとエラーになる不具合を修正
- [fixbug] Tablegateway 経由だと arrayFetch オプションが効かない不具合を修正
- [feature] 例外を投げずに常に主キーを返す AndPrimary を実装
- [feature] 行自体がメソッドを持つ affectArray を実装
- [feature] 論理削除を行う invalid メソッドを実装
- [feature] バインドパラメータにクロージャを指定できる機能

## 2.1.13

- [fixbug] async 無名クラスが解放されないことがある不具合を修正
- [fixbug] 非同期クエリで型が死ぬ不具合を修正
- [feature] デフォルトチャンクサイズ・条件を指定できる defaultChunk オプションを追加
- [feature] exists されたクエリビルダを実行する方法がなかったので existize を追加
- [fixbug] countize で余計なメソッドが呼ばれていた不具合を修正
- [fixbug] subbuilder の chunk が効いていない不具合を修正
- [fixbug] changeArray で一意エラーが出る不具合を修正
- [refactor] 無駄が多いので CompatibleConnection の生成をキャッシュ化

## 2.1.12

- [feature] CsvGenerator の自動ヘッダ出力機能
- [feature] Yielder の chunk 対応
- [fixbug] yield 系メソッドに QueryBuilder を渡すとサブ設定が消えてしまう不具合を修正
- [feature] 空データ UPDATE 対応
- [fixbug] orderBy が複数のテーブル記法に対応していない不具合を修正
- [feature] orWhere(Having)/endWhere(Having) を実装

## 2.1.11

- [fixbug] キャッシュがあると遅延外部キーが有効にならない不具合を修正
- [fixbug] 条件付き外部キー join でテーブル修飾子が入れ替わることがある不具合を修正

## 2.1.10

- [feature] prepare でコケた時にもパラメータでログれる機能
- [feature] 配列の差分を取る機能
- [feature] modify 系で一意制約を指定できる機能
- [feature] ユニークキーのカラムを取得する機能
- [change] デフォルトキャッシュディレクトリを変更

## 2.1.9

- [feature] length を超える文字列が来た時に切り落とす機能
- [feature] numeric をタイムスタンプとみなす機能
- [change] Parser のパラメータが足りないときは NULL ではなく文字列を埋め込むように変更
- [fixbug] Parser に非連番パラメータを渡すとエラーになる不具合を修正
- [fixbug] 行値式の IN に空配列を与えたときにエラーになる不具合を修正

## 2.1.8

- [fixbug] 外部キーをキャッシュしてしまってキャッシュオブジェクトが巨大になる不具合を修正

## 2.1.7

- [feature] 仮想外部キーにオプション（onUpdate/Delete, condition）を渡せる機能と条件付き外部キー
- [feature] トランザクション中だけログる機能

## 2.1.6

- [feature] クロージャのデフォルト引数を自動で依存カラムに加える機能

## 2.1.5

- [feature] Paginator の shownPage をステートレスに変更
- [feature] changeArrayReturning の改善

## 2.1.4

- [feature] TableGateway の set スコープ対応
- [feature] TableGateway でも secureOrderBy を使用できるようにした
- [fixbug] orderBySecure が "+-" プレフィックスに対応していない不具合を修正

## 2.1.3

- [change] echoPhpStormMeta の $innerOnly を廃止（非推奨）
- [fixbug] echoAnnotation で TableGateway と EntityGateway が重複する不具合を修正

## 2.1.2

- [feature] 非同期クエリを実験的に実装
- [feature] mysqli の updateOrThrow をマッチ行にする
- [feature] PDO ではなく一部のエクステンションに対応
- [change] クエリパーサを使えるところは使う
- [feature] クエリパーサの導入
- [feature] onIntrospectTable イベントを追加
- [feature] CompatiblePlatform に SLEEP 構文を追加
- [feature] affect 系の where で空文字キーを主キーに読み替える機能
- [feature] 型の強化

## 2.1.1

- [feature][QueryBuilder] arrayFetch に null を与えると親の fetch method が伝播される機能
- [feature][Database] changeArray に RETURNING 的挙動を実装
- [refactor][Database] バルク系の chunk の実装を iterator_chunk に変更
- [refactor][Database] normalize のループが冗長かつ非効率だったので修正
- [fixbug][Database] SQLServer で BINARY に文字列を入れようとするとエラーになる不具合を修正
- [fixbug][Schema] SQLServer で view のカラムが得られていなかった不具合を修正
- [fixbug][QueryBuilder] SQLServer の WITH に RECURSIVE がついてしまう不具合を修正
- [fixbug][QueryBuilder] 遅延外部キーが遅延されていなかった不具合を修正
- [feature][Logging] json を追加
- [feature][Logging] metadata の 直値と固定クロージャ対応
- [feature][Logging] すべてのログに time データを追加
- [change][Logging] 失敗時はログレベルを変える
- [fixbug][Logger] パラメータが数値配列になっていた不具合を修正

## 2.1.0

- [*change][all] マジックメソッドを極力廃止して trait に変更
- [*change][all] 一部のオプションのデフォルト値を変更
- [*change][Database] 仮想カラムの expression 指定を廃止
- [*change][Database] execute(Query|Statement) の廃止
- [*change][Database] modifyAutoSelect オプションの廃止
- [*change][Database] DoctrineCache の廃止
- [*change][Logger] DoctrineLogger の廃止
- [*change][Schema] getTableColumnMetadata の廃止
- [*change][Operator] phrase 演算子を正規表現で再実装
- [feature][Operator] between や 範囲演算子の INF 対応
- [feature][Database] CTE を宣言する declareCommonTable を追加
- [fixbug][Paginator] getPageRange で小数が混ざる不具合を修正
- [fixbug][QueryBuilder] with の RECURSIVE は識別子に紐づくものではなく句に紐づく
- [fixbug][TableGateway] get/set の両方で仮想カラム実装されていると一方しか有効にならない不具合を修正
- [fixbug][Database] refresh すると cache が配列になってしまう不具合を修正
- [fixbug][Database] initCommand が空文字のときにエラーになる不具合を修正

## 2.0.22

- [change] doctrine:3.5 の対応
- [change] doctrine:3.4 の対応
- [change] CustomSchemaOption の非推奨対応
- [change] Events の非推奨対応
- [change] SQLLogger の非推奨対応

## 2.0.21

- [refactor] fix format/inspection
- [fixbug][Database] convert の順番に差異がありメソッドによって結果が変わったり notice が出ることがある不具合を修正
- [fixbug][TableDescriptor] column に空文字が紛れている場合があり notice が出ていた不具合を修正

## 2.0.20

- [all] php8.1 対応
- [all] update 対応

## 2.0.19

- [feature][Gateway] nomalize フックを実装
- [feature][Database] スキーマ漁りのコールバックを実装
- [fixbug][QueryBuilder] callback で静的クロージャを bind しようとする不具合を修正

## 2.0.18

- [fixbug][Adhoc] modifier で実在しないカラムにも付与されていた不具合を修正
- [feature][Database] データ配列を SQL に変換する migrate メソッドを追加
- [feature][Database] changeArray で bulk や prepare を明示的に指定できるように変更
- [feature][Database] updateArray に chunk 引数を追加
- [feature][Database] not null に null を入れようとしたときにフィルタする FilterNullAtNotNullColumn オプションを追加
- [fixbug][Database] changeArray に空文字主キーが来た場合に更新外削除が不正になる不具合を修正
- [fixbug][Database] reduce が 0 で呼べない不具合を修正
- [fixbug][Database] パラメータが空のときに INSERT SET 構文を使うとエラーになる不具合を修正
- [fixbug][Database] save で親外部キーと子外部キーの名前が違うとエラーになる不具合を修正
- [fixbug][Database] 状況によっては tablemap の例外が飛ばない不具合を修正

## 2.0.17

- [feature][Logger] シグネチャで渡ってくる引数が可変になる機能
- [feature][TableGateway] 空文字でテーブルに紐付かないカラム指定ができる機能
- [feature][QueryBuilder] bool で主キーの order by ができる機能
- [feature][Schema] テーブルに SchemaConfig を設定する

## 2.0.16

- [fixbug][Yielder] setBufferMode が動いていない不具合を修正
- [fixbug][Database] checkSameColumn 指定時に export するとエラーになる不具合を修正
- [feature][Database] convertBoolToInt を新設
- [refactor] composer update

## 2.0.15

- [change][Database] echoAnnotation の出力を変更
- [feature][Database] convertEmptyToNull の制限を緩和
- [feature][QueryBuilder] 主キーカラム・エニーカラム・仮想カラムなどのトップレベルの制限を緩和
- [fixbug][TableGateway] create がやっつけ実装だったので改善

## 2.0.14

- [feature][Database] ForAffect（ForUpdateOrThrow のエイリアス）を実装
- [feature][Database] create メソッドを実装
- [fixbug][Database] autoCastType と Expression の併用で値が空になってしまう不具合を修正
- [feature][Operator] フレーズ演算子

## 2.0.13

- [refactor][CompatiblePlatform] dbal と重複している機能を委譲
- [feature][Database] 一部対応していなかった affect 系メソッドの dryrun に対応
- [fixbug][Database] symfony/cache が入っていない環境でエラーになっていた不具合を修正
- [fixbug][Database] 自動採番列 が null のときに伏せる処理があると modify がコケることがある不具合を修正

## 2.0.12

- [fixbug][Database] echoPhpStormMeta で autoCastType を考慮するように修正

## 2.0.11

- bump version
  - php: 7.4
  - doctrine: 3.*
- [feature][Database] declareVirtualTable で仮想テーブルを登録できるように実装
- [feature][QueryBuilder] subselect 仮想カラムに配列が来た場合に where して exists する機能
- [fixbug][Database] 仮想カラムの where で ! が効かない不具合を修正
- [feature][Gateway] 特殊なメソッドを定義するとスコープ・仮想カラムとして使える機能

## 2.0.10

- [feature][Database] echoPhpStormMeta 周りを修正

## 2.0.9

- [feature][Database] IGNORE シンタックスが使えるメソッドに ～Ignore を用意
- [feature][Database] save の実装
- [feature][Database] changeArray のリファクタ
- [feature][Database] sqlite と postgresql の merge を実装
- [feature][Database] isEmulationMode を追加
- [feature][Database] 仮想カラムの更新機能
- [change][Database] modify でエラーにならないように insert する機能の廃止
- [change][TableGateway] bindScope は引数が累積するのではなくオリジナルの上書きとする

## 2.0.8

- [feature] bump version

## 2.0.7

- [fixbug][TableGateway] 継承クラスで scopes にアクセスできない不具合を修正
- [feature][TableGateway] paginate/sequence の委譲メソッドを追加
- [feature][Database] 暖気運転を行う warmup メソッドを追加
- [fixbug][QueryBuilder] トップレベル以外で仮想カラムが使われてしまう不具合を修正
- [feature][QueryBuilder] before/after コールバックを実装
- [feature][QueryBuilder] 配列時の取得方法を指定する arrayFetch オプションを追加
- [feature][Logger] メタデータの出力機能を追加

## 2.0.6

- [feature][QueryBuilder] with 句の対応
- [feature][QueryBuilder] 常に末尾に並び順を追加できる defaultOrder オプションを追加
- [feature][QueryBuilder] nullsOrder を個別に当てられるように修正
- [fixbug][QueryBuilder] where を呼ぶだけで遅延仮想カラムの取得が走っていた不具合を修正

## 2.0.5

- [fixbug][Database] overrideColumns で循環参照になることがある不具合を修正
- [fixbug][QueryBuilder] 仮想カラムが暴走する不具合を修正
- [feature][QueryBuilder] NULL の並び順を制御できる nullsOrder オプションを追加
- [feature][Paginator] 前後ページがあるかを返す hasPrev/hasNext を実装

## 2.0.4

- [feature][Database] 仮想カラムの型はある程度推測できるのでそのようにした
- [fixbug][Database] reduce で想定より多くの行が消えてしまう不具合を修正
- [fixbug][Database] range の [空, 空] の自動フィルタの不具合を修正
- [feature][QueryBuilder] 設定されている select 句を伏せることができる unselect メソッドを実装
- [feature][QueryBuilder] テーブル指定でスコープを当たられる scope メソッドを実装
- [fixbug][QueryBuilder] 仮想カラムを使っていないのに値が変わってしまう不具合を修正
- [fixbug][QueryBuilder] Queryable に ASC/DESC 込みで orderBy すると不正な sql になる不具合を修正
- [fixbug][QueryBuilder] 親・子行がない時に notice エラーが出る不具合を修正
- [feature][TableGateway] スコープが定義されているか調べられる definedScope を実装
- [feature][TableGateway] スコープのデフォルト引数を設定できる bindScope を実装
- [fixbug][TableGateway] mixScope で可変引数があると値が渡らない不具合を修正

## 2.0.3

- [change][TableDescriptor] テーブル記法の埋め込みを json ではなく paml に変更
- [fixbug][TableDescriptor] 特定条件で仮想カラム・スコープが見つからない不具合を修正
- [fixbug][QueryBuilder] スコープが JOIN 記述を含んでいても無視される不具合を修正
- [fixbug][TableGateway] 継承したクラスで private エラーになる不具合を修正
- [feature][TableGateway] mixScope で追加スコープを与えられるように修正
- [feature][Database] 曖昧な外部キーや存在しない外部キー指定時にしっかりと例外が飛ぶように改善

## 2.0.2

- [feature][QueryBuilder] 暗黙スコープを指定可能にした
- [feature][TableGateway] affect 系にスコープを当てないオプションを用意
- [fixbug][Database] echoAnnotation の結果が FQSEN じゃない不具合を修正

## 2.0.1

- [feature][QueryBuilder] on メソッドを追加してサブクエリとの JOIN 機能を強化
- [feature][QueryBuilder] operatize を実装
- [feature][Operator] default の行値式対応
- [feature][Database] echoPhpStormMeta を実装
- [feature][Database] getAffectedRows メソッドを追加
- [feature][TableDescriptor] group 記述を追加
- [feature][Logger] array モードを新設
- [feature][Generator] ArrayGenerator の assoc 対応
- [feature][Operator] マジックメソッドで左辺未設定インスタンスを返せるように変更
- [feature][TableGateway] sub系のエイリアスメソッドを用意
- [feature][TableGateway] export系メソッドを用意
- [feature][TableGateway] スコープ定義のインスタンス返しの対応
- [refactor][all] doctrine 由来のメソッド名を独自体系に変更
  - deprecated Database::executeQuery, use Database::executeSelect
  - deprecated Database::executeUpdate, use Database::executeAffect
- [fixbug][Logger] バイナリ時の表示を修正
- [fixbug][Database] modify でエラーになることがある不具合を修正
- [change][Schema] テーブル・カラムの ini 仕様を廃止

## 2.0.0

- [feature][all] 各所で iterable を受けられるように修正
- [feature][Database] binder メソッドの追加
- [feature][Database] ゲッター/セッターの強化
  - getConnections を追加してコネクション単位の取得を可能にした
  - setLogger を追加してコネクション毎にロガーの設定を可能にした
  - compatiblePlatform をインジェクション可能にした
- [feature][CompatiblePlatform] getPrimaryCondition の行値式対応
- [feature][TableDescriptor] `{id}` が `{id: id}` になるような短縮構文を導入
- [feature][SubqueryBuilder] lazy mode に batch/yield を新設
- [feature][Entity] エンティティのコンストラクタを自由化
- [fixbug][CompatiblePlatform] postgresql で php と db のキーが異なる不具合を修正
- [fixbug][QueryBuilder] 同名テーブルを自動 JOIN したときに統合されない不具合を修正
- [fixbug][Operator] オペレータが大文字化されてしまう不具合を修正
- [fixbug][Operator] LIKEIN で エスケープが行われていない不具合を修正
- [*change][Database] テーブル名 <=> エンティティ名の1対1制限を撤廃
- [*change][Database] autoCastSuffix を削除
- [*change][Transaction] Logger のシンプル化
  - 文字列化系オプションをすべて callback にまとめた
  - バッファリングを有効にしてログが直列化されるようにした
- [*change][QueryBuilder] PhpExpression の廃止
- [*change][QueryBuilder] groupBy 可変引数に変更
- [*change][QueryBuilder] autoSelectClosure を削除
- [*change][QueryBuilder] 直接 param を持つのではなく、 param を持った Queryable を持つようにした
- [*change][QueryBuilder] SubqueryBuilder を削除して機能マージした
- [*change][all] 仮想カラムは Gateway が握るのではなく Schema が握るように変更
  - Gateway 周りから仮想カラム関係を全て削除
  - 代わりに Database, Schema で仮想カラムを追加・変更できるようになった
- [*change][all] 互換用に残っていたコード・設定を削除
  - Database::addRelation の lazy フラグを削除
  - Database::notableAsColumn を削除
  - Gateway::offsetGetFind を削除
  - Gateway::addVirtualColumn() を削除

## 1.0.7

- [change] 内部処理をいくつか変更
- [change][QueryBuilder] 存在するカラムのクロージャを自動で SELECT 句に加えるオプションを用意

## 1.0.6

- [refactor] 依存ライブラリの整理
- [feature][Database] subquery メソッドを追加
- [change][Database] upsert のデフォルト引数を統一
- [feature][TableGateway] affect 系メソッドのオーバーライドに対応
- [feature][Schema] 外部キーの遅延追加を実装

## 1.0.5

- [feature][Database] anywhere のクオート対応
- [feature][Database] addRelation メソッドを追加
- [feature][TableGateway] 仮想カラムの機能を強化
  - implicit 属性を持たせて !取得や where,having で仮想カラムを使用できるようになった
  - 仮想カラムの削除に対応（メソッド名が変わったが旧名も使える）
- [feature][TableGateway] スコープ周りの改善
  - スコープの同時指定
  - 合成スコープ
- [feature][TableGateway] autoincrement 系メソッドの移譲
  - getLastInsertId
  - resetAutoIncrement
- [feature][TableDescriptor] テーブルに紐付かないカラムのフラット指定を可能にした
- [fixbug][QueryBuilder] スコープ付きテーブル記法の場合は ON やサブクエリに飲み込まれるのが正しい仕様
- [fixbug][QueryBuilder] スコープ付きテーブル記法の場合に修飾子がつかない不具合を修正
- [fixbug][QueryBuilder] neighbor を呼ぶと limit が設定される副作用を修正
- [fixbug][QueryBuilder] orderBy で Queryable を使えるように修正
- [change][Database] var_dump の出力を変更

## 1.0.4

- [feature][Operator] range で行値式が使えるように拡張
- [feature][Transaction] catch イベントを追加
- [feature][TableGateway] invoke で find が呼び出せるように拡張
- [feature][Database] aggregate でクロス集計がしやすいように拡張
- [feature][Database] [insert|upsert|modify]Conditionally を実装
- [fixbug][Database] loadCsv が dryrun に対応していなかった不具合を修正

## 1.0.3

- [feature][TableGateway] offsetGet の * 対応
- [feature][Database] INSERT SET 構文対応
- [feature][QueryBuilder] 前後のレコードを返す neighbor を実装
- [fixbug][QueryBuilder] ORDER, LIMIT 付きの UNION でエラーになっていた不具合を修正
- [fixbug][All] 一部マルチバイトで動かないメソッドがあったので正規表現に/uオプションを追加
- [chage][Database] :placeholder の仕様を変更
  - column は ":name" と指定するのを正式仕様に変更
  - where も ":name" で placeholder が使えるように変更

## 1.0.2

- [feature][Database] クエリビルダを返す aggregate を実装
- [feature][Database] bool は int で bind されるように修正
- [feature][Database] レコードをかき集める gather を実装
- [feature][Database] update/delete のテーブル記法対応
- [feature][Database] レコードを削減する reduce を実装
- [feature][QueryBuilder] 集約系を where でも使えるように拡張
- [feature][QueryBuilder] クロージャを返すクロージャに対応
- [feature][QueryBuilder] where で主キーを表す空文字キーを実装
- [feature][QueryBuilder] レコードを少しづつ取得する chunk を実装
- [feature][CompatiblePlatform] Postgres の upsert に対応
- [feature][Logger] クロージャログの対応
- [refactor][QueryBuilder] ORDER BY の内部の保持形式を変更
- [fixbug][Database] mysql 以外で自動採番列に null を与えるとエラーになる不具合を修正
- [fixbug][Database] テーブル記法と引数渡しの複合で記法が効かない不具合を修正
- [fixbug][Database] 集約結果が数値前提だった不具合を修正
- [fixbug][Database] スキーマ取得前に context 相当のことをすると2重に取ってしまう不具合を修正
- [fixbug][TableGateway] subselect 系が一切動いていない不具合を修正
- [fixbug][TableGateway] スコープを当てても結果が残る不具合を修正
- [fixbug][TableGateway] 特定条件で WHERE が2倍になる不具合を修正
- [fixbug][QueryBuilder] detectAutoOrder を修正
- [fixbug][QueryBuilder] join の時の駆動表判定が誤る可能性がある不具合を修正
- [fixbug][QueryBuilder] join 条件で sub 系や!が使えない不具合を修正
- [change][TableGateway] offsetGet の挙動を find から pk に変更（互換性のため要オプション）

## 1.0.1

- [fixbug][Database] エンティティ名でアクセスしてもエンティティゲートウェイが返らない不具合を修正
- [fixbug][Database] エンティティを作用系に投げるとエラーが出る不具合を修正
- [fixbug][Database] sqlite の truncate で自動採番列がリセットされない不具合を修正
- [feature][Database] NOT ブロックの実装
- [feature][Database] echoAnnotation を実装
- [feature][TableGateway] 主キー指定の配列アクセスを実装

## 1.0.0

- 公開
