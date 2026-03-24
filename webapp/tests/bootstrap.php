<?php

define('PATH', dirname(__DIR__) . '/');

require_once PATH . 'functions.php';
require_once PATH . 'twig_extras.php';

date_default_timezone_set('Europe/Budapest');

$_honapok = [
    1 => ['jan', 'január'],
    2 => ['feb', 'február'],
    3 => ['márc', 'március'],
    4 => ['ápr', 'április'],
    5 => ['máj', 'május'],
    6 => ['jún', 'június'],
    7 => ['júl', 'július'],
    8 => ['aug', 'augusztus'],
    9 => ['szept', 'szeptember'],
    10 => ['okt', 'október'],
    11 => ['nov', 'november'],
    12 => ['dec', 'december'],
];
