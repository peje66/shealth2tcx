<?php
// if ($argc != 2) {
//     echo "${argv[0]} json\n";
//     exit(0);
// }
//
//
// $name = $argv[1];
function utcdate() {
    return gmdate("Y-m-d\Th:i:s\Z");
}

function element($dom, $parent, $element_name) {
    $element = $dom->createElement($element_name);
    $parent->appendChild($element);
    return $element;
}

function element_with_text_node($dom, $parent, $element_name, $element_text) {
    $element = $dom->createElement($element_name);
    $parent->appendChild($element);
    $node = $dom->createTextNode($element_text);
    $element->appendChild($node);
    return $element;
}

function element_with_attributes($dom, $parent, $element_name, $attributes) {
    $element = $dom->createElement($element_name);
    if(isset($parent))
        $parent->appendChild($element);
    foreach($attributes as $key => $value) {
        $attribute = $dom->createAttribute($key);
        $element->appendChild($attribute);
        $attribute_text = $dom->createTextNode($value);
        $attribute->appendChild($attribute_text);
    }
    return $element;
}

function blob2tcx($dirname, $mode, $meta)
{
    echo $meta['start_time'], " - ", $meta['end_time'];
    if (!array_key_exists('location_data', $meta) || !strlen($meta['location_data'])) {
        echo " no location data to decode\n";
        return;
    }
    $name = $dirname.'/blobs/'.$mode.'/'.$meta['location_data'];
    $string = file_get_contents($name);

    if (!strlen($string))
        return;

    if (ord($string[0]) == 0x1f && ord($string[1]) == 0x8b) {
        $json = json_decode(gzdecode($string), TRUE);
    } else
        $json = json_decode($string, TRUE);

    $dom_tcx = new DOMDocument('1.0', 'UTF-8');
    $dom_tcx->standalone = false;
    $dom_tcx->formatOutput = true;

    //root node
    $tcx = element_with_attributes($dom_tcx, NULL, 'TrainingCenterDatabase', array(
        'xmlns' => 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2',
        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        'xsi:schemaLocation' => 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2 http://www.garmin.com/xmlschemas/TrainingCenterDatabasev2.xsd'
    ));
    $tcx = $dom_tcx->appendChild($tcx);


    $tcx_activities = element($dom_tcx, $tcx, 'Activities');
//     $tcx_activities = $tcx->appendChild($tcx_activities);

    $excercise = 'Other';
    switch($meta['exercise_type']) {
    case 1002: $excercise = 'Running'; break;
    case 11007: $excercise = 'Biking'; break;
    }
    $tcx_activity = element_with_attributes($dom_tcx, $tcx_activities, 'Activity', array('Sport' => $excercise));
    element_with_text_node($dom_tcx, $tcx_activity, 'Id', gmdate("Y-m-d\Th:i:s\Z", $json[0]['start_time']/1000));
    $lap = element_with_attributes($dom_tcx, $tcx_activity, 'Lap', array('StartTime' => gmdate("Y-m-d\Th:i:s\Z", $json[0]['start_time']/1000)));

    element_with_text_node($dom_tcx, $lap, 'TotalTimeSeconds', round((end($json)['start_time'] - $json[0]['start_time'])/1000));
    element_with_text_node($dom_tcx, $lap, 'DistanceMeters', $meta['distance']);
    element_with_text_node($dom_tcx, $lap, 'MaximumSpeed', '0');
    element_with_text_node($dom_tcx, $lap, 'Calories', '0');
    $lap_average_heart_rate_bpm = element_with_attributes($dom_tcx, $lap, 'AverageHeartRateBpm', array('xsi:type'=>'HeartRateInBeatsPerMinute_t'));
        element_with_text_node($dom_tcx, $lap_average_heart_rate_bpm, 'Value', strlen($meta['mean_heart_rate']) ? $meta['mean_heart_rate'] : 100);
    $lap_maximum_heart_rate_bpm = element_with_attributes($dom_tcx, $lap, 'MaximumHeartRateBpm', array('xsi:type'=>'HeartRateInBeatsPerMinute_t'));
        element_with_text_node($dom_tcx, $lap_maximum_heart_rate_bpm, 'Value', strlen($meta['max_heart_rate']) ? $meta['mean_heart_rate'] : 200);
    element_with_text_node($dom_tcx, $lap, 'Intensity', 'Active');
    element_with_text_node($dom_tcx, $lap, 'TriggerMethod', 'Manual');

    $track = element($dom_tcx, $lap, 'Track');

    foreach ($json as $point => $data) {
        $trackpoint = element($dom_tcx, $track, 'Trackpoint');
            element_with_text_node($dom_tcx, $trackpoint, 'Time', gmdate("Y-m-d\Th:i:s\Z", $data['start_time']/1000));
            $trackpoint_pos = element($dom_tcx, $trackpoint, 'Position');
                element_with_text_node($dom_tcx, $trackpoint_pos, 'LatitudeDegrees', $data['latitude']);
                element_with_text_node($dom_tcx, $trackpoint_pos, 'LongitudeDegrees', $data['longitude']);
                if(array_key_exists('altitude', $data))
                    element_with_text_node($dom_tcx, $trackpoint_pos, 'AltitudeMeters', $data['altitude']);
    }

//     header("Content-Type: text/xml");
    file_put_contents(basename($meta['location_data']).'.tcx', $dom_tcx->saveXML());
    echo " created: ", basename($meta['location_data']).'.tcx', "\n";
}
?>


