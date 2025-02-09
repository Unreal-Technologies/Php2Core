<?php
namespace Php2Core\Exceptions;

class UnexpectedValueException extends \Exception
{
    /**
     * @param  string          $message
     * @param  int             $code
     * @param  \Throwable|null $previous
     * @return \Exception
     */
    #[\Override]
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
