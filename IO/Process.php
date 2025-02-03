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
        exec('tasklist 2>nul', $lines);

        for ($i = 3; $i < count($lines); $i++) {
            $wdp = new \Php2Core\Collections\Generic\WindowsDataParser($lines[1], $lines[$i], $lines[2]);

            $buffer[] = [
                'Session' => [
                    'Id' => (int)$wdp -> get('Session#'),
                    'Name' => $wdp -> get('Session Name')
                ],
                'Process' => $wdp -> get('Image Name'),
                'PID' => (int)$wdp -> get('PID'),
                'Memory' => Memory::parse($wdp -> get('Mem Usage'))
            ];
        }
        $mergedByProcess = self::mergeByProcess($buffer);

        return (new \Php2Core\Collections\Linq(array_values($mergedByProcess)))
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
        $sortedData = (new \Php2Core\Collections\Linq($data))
            -> orderBy(function ($x) {
                return $x['Session']['Name'];
            }, \Php2Core\Collections\SortDirections::Asc)
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
            $sortedItems = (new \Php2Core\Collections\Linq($items))
                -> orderBy(function ($x) {
                    return $x['Process'];
                }, \Php2Core\Collections\SortDirections::Asc)
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
     * @return \Php2Core\Collections\Dictionary
     */
    public function pidInfo(int $pid): \Php2Core\Collections\Dictionary
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

        return new \Php2Core\Collections\Generic\WindowsDataParser($lines[0], $lines[1]);
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
        return (new \Php2Core\Collections\Linq($this -> processes))
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
        $sum = (new \Php2Core\Collections\Linq($this -> processes))
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
        $selected = (new \Php2Core\Collections\Linq($this -> processes))
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
