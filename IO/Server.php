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

		if(count($buffer) === 0)
		{
			$fh = fopen('/proc/meminfo', 'r');
			$mem = 0;
			while ($line = fgets($fh)) 
			{
				$pieces = array();
				if (preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces)) 
				{
					$buffer[] = $pieces[1] * 1024;
					break;
				}
			}
			fclose($fh);
		}
		
        $result =
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
		return $result;
    }
}
