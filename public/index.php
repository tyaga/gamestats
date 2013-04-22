<?

umask(0000);
require(__DIR__ . '/../vendor/autoload.php');

/** @var $app \Application\Application  */
$app = require(__DIR__ . '/../src/app.php');

$app->run();
