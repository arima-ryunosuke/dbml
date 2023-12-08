<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use function ryunosuke\dbml\parameter_default;

trait AffectIgnoreTrait
{
    private function _invokeAffectIgnore($method, $arguments)
    {
        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[] = ['primary' => 2, 'ignore' => true];
        return $this->$method(...$arguments);
    }

    /**
     * IGNORE 付き {@uses Database::insertSelect()}
     *
     * @inheritdoc Database::insertSelect()
     */
    public function insertSelectIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('insertSelect', $args);
    }

    /**
     * IGNORE 付き {@uses Database::insertArray()}
     *
     * @inheritdoc Database::insertArray()
     */
    public function insertArrayIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('insertArray', $args);
    }

    /**
     * IGNORE 付き {@uses Database::updateArray()}
     *
     * @inheritdoc Database::updateArray()
     */
    public function updateArrayIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('updateArray', $args);
    }

    /**
     * IGNORE 付き {@uses Database::modifyArray()}
     *
     * @inheritdoc Database::modifyArray()
     */
    public function modifyArrayIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('modifyArray', $args);
    }

    /**
     * IGNORE 付き {@uses Database::changeArray()}
     *
     * @inheritdoc Database::changeArray()
     */
    public function changeArrayIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('changeArray', $args);
    }

    /**
     * IGNORE 付き {@uses Database::save()}
     *
     * @inheritdoc Database::save()
     */
    public function saveIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('save', $args);
    }

    /**
     * IGNORE 付き {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     */
    public function insertIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('insert', $args);
    }

    /**
     * IGNORE 付き {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     */
    public function updateIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('update', $args);
    }

    /**
     * IGNORE 付き {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     */
    public function deleteIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('delete', $args);
    }

    /**
     * IGNORE 付き {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     */
    public function invalidIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('invalid', $args);
    }

    /**
     * IGNORE 付き {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     */
    public function removeIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('remove', $args);
    }

    /**
     * IGNORE 付き {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     */
    public function destroyIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('destroy', $args);
    }

    /**
     * IGNORE 付き {@uses Database::create()}
     *
     * @inheritdoc Database::create()
     */
    public function createIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('create', $args);
    }

    /**
     * IGNORE 付き {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     */
    public function modifyIgnore(...$args)
    {
        return $this->_invokeAffectIgnore('modify', $args);
    }
}
