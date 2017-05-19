<?php


// try to get Outer Extension
foreach (glob(__DIR__.'/../AppBundle/Entity/OuterExtension/*/*.php') as $file) {
        require_once $file;
}
