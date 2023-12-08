<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use function ryunosuke\dbml\parameter_default;

trait AffectAndPrimaryTrait
{
    private function _invokeAffectAndPrimary($method, $arguments)
    {
        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[] = ['primary' => 3];
        return $this->$method(...$arguments);
    }

    /**
     * 主キーを返す {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     */
    public function insertAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('insert', $args);
    }

    /**
     * 主キーを返す {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     */
    public function updateAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('update', $args);
    }

    /**
     * 主キーを返す {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     */
    public function deleteAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('delete', $args);
    }

    /**
     * 主キーを返す {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     */
    public function invalidAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('invalid', $args);
    }

    /**
     * 主キーを返す {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     */
    public function removeAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('remove', $args);
    }

    /**
     * 主キーを返す {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     */
    public function destroyAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('destroy', $args);
    }

    /**
     * 主キーを返す {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     */
    public function upsertAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('upsert', $args);
    }

    /**
     * 主キーを返す {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     */
    public function modifyAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('modify', $args);
    }

    /**
     * 主キーを返す {@uses Database::replace()}
     *
     * @inheritdoc Database::replace()
     */
    public function replaceAndPrimary(...$args)
    {
        return $this->_invokeAffectAndPrimary('replace', $args);
    }
}
