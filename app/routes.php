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

function splicemanPipeline($inputFileContents, $visualization) {

    //return $inputFileContents;

    $mutations = explode("\n", $inputFileContents);

    $servername = "localhost";
    $username = "root";
    $password = "4y8B2cx9";
    $dbname = "spliceman_database";
    $table = "Spliceman";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    $mutations_in_db_output = "";
    $mutations_not_in_db = "";

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 

    for ($i = 0; $i < count($mutations) - 1; $i++){
        $mutation_data = explode("\t", $mutations[$i]);
        if (count($mutation_data) >= 5) {
            $db_value = 
                $mutation_data[0]."_".$mutation_data[1]."_"
                    .$mutation_data[3]."_".$mutation_data[4];
            $sql = "SELECT * FROM $table WHERE chr_loc_wild_mut = '$db_value'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                // output data of each row
                $row = $result->fetch_assoc();
                $RBP_data = explode("_", $row["RBPs"]);
                if ($row["enhancer_repressor"] == "-") {
                    $ESE_seq = "n/a";
                    $ESE_effect = "n/a";
                } else {
                    $ESE_seq = $row["ESE_seq"];
                    $ESE_effect = $row["enhancer_repressor"];
                }
                $output_array = 
                    [$mutation_data[0], $mutation_data[1], $mutation_data[2],
                    $mutation_data[3], $mutation_data[4],
                    $row["start_loc"], $row["end_loc"], $row["gene"],
                    $row["pos_neg"], $row["strand_type"], $row["L1_percentile"],
                    $RBP_data[0], $RBP_data[1], $RBP_data[2], $RBP_data[3],
                    $RBP_data[4], $ESE_seq, $ESE_effect];
                $mutations_in_db_output = 
                    join($output_array, "\t")."\n".$mutations_in_db_output;
            } else {
                $mutations_not_in_db = $mutations[$i]."\n".$mutations_not_in_db;
            }
        }   
    }    

    $conn->close();

    $destinationPath = public_path().'/uploads';

    $filename_input = str_random(12);
    $filename_database = str_random(12);
    $filename_spliceman = str_random(12);
    $filename_spliceman = str_random(12);
    $filename_RBPs = str_random(12);
    $filename_bedtools_new = str_random(12);
        
    $path_input = $destinationPath."/".$filename_input;
    $path_database = $destinationPath."/".$filename_database;
    $path_spliceman = $destinationPath."/".$filename_spliceman;
    $path_RBPs = $destinationPath."/".$filename_RBPs;
    $path_bedtools_new = $destinationPath."/".$filename_bedtools_new;

    $non_empty_db_file = ($mutations_in_db_output != "");
    $non_empty_new_file = ($mutations_not_in_db != "");
    if ($non_empty_new_file) {
        $upload_success = file_put_contents($path_input, $mutations_not_in_db);
    } else {
        $upload_success = false;
    }

    if ($non_empty_db_file) {
        file_put_contents($path_database, $mutations_in_db_output);
    }

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
            File::delete($path_database);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            return pipeline_error(
                "Error in pipeline, please contact administrator
                and provide step 1");
        }

        if (count($output) > 0){
            File::delete($path_input);
            File::delete($path_database);
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
            File::delete($path_database);
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
            File::delete($path_database);
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
            File::delete($path_database);
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
            File::delete($path_database);
            File::delete($path_spliceman);
            File::delete($path_RBPs);
            File::delete($path_bedtools_new);
            File::delete($path_bedtools_final);
            File::delete($path_L1_distance_final);
            File::delete($path_ESEseq_final);
            File::delete($path_RBPs_final);

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
            File::delete($path_database);
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

        $filename_final = str_random(12);
        $path_final = $destinationPath."/".str_random(12);
        $path_download = $destinationPath."/"."file_to_download.txt";
        exec(
            "bash /var/www/html/spliceman_beta/process_final.sh\
            '$path_final_processing_final'\
            > '$path_final'", 
            $final_final_result, 
            $return);

        //return file_get_contents($path_final_processing_final).".......".file_get_contents($path_final);

        if ($non_empty_db_file) {
            shell_exec(
                "cat\
                '$path_final'\
                '$path_database'\
                > '$path_download'");
        } else {
            shell_exec(
                "cp\
                '$path_final'\
                '$path_download'");
        }

        File::delete($path_input);
        File::delete($path_database);
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
        File::delete($path_final);

        // Add new elements to database.
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $database_entries = explode("\n", file_get_contents($path_download));

        $stmt = $conn->prepare(
            "INSERT INTO $table VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisssisds", $chr_loc_wild_mut, $start_loc, $end_loc,
            $gene, $pos_neg, $strand_type, $L1_percentile, $RBPs, $ESE_seq,
            $enhancer_repressor);

        foreach ($database_entries as $database_entry) {
            $entry_array = explode("\t", $database_entries[0]);
            $chr_loc_wild_mut = $entry_array[0]."_".$entry_array[1].
                "_".$entry_array[3]."_".$entry_array[4];
            $start_loc = $entry_array[5];
            $end_loc = $entry_array[6];
            $gene = $entry_array[7];
            $pos_neg = $entry_array[8];
            $strand_type = $entry_array[9];
            $L1_percentile = $entry_array[10];
            $RBPs = $entry_array[11]."_".$entry_array[12]."_".$entry_array[13].
                "_".$entry_array[14]."_".$entry_array[15];
            if ($entry_array[17] == "n/a") {
                $ESE_seq = 0;
                $enhancer_repressor = "-";
            } else {
                $ESE_seq = $entry_array[16];
                $enhancer_repressor = $entry_array[17];
            }
            $stmt->execute();
        }

        $stmt->close();
        $conn->close();

        if ($visualization) {
            $final_output = file_get_contents($path_download);
            return Redirect::to('result')->with('message',$final_output);
        } else {
            return Response::download($path_download);
        }

    } else {
        if ($non_empty_db_file) {
            $path_download = $destinationPath."/"."file_to_download.txt";
            shell_exec(
                "cp\
                '$path_database'\
                '$path_download'");
             if ($visualization) {
                $final_output = file_get_contents($path_download);
                return Redirect::to('result')->with('message',$final_output);
            } else {
                return Response::download($path_download);
            }
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
