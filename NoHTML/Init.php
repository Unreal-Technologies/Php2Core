<?php
VERSION -> Add(new Php2Core\Version('NoHTML', 1, 0, 0, 0, 'https://github.com/Unreal-Technologies/Php2Core-NoHTML'));

$xhtml = new Php2Core\NoHTML\XHtml();
$xhtml -> Add('head');
$xhtml -> Add('body');