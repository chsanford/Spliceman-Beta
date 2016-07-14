<?php namespace App\Http\Controllers;

use Validator;
use Input;
use Redirect;
use File;
use DB;
use Response;
use App\Jobs\ProcessNewInput;

class UploadController extends Controller {

	/**
	 * Handles the upload request.
	 *
	 * @return Response
	 */
	public function index() {
		//return $request->input('recommend');

		$file_sample_vcf = public_path()."/example_2.vcf";

		if (Input::get('recommend')) {
	        return Redirect::to(
	            'http://fairbrother.biomed.brown.edu/dashboard/webmanage/register');

	    } elseif (Input::get('reset')) {
	        return Redirect::to('upload');

	    } elseif (Input::get('process_sample_text')) {
	        return Redirect::to('upload')->withInput(
	            array('sequence' => 
	                "chr20 2300608 C   T\nchr20   2301308 T   G",
	                'file' => ''));
	    } elseif (Input::get('process')) {
	        $rules = [
	            'sequence' =>  'required_without:file',
	            'file' => 'required_without:sequence',
	        ];

        	$validator = Validator::make(Input::all(), $rules);

	        if ($validator->fails()) {
	            return Redirect::to('upload')->withInput()->withErrors($validator);
	        }

	        if (Input::hasFile('file')) {
	            $output = Input::get('output');
	            //$visualization = ($output == 'visualization');
	            // process the uploaded file
	            //$filename = Input::file('file')->getClientOriginalName();
	            //$extension = Input::file('file')->getClientOriginalExtension();
	            $file = Input::file('file');
	            //$homepage = file_get_contents($file);
	            return $this->spliceman_pipeline($file);//, $visualization);
	        } else {
	            $output = Input::get('output');
	            //$visualization = ($output == 'visualization');
	            $input_text = Input::get('sequence');

	            $destinationPath = public_path().'/uploads';

	            $filename = str_random(12);
	            $filename2 = str_random(12);

	            $file_new = $destinationPath."/".$filename;
	            $file_new2 = $destinationPath."/".$filename2;

	            File::put($file_new, $input_text);

	            $totalLines = intval(exec("wc -l '$file_new'"));
	            if ($totalLines > 4) {
	                File::delete($destinationPath."/".$filename);
	                return Redirect::to('upload')->withInput()->withErrors(
	                    "Maximum number of mutations is 5, please upload .vcf if 
	                    you have more mutations");
	            }
	            exec(
	                "perl /var/www/html/spliceman_beta/scripts/validate_text_field.pl\
	                '$file_new'\
	                '$file_new2'", 
	                $output, 
	                $return);
	            if ($return) {
	                return Redirect::to('upload')->withInput()->withErrors(
	                    "Error in pipeline, please contact administrator");
	            }
	            if (count($output) > 0) {
	                return Redirect::to('upload')->withInput()->withErrors($output);
	            }
	            //$homepage = file_get_contents($file_new2);

                File::delete($file_new);
                //File::delete($file_new2);

	            return $this->spliceman_pipeline($file_new2);//, $visualization);
	        }
	    }
	}

	/**
	 * Processes the input through the pipeline
	 *
	 * @return Response
	 */
	private function spliceman_pipeline($path_start) {

	    $table = "Spliceman";

	    $destination_path = public_path().'/uploads';
        $input_id = str_random(12);
        $final_directory_path = $destination_path.'/'.$input_id."_final";
        mkdir($final_directory_path);
        $path_input = $final_directory_path."/input";
        $path_processed = $final_directory_path."/processed";

        $read_start = fopen($path_start, "r") or die ("Unable to open file!");
        $write_input = fopen($path_input, "a") or die ("Unable to open file!");
        $write_processed = fopen($path_processed, "a") or die ("Unable to open file!");

	    $non_empty_new_file = false;
	    $valid_mutations = false;

	    while (! feof($read_start)) {
	    	$mutation = fgets($read_start);
	        if (ctype_space($mutation)) {
	            break;
	        }
	        $mutation_data = explode("\t", $mutation);
	        if (!preg_match("/^chr/", $mutation_data[0])) {
	        	$mutation_data[0] = "chr".$mutation_data[0];
	        }
	        if ($this->is_valid($mutation_data)) {
	            $db_key = 
	                $mutation_data[0]."_".$mutation_data[1]."_"
	                    .$mutation_data[3]."_".$mutation_data[4];
	            // Removes newline characters, if they are present
	            $db_key = str_replace("\n", "", $db_key);
	            $result = DB::table($table)->where('chr_loc_wild_mut', $db_key)->first();
	            $valid_mutations = true;
	            $mutation_input = $mutation_data[0]."\t".$mutation_data[1]
	            	."\t".$mutation_data[2]."\t".$mutation_data[3]
	            	."\t".$mutation_data[4];
				$mutation_input = str_replace("\n", "", $mutation_input);

	            if (count($result) == 0) {
	            	fwrite($write_input, $mutation_input."\n");
	            	$non_empty_new_file = true;
	            } else {
	            	fwrite($write_processed, $mutation_input."\n");
	            }
	        }   
	    } 

	    fclose($read_start);
	    fclose($write_input);
	    fclose($write_processed);

	    if (!$valid_mutations) {
	    	return Redirect::to('upload/')->withInput()->withErrors(
                "The file was not readable; no mutations could be identified.");
	    }

	    if ($non_empty_new_file) {
	    	$process_job = new ProcessNewInput($input_id);
	    	//return get_object_vars($process_job);
        	$this->dispatch($process_job);
        	return Redirect::to('processing/'.$input_id);
	    }

		$path_progress = $final_directory_path."/progress";
        $write_progress = fopen($path_progress, "w");
        fwrite($write_progress, "Job complete!");
        fclose($write_progress);
	    return Redirect::to('processing/'.$input_id);

	}

    /**
     * Returns a pipeline error to the upload page
     *
     * @return Response
     */
    private function pipeline_error($message) {
        Redirect::to('upload')->withInput()->withErrors($message);
    }

    /**
     * Determines whether or a not a mutation line of a file is valid
     *
     * @return boolean
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