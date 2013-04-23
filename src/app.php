<?

$app = new \Application\Application();

$app->get('/', function() use ($app) {
	return $app['twig']->render('index.twig');
});

$app->match('/stat/list/{game_id}', function($game_id) use ($app) {
	$stats = $app['db.stats']->find(array('game_id' => $game_id));
	return $app->json(iterator_to_array($stats, false));
});

return $app;
