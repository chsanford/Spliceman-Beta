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

Route::get('sample', function()
{
  return View::make('sample_data');
});

Route::get('upload', function()
{
  return View::make('uploading');
});

Route::post('upload', function()
{



if(Input::get('recommend')){
    return Redirect::away('http://fairbrother.biomed.brown.edu/dashboard/webmanage/register');
}
elseif(Input::get('reset')){
    return Redirect::to('upload');
}

elseif (Input::get('process')) {

  $rules = [
    'sequence' =>  'required_without:file',
    'file' => 'required_without:sequence',
    'file' => 'max:10240',
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
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
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
		exec("perl /var/www/html/spliceman_beta/spliceman_2_processing_variants.pl /var/www/html/spliceman_beta/hg19_L1_distance.fa '$filename_spliceman_new' > '$filename_L1_distance_final_loc'", $L1_distance_array, $return);

    if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);

        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step 3");
    }

    $filename_RBP_final_name = str_random(12);
    $filename_RBP_final_loc = $destinationPath."/".$filename_RBP_final_name;

    exec("perl /var/www/html/spliceman_beta/spliceman_rbp_scanner.pl /var/www/html/spliceman_beta/rbp_binding_percentiles.fa /var/www/html/spliceman_beta/RBP_motif_lengths.fa /var/www/html/spliceman_beta/matrix_gene_human_v2.txt '$filename_RBPs_new' '$filename_RBP_final_loc'", $RBP_output, $return);

    if ($return) {
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);

        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step 4");
    }

    // return "0";

    $filename_for_final_processing_final_name = str_random(12);
    $filename_for_final_processing_final_loc = $destinationPath."/".$filename_for_final_processing_final_name;

    exec("paste '$filename_bedtools_final_loc' '$filename_L1_distance_final_loc' $filename_RBP_final_loc > '$filename_for_final_processing_final_loc'", $final_result, $return);

    if ($return){
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);
        File::delete($destinationPath."/".$filename_for_final_processing_final_name);
        return Redirect::to('upload')->withInput()->withErrors("Error in pipeline, please contact administrator and provide step 5");
    }

    //return File::get($filename_for_final_processing_final_loc);
   exec("bash /var/www/html/spliceman_beta/process_final.sh '$filename_for_final_processing_final_loc'",$final_final_result, $return);
  
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);
        File::delete($destinationPath."/".$filename_for_final_processing_final_name);
  //return $final_final_result;
	return Redirect::to('result')->with('message',$final_final_result);




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
    File::delete($destinationPath."/".$filename);
    File::delete($destinationPath."/".$filename2);
    File::delete($destinationPath."/".$filename_spliceman);
    File::delete($destinationPath."/".$filename_RBPs);
    File::delete($destinationPath."/".$filename_bedtools);
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
    exec("bedtools intersect -wao -a '$filename_bedtools_new' -b /var/www/html/spliceman_beta/RefSeq_exon_intron_sum_corr.txt > '$filename_bedtools_final_loc'", $bedtools_array, $return);
    
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
    exec("perl /var/www/html/spliceman_beta/spliceman_2_processing_variants.pl /var/www/html/spliceman_beta/hg19_L1_distance.fa '$filename_spliceman_new' > '$filename_L1_distance_final_loc'", $L1_distance_array, $return);

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

    exec("paste '$filename_bedtools_final_loc' '$filename_L1_distance_final_loc' $filename_RBP_final_loc > '$filename_for_final_processing_final_loc'", $final_result, $return);

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

    //return File::get($filename_for_final_processing_final_loc);
   exec("bash /var/www/html/spliceman_beta/process_final.sh '$filename_for_final_processing_final_loc'",$final_final_result, $return);
  
        File::delete($destinationPath."/".$filename);
        File::delete($destinationPath."/".$filename2);
        File::delete($destinationPath."/".$filename_spliceman);
        File::delete($destinationPath."/".$filename_RBPs);
        File::delete($destinationPath."/".$filename_bedtools);
        File::delete($destinationPath."/".$filename_bedtools_final_name);
        File::delete($destinationPath."/".$filename_L1_distance_final_name);
        File::delete($destinationPath."/".$filename_RBP_final_name);
        File::delete($destinationPath."/".$filename_for_final_processing_final_name);
//  return $final_final_result;
return Redirect::to('result')->with('message',$final_final_result);
//return Redirect::to('result')->with('message',var_dump($final_final_result));




















  }
}
  // return 'Form passed validation!';
});
