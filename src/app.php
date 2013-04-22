<?

$app = new \Application\Application();

$app->get('/', function() use ($app) {
	return $app['twig']->render('index.twig');
});

$app->match('/stat/list', function() use ($app) {
	$stats = $app['doctrine.odm.mongodb.dm']->getRepository('Documents\\Stat')->findAll();
	foreach ($stats as $stat) {
		var_dump($stat->getId(), $stat->getTimestamp(), $stat->getValue() );
	}
});

return $app;
