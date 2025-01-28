<?php

class EntityExtension
{
    public function old(): void
    {
    }

    /**
     * @abstract
     */
    public function new(): void
    {

    }
}

class Impl extends EntityExtension
{
    public function old(): void
    {
    }
}