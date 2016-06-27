<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

//This is for the get event of the index page
Route::get('output', function() {
    return View::make('output');
});

Route::get('result', function() {
    return View::make('results');
});

Route::get('methods', function() {
    return View::make('methods');
});

Route::get('sample_download', function() {
    return Response::download("example_2.vcf");
});



Route::get('output_sample', function() {
    return Redirect::to('result')->with('message', array(
        "chr20\t2300608\tchr20\t2298132\t2306494\tTGM3\t+\t
        intron\t8\tYTHDC1\tPCBP4\tPCBP1\tPCBP3\tSRSF1",
        "chr20\t2301308\tchr20\t2298132\t2306494\tTGM3\t+\t
        intron\t86\tMBNL3\tMBNL2\tMBNL1\tELAVL1\tELAVL3"));
});


Route::get('sample', function() {
  return View::make('sample_data');
});

Route::get('upload', function() {
  return View::make('uploading');
});

Route::get('help', function() {
    return View::make('help');
});


function pipeline_error($message) {
    return Redirect::to('upload')->withInput()->withErrors($message);
}

function download() {

}

function splicemanPipeline($inputFileContents, $visualization) {

    $destinationPath = public_path().'/uploads';

    $filename_input = str_random(12);
    $filename_spliceman = str_random(12);
    $filename_RBPs = str_random(12);
    $filename_bedtools_new = str_random(12);
        
    $path_input = $destinationPath."/".$filename_input;
    $path_spliceman = $destinationPath."/".$filename_spliceman;
    $path_RBPs = $destinationPath."/".$filename_RBPs;
    $path_bedtools_new = $destinationPath."/".$filename_bedtools_new;
    
    $upload_success = file_put_contents($path_input, $inputFileContents);

    if($upload_success) {
        exec("perl\
            /var/www/html/spliceman_beta/vcf_fasta_v2.pl\
            /var/www/html/spliceman_beta/hg19.fa '$path_input'\
            '$path_spliceman'\
            '$path_RBPs'\
            '$path_bedtools_new'", 
            $output, 
            $return);

        if ($return) {
            File::delete($path_input);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            return pipeline_error(
                "Error in pipeline, please contact administrator
                and provide step 1");
        }

        if (count($output) > 0){

            File::delete($path_input);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            return pipeline_error($output);
        }

        $filename_bedtools_final= str_random(12);
        $path_bedtools_final = $destinationPath."/".$filename_bedtools_final;

        exec("bedtools intersect -wao\
            -a '$path_bedtools_new'\
            -b /var/www/html/spliceman_beta/RefSeq_exon_intron_sum_corr.txt\
            > '$path_bedtools_final'",
            $bedtools_array,
            $return);
        

        if ($return) {
            File::delete($path_input);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            File::delete($path_bedtools_final);
            if (count($filename_bedtools_new) - 1 == 0){
                return pipeline_error(
                    "Your file did not have any mutations that we were able 
                    to process. Is your file correctly formatted with one 
                    line per mutation?");
            }
            return pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 2");
        }
    //return "0";

        $filename_L1_distance_final = str_random(12);
        $path_L1_distance_final = 
            $destinationPath."/".$filename_L1_distance_final;
        exec("perl\
            /var/www/html/spliceman_beta/spliceman_2_processing_variants.pl\
            /var/www/html/spliceman_beta/hg19_L1_distance_only_hexamers_dif_by_one.fa\
            '$path_spliceman'\
             > '$path_L1_distance_final'",
             $L1_distance_array,
             $return);

        if ($return) {
            File::delete($path_input);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            File::delete($path_bedtools_final);
            File::delete($path_L1_distance_final);
            return pipeline_error(
                "Error in pipeline, please contact 
                administrator and provide step 3");
        }

        $filename_ESEseq_final = str_random(12);
        $path_ESEseq_final = $destinationPath."/".$filename_ESEseq_final;
        exec("perl\
            /var/www/html/spliceman_beta/spliceman_2_ESEseq.pl\
            /var/www/html/spliceman_beta/ESEseq_table.csv\
            '$path_spliceman'\
            > '$path_ESEseq_final'", 
            $ESEseq_array, 
            $return);

        if ($return) {
            File::delete($path_input);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            File::delete($path_bedtools_final);
            File::delete($path_L1_distance_final);
            File::delete($path_ESEseq_final);
            return pipeline_error(
                "Error in pipeline, please contact administrator");
        }  

        $filename_RBPs_final = str_random(12);
        $path_RBPs_final = $destinationPath."/".$filename_RBPs_final;

        exec("perl\
            /var/www/html/spliceman_beta/spliceman_rbp_scanner.pl\
            /var/www/html/spliceman_beta/rbp_binding_percentiles.fa\
            /var/www/html/spliceman_beta/RBP_motif_lengths.fa\
            /var/www/html/spliceman_beta/matrix_gene_human_v2.txt\
            '$path_RBPs'\
            '$path_RBPs_final'",
            $RBP_output,
            $return);

        if ($return) {
            File::delete($path_input);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            File::delete($path_bedtools_final);
            File::delete($path_L1_distance_final);
            File::delete($path_ESEseq_final);
            File::delete($path_RBP_final);

            return pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 4");
        }

        $filename_final_processing_final = str_random(12);
        $path_final_processing_final = 
            $destinationPath."/".$filename_final_processing_final;
        $filename_final_processing_temp1 = str_random(12);
        $path_final_processing_temp1 = 
            $destinationPath."/".$filename_final_processing_temp1;
        $filename_final_processing_temp2 = str_random(12);
        $path_final_processing_temp2 = 
            $destinationPath."/".$filename_final_processing_temp2;

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
            {a[FNR] = $6\"\t\"$7\"\t\"$8\"\t\"$9\"\t\"$10; next}\
            FNR in a\
            {print $0\"\t\"a[FNR]}'\
            '$path_RBPs_final'\
            '$path_final_processing_temp2'\
            > '$path_final_processing_final'", 
            $final_result, 
            $return);     

        if ($return){
            File::delete($path_input);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            File::delete($path_bedtools_final);
            File::delete($path_L1_distance_final);
            File::delete($path_ESEseq_final);
            File::delete($path_RBPs_final);
            File::delete($path_final_processing_temp1);
            File::delete($path_final_processing_temp2);
            File::delete($path_final_processing_final);
            return pipeline_error(
                "Error in pipeline, please contact administrator and provide 
                step 5");
        }

        exec(
            "bash /var/www/html/spliceman_beta/process_final_2.sh\
            '$path_final_processing_final'",
            $final_final_result_pre, 
            $return);
        if (count($final_final_result_pre)>1){
            return pipeline_error($final_final_result_pre);
        }

        $path_download = $destinationPath."/"."file_to_download.txt";
        exec(
            "bash /var/www/html/spliceman_beta/process_final.sh\
            '$path_final_processing_final'\
            > '$path_download'", 
            $final_final_result, 
            $return);
        File::delete($path_input);
        File::delete($path_spliceman);
        File::delete($path_RBPs);
        File::delete($path_bedtools_new);
        File::delete($path_bedtools_final);
        File::delete($path_L1_distance_final);
        File::delete($path_ESEseq_final);
        File::delete($path_RBPs_final);
        File::delete($path_final_processing_temp1);
        File::delete($path_final_processing_temp2);
        File::delete($path_final_processing_final);

        if ($visualization) {
            $final_final_result = file_get_contents($path_download);
            return Redirect::to('result')->with('message',$final_final_result);
        } else {
            return Response::download($path_download);
        }
    }
}

Route::post('upload', function() {

    $file_sample_vcf = public_path()."/example_2.vcf";

    if (Input::get('recommend')) {
        return Redirect::away(
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
            $visualization = $output == 'visualization';

            // process the uploaded file
            $filename = Input::file('file')->getClientOriginalName();
            $extension = Input::file('file')->getClientOriginalExtension();
            $file = Input::file('file');

            $homepage = file_get_contents($file);

            return splicemanPipeline($homepage, $visualization);

        #########uploading file did not work
        /*else {
           return Redirect::to('upload')->withInput()->withErrors(
                "Error uploading your file, try again");
        }

          }*/


        ########input is a text 
        } else {

            $output = Input::get('output');
            $visualization = $output == 'visualization';

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
                "perl /var/www/html/spliceman_beta/validate_text_field.pl\
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
            $homepage = file_get_contents($file_new2);

            return splicemanPipeline($homepage, $visualization);
        }
    }
      // return 'Form passed validation!';
});
