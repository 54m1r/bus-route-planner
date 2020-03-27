<?php


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use NumberToWords\Locale\German;
use NumberToWords\NumberToWords;

Route::view('/', 'index');

Route::post('/search', function () {
    $start = request('start');
    $destination = request('destination');

    //TODO Validation

    $startStation = \App\Station::findOrFail($start);
    $destinationStation = \App\Station::findOrFail($destination);

    return redirect('/route/' . $startStation->name . '/' . $destinationStation->name);
})->name('search');

Route::get('route/{start}/{destination}', function ($start, $destination) {
    $startStation = \App\Station::where('name', '=', $start)->firstOrFail();
    $destinationStation = \App\Station::where('name', '=', $destination)->firstOrFail();

    //Build graph
    $graph = array();

    foreach (\App\Station::all() as $station) {
        $graph[$station->name] = array();
    }

    foreach (\App\Route::all() as $route) {
        foreach (App\RouteStation::where('route_id', '=', $route->id)->get() as $routeStation) {
            $station = $routeStation->station;

            $next = \App\RouteStation::where('id', '>', $routeStation->id)->where('route_id', '=', $route->id)->orderBy('id', 'ASC')->first();
            if ($next == null) {
                $next = \App\RouteStation::where('route_id', '=', $route->id)->orderBy('id', 'ASC')->first();
            }
            $graph[$station->name][$next->station->name] = 1;
        }
    }

    $algorithm = new \Fisharebest\Algorithm\Dijkstra($graph);

    $path = $algorithm->shortestPaths($startStation->name, $destinationStation->name);

    $paths = array();
    $routes = array();

    foreach ($path as $p) {

        //$fullPath = array();

        //dd($path);

        $testStation = $startStation;
        $previousStation = null;
        //dd($startStation->routes);

        //for($i = 0; $i<100;$i++) {
        //dump($path);

        $result = array();

        $start = 0;
        for ($i = 0; $i < 10; $i++) {

            if ($previousStation == $destinationStation) {
                break;
            }

            if (!($start + 1) > sizeof($p)) {
                break;
            }

            //echo($start.'<br>');

            //echo 'searching from ' . $testStation->name . '<br><br>';

            $founds = array();
            //dump($testStation);

            $rounds = 0;
            foreach ($testStation->routes as $routeStation) {

                //echo 'Teste Linie: ' . $routeStation->route->name . ' <br>';
                //echo 'Round: ' . $rounds . '<br><br>';

                //foreach ($path[0] as $station) {
                for ($b = $start; $b < sizeof($p); $b++) {
                    //dump($b);
                    $db_station = \App\Station::where('name', '=', $p[$b])->first();

                    //dump($path[0][$b]);
                    //dump($db_station->name);
                    //dump($db_station->routes);
                    $found = false;
                    //echo "2";
                    // dd($db_station);

                    $alreadyDone = array();
                    foreach ($db_station->routes as $db_routes) {
                        if (in_array($db_routes->route->name, $alreadyDone)) {
                            // dd($alreadyDone);
                            continue;
                        }

                        array_push($alreadyDone, $db_routes->route->name);
                        //dump($db_routes->route->name);

                        $previous = \App\RouteStation::where('id', '<', $db_routes->id)->where('route_id', '=', $db_routes->route->id)->orderBy('id', 'DESC')->first();
                        if ($previous == null) {
                            $previous = \App\RouteStation::where('route_id', '=', $db_routes->route->id)->orderBy('id', 'DESC')->first();
                        }

                        //echo('PRE: '.$previous->station->name.' <br>');

                        //dump($previousStation);

                        if ($db_routes->route->id === $routeStation->route->id) {
                            if (array_key_exists($routeStation->route->name, $founds)) {

                                //echo "FOUNDSAAA: ";
                                //dump($founds[$routeStation->route->name]);
                                if (array_key_exists($routeStation->route->name, $founds) && $founds[$routeStation->route->name] > 1) {
                                    if ($previousStation != null && $previousStation != $startStation) {
                                        if ($previousStation != null && $previous->station != $previousStation) {
                                            //echo $previous->station->name . ' != ' . $previousStation->name . '<br>';
                                            break;
                                        }
                                    }
                                }
                                // if ($previousStation != null)
                                //echo $previous->station->name . ' === ' . $previousStation->name . '<br>';
                                if ($rounds == 0) {
                                    //dump(3);
                                    if (gettype($founds[$routeStation->route->name]) != "array") {
                                        // dump(4);
                                        //create subarray
                                        $value = $founds[$routeStation->route->name];
                                        $founds[$routeStation->route->name] = array();
                                        $founds[$routeStation->route->name][0] = $value;
                                        $founds[$routeStation->route->name][1] = 0;
                                        //dd($founds);
                                    } else {
                                        //dump(5);
                                        $size = sizeof($founds[$routeStation->route->name]);
                                        $founds[$routeStation->route->name][$size] = $founds[$routeStation->route->name];
                                    }
                                }
                                if (gettype($founds[$routeStation->route->name]) != "array") {
                                    $founds[$routeStation->route->name] = $founds[$routeStation->route->name] + 1;
                                } //else {
                                //$size = sizeof($founds[$routeStation->route->name]);
                                //$founds[$routeStation->route->name][$size - 1] = $founds[$routeStation->route->name];
                                //dd($founds);
                                //}
                                $rounds++;
                                $found = true;
                            } else {
                                $founds[$routeStation->route->name] = 0;
                                $rounds++;
                                $found = true;
                            }

                            //if ($found) {
                            //echo $db_station->name . ' -> ' . $routeStation->route->name . ' Round: ' . $rounds . '<br>';
                            // }
                        }
                    }
                    if (!$found) {
                        //echo '<br>';
                        $rounds = 0;
                        break;
                    }
                }
            }

            //dump('FOUNDS: ', $founds);

            //dd($founds);

            $keys = array_keys($founds);

            for ($i = 0; $i < sizeof($founds); $i++) {
                $item = $founds[$keys[$i]];
                if (gettype($item) == "array") {
                    arsort($founds);
                    $first = array_key_first($item);
                    //dump($founds);
                    $founds[$keys[$i]] = $item[$first];
                    // dump($founds[$keys[$i]]);
                    ////dump()
                    //dd($founds);
                }
            }

            //dump('FOUNDS: ', $founds);

            arsort($founds);
            $first = array_key_first($founds);
            //dump($first);

            //dump($start);

            //echo '$founds[$first] '.($founds[$first]).'<br>';
            for ($a = $start; $a <= $start + $founds[$first]; $a++) {
                if ($a >= sizeof($p)) break;
                //echo '$a '.$a.'<br>';
                $result[$p[$a]] = $first;
            }

            //$start = $i < $founds[$first];
            $start = $start + $founds[$first];
            //dump($founds);
            if ($start >= sizeof($p)) {
                $start = sizeof($p) - 1;
            }

            //dd($testStation);

            $previousStation = \App\Station::where('name', '=', $testStation->name)->firstOrFail();
            //dump($previousStation->name);

            $testStation = $p[$start];
            $testStation = \App\Station::where('name', '=', $testStation)->first();
            $previousStation = $testStation;
        }

        //dump('RESULT: ', $result);

        foreach ($path as $item) {
            foreach ($item as $station) {
                $pointStation = \App\Station::where('name', '=', $station)->firstOrFail();
            }
        }

        //dump($result);

        $orderedResult = array();
        $internal_routes = array();

        $stations = 0;

        $oldValue = null;
        $toOrder = array();
        foreach ($result as $key => $value) {
            if ($oldValue == null)
                $oldValue = $value;

            //echo $value.'<br>';

            $hasToEnd = false;
            if ($oldValue != null && $oldValue != $value) {
                //echo '<br>';
                //array_push($orderedResult, $toOrder);
                array_push($toOrder, $key);
                array_push($internal_routes, $oldValue);
                //$orderedResult[$oldValue] = $toOrder;
                $array = array('route' => $oldValue, 'stations' => $toOrder);
                array_push($orderedResult, $array);
                $toOrder = array();
                $oldValue = $value;
            }

            //echo $key.'<br>';
            array_push($toOrder, $key);
            $stations++;
        }
        $array = array('route' => $oldValue, 'stations' => $toOrder);
        array_push($orderedResult, $array);
        array_push($internal_routes, $oldValue);
        //dd($orderedResult);

        //dump($orderedResult);

        //foreach ($orderedResult as $item) {
        // echo($item['route'].'<br>');
        //}
        $array = array();
        $array['route'] = $orderedResult;
        $array['routes'] = $internal_routes;
        $array['stations'] = $stations;

        array_push($paths, $array);
        //array_push($routes, $orderedResult);
    }
    //dd($paths);

    $numberToWords = new NumberToWords();

// build a new number transformer using the RFC 3066 language identifier
    $numberTransformer = $numberToWords->getNumberTransformer('de');
    $count =  $numberTransformer->toWords(sizeof($paths));

    return view('index', ['paths' => $paths, 'result' => true, 'start' => $startStation, 'destination' => $destinationStation, 'count' => $count, 'routes' => $routes]);
});

Route::get('/routes', function () {
    echo "Stationen: <br>";
    $i = 0;
    foreach (\App\Station::all() as $station) {
        echo 'addMarker(' . $station->position['x'] . ', ' . $station->position['y'] . ', "static", "' . $station->name . '", \'bus-station\', true);';
        echo '<br>';
        $i++;
    }

    echo $i . '<br>';

    foreach (\App\Route::all() as $route) {
        echo '<b>' . $route->name . '</b><br>';
        foreach (App\RouteStation::where('route_id', '=', $route->id)->get() as $routeStation) {
            echo $routeStation->station->id . '. ' . $routeStation->station->name . '<br>';
            //dd($routeStation->station->position['x']);
        }
    }
});

Route::get('/setup/41ba0e97-44f4-419d-a38b-ca4af511b68d', function () {
    $stations = json_decode(' {"1":{"Pos":{"x":-147.3548,"y":-2137.37622,"z":16.1928},"Rot":{"x":0.0,"y":0.0,"z":19.059},"Name":"ZOB","Routes":["X1","850"],"Hash":"prop_busstop_04"},"10":{"Pos":{"x":-118.678009,"y":-2128.04175,"z":16.2042313},"Rot":{"x":0.0,"y":0.0,"z":19.7788258},"Name":"ZOB","Routes":["1","2","3","4"],"Hash":"prop_busstop_02"},"2":{"Pos":{"x":-260.76,"y":-1475.407,"z":29.873},"Rot":{"x":0.0,"y":1.8,"z":-95.572},"Name":"Alta Street","Routes":["X1"],"Hash":"prop_busstop_04"},"3":{"Pos":{"x":-232.427,"y":-1059.108,"z":25.947},"Rot":{"x":0.0,"y":4.0,"z":-109.986},"Name":"Pillbox Hill Metro Station","Routes":["X1","2"],"Hash":"prop_busstop_04"},"4":{"Pos":{"x":-267.937,"y":-824.391,"z":30.837},"Rot":{"x":-1.0,"y":0.5,"z":-110.542},"Name":"Peaceful Street","Routes":["X1"],"Hash":"prop_busstop_04"},"5":{"Pos":{"x":-259.712,"y":-284.725,"z":29.861},"Rot":{"x":0.0,"y":2.3,"z":-83.504},"Name":"San Vitus Metro Station","Routes":["X1","2"],"Hash":"prop_busstop_04"},"6":{"Pos":{"x":236.255,"y":-373.668,"z":43.375},"Rot":{"x":0.0,"y":0.8,"z":160.769},"Name":"Town Hall","Routes":["X1","3"],"Hash":"prop_busstop_04"},"7":{"Pos":{"x":247.264,"y":-575.099,"z":42.304},"Rot":{"x":0.0,"y":0.0,"z":68.041},"Name":"Pillbox Hill Medical Center","Routes":["X1"],"Hash":"prop_busstop_04"},"8":{"Pos":{"x":-32.036,"y":-1351.606,"z":28.316},"Rot":{"x":0.0,"y":0.0,"z":1.002},"Name":"Innocence Boulevard","Routes":["X1"],"Hash":"prop_busstop_04"},"9":{"Pos":{"x":-294.107,"y":-1479.068,"z":29.823},"Rot":{"x":-0.5,"y":-2.3,"z":84.671},"Name":"Alta Street","Routes":["X1"],"Hash":"prop_busstop_04"},"11":{"Pos":{"x":-1008.272,"y":-2741.317,"z":12.757},"Rot":{"x":0.0,"y":0.0,"z":152.662},"Name":"Airport","Routes":["X1","4","850"],"Hash":"prop_busstop_04"},"12":{"Pos":{"x":231.977,"y":-341.538,"z":43.289},"Rot":{"x":0.0,"y":-0.63,"z":-17.286},"Name":"Town Hall","Routes":["X2"],"Hash":"prop_busstop_04"},"13":{"Pos":{"x":283.264,"y":181.793,"z":103.452},"Rot":{"x":0.0,"y":0.6,"z":17.446},"Name":"Power Street","Routes":["X2"],"Hash":"prop_busstop_04"},"14":{"Pos":{"x":27.651,"y":638.528,"z":206.39},"Rot":{"x":0.0,"y":0.0,"z":10.436},"Name":"Lake Vinewood Staudamm","Routes":["X2"],"Hash":"prop_busstop_04"},"15":{"Pos":{"x":-614.55,"y":691.8675,"z":148.6717},"Rot":{"x":2.2,"y":3.7,"z":-11.756},"Name":"Normandy Drive","Routes":["X2"],"Hash":"prop_busstop_04"},"16":{"Pos":{"x":-949.5928,"y":704.8868,"z":152.5018},"Rot":{"x":0.0,"y":0.0,"z":-2.1653},"Name":"North Sheldon Avenue","Routes":["X2"],"Hash":"prop_bus_stop_sign"},"17":{"Pos":{"x":-1362.478,"y":632.781,"z":133.732},"Rot":{"x":0.0,"y":0.0,"z":0.074},"Name":"Hangman Avenue","Routes":["X2"],"Hash":"prop_busstop_04"},"19":{"Pos":{"x":-1294.577,"y":301.936,"z":63.915},"Rot":{"x":-0.6,"y":0.0,"z":-6.72},"Name":"Richman Hotel","Routes":["X2"],"Hash":"prop_busstop_04"},"20":{"Pos":{"x":-1474.7,"y":-308.109,"z":45.365},"Rot":{"x":0.0,"y":-4.7,"z":130.886},"Name":"South Rockford Drive","Routes":["X2"],"Hash":"prop_busstop_04"},"21":{"Pos":{"x":-1164.718,"y":-689.17,"z":21.082},"Rot":{"x":0.0,"y":-1.9,"z":132.9},"Name":"Carflex West","Routes":["X2","1","4"],"Hash":"prop_busstop_04"},"22":{"Pos":{"x":-743.841,"y":-933.936,"z":17.61},"Rot":{"x":0.5,"y":3.2,"z":-108.231},"Name":"Ginger Street","Routes":["X2"],"Hash":"prop_busstop_04"},"23":{"Pos":{"x":-243.754,"y":-889.423,"z":29.39},"Rot":{"x":0.0,"y":-1.9,"z":161.194},"Name":"Pillbox Hill Parkplatz","Routes":["X2"],"Hash":"prop_busstop_04"},"24":{"Pos":{"x":65.201,"y":-1001.902,"z":28.357},"Rot":{"x":0.0,"y":0.0,"z":160.271},"Name":"Legion Square","Routes":["X2"],"Hash":"prop_busstop_04"},"25":{"Pos":{"x":279.678,"y":-585.341,"z":42.304},"Rot":{"x":0.0,"y":0.0,"z":-110.395},"Name":"Pillbox Hill Krankenhaus","Routes":["X2"],"Hash":"prop_busstop_04"},"27":{"Pos":{"x":-154.87,"y":-1579.102,"z":33.714},"Rot":{"x":-1.3,"y":0.0,"z":-128.486},"Name":"Carson Avenue","Routes":["1"],"Hash":"prop_busstop_02"},"28":{"Pos":{"x":-588.924,"y":-1222.595,"z":14.547},"Rot":{"x":0.0,"y":0.0,"z":49.778},"Name":"Calais Avenue","Routes":["1"],"Hash":"prop_busstop_02"},"29":{"Pos":{"x":-720.05,"y":-1164.722,"z":9.623},"Rot":{"x":0.0,"y":0.0,"z":-51.301},"Name":"La Puerta Anlegehafen","Routes":["1"],"Hash":"prop_busstop_02"},"30":{"Pos":{"x":-1098.992,"y":-290.172,"z":4.467},"Rot":{"x":0.0,"y":-0.27,"z":30.463},"Name":"Prosperity Street","Routes":["1"],"Hash":"prop_busstop_02"},"31":{"Pos":{"x":-1125.743,"y":-1423.764,"z":4.14},"Rot":{"x":0.0,"y":-0.8,"z":117.487},"Name":"Bay City Avenue","Routes":["1"],"Hash":"prop_busstop_02"},"32":{"Pos":{"x":-1020.781,"y":-1626.624,"z":3.726},"Rot":{"x":0.0,"y":-0.8,"z":59.628},"Name":"Rub Street - Fahrrad-Shop","Routes":["1"],"Hash":"prop_busstop_02"},"33":{"Pos":{"x":-1172.913,"y":-1444.902,"z":3.386},"Rot":{"x":0.0,"y":0.0,"z":-56.174},"Name":"Agujia Street - Vespucci Beach","Routes":["1"],"Hash":"prop_busstop_02"},"34":{"Pos":{"x":-1370.913,"y":-963.267,"z":8.082},"Rot":{"x":0.0,"y":2.2,"z":-55.773},"Name":"Vespucci Boulevard","Routes":["1"],"Hash":"prop_busstop_02"},"35":{"Pos":{"x":-1138.636,"y":-826.904,"z":14.215},"Rot":{"x":0.0,"y":3.7,"z":-138.748},"Name":"Vespucci Kanäle - 24/7 Shop","Routes":["1"],"Hash":"prop_busstop_02"},"36":{"Pos":{"x":-1102.401,"y":-711.623,"z":19.407},"Rot":{"x":0.0,"y":1.6,"z":-48.465},"Name":"Carflex West","Routes":["1","850"],"Hash":"prop_busstop_02"},"37":{"Pos":{"x":-1139.237,"y":-424.259,"z":35.158},"Rot":{"x":0.0,"y":1.2,"z":-172.34},"Name":"South Rockford Drive - Marktplatz","Routes":["1"],"Hash":"prop_busstop_02"},"38":{"Pos":{"x":-680.807,"y":-379.701,"z":33.28},"Rot":{"x":0.0,"y":0.0,"z":156.477},"Name":"Rockford Hills","Routes":["1"],"Hash":"prop_busstop_02"},"39":{"Pos":{"x":-739.723,"y":-125.485,"z":36.709},"Rot":{"x":0.0,"y":2.2,"z":-31.747},"Name":"Eastburne Way - Kleidungs-Shop","Routes":["1"],"Hash":"prop_busstop_02"},"40":{"Pos":{"x":-958.134,"y":29.819,"z":48.38},"Rot":{"x":0.0,"y":0.0,"z":-58.702},"Name":"Rockford Hills - Golfplatz","Routes":["1"],"Hash":"prop_busstop_02"},"41":{"Pos":{"x":-1069.706,"y":370.781,"z":67.815},"Rot":{"x":0.0,"y":1.7,"z":-91.11},"Name":"Greenwich Way","Routes":["1"],"Hash":"prop_busstop_02"},"42":{"Pos":{"x":-674.4344,"y":486.2485,"z":109.1017},"Rot":{"x":-0.01,"y":0.5601,"z":-164.4601},"Name":"Hillchest Ridge Access Road","Routes":["1"],"Hash":"prop_busstop_02"},"43":{"Pos":{"x":-317.296,"y":450.364,"z":107.484},"Rot":{"x":0.0,"y":-0.8,"z":145.122},"Name":"Cox Way","Routes":["1"],"Hash":"prop_busstop_02"},"44":{"Pos":{"x":51.333,"y":339.98,"z":111.613},"Rot":{"x":0.0,"y":0.5,"z":157.105},"Name":"Las Lagunas Boulevard","Routes":["1"],"Hash":"prop_busstop_02"},"45":{"Pos":{"x":-111.801,"y":318.267,"z":108.082},"Rot":{"x":2.0,"y":2.9,"z":-32.646},"Name":"Eclipse Boulevard","Routes":["1"],"Hash":"prop_busstop_02"},"46":{"Pos":{"x":-271.958,"y":436.093,"z":107.19},"Rot":{"x":0.0,"y":-0.3,"z":-25.42},"Name":"Cox Way","Routes":["1"],"Hash":"prop_busstop_02"},"47":{"Pos":{"x":-591.603,"y":515.118,"z":105.5767},"Rot":{"x":0.0,"y":-0.1,"z":11.571},"Name":"Hillchest Ridge Access Road","Routes":["1"],"Hash":"prop_busstop_02"},"48":{"Pos":{"x":-1085.205,"y":356.162,"z":66.936},"Rot":{"x":0.0,"y":0.0,"z":91.154},"Name":"Greenwich Way","Routes":["X2","1"],"Hash":"prop_busstop_02"},"49":{"Pos":{"x":-955.849,"y":-23.624,"z":42.941},"Rot":{"x":0.0,"y":0.0,"z":126.132},"Name":"Rockford Hills - Golfplatz","Routes":["1"],"Hash":"prop_busstop_02"},"50":{"Pos":{"x":-727.974,"y":-214.623,"z":36.154},"Rot":{"x":0.0,"y":-0.8,"z":159.752},"Name":"Eastburne Way - Kleidungs-Shop","Routes":["1"],"Hash":"prop_busstop_02"},"51":{"Pos":{"x":-683.635,"y":-343.988,"z":33.769},"Rot":{"x":0.0,"y":0.0,"z":-20.662},"Name":"Rockford Hills","Routes":["1","4"],"Hash":"prop_busstop_02"},"52":{"Pos":{"x":-1168.895,"y":-398.059,"z":34.5408},"Rot":{"x":0.0,"y":-3.1,"z":8.8088},"Name":"South Rockford Drive - Marktplatz","Routes":["1"],"Hash":"prop_busstop_02"},"54":{"Pos":{"x":-1216.767,"y":-855.398,"z":12.516},"Rot":{"x":0.0,"y":-1.7,"z":33.21},"Name":"Vespucci Kanäle - 24/7 Shop","Routes":["1"],"Hash":"prop_busstop_02"},"55":{"Pos":{"x":-1379.202,"y":-978.643,"z":7.739},"Rot":{"x":0.0,"y":-2.3,"z":124.635},"Name":"Vespucci Boulevard","Routes":["1"],"Hash":"prop_busstop_02"},"56":{"Pos":{"x":-1170.854,"y":-1474.205,"z":3.37},"Rot":{"x":-1.3,"y":0.0,"z":124.199},"Name":"Agujia Street - Vespucci Beach","Routes":["1"],"Hash":"prop_busstop_02"},"57":{"Pos":{"x":-1002.941,"y":-1627.447,"z":3.92},"Rot":{"x":0.0,"y":0.0,"z":-114.384},"Name":"Rub Street - Fahrrad-Shop","Routes":["1"],"Hash":"prop_busstop_02"},"58":{"Pos":{"x":-1140.99,"y":-1362.488,"z":4.073},"Rot":{"x":0.0,"y":-0.2,"z":-64.37},"Name":"Bay City Avenue","Routes":["1"],"Hash":"prop_busstop_02"},"59":{"Pos":{"x":-958.946,"y":-1246.691,"z":4.505},"Rot":{"x":0.0,"y":0.0,"z":-151.129},"Name":"Prosperity Street","Routes":["1"],"Hash":"prop_busstop_02"},"60":{"Pos":{"x":-741.138,"y":-1189.371,"z":9.643},"Rot":{"x":0.0,"y":0.0,"z":130.131},"Name":"La Puerta Anlegehafen","Routes":["1","4"],"Hash":"prop_busstop_02"},"61":{"Pos":{"x":-556.49,"y":-1223.531,"z":15.661},"Rot":{"x":3.6,"y":4.1,"z":-124.107},"Name":"Calais Avenue","Routes":["1","4"],"Hash":"prop_busstop_02"},"62":{"Pos":{"x":-160.265,"y":-1559.721,"z":34.118},"Rot":{"x":-3.8,"y":1.3,"z":48.781},"Name":"Carson Avenue","Routes":["1"],"Hash":"prop_busstop_02"},"65":{"Pos":{"x":-82.873,"y":-1536.772,"z":32.145},"Rot":{"x":0.0,"y":4.0,"z":-39.541},"Name":"Forum Drive","Routes":["2"],"Hash":"prop_busstop_02"},"67":{"Pos":{"x":-140.189,"y":-797.906,"z":31.146},"Rot":{"x":0.0,"y":2.1,"z":-86.305},"Name":"Vespucci Boulevard - FIB-Gebäude","Routes":["2"],"Hash":"prop_busstop_02"},"69":{"Pos":{"x":-213.129,"y":98.024,"z":68.331},"Rot":{"x":0.0,"y":3.0,"z":-96.018},"Name":"Hawick Avenue","Routes":["2"],"Hash":"prop_busstop_02"},"70":{"Pos":{"x":-141.97,"y":240.004,"z":94.1219},"Rot":{"x":0.0,"y":4.5,"z":178.431},"Name":"North Archer Avenue","Routes":["2"],"Hash":"prop_busstop_02"},"71":{"Pos":{"x":262.141,"y":155.95,"z":103.661},"Rot":{"x":0.0,"y":-0.8,"z":160.281},"Name":"Power Street","Routes":["2"],"Hash":"prop_busstop_02"},"72":{"Pos":{"x":477.806,"y":77.418,"z":96.219},"Rot":{"x":0.0,"y":-2.9,"z":160.168},"Name":"Meteor Street","Routes":["2"],"Hash":"prop_busstop_02"},"73":{"Pos":{"x":778.799,"y":112.591,"z":77.696},"Rot":{"x":0.0,"y":0.0,"z":-123.635},"Name":"Vinewood Hills Autobahn","Routes":["2"],"Hash":"prop_busstop_02"},"74":{"Pos":{"x":715.402,"y":666.968,"z":128.058},"Rot":{"x":0.0,"y":0.0,"z":-20.306},"Name":"Vinewood Bowl","Routes":["2"],"Hash":"prop_busstop_02"},"75":{"Pos":{"x":956.336,"y":155.898,"z":79.842},"Rot":{"x":0.0,"y":0.0,"z":51.062},"Name":"Diamond Casino","Routes":["2","850"],"Hash":"prop_busstop_02"},"76":{"Pos":{"x":817.494,"y":-173.956,"z":71.81},"Rot":{"x":0.0,"y":-2.5,"z":58.252},"Name":"York Street","Routes":["2"],"Hash":"prop_busstop_02"},"77":{"Pos":{"x":861.637,"y":-556.453,"z":56.387},"Rot":{"x":0.0,"y":-0.4,"z":103.811},"Name":"West Mirror Drive","Routes":["2"],"Hash":"prop_busstop_02"},"78":{"Pos":{"x":1281.998,"y":-644.451,"z":66.998},"Rot":{"x":0.0,"y":-3.0,"z":116.63},"Name":"Mirror Park Neubaugebiet","Routes":["2"],"Hash":"prop_busstop_02"},"79":{"Pos":{"x":833.396,"y":-996.649,"z":25.944},"Rot":{"x":0.0,"y":-4.93,"z":2.371},"Name":"La Mesa Tankstelle","Routes":["2"],"Hash":"prop_busstop_02"},"80":{"Pos":{"x":412.373,"y":-900.796,"z":28.43},"Rot":{"x":0.0,"y":0.0,"z":-92.342},"Name":"Atlee Street - L.S.P.D","Routes":["2"],"Hash":"prop_busstop_02"},"81":{"Pos":{"x":513.905,"y":-719.38,"z":23.891},"Rot":{"x":0.0,"y":0.0,"z":-94.799},"Name":"La Mesa Güterbahnhof","Routes":["2"],"Hash":"prop_busstop_02"},"82":{"Pos":{"x":303.922,"y":-765.249,"z":28.305},"Rot":{"x":0.0,"y":0.0,"z":71.7},"Name":"Strawberry Avenue","Routes":["2"],"Hash":"prop_busstop_02"},"83":{"Pos":{"x":256.915,"y":-1534.655,"z":28.303},"Rot":{"x":0.0,"y":0.0,"z":30.276},"Name":"Central Medical Center","Routes":["2"],"Hash":"prop_busstop_02"},"85":{"Pos":{"x":177.166,"y":-1945.905,"z":19.19},"Rot":{"x":0.0,"y":-4.1,"z":50.89},"Name":"Covenant Avenue","Routes":["2","3"],"Hash":"prop_busstop_02"},"87":{"Pos":{"x":121.379,"y":-1457.738,"z":28.306},"Rot":{"x":0.0,"y":0.0,"z":-130.338},"Name":"Innocence Boulevard","Routes":["3"],"Hash":"prop_busstop_02"},"88":{"Pos":{"x":42.105,"y":-958.123,"z":28.362},"Rot":{"x":0.0,"y":0.0,"z":-20.134},"Name":"Legion Square","Routes":["3"],"Hash":"prop_busstop_02"},"89":{"Pos":{"x":150.996,"y":-597.521,"z":42.911},"Rot":{"x":0.0,"y":-0.6,"z":160.369},"Name":"Integrity Way Tunnel","Routes":["3"],"Hash":"prop_busstop_02"},"90":{"Pos":{"x":174.789,"y":-763.72,"z":31.439},"Rot":{"x":0.0,"y":-2.3,"z":71.102},"Name":"Strawberry Avenue","Routes":["3"],"Hash":"prop_busstop_02"},"91":{"Pos":{"x":-49.668,"y":-1125.666,"z":25.016},"Rot":{"x":0.0,"y":-1.3,"z":3.945},"Name":"Premium Deluxe Motorsport","Routes":["3"],"Hash":"prop_busstop_02"},"93":{"Pos":{"x":767.181,"y":-937.528,"z":24.667},"Rot":{"x":0.0,"y":0.5,"z":95.586},"Name":"La Mesa Tankstelle","Routes":["3"],"Hash":"prop_busstop_02"},"94":{"Pos":{"x":785.166,"y":-1364.903,"z":25.536},"Rot":{"x":0.0,"y":1.1,"z":89.603},"Name":"Supply Street","Routes":["3"],"Hash":"prop_busstop_02"},"95":{"Pos":{"x":821.442,"y":-1645.351,"z":29.028},"Rot":{"x":0.0,"y":-2.9,"z":86.054},"Name":"Logistik","Routes":["3"],"Hash":"prop_busstop_02"},"96":{"Pos":{"x":431.971,"y":-1980.942,"z":22.144},"Rot":{"x":0.0,"y":0.2,"z":-47.07},"Name":"Little Bighorn Avenue","Routes":["3"],"Hash":"prop_busstop_02"},"97":{"Pos":{"x":500.005,"y":-1643.393,"z":28.282},"Rot":{"x":0.0,"y":0.0,"z":-39.415},"Name":"Rancho Railroad Crossing","Routes":["3"],"Hash":"prop_busstop_02"},"101":{"Pos":{"x":-389.306,"y":-1785.869,"z":20.524},"Rot":{"x":0.0,"y":-2.2,"z":-78.976},"Name":"Mülldeponie","Routes":["4"],"Hash":"prop_busstop_02"},"102":{"Pos":{"x":-625.65,"y":-913.835,"z":23.1},"Rot":{"x":0.0,"y":-3.1,"z":88.995},"Name":"Palomino Avenue","Routes":["4"],"Hash":"prop_busstop_02"},"103":{"Pos":{"x":-619.134,"y":-605.537,"z":32.45},"Rot":{"x":0.0,"y":3.3,"z":-91.361},"Name":"San Andreas Avenue","Routes":["4"],"Hash":"prop_busstop_02"},"105":{"Pos":{"x":-1290.801,"y":-47.47,"z":46.056},"Rot":{"x":0.0,"y":3.7,"z":-15.336},"Name":"Rockford Hills - Golfplatz","Routes":["4"],"Hash":"prop_busstop_02"},"106":{"Pos":{"x":-1555.001,"y":187.314,"z":56.643},"Rot":{"x":0.0,"y":0.8,"z":25.937},"Name":"Universität","Routes":["4"],"Hash":"prop_busstop_02"},"107":{"Pos":{"x":-1950.494,"y":317.406,"z":88.428},"Rot":{"x":0.0,"y":3.3,"z":-82.349},"Name":"Richman - Ace Jones Drive","Routes":["4"],"Hash":"prop_busstop_02"},"108":{"Pos":{"x":-1953.694,"y":575.176,"z":115.838},"Rot":{"x":0.0,"y":7.5,"z":-99.688},"Name":"Vinewood Hills - Ace Jones Drive","Routes":["4"],"Hash":"prop_busstop_02"},"110":{"Pos":{"x":-1979.543,"y":558.055,"z":112.535},"Rot":{"x":0.0,"y":-11.2,"z":74.904},"Name":"Vinewood Hills - Ace Jones Drive","Routes":["4"],"Hash":"prop_busstop_02"},"111":{"Pos":{"x":-1939.896,"y":216.965,"z":83.619},"Rot":{"x":0.0,"y":0.0,"z":120.007},"Name":"Richman - Ace Jones Drive","Routes":["4"],"Hash":"prop_busstop_02"},"113":{"Pos":{"x":-1631.694,"y":-271.614,"z":51.735},"Rot":{"x":0.0,"y":-2.9,"z":82.415},"Name":"Morningwood Friedhof","Routes":["4"],"Hash":"prop_busstop_02"},"114":{"Pos":{"x":-1621.939,"y":-529.054,"z":33.54},"Rot":{"x":0.0,"y":-1.7,"z":40.148},"Name":"Bay City Incline","Routes":["4"],"Hash":"prop_busstop_02"},"115":{"Pos":{"x":-1468.271,"y":-630.115,"z":29.828},"Rot":{"x":0.0,"y":0.8,"z":-144.887},"Name":"Marathon Street","Routes":["4"],"Hash":"prop_busstop_02"},"121":{"Pos":{"x":464.197,"y":-1665.509,"z":28.327},"Rot":{"x":0.0,"y":0.0,"z":-130.251},"Name":"Logistik","Routes":["850"],"Hash":"prop_busstop_04"},"122":{"Pos":{"x":1113.174,"y":-1749.087,"z":34.665},"Rot":{"x":0.0,"y":0.0,"z":-159.181},"Name":"Elysian Fields Freeway Brücke","Routes":["850"],"Hash":"prop_busstop_04"},"123":{"Pos":{"x":746.11,"y":-365.259,"z":43.805},"Rot":{"x":0.9,"y":6.0,"z":-157.709},"Name":"An der Steigung","Routes":["850"],"Hash":"prop_busstop_04"},"125":{"Pos":{"x":2549.863,"y":1539.186,"z":29.5636},"Rot":{"x":1.64,"y":-0.21,"z":-92.1081},"Name":"Windpark","Routes":["850"],"Hash":"prop_busstop_04"},"126":{"Pos":{"x":2580.446,"y":2636.06,"z":36.684},"Rot":{"x":0.0,"y":3.3,"z":-72.74},"Name":"Davis Quartz","Routes":["850"],"Hash":"prop_busstop_04"},"127":{"Pos":{"x":1850.151,"y":2552.3,"z":44.668},"Rot":{"x":0.0,"y":0.0,"z":90.644},"Name":"Staatsgefängnis","Routes":["850"],"Hash":"prop_busstop_04"},"128":{"Pos":{"x":1948.751,"y":3319.189,"z":44.22},"Rot":{"x":0.0,"y":0.0,"z":-20.437},"Name":"Sandy Shores Airfield","Routes":["850"],"Hash":"prop_busstop_04"},"129":{"Pos":{"x":1634.193,"y":3637.725,"z":34.211},"Rot":{"x":0.0,"y":-1.2,"z":-60.181},"Name":"Sandy Shores","Routes":["850"],"Hash":"prop_busstop_04"},"130":{"Pos":{"x":1787.626,"y":3766.128,"z":32.718},"Rot":{"x":0.0,"y":0.0,"z":-149.047},"Name":"Sandy Shores Ammunation","Routes":["850"],"Hash":"prop_busstop_04"},"131":{"Pos":{"x":1936.221,"y":3738.571,"z":31.312},"Rot":{"x":0.0,"y":-0.2,"z":120.786},"Name":"Zancudo Avenue","Routes":["850"],"Hash":"prop_busstop_04"},"132":{"Pos":{"x":2487.81348,"y":4376.494,"z":35.2965},"Rot":{"x":2.15,"y":0.68,"z":-108.598},"Name":"Grapeseed","Routes":["850"],"Hash":"prop_busstop_04"},"133":{"Pos":{"x":2078.71,"y":4709.72656,"z":40.0386},"Rot":{"x":-6.3,"y":0.0,"z":44.392},"Name":"Mc Kenzie Airfield","Routes":["850"],"Hash":"prop_busstop_04"},"134":{"Pos":{"x":1400.913,"y":4392.737,"z":42.174},"Rot":{"x":2.8,"y":-1.9,"z":41.621},"Name":"Millars Fishery Angelsteg","Routes":["850"],"Hash":"prop_busstop_04"},"135":{"Pos":{"x":1699.041,"y":4731.471,"z":41.118},"Rot":{"x":1.5,"y":-0.7,"z":-70.488},"Name":"Grapeseed Ave","Routes":["850"],"Hash":"prop_busstop_04"},"136":{"Pos":{"x":1713.726,"y":4959.51,"z":43.386},"Rot":{"x":0.0,"y":4.7,"z":-151.559},"Name":"Ltd. Tankstelle","Routes":["850"],"Hash":"prop_busstop_04"},"137":{"Pos":{"x":2431.714,"y":5124.247,"z":45.819},"Rot":{"x":0.0,"y":0.0,"z":149.888},"Name":"O#! Nail Way","Routes":["850"],"Hash":"prop_busstop_04"},"138":{"Pos":{"x":3786.989,"y":4453.062,"z":4.343},"Rot":{"x":-3.7,"y":-4.7,"z":151.705},"Name":"San-Chianski Bergkette","Routes":["850"],"Hash":"prop_busstop_04"},"139":{"Pos":{"x":1598.057,"y":6442.027,"z":24.329},"Rot":{"x":-1.9,"y":-2.6,"z":-23.773},"Name":"Up-n-Atom Dinner","Routes":["850"],"Hash":"prop_busstop_04"},"140":{"Pos":{"x":441.915,"y":6583.463,"z":26.087},"Rot":{"x":1.8,"y":0.0,"z":-5.151},"Name":"Farm","Routes":["850"],"Hash":"prop_busstop_04"},"141":{"Pos":{"x":-92.279,"y":6584.891,"z":28.38},"Rot":{"x":-0.4,"y":0.95,"z":40.55},"Name":"Procopio Drive","Routes":["850"],"Hash":"prop_busstop_04"},"142":{"Pos":{"x":-169.649,"y":6380.499,"z":30.473},"Rot":{"x":0.0,"y":0.0,"z":44.046},"Name":"J#!s Bonds","Routes":["850"],"Hash":"prop_busstop_04"},"143":{"Pos":{"x":-445.065,"y":6087.075,"z":30.546},"Rot":{"x":1.0,"y":0.8,"z":69.456},"Name":"L.S.S.D","Routes":["850"],"Hash":"prop_busstop_04"},"144":{"Pos":{"x":-731.695,"y":5788.055,"z":16.817},"Rot":{"x":2.3,"y":0.3,"z":102.856},"Name":"Paleto Forest","Routes":["850"],"Hash":"prop_busstop_04"},"145":{"Pos":{"x":-1533.121,"y":4994.542,"z":61.224},"Rot":{"x":-2.3,"y":0.94,"z":50.245},"Name":"Paleto Cove","Routes":["850"],"Hash":"prop_busstop_04"},"146":{"Pos":{"x":-1851.09,"y":2039.448,"z":134.996},"Rot":{"x":0.0,"y":8.4,"z":11.673},"Name":"Tongva Hills Weinfelder","Routes":["850"],"Hash":"prop_busstop_04"},"147":{"Pos":{"x":-2548.318,"y":1876.241,"z":165.849},"Rot":{"x":-5.07,"y":-1.61,"z":116.975},"Name":"Tongva Hills Anwesen","Routes":["850"],"Hash":"prop_busstop_04"},"148":{"Pos":{"x":-2731.126,"y":1464.962,"z":97.475},"Rot":{"x":0.0,"y":-9.9,"z":66.355},"Name":"Barham Canyon Anwesen","Routes":["850"],"Hash":"prop_busstop_04"},"149":{"Pos":{"x":-3183.451,"y":1242.885,"z":9.631},"Rot":{"x":-4.0,"y":-2.6,"z":81.762},"Name":"Chumash","Routes":["850"],"Hash":"prop_busstop_04"},"150":{"Pos":{"x":-3235.132,"y":958.452,"z":12.198},"Rot":{"x":0.0,"y":0.9,"z":100.312},"Name":"Chumash Pie","Routes":["850"],"Hash":"prop_busstop_04"},"151":{"Pos":{"x":-3073.958,"y":669.517,"z":11.604},"Rot":{"x":-2.9,"y":-9.4,"z":128.754},"Name":"Banham Canyon","Routes":["850"],"Hash":"prop_busstop_04"},"152":{"Pos":{"x":-3061.192,"y":220.65,"z":14.914},"Rot":{"x":-2.1,"y":2.8,"z":172.321},"Name":"Ineseno Road","Routes":["850"],"Hash":"prop_busstop_04"},"153":{"Pos":{"x":-1975.149,"y":-492.13,"z":10.798},"Rot":{"x":1.9,"y":0.0,"z":140.1},"Name":"Del Perro Freeway","Routes":["850"],"Hash":"prop_busstop_04"},"155":{"Pos":{"x":-937.321,"y":-579.379,"z":17.286},"Rot":{"x":0.0,"y":0.6,"z":-157.92},"Name":"Del Perro Freeway Auffahrt","Routes":["850"],"Hash":"prop_busstop_04"},"156":{"Pos":{"x":-429.532,"y":-1318.882,"z":21.112},"Rot":{"x":-1.8,"y":0.9,"z":88.944},"Name":"Innocence Boulevard Unterführung","Routes":["850"],"Hash":"prop_busstop_04"},"157":{"Pos":{"x":-1580.453,"y":1359.861,"z":128.913},"Rot":{"x":0.0,"y":-6.7,"z":-130.82},"Name":"Downstream","Routes":["4"],"Hash":"prop_busstop_02"}}', true);

    $busRoutes = json_decode("{\"1\":[10,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,21,54,55,56,57,58,59,60,61,62],\"2\":[10,65,3,67,5,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,85],\"3\":[10,87,88,89,90,91,6,93,94,95,96,97,85],\"4\":[10,101,61,102,103,51,105,106,107,108,157,110,111,113,114,115,21,60,11],\"850\":[1,121,122,123,75,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,36,155,156,11],\"X1\":[1,2,3,4,5,6,7,8,9,11],\"X2\":[12,13,14,15,16,17,48,19,20,21,22,23,24,25]}", true);

    foreach ($stations as $station) {
        if (\App\Station::where('name', '=', $station['Name'])->first() == null)
            $db_station = App\Station::create(['name' => $station['Name'], 'position' => $station['Pos']]);
        foreach ($station['Routes'] as $route) {
            $db_route = App\Route::firstOrCreate(['name' => $route]);
        }
    }

    foreach (\App\Route::all() as $route) {
        $busRouteStations = $busRoutes[$route->name];
        foreach ($busRouteStations as $busStation) {
            $station = $stations[$busStation];
            //dd($station);
            $db_station = \App\Station::where('name', '=', $station['Name'])->first();
            if ($db_station)
                App\RouteStation::create(['route_id' => $route->id, 'station_id' => $db_station->id]);
        }
    }

    /*
     *
     * rot = 1
 <21:18:05> "FIorian": 2 = blau
 <21:18:14> "FIorian": 3 = hellgrün
 <21:18:19> "FIorian": 4 = orange
 <21:18:28> "FIorian": X1 = dunkelgrün
 <21:18:48> "FIorian": 850 = schwarz
 <21:20:22> "FIorian": X2 = gelb
     */
});

Route::get('/nodes', function () {
    ini_set('memory_limit', '-1');
    $path = storage_path() . "/app/nodes.json"; // ie: /var/www/laravel/app/storage/json/filename.json
    //dd($path);
    $json = json_decode(file_get_contents($path), false);
    foreach ($json as $item) {
        //dd($json[40]);
        $roads = array();
        foreach ($json[40]->Nodes as $node) {
            // dd($node);
            if ($node->IsValidForGps) {
                //echo 'addMarker('.$node->Position->X.', '.$node->Position->Y.', "static", "'.$node->StreetName.'", \'yellow-dot\', true);';
                //echo '<br>';
                if (!array_key_exists($node->StreetName, $roads)) {
                    $roads[$node->StreetName] = array();
                }
                array_push($roads[$node->StreetName], array('X' => $node->Position->X, 'Y' => $node->Position->Y));
            }
        }

        foreach ($roads['El Burro Blvd'] as $b) {
            // echo 'addMarker('.$b['X'].', '.$b['Y'].', "static", "'.'El Burro Blvd'.'", \'yellow-dot\', true);';
            echo ' {lat: ' . $b['X'] . ', lng: ' . $b['Y'] . '},';
            echo '<br>';
        }

        //dd($item->DimensionMin);
        //addMarker(-498.75, -1097.0, "static", "Adam's Apple Blvd", 'yellow-dot', true);
        /*echo 'addMarker('.$item->DimensionMin->X.', '.$item->DimensionMin->Y.', "static", "Adam\'s Apple Blvd", \'yellow-dot\', true);';
        echo '<br>';
        echo 'addMarker('.$item->DimensionMax->X.', '.$item->DimensionMax->Y.', "static", "Adam\'s Apple Blvd", \'yellow-dot\', true);';
        echo '<br>'; */
    }
});
