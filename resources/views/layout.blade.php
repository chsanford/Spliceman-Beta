<!DOCTYPE html>
<html lang="en">
  <head>
  @if(Session::has('download.in.the.next.request'))
         <meta http-equiv="refresh" content="5;url={{ Session::get('download.in.the.next.request') }}">
  @endif
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fairbrother Lab Website</title>

    <!-- Bootstrap CSS served from a CDN -->
    <link
      href="http://netdna.bootstrapcdn.com/bootswatch/3.1.0/superhero/bootstrap.min.css"
    	 rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="/dashboard/spliceman_new/spliceman_new.css">
    <style>
    
    body{
      /*background: url("img/stardust.png");*/
  background: #fcfcfc;
    }

    .centered-form .panel{
      background: rgba(255, 255, 255, 0.8);
      box-shadow: rgba(0, 0, 0, 0.3) 20px 20px 20px;
      color: #4e5d6c;
    }
    .centered-form{
      margin-top: 60px;
    }
    </style>
    <link rel="shortcut icon" href="">
  </head>

  <body>




  <nav class="navbar navbar-default" role="navigation">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="http://fairbrother.biomed.brown.edu/">The Fairbrother Lab</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="http://fairbrother.biomed.brown.edu/spliceman/">Spliceman 1 <span class="sr-only">(current)</span></a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Spliceman 2 <span class="caret"></span></a>
          <ul class="dropdown-menu" role="menu">
            <li><a href="/beta/upload">HomePage</a></li>
            <li><a href="/beta/methods">Methods</a></li>
            <li><a href="/beta/help">Help Page</a></li>
            <!-- <li><a href="#">Results</a></li> -->
            <li class="divider"></li>
            <li><a href="/beta/sample">Sample Data</a></li>
          </ul>
        </li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>





    <div class="container">
      @yield('content')
    </div>

    <script src="/dashboard/public/scripts/jquery/jquery-2.1.1.js" type="text/javascript"></script>
    <script src="/dashboard/public/scripts/raphael/raphael.js"></script>
    <script src="/dashboard/public/scripts/raphael/g.raphael-min.js"></script>
    <script src="/dashboard/public/scripts/raphael/g.pie-min.js"></script>
    <script src="http://netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
	@yield('script')


  </body>
</html>



