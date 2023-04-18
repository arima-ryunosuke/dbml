<?php /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

namespace ryunosuke\dbml\Driver\PgSQL;

use Doctrine\DBAL\Driver\PgSQL\Exception\UnexpectedValue;
use PgSql\Result as PgSqlResult;
use ryunosuke\dbml\Driver\AbstractResult;
use TypeError;
use function array_keys;
use function array_map;
use function get_class;
use function gettype;
use function hex2bin;
use function is_object;
use function is_resource;
use function pg_affected_rows;
use function pg_fetch_row;
use function pg_field_name;
use function pg_field_type;
use function pg_free_result;
use function pg_num_fields;
use function sprintf;
use function substr;
use const PHP_INT_SIZE;

/**
 * @see \Doctrine\DBAL\Driver\PgSQL\Result
 * @copyright 2006 Doctrine Project
 * @link https://raw.githubusercontent.com/doctrine/dbal/master/LICENSE
 *
 * @codeCoverageIgnore
 */
final class Result extends AbstractResult
{
    /** @var PgSqlResult|resource|null */
    private $result;

    private $tableNames;

    private $columnNames;

    private $types;

    /** @param PgSqlResult|resource $result */
    public function __construct($result)
    {
        if (!is_resource($result) && !$result instanceof PgSqlResult) {
            throw new TypeError(sprintf(
                'Expected result to be a resource or an instance of %s, got %s.',
                PgSqlResult::class,
                is_object($result) ? get_class($result) : gettype($result),
            ));
        }

        $this->result = $result;

        $this->tableNames = [];
        $this->columnNames = [];
        $this->types = [];

        $numFields = pg_num_fields($this->result);
        for ($i = 0; $i < $numFields; ++$i) {
            $this->tableNames[$i] = pg_field_table($this->result, $i);
            $this->columnNames[$i] = pg_field_name($this->result, $i);
            $this->types[$i] = pg_field_type($this->result, $i);
        }
    }

    public function __destruct()
    {
        if (!isset($this->result)) {
            return;
        }

        $this->free();
    }

    public function prefixTableName()
    {
        foreach ($this->columnNames as $n => $columnName) {
            $this->columnNames[$n] = $this->tableNames[$n] . '.' . $columnName;
        }
    }

    /** {@inheritdoc} */
    public function fetchNumeric()
    {
        if ($this->result === null) {
            return false;
        }

        $row = pg_fetch_row($this->result);
        if ($row === false) {
            return false;
        }

        return array_map(
            fn($value, $field) => $this->mapType($this->types[$field], $value),
            $row,
            array_keys($row),
        );
    }

    /** {@inheritdoc} */
    public function fetchAssociative()
    {
        $values = $this->fetchNumeric();

        if ($values === false) {
            return false;
        }

        if ($this->groupByName) {
            return $this->_fetchGroup($this->columnNames, $values);
        }

        return array_combine($this->columnNames, $values);
    }

    public function rowCount(): int
    {
        if ($this->result === null) {
            return 0;
        }

        return pg_affected_rows($this->result);
    }

    public function columnCount(): int
    {
        if ($this->result === null) {
            return 0;
        }

        return pg_num_fields($this->result);
    }

    public function free(): void
    {
        if ($this->result === null) {
            return;
        }

        pg_free_result($this->result);
        $this->result = null;
    }

    /** @return string|int|float|bool|null */
    private function mapType(string $postgresType, ?string $value)
    {
        if ($value === null) {
            return null;
        }

        switch ($postgresType) {
            case 'bool':
                switch ($value) {
                    case 't':
                        return true;
                    case 'f':
                        return false;
                }

                throw UnexpectedValue::new($value, $postgresType);

            case 'bytea':
                return hex2bin(substr($value, 2));

            case 'float4':
            case 'float8':
                return (float) $value;

            case 'int2':
            case 'int4':
                return (int) $value;

            case 'int8':
                return PHP_INT_SIZE >= 8 ? (int) $value : $value;
        }

        return $value;
    }
}
