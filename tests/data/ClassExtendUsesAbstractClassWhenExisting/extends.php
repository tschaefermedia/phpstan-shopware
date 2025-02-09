<?php

abstract class AbstractCore
{
    abstract public function getDecorated(): AbstractCore;
}

class Core extends AbstractCore
{
    public function getDecorated(): Core
    {
        return new Core();
    }
}

class Plugin extends Core
{
    public function getDecorated(): Plugin
    {
        return new Plugin();
    }
}
