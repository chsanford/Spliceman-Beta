<?php

namespace App\Jobs;

use App\Jobs\Job;
use File;
use DB;
use Redirect;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessNewInput extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $input_id;
    protected $working_directory_path;
    protected $final_directory_path;

    protected $path_start;
    protected $path_bedtools_new;
    protected $path_bedtools_final;
    protected $path_errors;
    protected $path_valid;
    protected $path_ss_dist;
    protected $path_new_input;
    protected $path_RBPs_new;
    protected $path_RBPs_final;
    protected $path_spliceman;
    protected $path_L1_distance_final;
    protected $path_ESEseq_final;
    protected $path_final;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($input_id, $path_start) {
        $destination_path = public_path().'/uploads';

        $working_directory_path = $destination_path.'/'.$input_id."_working";
        mkdir($working_directory_path);
        $final_directory_path = $destination_path.'/'.$input_id."_final";

        $this->working_directory_path = $working_directory_path;
        $this->final_directory_path = $final_directory_path;
        $this->input_id = $input_id;
        $this->record_progress("Job not started");

        $this->path_start = $path_start;
        $this->path_bedtools_new = $working_directory_path."/bedtools_new";
        $this->path_bedtools_final = $working_directory_path."/bedtools_final";
        $this->path_errors = $final_directory_path."/errors";
        $this->path_valid = $working_directory_path."/valid";
        $this->path_ss_dist = $working_directory_path."/ss_dist";
        $this->path_new_input = $working_directory_path."/new_input";
        $this->path_RBPs_new = $working_directory_path."/RBPs_new";
        $this->path_RBPs_final = $working_directory_path."/RBPs_final";
        $this->path_spliceman = $working_directory_path."/spliceman";
        $this->path_L1_distance_final = 
            $working_directory_path."/L1_distance_final";
        $this->path_ESEseq_final = $working_directory_path."/ESEseq_final";
        $this->path_final = $working_directory_path."/final";
    }

    /**
     * Processes the input through the pipeline
     *
     * @return Response
     */
    public function handle() {
        $this->check_REF(); // Step 1
        $this->bedtools_analysis(); // Step 2
        $this->valid_intersection(); // Step 3
        $this->check_DB(); // Step 4 
        if (filesize($this->path_new_input) != 0) {
            $this->splice_site_distance(); // Step 5
            $this->setup_files(); // Step 6
            $this->L1_distance_processing(); // Step 7
            $this->ESEseq_processing(); // Step 8
            $this->RBP_processing(); // Step 9
            $this->results_processing(); // Step 10
            $this->database_entry(); // Step 11
        }
        
        //$this->remove_directory($this->working_directory_path);

        $this->record_progress("Job complete!");
    }

    /**
     * Step 1
     * Checks that the mutations are valid hg19 coordinates and creates a file
     * with all valid coordinates ready to be used in a Bedtools intersect.
     * All invalid mutations are placed in a file for errors.
     */
    private function check_REF() {
        $this->record_progress("Step 1: Checking REF");

        $path_start = $this->path_start;
        $path_bedtools_new = $this->path_bedtools_new;
        $path_errors = $this->path_errors;

        exec("perl\
            /var/www/html/spliceman_beta/scripts/check_ref.pl\
            /var/www/html/spliceman_beta/genome_data/hg19.fa\
            '$path_start'\
            '$path_bedtools_new'\
            '$path_errors'", 
            $output, 
            $return);

        if ($return) {
            $this->pipeline_error(
                "Error in pipeline, please contact administrator
                and provide step 1");
        }
        if (count($output) > 0) {
            $this->pipeline_error($output);
        }
    }

    /**
     * Step 2
     * Processes a file through Bedtools analysis. This creates an final
     * Bedtools file which contains processed data.
     */
    private function bedtools_analysis() {
        $this->record_progress("Step 2: Bedtools analysis");

        $path_bedtools_new = $this->path_bedtools_new;
        $path_bedtools_final = $this->path_bedtools_final;

        exec("bedtools intersect -wao\
            -a '$path_bedtools_new'\
            -b /var/www/html/spliceman_beta/genome_data/reformatted_coding_exons.txt\
            > '$path_bedtools_final'",
            $bedtools_array,
            $return);
            
        if ($return) {
            if (count($bedtools_array) - 1 == 0) {
                $this->pipeline_error(
                    "Your file did not have any mutations that we were able 
                    to process. Is your file correctly formatted with one 
                    line per mutation?");
            }
            $this->pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 2");
        }
    }

    /**
     * Step 3
     * Iterates through all intersections, checking for which ones are valid.
     * Valid intersections have strands which are not on a terminal exon 
     * transcript. These are added to a "valid" file, which will be further
     * processed. Intersections on terminal exons are added to the errors
     * database. Those without intersections are added to the errors file,
     * but not the database.
     */
    private function valid_intersection() {
        $this->record_progress(
            "Step 3: Determining whether intersections are valid");

        $path_bedtools_final = $this->path_bedtools_final;
        $path_valid = $this->path_valid;
        $path_errors = $this->path_errors;

        $read_bedtools_final = fopen($path_bedtools_final, "r") or
            die ("Unable to open file!");
        $write_valid = fopen($path_valid, "w") or
            die ("Unable to open file!");
        $write_errors = fopen($path_errors, "a") or
            die ("Unable to open file!");

        while (! feof($read_bedtools_final)) {
            $line = fgets($read_bedtools_final);
            $line_array = explode("\t", $line);

            if (count($line_array) > 22) {
                if (!preg_match("/^chr/", $line_array[8])) {
                    $error = $line_array[0]."\t".$line_array[5]."\t".
                        $line_array[4]."\t".$line_array[6]."\t".$line_array[7]."\t".
                        $line_array[11]."\t".$line_array[12]."\t".
                        "ERROR: Mutation was not found in the Bedtools intersection.\n";
                    fwrite($write_errors, $error);
                } elseif ($line_array[22] == "exon_terminal_3" or
                        $line_array[22] == "exon_terminal_5") {
                    $message = 
                        "ERROR: Mutation on this transcript is on an exon terminal.";
                    $chr_loc_wild_mut_transcript_exon = 
                        $line_array[0]."_".$line_array[5]."_".
                        $line_array[6]."_".$line_array[7]."_".
                        $line_array[11]."_".$line_array[12];
                    $this->insert_errors_DB(
                        $chr_loc_wild_mut_transcript_exon, $message);
                } elseif ($line_array[24] == 
                        "NOTE:Variant too far from splice junction") {
                    $message = 
                        "ERROR: Variant too far from splice junction.";
                    $chr_loc_wild_mut_transcript_exon = 
                        $line_array[0]."_".$line_array[5]."_".
                        $line_array[6]."_".$line_array[7]."_".
                        $line_array[11]."_".$line_array[12];
                    $this->insert_errors_DB(
                        $chr_loc_wild_mut_transcript_exon, $message);
                } else {
                    fwrite($write_valid, $line);
                }
            }
        }

        fclose($read_bedtools_final);
        fclose($write_valid);
        fclose($write_errors);
    }

    /**
     * Step 4
     * Checks the database to see which mutations are already processed. All
     * others are added to another file for further processing.
     */
    private function check_DB() {
        $this->record_progress(
            "Step 4: Identifying which mutations are already in database");

        $path_valid = $this->path_valid;
        $path_new_input = $this->path_new_input;

        $table = "NewSpliceman";

        $this->record_progress("1");

        $read_valid = fopen($path_valid, "r") or 
            die ("Unable to open file!");
        $write_new_input = fopen($path_new_input, "a") or 
            die ("Unable to open file!");

        $this->record_progress("2");

        $valid_mutations = false;

        while (! feof($read_valid)) {
            $this->record_progress("3");
            $mutation = fgets($read_valid);
            if (ctype_space($mutation)) {
                break;
            }
            $mutation_data = explode("\t", $mutation);
            $this->record_progress("4");
            if ($this->is_valid($mutation_data)) {
                $this->record_progress("5");
                $db_key = 
                    $mutation_data[0]."_".$mutation_data[5]."_".
                        $mutation_data[6]."_".$mutation_data[7]."_".
                        $mutation_data[11]."_".$mutation_data[12];
                // Removes newline characters, if they are present
                $db_key = str_replace("\n", "", $db_key);
                $this->record_progress("6");
                $result = DB::table($table)->
                    where('chr_loc_wild_mut_transcript_exon', $db_key)->
                    first();
                $valid_mutations = true;
                $this->record_progress("8");
                if (count($result) == 0) {
                    fwrite($write_new_input, $mutation);
                }
                $this->record_progress("9");
            }   
        } 

        $this->record_progress("10");
        fclose($read_valid);
        fclose($write_new_input);

        $this->record_progress("11");
    }

    /**
     * Step 5
     * Computes the distance to the splice site for each valid mutation, and
     * determines whether the splice site is 3' or 5'. 
     */
    private function splice_site_distance() {
        $this->record_progress(
            "Step 5: Computing splice site distances");

        $path_new_input = $this->path_new_input;
        $path_intronic = ($this->working_directory_path)."/intronic";
        $path_intronic_borders = 
            ($this->working_directory_path)."/intronic_borders";
        $path_ss_dist = $this->path_ss_dist;

        $read_new_input = fopen($path_new_input, "r");
        $write_intronic = fopen($path_intronic, "w");

        while (! feof($read_new_input)) {
            $line_new_input = fgets($read_new_input);
            $line_new_input = str_replace("\n", "", $line_new_input);
            $line_array = explode("\t", $line_new_input);
            $distance = 0;
            $splice_site = "_";
            if (count($line_array) > 24) {
                $chromosome = $line_array[0];
                $mutant_loc = intval($line_array[5]);
                $start_loc = intval($line_array[9]);
                $end_loc = intval($line_array[10]);
                $strand = $line_array[14];
                $variant_type = $line_array[23];
                $left_dist = $mutant_loc - $start_loc + 1;
                $right_dist = $end_loc - $start_loc + 1;
                if ($variant_type == "intronic_variant") {
                    // For intronic variants, records positions to a file to
                    // be processed to find the corresponding exons.
                    $left_exon_end_loc = $start_loc - 1;
                    $right_exon_start_loc = $end_loc + 1;
                    $left_line = $chromosome."\t".($left_exon_end_loc - 1).
                        "\t".$left_exon_end_loc."\t".$mutant_loc."\n";
                    fwrite($write_intronic, $left_line);
                    $right_line = $chromosome."\t".$right_exon_start_loc.
                        "\t".($right_exon_start_loc + 1)."\t".$mutant_loc."\n";
                    fwrite($write_intronic, $right_line);

                }
            }
        }

        fclose($read_new_input);
        fclose($write_intronic);

        exec("bedtools intersect -wao\
            -a '$path_intronic'\
            -b /var/www/html/spliceman_beta/genome_data/reformatted_coding_exons.txt\
            > '$path_intronic_borders'",
            $bedtools_array,
            $return);
            
        if ($return) {
            if (count($bedtools_array) - 1 == 0) {
                $this->pipeline_error(
                    "Your file did not have any mutations that we were able 
                    to process. Is your file correctly formatted with one 
                    line per mutation?");
            }
            $this->pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 5");
        }

        $read_new_input = fopen($path_new_input, "r");
        $read_intronic_borders = fopen($path_intronic_borders, "r");
        $write_ss_dist = fopen($path_ss_dist, "w");

        if (feof($read_intronic_borders)) {
            $line_intronic_borders = "";
        } else {
            $line_intronic_borders = fgets($read_intronic_borders);
        }

        while (! feof($read_new_input)) {
            $line_new_input = fgets($read_new_input);
            $line_new_input = str_replace("\n", "", $line_new_input);
            $array_new_input = explode("\t", $line_new_input);
            if (count($array_new_input) > 24) {
                $chromosome = $array_new_input[0];
                $mutant_loc = intval($array_new_input[5]);
                $start_loc = intval($array_new_input[9]);
                $end_loc = intval($array_new_input[10]);
                $strand = $array_new_input[14];
                $variant_type = $array_new_input[23];
                $left_dist = $mutant_loc - $start_loc + 1;
                $right_dist = $end_loc - $start_loc + 1;
                if ($variant_type == "exonic_variant") {
                    // For exonic variants, calculates the distance and writes
                    // it to an intermediate file.
                    if ($left_dist <= $right_dist) {
                        $distance = $left_dist;
                        $splice_site = ($strand == "+" ? "5'" : "3'");
                    } else {
                        $distance = $right_dist;
                        $splice_site = ($strand == "+" ? "3'" : "5'");
                    }
                } else {
                    // If an intron, iterates through the border data to find
                    // an exon that borders it. Then, computes the distance
                    // between
                    $found = false;
                    while ((! $found) and (! feof($read_intronic_borders))) {
                        $array_intronic_borders = 
                            explode("\t", $line_intronic_borders);
                        if ($start_loc - 1 == $array_intronic_borders[2]) {
                            $distance = $left_dist;
                            $splice_site = ($strand == "+" ? "5'" : "3'");
                            $found = true;
                        } elseif ($end_loc + 1 == $array_intronic_borders[1]) {
                            $distance = $right_dist;
                            $splice_site = ($strand == "+" ? "3'" : "5'");
                            $found = false;
                        } else {
                            if (feof($read_intronic_borders)) {
                                $line_intronic_borders = "";
                            } else {
                                $line_intronic_borders = fgets($read_intronic_borders);
                            }
                        }
                    }
                }
                array_push($array_new_input, $distance);
                array_push($array_new_input, $splice_site);
                $new_line = implode("\t", $array_new_input)."\n";
                fwrite($write_ss_dist, $new_line);
            }
        }
        fclose($read_new_input);
        fclose($read_intronic_borders);
        fclose($write_ss_dist);
    }

    /**
     * Step 6
     * From the "new_input" file, creates files for "RBP_new" and
     * "spliceman_new", which are to be processed in further pipelines
     * and later be recombined.
     */
    private function setup_files() {
        $this->record_progress(
            "Step 6: Preparing files for further analysis");

        $path_new_input = $this->path_new_input;
        $path_spliceman = $this->path_spliceman;
        $path_RBPs_new = $this->path_RBPs_new;
        $path_errors = $this->path_errors;

        exec("perl\
            /var/www/html/spliceman_beta/scripts/setup_spliceman_RBP_files.pl\
            /var/www/html/spliceman_beta/genome_data/hg19.fa\
            '$path_new_input'\
            '$path_spliceman'\
            '$path_RBPs_new'\
            '$path_errors'", 
            $output, 
            $return);

        if ($return) {
            $this->pipeline_error(
                "Error in pipeline, please contact administrator
                and provide step 6");
        }
        if (count($output) > 0) {
            $this->pipeline_error($output);
        }
    }

    /**
     * Step 7
     * Takes the "spliceman" file and processes it to analyze L1 distance.
     */
    private function L1_distance_processing() {
        $this->record_progress("Step 7: L1-distance analysis");

        $path_spliceman = $this->path_spliceman;
        $path_L1_distance_final = $this->path_L1_distance_final;
        
        exec("perl\
            /var/www/html/spliceman_beta/scripts/spliceman_2_processing_variants.pl\
            /var/www/html/spliceman_beta/genome_data/hg19_L1_distance_only_hexamers_dif_by_one.fa\
            '$path_spliceman'\
             > '$path_L1_distance_final'",
             $L1_distance_array,
             $return);

        if ($return) {
            $this->pipeline_error(
                "Error in pipeline, please contact administrator and
                provide step 7");
        }
    }

    /**
     * Step 8
     * Takes the "spliceman" file and processes it to analyze ESEseq data.
     */
    private function ESEseq_processing() {
        $this->record_progress("Step 8: ESEseq analysis");

        $path_spliceman = $this->path_spliceman;
        $path_ESEseq_final = $this->path_ESEseq_final;

        exec("perl\
            /var/www/html/spliceman_beta/scripts/spliceman_2_ESEseq.pl\
            /var/www/html/spliceman_beta/genome_data/ESEseq_table.csv\
            '$path_spliceman'\
            > '$path_ESEseq_final'", 
            $ESEseq_array, 
            $return);

        if ($return) {
            $this->pipeline_error(
                "Error in pipeline, please contact administrator and
                provide step 8");
        }
    }

    /**
     * Step 9
     * Processes a file to find the nearby RBP proteins and their corresponding
     * motifs.
     */
    private function RBP_processing() {
        $this->record_progress("Step 9: RBP analysis");

        $path_RBPs_new = $this->path_RBPs_new;
        $path_RBPs_final = $this->path_RBPs_final;

        exec("perl\
            /var/www/html/spliceman_beta/scripts/spliceman_rbp_scanner.pl\
            /var/www/html/spliceman_beta/genome_data/rbp_binding_percentiles.fa\
            /var/www/html/spliceman_beta/genome_data/RBP_motif_lengths.fa\
            /var/www/html/spliceman_beta/genome_data/matrix_gene_human_v2.txt\
            '$path_RBPs_new'\
            '$path_RBPs_final'",
            $RBP_output,
            $return);

        if ($return) {
            $this->pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 9");
        }  
    }

    /**
     * Step 10
     * Combines results from previous elements of the pipeline to create a
     * well-formatted output file.
     */
    private function results_processing() {
        $this->record_progress("Step 10: Processing results");

        $path_ss_dist = $this->path_ss_dist;
        $path_L1_distance_final = $this->path_L1_distance_final;
        $path_ESEseq_final = $this->path_ESEseq_final;
        $path_RBPs_final = $this->path_RBPs_final;
        $path_final = $this->path_final;

        $read_ss_dist = fopen($path_ss_dist, "r");
        $read_L1_distance_final = fopen($path_L1_distance_final, "r");
        $read_ESEseq_final = fopen($path_ESEseq_final, "r");
        $read_RBPs_final = fopen($path_RBPs_final, "r");
        $write_final = fopen($path_final, "w");

        while ((! feof($read_ss_dist)) and 
                (! feof($read_L1_distance_final)) and
                (! feof($read_ESEseq_final)) and 
                (! feof($read_RBPs_final))) {
            $line_ss_dist = fgets($read_ss_dist);
            $line_L1_distance = fgets($read_L1_distance_final);
            $line_ESEseq = fgets($read_ESEseq_final);
            $line_RBPs = fgets($read_RBPs_final);


            //$this->record_progress($line_ss_dist);
            $array_ss_dist = explode("\t", $line_ss_dist);
            $array_L1_distance = explode("\t", $line_L1_distance);
            $array_ESEseq = explode("\t", $line_ESEseq);
            $array_RBPs = explode("\t", $line_RBPs);

            if ((count($array_ss_dist) > 27) and
                    (count($array_L1_distance) > 0) and
                    (count($array_ESEseq) > 0) and
                    (count($array_RBPs) > 14)) {
                $L1_data = explode(":", $array_L1_distance[0]);
                if (count($L1_data) > 2) {
                    $array_final = array(
                        $array_ss_dist[0], // chromosome
                        $array_ss_dist[5], // location
                        $array_ss_dist[6], // wild base
                        $array_ss_dist[7], // mutant base
                        $array_ss_dist[11], // transcript ID
                        $array_ss_dist[12], // exon number
                        $array_ss_dist[9], // start loc
                        $array_ss_dist[10], // end loc
                        $array_ss_dist[19], // gene name
                        $array_ss_dist[21], // gene ID
                        $array_ss_dist[14], // strand
                        $array_ss_dist[22], // feature type
                        $array_ss_dist[23], // variant type
                        $L1_data[2], // L1 distance percentile
                        $array_ESEseq[0], // ESEseq
                        $array_RBPs[5], // RBPs
                        $array_RBPs[6],
                        $array_RBPs[7],
                        $array_RBPs[8],
                        $array_RBPs[9],
                        $array_RBPs[10], // motifs
                        $array_RBPs[11],
                        $array_RBPs[12],
                        $array_RBPs[13],
                        $array_RBPs[14],
                        $array_ss_dist[26], // splice site distance
                        $array_ss_dist[27] // splice site
                    );
                    $line_final = implode("\t", $array_final);
                    $line_final = str_replace("\n", "", $line_final)."\n";
                    fwrite($write_final, $line_final);
                }
            }
        }
        fclose($read_ss_dist);
        fclose($read_L1_distance_final);
        fclose($read_ESEseq_final);
        fclose($read_RBPs_final);
        fclose($write_final);
    }

    /**
     * Step 11
     * For each line in the final file, the elements are added to the database.
     */
    private function database_entry() {
        $this->record_progress("Step 11: Entering results in the database");

        $path_final = $this->path_final;
        $path_errors = $this->path_errors;

        $read_final = fopen($path_final, "r");
        $write_errors = fopen($path_errors, "a");

        $table = "NewSpliceman";
        $errors_table = "NewErrors";

        while (! feof($read_final)) {
            $database_entry = fgets($read_final);
            $entry_array = explode("\t", $database_entry);
            if (ctype_space($database_entry)) {
                break;
            }
            if (count($entry_array) > 26) {
                $chr_loc_wild_mut_transcript_exon = 
                    $entry_array[0]."_".$entry_array[1]."_".$entry_array[2]."_".
                    $entry_array[3]."_".$entry_array[4]."_".$entry_array[5];
                $start_loc = $entry_array[6];
                $end_loc = $entry_array[7];
                $gene_name = $entry_array[8];
                $gene_id = $entry_array[9];
                $strand = $entry_array[10];
                $feature_type = $entry_array[11];
                $variant_type = $entry_array[12];
                $L1_percentile = $entry_array[13];
                $RBPs = $entry_array[15].",".$entry_array[16].",".
                    $entry_array[17].",".$entry_array[18].",".$entry_array[19];
                $motifs = $entry_array[20].",".$entry_array[21].",".$entry_array[22].
                    ",".$entry_array[23].",".$entry_array[24];
                $motifs = str_replace("\n", "", $motifs);
                $motifs = str_replace("\r", "", $motifs);
                $ss_distance = $entry_array[25];
                $splice_site = $entry_array[26];
                if ($entry_array[14] == "n/a") {
                    $ESEseq = 0;
                    //$enhancer_repressor = "-";
                } else {
                    $ESEseq = $entry_array[14];
                    //$enhancer_repressor = $entry_array[17];
                }
                try {
                    DB::table($table)->
                        insert([
                            'chr_loc_wild_mut_transcript_exon' => 
                                $chr_loc_wild_mut_transcript_exon,
                            'start_loc' => $start_loc,
                            'end_loc' => $end_loc,
                            'strand' => $strand,
                            'gene_name' => $gene_name,
                            'gene_id' => $gene_id,
                            'feature_type' => $feature_type,
                            'variant_type' => $variant_type,
                            'L1_percentile' => $L1_percentile,
                            'RBPs' => $RBPs,
                            'motifs' => $motifs,
                            'ESEseq' => $ESEseq,
                            'error' => false,
                            'ss_distance' => $ss_distance,
                            'splice_site' => $splice_site
                        ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $error_string = $chr_loc_wild_mut_transcript_exon."\t".
                        "ERROR: Issue inserting to the database.\n";
                    fwrite($write_errors, $error_string);
                }
            }
        }

        fclose($read_final);
        fclose($write_errors);

        $read_errors = fopen($path_errors, "r");
        while (! feof($read_errors)) {
            $database_entry = fgets($read_errors);
            $entry_array = explode("\t", $database_entry);
            if (ctype_space($database_entry)) {
                break;
            }
            if (count($entry_array) > 1) {
                $chr_loc_wild_mut = $entry_array[0];
                $message = $entry_array[1];
                try {
                    DB::table($table)->
                        insert([
                            'chr_loc_wild_mut_transcript_exon' => 
                                $chr_loc_wild_mut_transcript_exon,
                            'error' => true
                        ]);
                } catch (\Illuminate\Database\QueryException $e) {}
                try {
                    DB::table($errors_table)->
                        insert([
                            'chr_loc_wild_mut_transcript_exon' => 
                                $chr_loc_wild_mut_transcript_exon,
                            'message' => $message
                        ]);
                } catch (\Illuminate\Database\QueryException $e) {}
            }
        }

        fclose($read_errors);
    }

    /**
     * Inserts an element into the NewErrors database. Intended for errors
     * which are likely to be called again to avoid excessive computation.
     */
    private function insert_errors_DB(
        $chr_loc_wild_mut_transcript_exon, $message) {
        $table = "NewSpliceman";
        $errors_table = "NewErrors";
        try {
            DB::table($table)->
                insert([
                    'chr_loc_wild_mut_transcript_exon' => 
                        $chr_loc_wild_mut_transcript_exon,
                    'error' => true
                ]);
        } catch (\Illuminate\Database\QueryException $e) {}
        try {
            DB::table($errors_table)->
                insert([
                    'chr_loc_wild_mut_transcript_exon' => 
                        $chr_loc_wild_mut_transcript_exon,
                    'message' => $message
                ]);
        } catch (\Illuminate\Database\QueryException $e) {}
    }

    /**
     * Returns a pipeline error to the upload page
     *
     * @return Response
     */
    private function pipeline_error($message) {
        $this->record_progress($message);
        remove_directory($this->working_directory_path);
    }

    /**
     * Removes a directory and all files contained in it
     */
    private function remove_directory($path) {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? remove_directory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }

    /**
     * Writes to a text file to record progress in the pipeline.
     */
    private function record_progress($message) {
        $path_progress = $this->final_directory_path."/progress";
        $write_progress = fopen($path_progress, "w");
        fwrite($write_progress, $message);
        fclose($write_progress);
    }

    /**
     * Verifies that certain fields in input data meet certain conditions.
     */
    private function is_valid($mutation_data) {
        if (count($mutation_data) >= 5) {
            $chromosome_format = preg_match("/^chr/", $mutation_data[0]);
            $wild_format = preg_match("/^[A,C,G,T]$/", $mutation_data[6]);
            $mut_format = preg_match("/^[A,C,G,T]$/", $mutation_data[7]);
            return $chromosome_format && $wild_format && $mut_format;
        } else {
            return false;
        }
    }
}
