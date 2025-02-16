<?php
require_once('Php2Core/TServerAdminCommands.php');
require_once('Php2Core/TDatabase.php');
require_once('Php2Core/TRouting.php');
require_once('Php2Core/TAutoloading.php');
require_once('Php2Core/THandlers.php');
require_once('Php2Core/TOtherInitializers.php');

class Php2Core
{
    use \Php2Core\Php2Core\TServerAdminCommands;
    use \Php2Core\Php2Core\TDatabase;
    use \Php2Core\Php2Core\TRouting;
    use \Php2Core\Php2Core\TAutoloading;
    use \Php2Core\Php2Core\THandlers;
    use Php2Core\Php2Core\TOtherInitializers;

    /**
     * @return string
     */
    public static function baseUrl(): string
    {
        $pi = pathinfo($_SERVER['SCRIPT_NAME']);
		if(!isset($_SERVER['SCRIPT_URI']))
		{
			return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$pi['dirname'];
		}

        return preg_replace('/'.substr($pi['dirname'], 1).'.+$/i', substr($pi['dirname'], 1), $_SERVER['SCRIPT_URI']);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function physicalToRelativePath(string $path): string
    {
        $basePath = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];

        $new = str_replace([ROOT.'\\', ROOT.'/', '\\', '//', ':/'], ['', '', '/', '/', '://'], $path);
        if($new !== $path)
        {
            return $basePath.'/'.$new;
        }

        throw new Php2Core\Exceptions\NotImplementedException($path);
    }
    
    /**
     * @return string
     */
    public static function root(): string
    {
        //get Directory
        $pi = pathinfo(__DIR__);
        return $pi['dirname'];
    }
    
    /**
     * @param string $directory
     * @return array
     */
    public static function scanDir(string $directory): array
    {
        $entries = [];
        if (($handle = opendir($directory)) !== false) //Open Dir
        {
            while (false !== ($entry = readdir($handle))) //Loop through files
            {
                if ($entry != "." && $entry != "..") 
                {
                    $path = $directory.'/'.$entry; //Get new Path
                    $entries[] = ['Path' => realpath($path), 'Type' => is_dir($path) ? 'Dir' : 'File']; //Register Path & Type
                }
            }

            closedir($handle); //Close Dir
        }
        return $entries;
    }
}
