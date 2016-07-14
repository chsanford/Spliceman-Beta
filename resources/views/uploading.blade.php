@extends('layout')

@section('content')

<div class="row centered-form">
  <div class="col-xs-12 col-sm-8 col-md-4 col-sm-offset-2 col-md-offset-4">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h1 class="panel-title">Spliceman 2</h1>
        <br>
        <p>Please either:</p>
        <li>upload your .vcf file</li>
        <li>input one variant per line using the format below</li>
      </div>
      <div class="panel-body" style="background: #f0f0f0">
      
      @if(Session::get('errors'))
        <div class="alert alert-danger alert-dismissable">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5>There were errors during processing:</h5>
        @foreach($errors->all('<li>:message</li>') as $message)
          {!!$message!!}
        @endforeach
        </div>
      @endif

        {!! Form::open(['files'=>true]) !!}

          <div class="form-group">
          {!! Form::textarea('sequence', null, array(
            'class' => 'form-control input-sm',
            'placeholder' => 
              '[chr variant_position(1-based) reference_allele alternative_allele] space separated; for example:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; chr20&nbsp;2301308&nbsp;T&nbsp;G',
            'size' => '30x5')) !!}
          </div>

          <div class="form-group">
            {!! Form::file('file', null, array('class'=>'btn btn-info btn-block','placeholder'=>'Choose File')) !!}
          </div>

          <div class="form-group">
            <!--{!! Form::checkbox('output', 'visualization') !!}
            View Visualization of Results-->
          </div>

          <div class="row">
            <div class="col-xs-6 col-sm-6 col-md-6">
              <input type="submit" name="process_sample_text" value="Load Sample Data" class="btn btn-info btn-block">
              <!-- {!! Form::submit('Process Sequences', array('class'=>'btn btn-info btn-block')) !!} -->
            </div>
            <div class="col-xs-6 col-sm-6 col-md-6">
              <input type="submit" name="reset" value="Reset Values" class="btn btn-info btn-block">
              <!-- {!! Form::reset('Reset Values', array('class'=>'btn btn-info btn-block')) !!} -->
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
              <input type="submit" name="recommend" value="Recommend Variants for Submission" class="btn btn-info btn-block">
          <!-- {!! Form::submit('Recommend variants for submission', array('class'=>'btn btn-info btn-block'))!!} -->
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
            
            <input type="submit" name="process" value="Process Sequences" class="btn btn-info btn-block">

              <!-- {!! Form::submit('Process Sequences', array('class'=>'btn btn-info btn-block')) !!} -->
            </div>
            </div>

          </div>


        {!! Form::close() !!}
      </div>
    </div>
  </div>
</div>

@stop
