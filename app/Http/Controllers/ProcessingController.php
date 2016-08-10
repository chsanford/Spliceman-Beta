<?php namespace App\Http\Controllers;

use Validator;
use Input;
use Redirect;
use File;
use DB;
use Response;
use View;
use Session;
use App\Jobs\ProcessNewInput;

class ProcessingController extends Controller {

	protected $id;
	protected $destination_path;

	/**
	 * Handles the upload request.
	 *
	 * @return Response
	 */
	public function index($id) {
		$this->id = $id;
		$destination_path = public_path()."/uploads/";
		$this->destination_path = $destination_path;

		if (Input::get('download')) {
			return $this->spliceman_output('download');
		} elseif (Input::get('visualization')) {
			return $this->spliceman_output('visualization');
		} elseif (Input::get('errors')) {
			return $this->errors_output();
		} elseif (Input::get('dashboard')) {
			return $this->dashboard_output('download');
		} elseif (Input::get('dash_vis')) {
			return $this->dashboard_output('download');
		}
	}

	private function spliceman_output($option) {
		$id = $this->id;
		$destination_path = $this->destination_path;

		$path_input = $destination_path.$id."_final/input";
		$path_download = $destination_path."/"."spliceman_results.txt";

		$read_input = fopen($path_input, "r");
		$write_download = fopen($path_download, "w");

		$table = "NewSpliceman";

		while (! feof($read_input)) {
	    	$mutation = fgets($read_input);
        	if (ctype_space($mutation)) {
	            break;
	        }
	        $mutation_data = explode("\t", $mutation);
	        if ($this->is_valid($mutation_data)) {
	            $db_key = 
	                $mutation_data[0]."_".$mutation_data[1]."_".
	                    $mutation_data[3]."_".$mutation_data[4];
	            $db_key = str_replace("\n", "", $db_key);
	            $results = DB::select(
	            	"SELECT * FROM $table 
	            		WHERE chr_loc_wild_mut_transcript_exon 
	            		LIKE '$db_key%';");
	            foreach ($results as $result) {
		        	if (is_object($result)) {
		        		if (! $result->error) {
		        			$chr_loc_wild_mut_transcript_exon = 
		        				explode("_", 
		        					$result->chr_loc_wild_mut_transcript_exon);
				            $RBP_data = explode(",", $result->RBPs);
				            $motif_data = explode(",", $result->motifs);
				            if ($result->variant_type == "intronic_variant") {
				                $ESEseq = "n/a";
				            } else {
				                $ESEseq = $result->ESEseq;
				            }
				            $output = array(
				                $mutation_data[0], // chromosome number
				                $mutation_data[1], // mutation location
				                $mutation_data[2], // mutation ID
				                $mutation_data[3], // wild base
				                $mutation_data[4], // mutant base
				                $chr_loc_wild_mut_transcript_exon[4], // transcript ID
				                $chr_loc_wild_mut_transcript_exon[5], // exon number
				                $result->start_loc, // start location
				                $result->end_loc, // end location
				                $result->gene_name, // gene name
				                $result->gene_id, // gene ID
				                $result->strand, //strand
				                $result->feature_type, // feature type
				                $result->variant_type, // variant type
				                $result->L1_percentile, // L1 percentile
				                $ESEseq, // ESEseq value
				                $result->ss_distance,
				                $result->splice_site,
				                $RBP_data[0], // RBPs
				                $RBP_data[1], 
				                $RBP_data[2], 
				                $RBP_data[3],
				                $RBP_data[4],
				                $motif_data[0], // motifs
				                $motif_data[1],
				                $motif_data[2], 
				                $motif_data[3], 
				                $motif_data[4]
				            );
				            $output = str_replace("\n", "", $output);
				            fwrite($write_download, join($output, "\t")."\n");
			        	}
		        	}
	        	}
        	}
        }
        fclose($read_input);
        fclose($write_download);

        if (Input::get('download')) {
        	return Response::download($path_download);
    	} elseif (Input::get('visualization')) {
    		$output_text = file_get_contents($path_download);
    		return Redirect::to('result')->with('message',$output_text);
        }
	}

	private function errors_output() {
		$destination_path = $this->destination_path;
		$id = $this->id;

		$path_input = $destination_path.$id."_final/input";
		$path_errors = $destination_path.$id."_final/errors";
		$path_errors_out = $destination_path."/"."spliceman_errors.txt";

		$read_input = fopen($path_input, "r");
		$read_errors = fopen($path_errors, "r");
		$write_errors_out = fopen($path_errors_out, "w");

		while (! feof($read_errors)) {
			$error = fgets($read_errors);
			fwrite($write_errors_out, $error);
		}

		$table = "NewSpliceman";
		$error_table = "NewErrors";

		while (! feof($read_input)) {
	    	$mutation = fgets($read_input);
        	if (ctype_space($mutation)) {
	            break;
	        }
	        $mutation_data = explode("\t", $mutation);
	        if ($this->is_valid($mutation_data)) {
	            $db_key = 
	                $mutation_data[0]."_".$mutation_data[1]."_"
	                    .$mutation_data[3]."_".$mutation_data[4];
	            $db_key = str_replace("\n", "", $db_key);
    			$results = DB::select(
    				"SELECT * FROM $error_table
	    				WHERE chr_loc_wild_mut_transcript_exon
	    				LIKE '$db_key%';");
    			foreach ($results as $result) {
    				$message = $result->message;
    				$data = $result->chr_loc_wild_mut_transcript_exon;
    				$data = str_replace("_", "\t", $data);
    				fwrite($write_errors_out, $data."\t".$message."\n");
    			}			
        	}
        }
        fclose($read_input);
        fclose($write_errors_out);
        fclose($read_errors);

		return Response::download($path_errors_out);		
	}

	private function dashboard_output($option) {
		$id = $this->id;
		$destination_path = $this->destination_path;

		$path_input = $destination_path.$id."_final/input";
		$path_download = $destination_path."/"."dashboard_data.txt";

		$read_input = fopen($path_input, "r");
		$write_download = fopen($path_download, "w");

        $categories = ['Idm', 'coord', 'ref_plus', 'ref_neg', 'vit_wu',
        'vit_mu', 'vit_ws_ann', 'vit_ms_ann', 'vitRmw_ann', 'vitP_ann',
        'viv_wu', 'viv_mu', 'viv_ws_ann', 'viv_ms_ann', 'vivRmw_ann',
        'vivP_ann'];

        fwrite($write_download, join($categories, "\t")."\n");

		$table = "DashboardID";

		while (! feof($read_input)) {
	    	$mutation = fgets($read_input);
        	if (ctype_space($mutation)) {
	            break;
	        }

	        $mutation_data = explode("\t", $mutation);
	        if (count($mutation_data) >= 5) {
	        	$chromosome = $mutation_data[0];
	        	$location = $mutation_data[1];
	        	$wild_base = $mutation_data[3];
	        	$mut_base = $mutation_data[4];
	        	$mut_base = str_replace("\n", "", $mut_base);
	        	$id_result = DB::table($table)->
	        		select('id')->
	        		where('chromosome', $chromosome)->
	        		where('location', $location)->
	        		where('wild_base', $wild_base)->
	        		where('mut_base', $mut_base)->
	        		first();
	        	if (is_object($id_result)) {
	        		$id = $id_result->id;
		            //fwrite($write_download, $id."\n");
		            $result = DB::connection('dashboard')->
						table('splicedata3')->
						where('Idm', $id)->
						first();

					$data = [$result->Idm, $result->coord, $result->ref_plus,
						$result->ref_neg, $result->vit_wu, $result->vit_mu, 
						$result->vit_ws_ann, $result->vit_ms_ann,
						$result->vitRmw_ann, $result->vitP_ann, 
						$result->viv_wu, $result->viv_mu, $result->viv_ws_ann, 
						$result->viv_ms_ann, $result->vivRmw_ann,
						$result->vivP_ann];

					fwrite($write_download, join($data, "\t")."\n");
	        	}
        	}
        }
        fclose($read_input);
        fclose($write_download);

        if (Input::get('dashboard')) {
        	return Response::download($path_download);
    	} elseif (Input::get('dash_vis')) {
    		$output_text = file_get_contents($path_download);
    		return Redirect::to('dashboard')->with('message',$output_text);
        }        
	}

    /**
     * Verifies that certain fields in input data meet certain conditions.
     */
    private function is_valid($mutation_data) {
        if (count($mutation_data) >= 5) {
            $chromosome_format = preg_match("/^chr/", $mutation_data[0]);
            $wild_format = preg_match("/^[A,C,G,T]$/", $mutation_data[3]);
            $mut_format = preg_match("/^[A,C,G,T]$/", $mutation_data[4]);
            return $chromosome_format && $wild_format && $mut_format;
        } else {
            return false;
        }
    }
}