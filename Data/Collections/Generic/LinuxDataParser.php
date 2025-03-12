<?php

namespace Php2Core\Data\Collections\Generic;

class LinuxDataParser extends \Php2Core\Data\Collections\Dictionary
{
    /**
     * @param string $header
     * @param string $data
     * @param string|null $indentations
     */
    public function __construct(string $header, string $data, ?string $indentations = null)
    {
        $indentations ??= $header;

        $indentationIndexes = $this -> getIndentationIndexes($indentations);
        $headers = $this -> getHeaders($header, $indentationIndexes);

        $this -> buffer = $this -> getData($headers, $indentationIndexes, $data);
    }

    /**
     * @param string[] $headers
     * @param array $indentationIndexes
     * @param string $data
     * @return string[]
     */
    private function getData(array $headers, array $indentationIndexes, string $data): array
    {
        $buffer = [];
        foreach ($indentationIndexes as $i => $index) {
            $end = $index['End'];
            $start = $index['Start'];
            $header = $headers[$i];

            $buffer[$header] = trim(
                $end === null ?
                substr($data, $start, strlen($data) - $start) :
                substr($data, $start, $end - $start)
            );
        }
        return $buffer;
    }

    /**
     * @param string $header
     * @param array $indentationIndexes
     * @return string[]
     */
    private function getHeaders(string $header, array $indentationIndexes): array
    {
        $buffer = [];
        foreach ($indentationIndexes as $index) {
            $end = $index['End'];
            $start = $index['Start'];

            $buffer[] = trim(
                $end === null ?
                substr($header, $start, strlen($header) - $start) :
                substr($header, $start, $end - $start)
            );
        }
        return $buffer;
    }

    /**
     * @param string $indentations
     * @return array
     */
    private function getIndentationIndexes(string $indentations): array
    {
        $list = (new \Php2Core\Data\Collections\Linq(explode(' ', $indentations)))
            -> toArray(function ($x) {
                return $x !== '';
            });

        $pos = 0;
        $buffer = [];
        foreach ($list as $idx => $item) {
            $next = isset($list[$idx + 1]) ? $list[$idx + 1] : null;
            $start = strpos($indentations, $item, $pos);
            $end = $next === null ? null : strpos($indentations, $next, $pos);

            $buffer[] = [
                'Start' => $start,
                'End' => $end === null ? null : ($end == $start ? $start + strlen($item) : $end)
            ];

            $pos = $start + strlen($item);
        }

        return $buffer;
    }
}
