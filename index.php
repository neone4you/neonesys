<?php
require dirname(__FILE__) . '/include.php';

//$_SESSION['uid'] = 1;

App::init();
App::securityPage($_SESSION['uid']>0, 'login', 'index');
App::exec();

Template::setTemplate('main', true);
Template::showPage();