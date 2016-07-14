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

	/**
	 * Handles the upload request.
	 *
	 * @return Response
	 */
	public function index($id) {
		$this->id = $id;

		$option;
		$destination_path = public_path()."/uploads/";

		if (Input::get('download')) {
			$option = 'download';
		} elseif (Input::get('visualization')) {
			$option = 'visualization';
		} elseif (Input::get('errors')) {
			$errors_path = $destination_path.$id."_final/errors";
			return Response::download($errors_path);
		} elseif (Input::get('progress')) {
			return Redirect::to('processing/'.$id);
		}

		$path_processed = $destination_path.$id."_final/processed";
		$path_download = $destination_path."/"."file_to_download.txt";

		$read_processed = fopen($path_processed, "r");
		$write_download = fopen($path_download, "w");

		$table = "Spliceman";

		while (! feof($read_processed)) {
	    	$mutation = fgets($read_processed);
        	if (ctype_space($mutation)) {
	            break;
	        }
	        $mutation_data = explode("\t", $mutation);
	        if (count($mutation_data) >= 5) {
	            $db_key = 
	                $mutation_data[0]."_".$mutation_data[1]."_"
	                    .$mutation_data[3]."_".$mutation_data[4];
	            $db_key = str_replace("\n", "", $db_key);
	        	$result = DB::table($table)->where('chr_loc_wild_mut', $db_key)->first();
	        	if (is_object($result)) {
		            $RBP_data = explode(",", $result->RBPs);
		            $motif_data = explode(",", $result->motifs);
		            if ($result->enhancer_repressor == "-") {
		                $ESE_seq = "n/a";
		                $ESE_effect = "n/a";
		            } else {
		                $ESE_seq = $result->ESEseq;
		                $ESE_effect = $result->enhancer_repressor;
		            }
		            $output = 
		                [$mutation_data[0], $mutation_data[1],
		                $mutation_data[2], $mutation_data[3],
		                $mutation_data[4], $result->start_loc,
		                $result->end_loc, $result->gene,
		                $result->pos_neg, $result->strand_type,
		                $result->L1_percentile, $ESE_seq, $ESE_effect,
		                $RBP_data[0], $RBP_data[1], $RBP_data[2], $RBP_data[3],
		                $RBP_data[4], $motif_data[0], $motif_data[1],
		                $motif_data[2], $motif_data[3], $motif_data[4]];
		            $output = str_replace("\n", "", $output);
		            fwrite($write_download, join($output, "\t")."\n");
	        	}
        	}
        }
        fclose($read_processed);
        fclose($write_download);

        if (Input::get('download')) {
        	return Response::download($path_download);
    	} elseif (Input::get('visualization')) {
    		$output_text = file_get_contents($path_download);
    		return Redirect::to('result')->with('message',$output_text);
        }
	}
}