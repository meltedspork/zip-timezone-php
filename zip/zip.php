<?php
require str_replace("php", "", __DIR__ ) . "vendor/autoload.php";

$app = new \Slim\Slim();

$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());

function postal($zipcode) {
	$zips = getAPI($zipcode);

	$result = new stdClass();
	$result->city = $zips->primary_city;
	$result->state = $zips->state;
	$result->county = $zips->county;
	$result->country = $zips->country;

	return $result;
}

function timezone($zipcode) {
	$zips = getAPI($zipcode);

	$result = new stdClass();
	$result->timezone = $zips->timezone;

	return $result;
}

function geolocation($zipcode) {
	$zips = getAPI($zipcode);

	$result = new stdClass();
	$result->latitude = $zips->latitude;
	$result->longitude = $zips->longitude;

	return $result;
}

function areacode($zipcode) {
	$zips = getAPI($zipcode);

	$area_code_str = $zips->area_codes;
	$area_codes = explode(",", $area_code_str);

	$result = new stdClass();
	$result->area_codes = new stdClass();
	$result->area_codes = $area_codes;

	return $result;
}

$app->get("/zip/:function(/(:zipcode))", function ($function,$zipcode=null) use ($app) {
	$result = '';
	if(is_callable($function)) {
		$result = call_user_func($function, $zipcode);

		$response = $app->response();
		$response->header("Access-Control-Allow-Origin", "*");

		$app->render(200,array(
			"zipcode" => $zipcode,
			"results" => $result,
			"source" => "http://www.unitedstateszipcodes.org/zip-code-database/",
		));
	} else if((strlen($function) == 5) && is_numeric($function)) {
		$result = getAPI($function);
	} else {
		throw new Exception("-" . $function . "- method does not exist");
	}

	$app->render(200,array(
			"zipcode" => $zipcode,
			"results" => $result,
			"source" => "http://www.unitedstateszipcodes.org/zip-code-database/",
		));
});

function showZip() {
	// static title from csv file
	return array(
		"zip"					// 0
		,"type"					// 1
		,"primary_city"			// 2
		,"acceptable_cities"	// 3
		,"unacceptable_cities"	// 4
		,"state"				// 5
		,"county"				// 6
		,"timezone"				// 7
		,"area_codes"			// 8
		,"latitude"				// 9
		,"longitude"			// 10
		,"world_region"			// 11
		,"country"				// 12
		,"decommissioned"		// 13
		,"estimated_population"	// 14
		,"notes"				// 15
	);
}

function getAPI($zipcode) {
	if ($zipcode == "" || $zipcode == null) {
		throw new Exception("<zipcode> cannot be empty");
	}
	$result = new stdClass();

	// csv file downloaded from
	// http://www.unitedstateszipcodes.org/zip-code-database/
	$zip_db = showZip();
	if (($handle = fopen("flatfile/zip_code_database.csv", "r")) !== FALSE) {
    	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    		if ($data[0] == $zipcode) {
    			$num = count($zip_db);
	    		for ($c=0; $c < $num; $c++) {
           			$result->$zip_db[$c] = $data[$c];
        		}
        		fclose($handle);
        		return $result;
        		break;
	    	}
    	}
    	fclose($handle);
	}
	throw new Exception(" -" . $zipcode . "- zip does not exist");
}

$app->run();