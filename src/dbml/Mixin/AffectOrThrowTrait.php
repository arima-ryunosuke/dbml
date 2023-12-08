<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use function ryunosuke\dbml\parameter_default;

trait AffectOrThrowTrait
{
    private function _invokeAffectOrThrow($method, $arguments)
    {
        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[] = ['primary' => 1, 'throw' => true];
        return $this->$method(...$arguments);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     */
    public function insertOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('insert', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     */
    public function updateOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('update', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     */
    public function deleteOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('delete', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     */
    public function invalidOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('invalid', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     */
    public function removeOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('remove', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     */
    public function destroyOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('destroy', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::reduce()}
     *
     * @inheritdoc Database::reduce()
     */
    public function reduceOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('reduce', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     */
    public function upsertOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('upsert', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     */
    public function modifyOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('modify', $args);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::replace()}
     *
     * @inheritdoc Database::replace()
     */
    public function replaceOrThrow(...$args)
    {
        return $this->_invokeAffectOrThrow('replace', $args);
    }
}
