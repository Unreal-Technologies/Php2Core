<?php
namespace Php2Core\Php2Core;

trait TRouting
{
    /**
     * @param string $slug
     * @return array
     */
    private static function getPossibleMatchesFromSlug(string $slug): array
    {
        $parts = explode('/', $slug);
        $buffer = [ '^'.$parts[0].'$' ];
        $offParts = [ $parts[0] ];
        
        for($i=1; $i<count($parts); $i++)
        {
            $offParts[$i] = '{.+}';
            
            $temp1 = implode('\\/', array_slice($parts, 0, $i));
            $temp2 = implode('\\/', array_slice($offParts, 0, $i));
            
            $buffer[] = '^'.$temp1.'\\/'.$parts[$i].'$';
            $buffer[] = '^'.$temp2.'\\/'.$parts[$i].'$';
            
            $buffer[] = '^'.$temp1.'\\/{.+}$';
            $buffer[] = '^'.$temp2.'\\/{.+}$';
        }
        
        return array_values(array_unique($buffer));
    }
    
    /**
     * @return int
     */
    public static function getInstanceID(): int
    {
        $coreDbc = \Php2Core\IO\Data\Db\Database::getInstance('Php2Core');
        $coreDbc -> query('select `id` from `instance` where `name` = "'.PHP2CORE -> get(\Php2Core::Title).'"');
        $result = $coreDbc -> execute();

        if($result['iRowCount'] > 0)
        {
            return $result['aResults'][0]['id'];
        }

        return -1;
    }
	
    /**
     * @return void
     * @throws \Exception
     */
    private static function initializeRouting(): void
    {
        //Get DB Instance
        $coreDbc = \Php2Core\IO\Data\Db\Database::getInstance('Php2Core');
        $instanceId = self::getInstanceID();
        $authenticated = PHP2CORE -> isAuthenticated();
        
        //Get Default handler
        $coreDbc -> query(
            'select '
            . 'case when `match` is null then \'index\' else `match` end as `match` '
            . 'from `route` '
            . 'where `default` = "true" '
            . 'and '.(SERVER_ADMIN ? '( `instance-id` = '.$instanceId.' or `instance-id` is null )' : '`instance-id` = '.$instanceId).' '
            . ($authenticated ? '' : 'and `auth` = "false" ')
            . 'order by `id` asc '
            . 'limit 0,1'
        );
        
        $defaultResults = $coreDbc -> execute();
        define('DEFAULT_ROUTE', $defaultResults['iRowCount'] === 0 ? 'index' : $defaultResults['aResults'][0]['match']);
        
        //Get Router Information
        $router = new \Php2Core\Data\Router(DEFAULT_ROUTE);
        $slug = $router -> slug();
        $possibilities = self::getPossibleMatchesFromSlug($slug);
        
        //Get Possible routes
        $coreDbc -> query(
            'select '
            . '`method`, `match`, `target`, `type`, `auth` '
            . 'from `route` '
            . 'where '.(SERVER_ADMIN ? '( `instance-id` = '.$instanceId.' or `instance-id` is null )' : '`instance-id` = '.$instanceId).' '
            . 'and (`match` regexp \''.implode('\' or `match` regexp \'', $possibilities).'\') '
            . ($authenticated ? '' : 'and `auth` = "false" ')
            . (SERVER_ADMIN ? '' : 'and `type` != \'function\' ')
        );
        
        //Register possible routes
        $routeResult = $coreDbc -> execute();
        foreach($routeResult['aResults'] as $row)
        {
            $router -> register($row['method'].'::'.$row['match'], $row['type'].'#'.$row['target']);
        }
        
        //get current route (if matched)
        define('ROUTE', $router -> match());
        
        //throw exception when not actually matched
        if(ROUTE === null)
        {
            throw new \Exception('Route not found');
        }
        else if(ROUTE -> target()['type'] === 'function')
        {
            eval(ROUTE -> target()['target'].'();');
            exit;
        }
    }
}
