<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use function ryunosuke\dbml\parameter_default;

trait AffectConditionallyTrait
{
    abstract protected function getConditionPosition();

    private function _invokeAffectConditionally($method, $arguments)
    {
        $condition = $arguments[$this->getConditionPosition()];
        unset($arguments[$this->getConditionPosition()]);
        $arguments = array_values($arguments);

        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[] = ['primary' => 2, 'where' => $condition];
        return $this->$method(...$arguments);
    }

    /**
     * 条件付き {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     */
    public function insertConditionally(...$args)
    {
        return $this->_invokeAffectConditionally('insert', $args);
    }

    /**
     * 条件付き {@uses Database::create()}
     *
     * @inheritdoc Database::create()
     */
    public function createConditionally(...$args)
    {
        return $this->_invokeAffectConditionally('create', $args);
    }

    /**
     * 条件付き {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     */
    public function upsertConditionally(...$args)
    {
        return $this->_invokeAffectConditionally('upsert', $args);
    }

    /**
     * 条件付き {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     */
    public function modifyConditionally(...$args)
    {
        return $this->_invokeAffectConditionally('modify', $args);
    }
}
