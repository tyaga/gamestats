<?
$app = new \Application\Application();

$app->get('/', function() use ($app) {
	return $app['twig']->render('index.twig');
});

$app->match('/stat/report/{game_id}/{slug}', function($game_id, $slug) use ($app) {

	$reports = array(
		'wins_level' => array(
			'chartType' => 'AnnotatedTimeLine',
			'findType' => 'find',
			'controls' => array(
				'level' => 'NumberRangeFilter'
			),
			'columns' => array(
				'Timestamp' => array( 'label' => 'Timestamp', 'type' => 'datetime' ),
				'wins' => array( 'label' => 'wins', 'type' => 'number' ),
				'level' => array( 'label' => 'level', 'type' => 'number' ),
			)
		),
		'online' => array(
			'chartType' => 'AnnotatedTimeLine',
			'findType' => 'group',
			'columns' => array(
				'Timestamp' => array( 'label' => 'Timestamp', 'type' => 'datetime' ),
				'users' => array( 'label' => 'users', 'type' => 'number' ),
			)
		),
	);

	$report = $reports[$slug];

	$params = array( 'game_id' => $game_id );

	$start = new MongoDate(strtotime('2013-04-28 18:40:00'));
	$end = new MongoDate(time()); //
	$params['Timestamp'] = array('$gt' => $start, '$lt' =>$end);

	switch($report['findType']) {
		case 'find':
		default:
			$stats = $app['db.stats']->find($params, array_keys($report['columns']));

			break;
		case 'group':
			$keysF = new MongoCode('function(doc) {
				var date = new Date(doc.Timestamp);
                var dateKey = (date.getMonth()+1)+"/"+date.getDate()+"/"+date.getFullYear()+"";
                return {"date":dateKey, "user_id": doc.user_id };
			}');

			$reduceF = new MongoCode("function (obj, prev) { prev.count++; }");

			$stats = $app['db.stats']->group($keysF, array('count' => 0), $reduceF, array('condition' => $params));
			//var_dump($stats);
			break;
	}

	$data = array(
		'cols' => array_values($report['columns']),
		'rows' => array()
	);

	foreach ($stats as $stat) {
		$d = array('c' => array());

		foreach ($report['columns'] as $column_name => $column_data) {
			$v = $stat[$column_name];
			switch($column_data['type']) {
				case 'date':
				case 'datetime':
					$v = date('Y-M-d h:i:s', $v->sec);
					break;
				default:
					break;
			}
			$d['c'][] = array('v' =>  $v);
		}
		$data['rows'][] = $d;
	}

	return $app->json(array('data' => $data, 'report' => $report));
});

return $app;
