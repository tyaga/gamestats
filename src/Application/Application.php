<?
namespace Application;

use \Silex\Provider\TwigServiceProvider;
use Neutron\Silex\Provider\MongoDBODMServiceProvider;

define('__ROOT__',  __DIR__ . '/../../');
class Application extends \Silex\Application {
	public function __construct(array $values = array()) {
		$app = $this;
		parent::__construct($values);

		$app['debug'] = true;

		$app['cache.path'] = __ROOT__.'cache';

		$app->register(new TwigServiceProvider(), array(
			'twig.path' => array(__ROOT__.'views'),
			'twig.options' => array('debug' => $app['debug'],'cache' => $app['cache.path'] . '/twig')
		));

		$app->register(new MongoDBODMServiceProvider(), array(
			'doctrine.odm.mongodb.connection_options' => array(
				'database' => 'gamestat',
				'host' => 'localhost',
				'port' => '27017',
			),
			'doctrine.odm.mongodb.documents' => array(
				array(
					'type' => 'annotation',
					'path' => array(
						'src/Documents',
					),
					'namespace' => 'Documents'
				),
			),
			'doctrine.odm.mongodb.proxies_dir'             => __ROOT__.'cache/doctrine/Proxy',
			'doctrine.odm.mongodb.proxies_namespace'       => 'DoctrineMongoDBProxy',
			'doctrine.odm.mongodb.auto_generate_proxies'   => true,
			'doctrine.odm.mongodb.hydrators_dir'           => __ROOT__.'cache/doctrine/Hydrator',
			'doctrine.odm.mongodb.hydrators_namespace'     => 'DoctrineMongoDBHydrator',
			'doctrine.odm.mongodb.auto_generate_hydrators' => true,
			'doctrine.odm.mongodb.metadata_cache'          => new \Doctrine\Common\Cache\ArrayCache(),
		));
	}
}
