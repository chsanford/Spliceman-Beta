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

Route::get('dashboard', function() {
    return View::make('dashboard');
});

Route::get('methods', function() {
    return View::make('methods');
});

Route::get('sample_download', function() {
    return Response::download("example_2.vcf");
});

Route::get('queue', "QueueController@index");

Route::get('output_sample', function() {
    return "hi";
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

Route::post('upload', "UploadController@index");

Route::get('processing/{id}', function($id) {
    return View::make('processing')->with('id', $id);
});

Route::post('processing/{id}', "ProcessingController@index");
