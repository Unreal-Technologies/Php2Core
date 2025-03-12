<?php

namespace Php2Core\Data\Collections\Enum;

trait TInfo
{
	/**
	 * @param string $name
	 * @return mixed
	 */
	public static function fromString(string $name): mixed
	{
		$class = get_class();
		
		foreach ($class::cases() as $status)
	 	{
            if($name === $status -> name)
			{
                return $status;
            }
        }
		return null;
	}
	
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function list(): array
    {
        return array_combine(self::values(), self::names());
    }

    public static function inverseList(): array
    {
        return array_combine(self::names(), self::values());
    }
    
    public function value()
    {
        dump(self::inverseList());
    }
}
