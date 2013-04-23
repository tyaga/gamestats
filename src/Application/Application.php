<?
namespace Application;

define('__ROOT__',  __DIR__ . '/../../');

class Application extends \Silex\Application {
	public function __construct(array $values = array()) {
		$app = $this;
		parent::__construct($values);

		$app['debug'] = true;

		$app['cache.path'] = __ROOT__.'cache';

		$app->register(new  \Silex\Provider\TwigServiceProvider(), array(
			'twig.path' => array(__ROOT__.'views'),
			'twig.options' => array('debug' => $app['debug'],'cache' => $app['cache.path'] . '/twig')
		));

		$app['db'] = $app->share(function() {
			return new \MongoClient("mongodb://localhost:27017");
		});

		$app['db.games'] = $app['db']->gamestat->games;
		$app['db.stats'] = $app['db']->gamestat->stats_log;

	}
}
