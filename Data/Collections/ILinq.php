<?php

namespace Php2Core\Data\Collections;

interface ILinq
{
    public function where(\Closure $lambda): ILinq;
    public function select(\Closure $lambda): ILinq;
    public function groupBy(\Closure $lambda): ILinq;
    public function toArray(\Closure $lambda = null): array;
    public function firstOrDefault(\Closure $lambda = null): mixed;
    public function count(): int;
    public function sum(\Closure $lambda = null): ILinq;
    public function avg(\Closure $lambda = null): ILinq;
    public function skip(int $count): ILinq;
    public function orderBy(
        \Closure $lambda = null,
        SortDirections $direction = SortDirections::Asc
    ): ILinq;
}
