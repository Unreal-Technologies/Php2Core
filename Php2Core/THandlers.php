<?php
namespace Php2Core\Php2Core;

trait THandlers
{
    /**
     * @return void
     */
    private static function initializeHandlerOverride(): void
    {
        $isXhr = isset($_GET['mode']) && $_GET['mode'] === 'xhr';
        
        if((int)CONFIGURATION -> get('Logic/ErrorHandling') === 1 && !$isXhr)
        {
            //register handlers
            set_error_handler('Php2Core::ErrorHandler');
            set_exception_handler('Php2Core::ExceptionHandler');
            register_shutdown_function('Php2Core::Shutdown');
        }
    }
    
    private static function getTrace(\Php2Core\NoHTML\Xhtml $body): ?\Php2Core\NoHTML\Xhtml
    {
        $trace = null;
        $body -> get('table@#trace', function(\Php2Core\NoHTML\Xhtml $table) use(&$trace)
        {
            $trace = $table;
        });
        if($trace === null)
        {
            \Php2Core::trace();
            $body -> get('table@#trace', function(\Php2Core\NoHTML\Xhtml $table) use(&$trace)
            {
                $trace = $table;
            });
        }
        return $trace;
    }
    
    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return void
     */
    public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): void
    {
        $hasBody = false;
        
        XHTML -> get('body', function(\Php2Core\NoHTML\Xhtml $body) use(&$hasBody, $errno, $errstr, $errfile, $errline)
        {
            $trace = self::getTrace($body);
            
            $body -> clear();
            $body -> add('h2') -> text('Php2Core::ErrorHandler');
            $body -> add('xmp') -> text(print_r($errfile.':'.$errline, true));
            $body -> add('xmp') -> text($errno."\r\n".print_r($errstr, true));
            if($trace !== null)
            {
                $body -> append($trace);
            }

            $hasBody = true; 
        });

        if(!$hasBody)
        {
            echo '<h2>Php2Core::ErrorHandler</h2>';
            echo '<xmp>';
            var_dump($errfile);
            var_dumP($errline);
            var_dumP($errno);
            var_dumP($errstr);
            echo '</xmp>';
        }
        exit;
    }
    
    /**
     * @param \Throwable $ex
     * @return void
     */
    public static function exceptionHandler(\Throwable $ex): void
    {
        $hasBody = false;
        
        XHTML -> get('body', function(\Php2Core\NoHTML\Xhtml $body) use(&$hasBody, $ex)
        {
            $body -> clear();
            $body -> add('h2') -> text('Php2Core::ExceptionHandler');
            $body -> add('xmp') -> text(print_r($ex, true));

            $hasBody = true; 
        });
        
        if(!$hasBody)
        {
            echo '<h2>Php2Core::ExceptionHandler</h2>';
            echo '<xmp>';
            print_r($ex);
            echo '</xmp>';
        }
        exit;
    }
    
    /**
     * @return void
     */
    public static function shutdown(): void
    {
        XHTML -> get('body', function(\Php2Core\NoHTML\Xhtml $body)
        {
            $dif = microtime(true) - TSTART;
            
            $body -> add('div@#execution-time') -> text('Process time: '.number_format(round($dif * 1000, 4), 4, ',', '.').' ms');
            $body -> add('div@#version', function(\Php2Core\NoHTML\Xhtml $div)
            {
                VERSION -> Render($div);
            });
        });
        XHTML -> get('head', function(\Php2Core\NoHTML\Xhtml $head)
        {
            $children = $head -> children();
            $head -> clear();
            
            $head -> add('link', function(\Php2Core\NoHTML\Xhtml $link)
            {
                $link -> Attributes() -> Set('rel', 'icon');
                $link -> Attributes() -> Set('type', 'image/x-icon');
                $link -> Attributes() -> Set('href', self::PhysicalToRelativePath(__DIR__.'/../Assets/Images/favicon.ico'));
            });
            $head -> add('link', function(\Php2Core\NoHTML\Xhtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', self::PhysicalToRelativePath(__DIR__.'/../Assets/FA-all.min.5.15.4.css'));
            });
            $head -> add('link', function(\Php2Core\NoHTML\Xhtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', self::PhysicalToRelativePath(__DIR__.'/../Assets/Materialize.css'));
            });
            $head -> add('link', function(\Php2Core\NoHTML\Xhtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', self::PhysicalToRelativePath(__DIR__.'/../Assets/Php2Core.css'));
            });
            $head -> add('link', function(\Php2Core\NoHTML\Xhtml $link)
            {
                $link -> Attributes() -> Set('rel', 'stylesheet');
                $link -> Attributes() -> Set('href', 'https://fonts.googleapis.com/icon?family=Material+Icons');
            });
            $head -> add('script', function(\Php2Core\NoHTML\Xhtml $script)
            {
                $script -> Attributes() -> Set('type', 'text/javascript');
                $script -> Attributes() -> Set('src', self::PhysicalToRelativePath(__DIR__.'/../Assets/jquery-3.7.1.min.js'));
            });
            $head -> add('script', function(\Php2Core\NoHTML\Xhtml $script)
            {
                $script -> Attributes() -> Set('type', 'text/javascript');
                $script -> Attributes() -> Set('src', self::PhysicalToRelativePath(__DIR__.'/../Assets/Materialize.js'));
            });
            $head -> add('script', function(\Php2Core\NoHTML\Xhtml $script)
            {
                $script -> Attributes() -> Set('type', 'text/javascript');
                $script -> Attributes() -> Set('src', self::PhysicalToRelativePath(__DIR__.'/../Assets/Xhr.js'));
            });

            foreach($children as $child)
            {
                $head -> Append($child);
            }
        });

        //output
        echo XHTML;
        
        if(DEBUG && (int)CONFIGURATION -> get('Configuration/XhtmlOut') === 1)
        {
            echo '<hr />';
            echo '<xmp>';
            print_r(str_replace(['<xmp>', '</xmp>'], ['<.xmp>', '</.xmp>'], (string)XHTML));
            echo '</xmp>';
        }
    }
}
