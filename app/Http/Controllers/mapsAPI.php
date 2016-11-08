<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// include('vendor/rmccue/requests/library/Requests.php');

class mapsAPI extends Controller
{

	protected $key1 = 'AIzaSyDCDYrTZsSzQ71XNIeQyojam4KBK2kSjAU';

	public function getData(Request $request) {

		$data = $request->input('data');
		var_dump($data);

	}


    public function getDirections($startln, $endln, $mode) {

    	$parameters = NULL;
    	$url = 'https://maps.googleapis.com/maps/api/directions/json?origin='.$startln.'&alternatives=true&mode='.$mode.'&destination='.$endln.'&key=AIzaSyDCDYrTZsSzQ71XNIeQyojam4KBK2kSjAU';
    	$content = file_get_contents($url);
    	$content = json_decode($content, true);

    	function decodePolylineToArray($encoded)
		{
		  $length = strlen($encoded);
		  $index = 0;
		  $points = array();
		  $lat = 0;
		  $lng = 0;

		  while ($index < $length)
		  {
		    // Temporary variable to hold each ASCII byte.
		    $b = 0;

		    // The encoded polyline consists of a latitude value followed by a
		    // longitude value.  They should always come in pairs.  Read the
		    // latitude value first.
		    $shift = 0;
		    $result = 0;
		    do
		    {
		      // The `ord(substr($encoded, $index++))` statement returns the ASCII
		      //  code for the character at $index.  Subtract 63 to get the original
		      // value. (63 was added to ensure proper ASCII characters are displayed
		      // in the encoded polyline string, which is `human` readable)
		      $b = ord(substr($encoded, $index++)) - 63;

		      // AND the bits of the byte with 0x1f to get the original 5-bit `chunk.
		      // Then left shift the bits by the required amount, which increases
		      // by 5 bits each time.
		      // OR the value into $results, which sums up the individual 5-bit chunks
		      // into the original value.  Since the 5-bit chunks were reversed in
		      // order during encoding, reading them in this way ensures proper
		      // summation.
		      $result |= ($b & 0x1f) << $shift;
		      $shift += 5;
		    }
		    // Continue while the read byte is >= 0x20 since the last `chunk`
		    // was not OR'd with 0x20 during the conversion process. (Signals the end)
		    while ($b >= 0x20);

		    // Check if negative, and convert. (All negative values have the last bit
		    // set)
		    $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));

		    // Compute actual latitude since value is offset from previous value.
		    $lat += $dlat;

		    // The next values will correspond to the longitude for this point.
		    $shift = 0;
		    $result = 0;
		    do
		    {
		      $b = ord(substr($encoded, $index++)) - 63;
		      $result |= ($b & 0x1f) << $shift;
		      $shift += 5;
		    }
		    while ($b >= 0x20);

		    $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
		    $lng += $dlng;

		    // The actual latitude and longitude values were multiplied by
		    // 1e5 before encoding so that they could be converted to a 32-bit
		    // integer representation. (With a decimal accuracy of 5 places)
		    // Convert back to original values.
		    $points[] = array($lat * 1e-5, $lng * 1e-5);
		  }

		  return $points;
		}

		$decoded = [];
		// return ($content['routes'][0]['legs'][0]['steps'][0]['polyline']['points']);
		$path = ($content['routes'][0]['legs'][0]['steps']);
		$total = sizeof($path);
		
		foreach ($path as $i) {
			$encoded_points = $i['polyline']['points'];
			$decoded_points = decodePolylineToArray($encoded_points);
			$decoded = array_merge($decoded, $decoded_points);
		}
		return $content;

    }

    public function decodePolylineToArray($encoded)
		{
		  $length = strlen($encoded);
		  $index = 0;
		  $points = array();
		  $lat = 0;
		  $lng = 0;

		  while ($index < $length)
		  {
		    // Temporary variable to hold each ASCII byte.
		    $b = 0;

		    // The encoded polyline consists of a latitude value followed by a
		    // longitude value.  They should always come in pairs.  Read the
		    // latitude value first.
		    $shift = 0;
		    $result = 0;
		    do
		    {
		      // The `ord(substr($encoded, $index++))` statement returns the ASCII
		      //  code for the character at $index.  Subtract 63 to get the original
		      // value. (63 was added to ensure proper ASCII characters are displayed
		      // in the encoded polyline string, which is `human` readable)
		      $b = ord(substr($encoded, $index++)) - 63;

		      // AND the bits of the byte with 0x1f to get the original 5-bit `chunk.
		      // Then left shift the bits by the required amount, which increases
		      // by 5 bits each time.
		      // OR the value into $results, which sums up the individual 5-bit chunks
		      // into the original value.  Since the 5-bit chunks were reversed in
		      // order during encoding, reading them in this way ensures proper
		      // summation.
		      $result |= ($b & 0x1f) << $shift;
		      $shift += 5;
		    }
		    // Continue while the read byte is >= 0x20 since the last `chunk`
		    // was not OR'd with 0x20 during the conversion process. (Signals the end)
		    while ($b >= 0x20);

		    // Check if negative, and convert. (All negative values have the last bit
		    // set)
		    $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));

		    // Compute actual latitude since value is offset from previous value.
		    $lat += $dlat;

		    // The next values will correspond to the longitude for this point.
		    $shift = 0;
		    $result = 0;
		    do
		    {
		      $b = ord(substr($encoded, $index++)) - 63;
		      $result |= ($b & 0x1f) << $shift;
		      $shift += 5;
		    }
		    while ($b >= 0x20);

		    $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
		    $lng += $dlng;

		    // The actual latitude and longitude values were multiplied by
		    // 1e5 before encoding so that they could be converted to a 32-bit
		    // integer representation. (With a decimal accuracy of 5 places)
		    // Convert back to original values.
		    $points[] = array($lat * 1e-5, $lng * 1e-5);
		  }

		  return $points;
		}

	public function crimeAPI($lat, $lng) {

		$data = array('name' => 'ahmad', 'company' => 'mashape');

		$body = \Unirest\Request\Body::json($data);


		$request_url = "https://crimescore.p.mashape.com/crimescore?f=json&lat=".$lat."&lon=".$lng;

		$response = \Unirest\Request::post($request_url,
		  array(
		    "X-Mashape-Key" => "YhKQaheGe8mshRDldgoELyMZKIirp1RYoi5jsnxZO8PMqFppVU",
		    "Accept" => "application/json",

		  ), $body ); 

		return $response;

	}
	

}
