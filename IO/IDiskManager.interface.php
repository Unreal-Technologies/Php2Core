<?php

namespace Php2Core\IO;

interface IDiskManager
{
    public function path(): string;
    public function exists(): bool;
    public function name(): string;
    public function remove(): bool;
}