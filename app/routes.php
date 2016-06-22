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
Route::get('output', function()
{
	return View::make('output');
});
Route::get('result', function(){
	return View::make('results');
});
Route::get('methods', function()
{
  return View::make('methods');
});
Route::get('sample_download', function()
{
  return Response::download("example_2.vcf");
});



Route::get('output_sample', function()
{
return Redirect::to('result')->with('message',array("chr20\t2300608\tchr20\t2298132\t2306494\tTGM3\t+\tintron\t8\tYTHDC1\tPCBP4\tPCBP1\tPCBP3\tSRSF1","chr20\t2301308\tchr20\t2298132\t2306494\tTGM3\t+\tintron\t86\tMBNL3\tMBNL2\tMBNL1\tELAVL1\tELAVL3"));
});


Route::get('sample', function()
{
  return View::make('sample_data');
});

Route::get('upload', function()
{
  return View::make('uploading');
});

Route::get('help', function(){
    return View::make('help');
});

Route::post('upload', function()
{

$file_sample_vcf = public_path()."/example_2.vcf";



if(Input::get('recommend')){
    return Redirect::away('http://fairbrother.biomed.brown.edu/dashboard/webmanage/register');
}
elseif(Input::get('reset')){
    return Redirect::to('upload');
}
elseif(Input::get('process_sample_text')){
    return Redirect::to('upload')->withInput(array('sequence' => 'chr20 2300608 C   T
chr20   2301308 T   G', 'file' => ''));
}





elseif (Input::get('process')) {

  $rules = [
    'sequence' =>  'required_without:file',
    'file' => 'required_without:sequence',
    // 'file' => 'max:110240',
  ];

  $validator = Validator::make(Input::all(), $rules);

  if ($validator->fails())
  {
      return Redirect::to('upload')->withInput()->withErrors($validator);
  }

  if (Input::hasFile('file')) {
    
    // process the uploaded file
    $filename = Input::file('file')->getClientOriginalName();
    $extension = Input::file('file')->getClientOriginalExtension();
    $file = Input::file('file');



    $filename = str_random(12);
    $filename_spliceman = str_random(12);
    $filename_RBPs = str_random(12);
    $filename_bedtools = str_random(12);
    $destinationPath = public_path().'/uploads';
    //$path = Input::file('file')->getRealPath();
    $file_new = $destinationPath."/".$filename;
    //return $file_new;
    $homepage = file_get_contents($file);
    //return $homepage;
    $upload_success = file_put_contents($file_new, $homepage);

    //File::put($filename, $homepage);
    //return $file_new;
    //return Response::download($file);
    // return $destinationPath;
    //exec("mv '$file' '$destinationPath'");
    //return $homepage;
    //$upload_success = Input::file('file')->move($destinationPath, $filename);
    // return $destinationPath;

    // $contents = File::get($destinationPath."/".$filename);
    // return $contents;
    if( $upload_success ) {
      //return "Yes!";
    	//$file_new = $destinationPath."/".$filename;
    	$filename_spliceman_new = $destinationPath."/".$filename_spliceman;
    	$filename_RBPs_new = $destinationPath."/".$filename_RBPs;
    	$filename_bedtools_new = $destinationPath."/".$filename_bedtools;

    	exec("perl /var/www/html/spliceman_beta/vcf_fasta_v2.pl /var/www/html/spliceman_beta/hg19.fa '$file_new' '$filename_spliceman_new' '$filename_RBPs_new' '$filename_bedtools_new'", $output, $return);

  if ($return) {
    	return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step 1");
	}

	if (count($output)>0){

		File::delete($destinationPath."/".$filename);
		File::delete($destinationPath."/".$filename_spliceman);
		File::delete($destinationPath."/".$filename_RBPs);
		File::delete($destinationPath."/".$filename_bedtools);

		return Redirect::to('upload')->withInput()->withErrors($output);
	}

    $filename_bedtools_final_name = str_random(12);
    $filename_bedtools_final_loc = $destinationPath."/".$filename_bedtools_final_name;

    //if(count($filename_bedtools))

		exec("bedtools intersect -wao -a '$filename_bedtools_new' -b /var/www/html/spliceman_beta/RefSeq_exon_intron_sum_corr.txt > '$filename_bedtools_final_loc'", $bedtools_array, $return);
		
		if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        if((count($filename_bedtools_new)-1)==0){
            return Redirect::to('upload')->withInput()->withErrors("Your file did not have any mutations that we were able to process. Is your file correctly formatted with one line per mutation?");
        }
        //return $return;
        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step 2");
		}
    //return "0";

    $filename_L1_distance_final_name = str_random(12);
    $filename_L1_distance_final_loc = $destinationPath."/".$filename_L1_distance_final_name;
		exec("perl /var/www/html/spliceman_beta/spliceman_2_processing_variants.pl /var/www/html/spliceman_beta/hg19_L1_distance_only_hexamers_dif_by_one.fa '$filename_spliceman_new' > '$filename_L1_distance_final_loc'", $L1_distance_array, $return);

    if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);

        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step 3");
    }

    $filename_ESEseq_final_name = str_random(12);
    $filename_ESEseq_final_loc = $destinationPath."/".$filename_ESEseq_final_name;
        exec("perl /var/www/html/spliceman_beta/spliceman_2_ESEseq.pl /var/www/html/spliceman_beta/ESEseq_table.csv '$filename_spliceman_new' > '$filename_ESEseq_final_loc'", $ESEseq_array, $return);
        //return file_get_contents($filename_ESEseq_final_loc);

    if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_ESEseq_final_name);

        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step ?");
    }  

    $filename_RBP_final_name = str_random(12);
    $filename_RBP_final_loc = $destinationPath."/".$filename_RBP_final_name;

    // exec("perl /var/www/html/spliceman_beta/spliceman_rbp_scanner.pl /var/www/html/spliceman_beta/rbp_binding_percentiles.fa /var/www/html/spliceman_beta/RBP_motif_lengths.fa /var/www/html/spliceman_beta/matrix_gene_human_v2.txt '$filename_RBPs_new' '$filename_RBP_final_loc'", $RBP_output, $return);

    // if ($return) {
    //     File::delete($destinationPath."/".$filename);
    //     File::delete($destinationPath."/".$filename_spliceman);
    //     File::delete($destinationPath."/".$filename_RBPs);
    //     File::delete($destinationPath."/".$filename_bedtools);
    //     File::delete($destinationPath."/".$filename_bedtools_final_name);
    //     File::delete($destinationPath."/".$filename_L1_distance_final_name);
    //     File::delete($destinationPath."/".$filename_RBP_final_name);

    //     return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step 4");
    // }

    // return "0";

    $filename_for_final_processing_final_name = str_random(12);
    $filename_for_final_processing_final_loc = $destinationPath."/".$filename_for_final_processing_final_name;

    exec("awk -F\"\t\" 'FNR==NR{a[$2\"\t\"$3\"\t\"$4\"\t\"$5]=$1;next}$4\"\t\"$5\"\t\"$6\"\t\"$7 in a {print $0\"\t\"a[$4\"\t\"$5\"\t\"$6\"\t\"$7]}' '$filename_L1_distance_final_loc' '$filename_bedtools_final_loc' > '$filename_for_final_processing_final_loc'", $final_result, $return);
    //return file_get_contents($filename_for_final_processing_final_loc);

    if ($return){
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);
        File::delete($destinationPath."/".$filename_for_final_processing_final_name);
        File::delete($destinationPath."/".$filename_ESEseq_final_name);
        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step 5");
    }

    exec("bash /var/www/html/spliceman_beta/process_final_2.sh '$filename_for_final_processing_final_loc'",$final_final_result_pre, $return);
    // exec("bash /var/www/html/spliceman_beta/process_final_3.sh /var/www/html/spliceman_beta/problem_with_bedtools.txt '$final_final_result_pre'",$final_final_result_pre_2, $return);
    if (count($final_final_result_pre)>1){
        // return Redirect::to('upload')->withInput(Form::input('sequence', 'name', 'value'));
        // return Redirect::to('upload')->with('sequence', 'bla');
        return Redirect::to('upload')->withInput()->withErrors($final_final_result_pre);
    }

    //return File::get($filename_for_final_processing_final_loc);
    $filename_to_download = $destinationPath."/"."file_to_download.txt";
   exec("bash /var/www/html/spliceman_beta/process_final.sh '$filename_for_final_processing_final_loc' > '$filename_to_download'",$final_final_result, $return);
  
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);
        File::delete($destinationPath."/".$filename_for_final_processing_final_name);
        File::delete($destinationPath."/".$filename_ESEseq_final_name);
  //return $final_final_result;

        // File::put($filename_to_download, $final_final_result);
	   // Session::flash('download.in.the.next.request', $filename_to_download);
    // return Redirect::to('result')->with('message',$final_final_result);
        return Response::download($filename_to_download); 


#########uploading file did not work
} 
else {
   return Redirect::to('upload')->withInput()->withErrors("Error uploading your file, try again");
}

  }


  ########input is a text 
  else{


  	$input_text = Input::get('sequence');
  	$filename = str_random(12);
    $filename2 = str_random(12);
    $destinationPath = public_path().'/uploads';
    $file_new = $destinationPath."/".$filename;
    $file_new2 = $destinationPath."/".$filename2;
    File::put($file_new, $input_text);

    $totalLines = intval(exec("wc -l '$file_new'"));
    // return $totalLines;
    if($totalLines > 4){
      File::delete($destinationPath."/".$filename);
       return Redirect::to('upload')->withInput()->withErrors("Maximum number of mutations is 5, please upload .vcf if you have more mutations");
    }
    exec("perl /var/www/html/spliceman_beta/validate_text_field.pl '$file_new' '$file_new2'", $output, $return);
    if ($return){
      return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator");
    }
    if (count($output)>0){
    return Redirect::to('upload')->withInput()->withErrors($output);
  }
  $homepage = file_get_contents($file_new2);
  //return $homepage;
  $upload_success = file_put_contents($file_new, $homepage);
  //return file_get_contents($file_new);
    // File::get('/var/www/html/spliceman_beta/public/uploads/slFruSpu0pbp');
      //File::get($file_new2);
      $filename_spliceman = str_random(12);
      $filename_RBPs = str_random(12);
      $filename_bedtools = str_random(12);
      $filename_spliceman_new = $destinationPath."/".$filename_spliceman;
      $filename_RBPs_new = $destinationPath."/".$filename_RBPs;
      $filename_bedtools_new = $destinationPath."/".$filename_bedtools;
      exec("perl /var/www/html/spliceman_beta/vcf_fasta_v2.pl /var/www/html/spliceman_beta/hg19.fa '$file_new' '$filename_spliceman_new' '$filename_RBPs_new' '$filename_bedtools_new'", $output, $return);

  if ($return) {
      return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator");
  }

  if (count($output)>0){

    File::delete($destinationPath."/".$filename);
    File::delete($destinationPath."/".$filename2);
    File::delete($destinationPath."/".$filename_spliceman);
    File::delete($destinationPath."/".$filename_RBPs);
    File::delete($destinationPath."/".$filename_bedtools);

    return Redirect::to('upload')->withInput()->withErrors($output);
  }

    $filename_bedtools_final_name = str_random(12);
    $filename_bedtools_final_loc = $destinationPath."/".$filename_bedtools_final_name;
    exec("bedtools intersect -wao -a '$filename_bedtools_new' -b /var/www/html/spliceman_beta/RefSeq_exon_intron_sum_corr.txt | awk '!a[$1$2$3]++' > '$filename_bedtools_final_loc'", $bedtools_array, $return);
    
    if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename2);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        if((count($filename_bedtools_new)-1)==0){
            return Redirect::to('upload')->withInput()->withErrors("Your file did not have any mutations that we were able to process. Is your file correctly formatted with one line per mutation?");
        }
        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator");
    }
    //return "0";

    $filename_L1_distance_final_name = str_random(12);
    $filename_L1_distance_final_loc = $destinationPath."/".$filename_L1_distance_final_name;
    exec("perl /var/www/html/spliceman_beta/spliceman_2_processing_variants.pl /var/www/html/spliceman_beta/hg19_L1_distance_only_hexamers_dif_by_one.fa '$filename_spliceman_new' > '$filename_L1_distance_final_loc'", $L1_distance_array, $return);

    if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename2);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);

        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator");
    }

    $filename_ESEseq_final_name = str_random(12);
    $filename_ESEseq_final_loc = $destinationPath."/".$filename_ESEseq_final_name;
        exec("perl /var/www/html/spliceman_beta/spliceman_2_ESEseq.pl /var/www/html/spliceman_beta/ESEseq_table.csv '$filename_spliceman_new' > '$filename_ESEseq_final_loc'", $ESEseq_array, $return);

    if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_ESEseq_final_name);

        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step ?");
    } 

    $filename_RBP_final_name = str_random(12);
    $filename_RBP_final_loc = $destinationPath."/".$filename_RBP_final_name;

    exec("perl /var/www/html/spliceman_beta/spliceman_rbp_scanner.pl /var/www/html/spliceman_beta/rbp_binding_percentiles.fa /var/www/html/spliceman_beta/RBP_motif_lengths.fa /var/www/html/spliceman_beta/matrix_gene_human_v2.txt '$filename_RBPs_new' '$filename_RBP_final_loc'", $RBP_output, $return);

    if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename2);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);

        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator");
    }

    // return "0";

    $filename_for_final_processing_final_name = str_random(12);
    $filename_for_final_processing_final_loc = $destinationPath."/".$filename_for_final_processing_final_name;

    exec("paste '$filename_bedtools_final_loc' '$filename_L1_distance_final_loc' '$filename_RBP_final_loc' > '$filename_for_final_processing_final_loc'", $final_result, $return);

    if ($return){
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename2);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);
        File::delete($destinationPath."/".$filename_for_final_processing_final_name);
        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator");
    }

    exec("bash /var/www/html/spliceman_beta/process_final_2.sh '$filename_for_final_processing_final_loc'",$final_final_result_pre, $return);
    // exec("bash /var/www/html/spliceman_beta/process_final_3.sh /var/www/html/spliceman_beta/problem_with_bedtools.txt '$final_final_result_pre'",$final_final_result_pre_2, $return);
    if (count($final_final_result_pre)>1){
        return Redirect::to('upload')->withInput()->withErrors($final_final_result_pre);
    }

    //return File::get($filename_for_final_processing_final_loc);
   exec("bash /var/www/html/spliceman_beta/process_final.sh '$filename_for_final_processing_final_loc'",$final_final_result, $return);
  
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename2);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        // File::delete($destinationPath."/".$filename_bedtools);
        // File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);
        File::delete($destinationPath."/".$filename_for_final_processing_final_name);
 // return $final_final_result;
return Redirect::to('result')->with('message',$final_final_result);
//return Redirect::to('result')->with('message',var_dump($final_final_result));




















  }
}
  // return 'Form passed validation!';
});
