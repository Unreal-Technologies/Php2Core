<?php
namespace Php2Core\Php2Core;

trait TSession
{
    private static function initializeSession(): void
    {
    }
    
    /**
     * @param string $path
     * @return mixed
     */
    public static function session_get(string $path): mixed
    {
        $eval = '$_SESSION';
        foreach(explode('/', $path) as $token)
        {
            $eval .= '["'.$token.'"]';
        }
        $data = null;
        eval('$data = isset('.$eval.') ? '.$eval.' : null;');
        
        return $data;
    }
    
    /**
     * @param string $path
     * @param mixed $data
     * @return void
     */
    public static function session_set(string $path, mixed $data): void
    {
        $eval = '$_SESSION';
        foreach(explode('/', $path) as $token)
        {
            $eval .= '["'.$token.'"]';
        }
        $eval .= ' = $data;';
        
        eval($eval);
    }
    
    /**
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        return self::session_get('user/id') !== null;
    }
}