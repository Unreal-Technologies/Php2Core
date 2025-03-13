<?php
$username = filter_input(INPUT_POST, 'username');
$password = filter_input(INPUT_POST, 'password');

$coreDbc = \Php2Core\IO\Data\Db\Database::getInstance('Php2Core');
$coreDbc -> query('select `id` from `user` where `username` = "'.$username.'" and `password` = user_password("'.$password.'")');
$result = $coreDbc -> execute();

if($result['iRowCount'] === 0)
{
    throw new \Php2Core\Exceptions\NotImplementedException('not found');
}

$id = $result['aResults'][0]['id'];
PHP2CORE -> session_set('user/id', $id);
PHP2CORE -> refresh(PHP2CORE -> baseUrl());