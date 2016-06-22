<?php

function readData($filename){
	$fb = fopen($filename, "r") or die("unable to open file");
	$output = array();
	while(($nextLine = fgets($fb))){
		$splitLine = split('\t', $nextLine);
		$output[] = array(
			"chm" => $splitLine[0],
			"mut_pos" => $splitLine[1],
			"feat_chm" => $splitLine[2],
			"feat_start" => $splitLine[3],
			"feat_end" => $splitLine[4],
			"gene" => $splitLine[5],
			"strand" => $splitLine[6],
			"feature" => $splitLine[7],
			"L1_Dist" => $splitLine[8],
			"rbp1" => $splitLine[9],
			"rbp2" => $splitLine[10],					
			"rbp3" => $splitLine[11],
			"rbp4" => $splitLine[12],
			"rbp5" => $splitLine[13],
		);
	}
	fclose($fb);
	return $output;
}

?>
