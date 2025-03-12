<?php

namespace Php2Core\Data\Collections;

interface IDictionary
{
    public function add(mixed $key, mixed $value, bool $setAsArray = false): bool;
    public function get(mixed $key): mixed;
    public function remove(mixed $key): bool;
    public function toArray(): array;
    public function keys(): array;
    public function values(): array;
}
