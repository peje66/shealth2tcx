<?php

require_once('blob2tcx.php');

if ($argc != 2) {
    echo "${argv[0]} json\n";
    exit(0);
}
/**
* @link http://gist.github.com/385876
*/
function csv_to_array($filename='', $delimiter=',')
{
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;

    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        $comment = fgets($handle);
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
        {
            if(!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }
    return array($data, trim($comment));
}

$name = $argv[1];

$dirname = dirname(realpath($name));

    $csv_data = csv_to_array($name);
    $csv = $csv_data[0];
    $shealth_mode = $csv_data[1];

    array_shift($csv);
    foreach($csv as $row) {
        blob2tcx($dirname, $shealth_mode, $row);
    }
//     print_r($csv);
?>
