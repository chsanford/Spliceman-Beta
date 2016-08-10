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

        	$input_id = str_random(12);
    		$destinationPath = public_path().'/uploads';

			$final_directory_path = $destinationPath.'/'.$input_id."_final";
    		mkdir($final_directory_path);

	        if ($validator->fails()) {
	            return Redirect::to('upload')->withInput()->withErrors($validator);
	        }

	        if (Input::hasFile('file')) {
	            $output = Input::get('output');
	            $file = Input::file('file');

	            $file_new = $final_directory_path."/input";
	            rename($file, $file_new);

	            $process_job = new ProcessNewInput($input_id, $file_new);
        		$this->dispatch($process_job);
        		return Redirect::to('processing/'.$input_id);
	        } else {
	            $output = Input::get('output');
	            $input_text = Input::get('sequence');

	            $filename = str_random(12);
	            $filename2 = $input_id;

	            $file_new = $destinationPath."/".$filename;
	            $file_new2 = $final_directory_path."/input";

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
	                return Redirect::to('upload')->
	                	withInput()->
	                	withErrors($output);
	            }

                File::delete($file_new);

     
            	$process_job = new ProcessNewInput($input_id, $file_new2);
        		$this->dispatch($process_job);
        		return Redirect::to('processing/'.$input_id);
	        }
	    }
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