<?php
$path_coding_exons = 
	"/var/www/html/spliceman_beta/genome_data/coding_exons_with_introns_divided_75nts_flanking.txt";
$read_coding_exons = fopen($path_coding_exons, "r");
$path_output = 
	"/var/www/html/spliceman_beta/genome_data/reformatted_coding_exons.txt";
$write_output = fopen($path_output, "w");

while (! feof($read_coding_exons)) {
	$line = fgets($read_coding_exons);
	$line_array = explode("\t", $line);
	$output_array = $line_array;
	$output_array[0] = "chr".$line_array[2];
	$output_array[1] = $line_array[3];
	$output_array[2] = $line_array[4];
	$output_array[3] = $line_array[0];
	$output_array[4] = $line_array[1];
	$output = join($output_array, "\t");
	fwrite($write_output, $output);
}

fclose($read_coding_exons);
fclose($write_output);