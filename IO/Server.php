<?php

namespace Php2Core\IO;

class Server
{
    /**
     * @return Memory[]
     */
    public static function ram(): array
    {
        $buffer = [];
        exec('wmic memorychip get capacity', $buffer);

        return
            (new \Php2Core\Collections\Linq($buffer))
            -> where(function ($x) 
            {
                return (int)$x == $x;
            })
            -> select(function ($x) 
            {
                return Memory::fromInt($x);
            })
            -> toArray();
    }
}
