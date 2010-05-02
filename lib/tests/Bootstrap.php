<?php
require_once 'Zend/Loader/Autoloader.php';

# Initialise Zend's Autoloader, used for plugins etc
$Autoloader = Zend_Loader_Autoloader::getInstance();
$Autoloader->registerNamespace('Bal_');
$Autoloader->registerNamespace('Doctrine_');