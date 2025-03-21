<?php

namespace Php2Core\IO;

class Process
{
    /**
     * @return Process[]
     */
    public static function list(): array
    {
        $buffer = [];
        $lines = [];
        
        $isWindows = true;
        exec('tasklist 2>nul', $lines);
        if(count($lines) === 0)
        {
            exec('ps -A -f', $lines);
            $isWindows = false;
        }
        $start = $isWindows ? 3 : 1;
        
        for ($i = $start; $i < count($lines); $i++) {
            if($isWindows)
            {
                $data = new \Php2Core\Data\Collections\Generic\WindowsDataParser($lines[1], $lines[$i], $lines[2]);
            }
            else
            {
                $data = new \Php2Core\Data\Collections\Generic\LinuxDataParser($lines[0], $lines[$i]);
            }
            
            $buffer[] = [
                'Session' => [
                    'Id' => (int)$data -> get('Session#'),
                    'Name' => $isWindows ? $data -> get('Session Name') : $data -> get('UID')
                ],
                'Process' => $isWindows ? $data -> get('Image Name') : $data -> get('CMD'),
                'PID' => (int)$data -> get('PID'),
                'Memory' => $isWindows ? Memory::parse($data -> get('Mem Usage')) : 0
            ];
        }
        $mergedByProcess = self::mergeByProcess($buffer);

        return (new \Php2Core\Data\Collections\Linq(array_values($mergedByProcess)))
            -> select(function ($x) {
                return new Process($x);
            })
            -> toArray();
    }

    /**
     * @param array $data
     * @return array
     */
    private static function mergeByProcess(array $data): array
    {
        $sortedData = (new \Php2Core\Data\Collections\Linq($data))
            -> orderBy(function ($x) {
                return $x['Session']['Name'];
            }, \Php2Core\Data\Collections\SortDirections::Asc)
            -> toArray();

        $buffer = [];
        foreach ($sortedData as $item) {
            $sId = $item['Session']['Name'];
            if (!isset($buffer[$sId])) {
                $buffer[$sId] = [];
            }

            $buffer[$sId][] = $item;
        }

        $output = [];
        foreach ($buffer as $items) {
            $sortedItems = (new \Php2Core\Data\Collections\Linq($items))
                -> orderBy(function ($x) {
                    return $x['Process'];
                }, \Php2Core\Data\Collections\SortDirections::Asc)
                -> toArray();

            $prev = null;
            $mergedBuffer = [];

            foreach ($sortedItems as $item) {
                if ($item['Process'] !== $prev) {
                    $mergedBuffer[$item['Process']] = [
                        'Session' => $item['Session'],
                        'Process' => $item['Process'],
                        'Data' => []
                    ];
                }

                $mergedBuffer[$item['Process']]['Data'][] = [
                    'PID' => $item['PID'],
                    'Memory' => $item['Memory']
                ];

                $prev = $item['Process'];
            }

            $output = array_merge($output, $mergedBuffer);
        }

        return $output;
    }

    /**
     * @var array
     */
    private array $session;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var array
     */
    private array $processes;

    /**
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this -> session = $data['Session'];
        $this -> name = $data['Process'];
        $this -> processes = $data['Data'];
    }

    /**
     * @return int
     */
    public function sessionId(): int
    {
        return $this -> session['Id'];
    }

    /**
     * @return string
     */
    public function sessionName(): string
    {
        return $this -> session['Name'];
    }

    /**
     * @param int $pid
     * @return \Php2Core\Data\Collections\Dictionary
     */
    public function pidInfo(int $pid): ?\Php2Core\Data\Collections\Dictionary
    {
        $exists = (new \Php2Core\Collections\Linq($this -> processes))
            -> firstOrDefault(function ($x) use ($pid) {
                return $x['PID'] === $pid;
            }) !== null;
        if (!$exists) {
            return null;
        }

        $info = shell_exec('wmic process where (processid=' . $pid . ') get *');
        if ($info === null || !$info) {
            return null;
        }

        $lines = explode("\r\n", trim($info));

        return new \Php2Core\Data\Collections\Generic\WindowsDataParser($lines[0], $lines[1]);
    }

    /**
     * @return int
     */
    public function pidCount(): int
    {
        return count($this -> pidList());
    }

    /**
     * @return int[]
     */
    public function pidList(): array
    {
        return (new \Php2Core\Data\Collections\Linq($this -> processes))
            -> select(function ($x) {
                return $x['PID'];
            })
            -> toArray();
    }

    /**
     * @param bool $format
     * @return string|int
     */
    public function totalMemory(bool $format = false): mixed
    {
        $sum = (new \Php2Core\Data\Collections\Linq($this -> processes))
            -> sum(function ($x) {
                return $x['Memory'] -> value();
            })
            -> firstOrDefault();

        $mem = Memory::fromInt($sum);
        if ($format) {
            return $mem -> format();
        }

        return $mem -> value();
    }

    /**
     * @param int $pid
     * @param bool $format
     * @return mixed
     */
    public function pidMemory(int $pid, bool $format = false): mixed
    {
        $selected = (new \Php2Core\Data\Collections\Linq($this -> processes))
            -> firstOrDefault(function ($x) use ($pid) {
                return $x['PID'] === $pid;
            });
        if ($selected == null) {
            return null;
        }

        $memory = $selected['Memory'];

        if ($format) {
            return $memory -> format();
        }

        return $memory -> value();
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this -> name;
    }
}
