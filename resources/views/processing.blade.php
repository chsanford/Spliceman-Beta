@extends('layout')
@section('content')


<div class="row centered-form">
  <div class="col-xs-12 col-sm-8 col-md-4 col-sm-offset-2 col-md-offset-4">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h1 class="panel-title">Results</h1>
        <br>
        <p>
        	This page links to your results. This URL will remain available for
        	later usage.
        </p>
        <br>
        <div id="progress">
          <b>Progress:</b>
          <?php
            //echo $progress;
          ?>
        </div>
      </div>
      <div class="panel-body" style="background: #f0f0f0">
        {!! Form::open(['files'=>true]) !!}
          <!--<div id='view_progress'>
            <div class='row'>
              <div class='col-xs-12 col-sm-12 col-md-12'>
                <input type='submit'
                  name='progress'
                  value='View Progress'
                  class='btn btn-info btn-block $progress_active'>
              </div>
            </div>
            <br>
          </div>-->
          <div id='download'>
            <div class='row'>
              <div class='col-xs-12 col-sm-12 col-md-12'>
                <input type='submit'
                  name='download' 
                  value='Download Results'
                  class='btn btn-info btn-block $results_active'>
              </div>
            </div>
            <br>
          </div>
          <div id='visualization'>
            <div class='row'>
              <div class='col-xs-12 col-sm-12 col-md-12'>
                <input type='submit'
                  name='visualization' 
                  value='View Visualization of Results'
                  class='btn btn-info btn-block $results_active'>
              </div>
            </div>
            <br>
          </div>
          <div id='errors'>
            <div class='row'>
              <div class='col-xs-12 col-sm-12 col-md-12'>
                <input type='submit'
                  name='errors' 
                  value='Download Errors'
                  class='btn btn-danger btn-block $errors_active'>
              </div>
            </div> 
          </div> 
        </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>

<script>
  var url = window.location.href;
  // Gets an array of all words in the url that are separated by slashes
  var url_array = url.split("/").filter(function(str) {return str});
  var fairbrother_site = (url_array[1] == "fairbrother.biomed.brown.edu");
  var id = url_array[url_array.length - 1];

  var download = document.getElementById('download');
  var visualization = document.getElementById('visualization');
  var errors = document.getElementById('errors');

  download.style.display = 'none';
  visualization.style.display = 'none';
  errors.style.display = 'none';

  var checked_errors = false;
  var errors_visible = false;

  if (fairbrother_site) {
    var final_directory_path = 
      "<?php 
        echo 'http://fairbrother.biomed.brown.edu/beta/uploads/'.$id.'_final';
      ?>";
  } else {
    var final_directory_path = 
      "<?php 
        echo 'http://138.16.174.16/beta/uploads/'.$id.'_final';
      ?>";        
  }

  var path_progress = final_directory_path + "/progress";
  var path_errors = final_directory_path + "/errors";

  window.setInterval("refreshDiv()", 100);
  function refreshDiv() {
    $(function() {
      $.get(path_progress, function(data) {
        progress = data;
        $('#progress').html("<b>Progress: </b>" + data);
      })
    });
    results_visible = (progress == "Job complete!");

    if (results_visible) {
      download.style.display = 'block';
      visualization.style.display = 'block';
      if (!checked_errors) {
        errors_http = new XMLHttpRequest();
        errors_http.open('HEAD', path_errors, false);
        errors_http.send();
        errors_visible = (errors_http.status!=404);
        checked_errors = true;
      }
      if (errors_visible) {
        errors.style.display = 'block';
      } else {
        errors.style.display = 'none';
      }
    } else {
      download.style.display = 'none';
      visualization.style.display = 'none';
      errors.style.display = 'none';
    }

  }


</script>

@stop 