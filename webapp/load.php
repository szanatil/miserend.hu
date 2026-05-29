<?php

define('PATH', dirname(__FILE__) . "/");

$vars = array();

if (!@include __DIR__ . '/vendor/autoload.php') {
    die('You must set up the project dependencies, run the following commands:
        wget http://getcomposer.org/composer.phar
        php composer.phar install');
}
date_default_timezone_set('Europe/Budapest');

$_tidsToWorkWith = 
[];

$config = array();
$db = false;

include_once('functions.php');

$env = env('MISEREND_WEBAPP_ENVIRONMENT', 'staging'); /* testing, staging, production, vagrant */
configurationSetEnvironment($env);

error_reporting($config['error_reporting'] ? $config['error_reporting'] : 0);
define('DOMAIN', $config['path']['domain']);

Translator::init('hu'); // vagy autodetect
// Short alias for Translator::translate(). Use as t('key') in PHP or templates when available.
function t($text, $default = null) {
    return Translator::translate($text, $default);
}

//Felhasználó
if (isset($_REQUEST['login'])) {
    try {
        \User::login($_REQUEST['login'], $_REQUEST['passw']);
    } catch (\Exception $ex) {        
        addMessage('Hibás név és/vagy jelszó!<br/><br/>Ha elfelejtetted a jelszavadat, <a href="/user/lostpassword">kérj ITT új jelszót</a>.', 'danger');
    }
}
if (isset($_REQUEST['logout']) AND $_REQUEST['logout'] != 'false') {
    \User::logout();

}
$user = \User::load();

include_once('twig_extras.php');
$loader = new \Twig\Loader\FilesystemLoader(PATH . 'templates');
$twig = new \Twig\Environment($loader);
$twig->addFilter(new \Twig\TwigFilter('miserend_date', 'twig_hungarian_date_format'));
$twig->addFilter(new \Twig\TwigFilter('trans', 'twig_translate'));
$twig->addFilter(new \Twig\TwigFilter('floor', 'floor'));
// DANGER: a twig declarálva van / meg van hívva a Class/Html/Html.php -ban is. Így ott is módosítani kellhet a filterket

//
//  Useful CONSTANTS

define("ROLES", serialize(['miserend', 'user']));
?>