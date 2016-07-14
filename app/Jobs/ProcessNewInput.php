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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($input_id) {
        $destination_path = public_path().'/uploads';

        $working_directory_path = $destination_path.'/'.$input_id."_working";
        $final_directory_path = $destination_path.'/'.$input_id."_final";

        mkdir($working_directory_path);

        $this->working_directory_path = $working_directory_path;
        $this->final_directory_path = $final_directory_path;

        $this->input_id = $input_id;
        $this->record_progress("Job not started");
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $this->record_progress("Step 1: Processing input");
        $input_id = $this->input_id;

        $final_directory_path = $this->final_directory_path;
        $working_directory_path = $this->working_directory_path;

        $path_input = $final_directory_path."/input";
        $path_errors = $final_directory_path."/errors";
        $path_processed = $final_directory_path."/processed";

        $path_database = $working_directory_path."/database";
        $path_spliceman = $working_directory_path."/spliceman";
        $path_RBPs = $working_directory_path."/RBPs";
        $path_bedtools_new = $working_directory_path."/bedtools_new";
        $path_bedtools_final = $working_directory_path."/bedtools_final";
        $path_L1_distance_final = $working_directory_path."/L1_distance_final";
        $path_ESEseq_final = $working_directory_path."/ESEseq_final";
        $path_RBPs_final = $working_directory_path."/RBPs_final";
        $path_final_processing_temp1 = $working_directory_path."/final_processing_temp1";
        $path_final_processing_temp2 = $working_directory_path."/final_processing_temp2";
        $path_final_processing_final = $working_directory_path."/final_processing_final";
        $path_final = $working_directory_path."/final";

        file_put_contents($path_final_processing_final, "AAAAAAAAAYYYYY");


        exec("perl\
            /var/www/html/spliceman_beta/scripts/vcf_fasta_v2.pl\
            /var/www/html/spliceman_beta/genome_data/hg19.fa '$path_input'\
            '$path_spliceman'\
            '$path_RBPs'\
            '$path_bedtools_new'\
            '$path_errors'\
            '$path_processed'", 
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

        $this->record_progress("Step 2: Bedtools analysis");

        exec("bedtools intersect -wao\
            -a '$path_bedtools_new'\
            -b /var/www/html/spliceman_beta/genome_data/RefSeq_exon_intron_sum_corr.txt\
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

        $this->record_progress("Step 3: L1-distance analysis");
        
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
                provide step 3");
        }

        $this->record_progress("Step 4: ESEseq analysis");

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
                provide step 4");
        }  

        $this->record_progress("Step 5: RBP analysis");

        exec("perl\
            /var/www/html/spliceman_beta/scripts/spliceman_rbp_scanner.pl\
            /var/www/html/spliceman_beta/genome_data/rbp_binding_percentiles.fa\
            /var/www/html/spliceman_beta/genome_data/RBP_motif_lengths.fa\
            /var/www/html/spliceman_beta/genome_data/matrix_gene_human_v2.txt\
            '$path_RBPs'\
            '$path_RBPs_final'",
            $RBP_output,
            $return);

        if ($return) {
            $this->pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 5");
        }

        $this->record_progress("Step 6: Processing output");

        exec(
            "awk -F\"\t\"\
            'FNR == NR\
            {a[$2\"\t\"$3\"\t\"$4\"\t\"$5] = $1; next}\
            $4\"\t\"$5\"\t\"$6\"\t\"$7 in a\
            {print $0\"\t\"a[$4\"\t\"$5\"\t\"$6\"\t\"$7]}'\
            '$path_L1_distance_final'\
            '$path_bedtools_final'\
            > '$path_final_processing_temp1'", 
            $final_result, 
            $return);

        exec(
            "awk -F\"\t\"\
            'FNR == NR\
            {a[$3\"\t\"$4\"\t\"$5\"\t\"$6] = $1\"\t\"$2; next}\
            $4\"\t\"$5\"\t\"$6\"\t\"$7 in a\
            {print $0\"\t\"a[$4\"\t\"$5\"\t\"$6\"\t\"$7]}'\
            '$path_ESEseq_final'\
            '$path_final_processing_temp1'\
            > '$path_final_processing_temp2'", 
            $final_result, 
            $return); 

        exec(
            "awk -F\"\t\"\
            'FNR == NR\
            {a[FNR] = $6\"\t\"$7\"\t\"$8\"\t\"$9\"\t\"$10\"\t\"$11\"\t\"$12\"\t\"$13\"\t\"$14\"\t\"$15; next}\
            FNR in a\
            {print $0\"\t\"a[FNR]}'\
            '$path_RBPs_final'\
            '$path_final_processing_temp2'\
            > '$path_final_processing_final'", 
            $final_result, 
            $return); 


        if ($return) {
            $this->pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 6");
        }

        exec(
            "bash /var/www/html/spliceman_beta/scripts/process_final_2.sh\
            '$path_final_processing_final'",
            $final_final_result_pre, 
            $return);
        if (count($final_final_result_pre) > 1){
            $this->pipeline_error($final_final_result_pre);
        }

        exec(
            "bash /var/www/html/spliceman_beta/scripts/process_final.sh\
            '$path_final_processing_final'\
            > '$path_final'", 
            $final_final_result, 
            $return);
        if ($return){
            return $this->pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 6");
        }

        $this->record_progress("Step 7: Entering results in the database");

        $database_entries = explode("\n", file_get_contents($path_final));

        $table = "Spliceman";

        $read_final = fopen($path_final, "r");
        $write_errors = fopen($path_errors, "a");

        //foreach ($database_entries as $database_entry) {
        while (! feof($read_final)) {
            $database_entry = fgets($read_final);
            $entry_array = explode("\t", $database_entry);
            if (ctype_space($database_entry)) {
                break;
            }

            if (count($entry_array) > 21) {
                fwrite($write_errors, join($entry_array, ",")."\n");
                $chr_loc_wild_mut = $entry_array[0]."_".$entry_array[1].
                    "_".$entry_array[3]."_".$entry_array[4];
                $start_loc = $entry_array[5];
                $end_loc = $entry_array[6];
                $gene = $entry_array[7];
                $pos_neg = $entry_array[8];
                $strand_type = $entry_array[9];
                $L1_percentile = $entry_array[10];
                $RBPs = $entry_array[13].",".$entry_array[14].",".$entry_array[15].
                    ",".$entry_array[16].",".$entry_array[17];
                $motifs = $entry_array[18].",".$entry_array[19].",".$entry_array[20].
                    ",".$entry_array[21].",".$entry_array[22];
                if ($entry_array[12] == "n/a") {
                    $ESEseq = 0;
                    $enhancer_repressor = "-";
                } else {
                    $ESEseq = $entry_array[11];
                    $enhancer_repressor = $entry_array[12];
                }
                try {
                    DB::table($table)->insert([
                        'chr_loc_wild_mut' => $chr_loc_wild_mut,
                        'start_loc' => $start_loc,
                        'end_loc' => $end_loc,
                        'gene' => $gene,
                        'pos_neg' => $pos_neg,
                        'strand_type' => $strand_type,
                        'L1_percentile' => $L1_percentile,
                        'RBPs' => $RBPs,
                        'motifs' => $motifs,
                        'ESEseq' => $ESEseq,
                        'enhancer_repressor' => $enhancer_repressor
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $error_string = $entry_array[0]."\t".$entry_array[1]."\t"
                        .$entry_array[2]."\t".$entry_array[3]."\t"
                        .$entry_array[4]."\t"
                        ."ERROR: Issue inserting to the database.\n";
                    fwrite($write_errors, $error_string);
                }
            }
        }

        fclose($read_final);
        fclose($write_errors);

        $this->remove_directory($working_directory_path);

        $this->record_progress("Job complete!");

        //return Redirect::to('processing/'.$input_id);
    }

    /**
     * Returns a pipeline error to the upload page
     *
     * @return Response
     */
    private function pipeline_error($message) {
        record_progress($message);
        //return Redirect::to('upload')->withInput()->withErrors($message);
    }

    private function remove_directory($path) {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? remove_directory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }

    private function record_progress($message) {
        $path_progress = $this->final_directory_path."/progress";
        $write_progress = fopen($path_progress, "w");
        fwrite($write_progress, $message);
        fclose($write_progress);
    }
}
