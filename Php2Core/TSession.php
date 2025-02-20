<?php
namespace Php2Core\Php2Core;

trait TSession
{
    private static function initializeSession(): void
    {
    }
    
    public static function isAuthenticated(): bool
    {
        echo '<xmp>';
        var_dump(__FILE__.':'.__LINE__);
        var_dump('NOT IMPLEMENTED ISAUTHENTICATED');
//        print_r($_SESSION);
        echo '</xmp>';
        return false;
    }
}