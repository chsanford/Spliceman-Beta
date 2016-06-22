@extends('layout')
@section('content')
<body>
  <div class="container">
    <h1><b>Inputs and Input Form Description:</b></h1><br />
              
              <br><br>
              <center><img src="http://fairbrother.biomed.brown.edu/beta/inputs.png"><br /><br /></center>
              Either (1) or (2) is required for Spliceman 2. If the format that the user inputs is not correct or if there are any variants in the input that fall outside gene boundaries the program will return appropriate error message to the user.
              <h3><b>1. Input variants in text field </b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              Spliceman can handle two different input formats. Here, you can input at most 5 mutations. Each mutation has to be in a single line and include: chromosome, variant_position(1-based), reference_allele, and alternative_allele; space separated, as shown in the placeholder. Spliceman 2 only accepts GRCh37 assembly coordinates. <br></br>
              Alternatively, you can click on Load Sample Data button (6). That will input two sample variants in the text field that will show the correctly formatted input. To process that sample just follow with a click on (3) Process Sequences Button. 
              <br /><br />
              </div>
              <h3><b>2. Input variants as .vcf file </b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              Spliceman can handle two different input formats. Here, you can upload .vcf file that is limited by size to 10MB. Detailed description of .vcf format can be found <a href="http://samtools.github.io/hts-specs/VCFv4.2.pdf">HERE</a>. Spliceman 2 only accepts GRCh37 assembly coordinates.
              <br /><br />
              </div>
              <h3><b>3. Process Sequences button</b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              This button is submitting the form for evaluation by the Spliceman 2 algorithm. 
              <br /><br />
              </div>
              <h3><b>4. Reset Form button</b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              This button clears the form from any input values. 
              <br /><br />
              </div>
              <h3><b>5. Recommend variants for submission button</b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              Now offers users the opportunity to recommend variants for submission to our high-throughput in vitro and in vivo splicing assays. If users select this option, they are asked to opt-out of the default permission (i.e., anonymous users and private data) and register on our internal portal.
              <br /><br />
              </div>
              <h3><b>6. Load Sample Data button</b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              Load two sample variants in the text field that will show the correctly formatted input.
              <br /><br />
              </div>
              
    
    <h1><b>Interpretation of the Outputs:</b></h1><br />
    Live output page that is described below can be seen <a href="/beta/output_sample">HERE</a>.
    		<br><br>
              <center><img src="http://fairbrother.biomed.brown.edu/beta/outputs.png"><br /><br /></center>
              <h3><b>1. Visualization of the variants </b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              Visualization of the locations – within exons and introns – of analyzed sequence variants. Variants are shown as red vertical lines, exons are blue rectangles introns are black horizontal lines.  
              <br /><br />
              </div>
              <h3><b>2. Table of RNA-binding proteins </b></h3>
              <div style="padding: 0px 0px 0px 20px;">
			  Table of prediction of which RNA-binding proteins are mostly disrupted by the variant. The table also lists location of each processed variant.            
			  <br /><br />
              </div>
              <h3><b>3. Bar plots of splicing effects</b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              Predicted effects on splicing of the pre-mRNA transcript of each variant. The reported value is the absolute value of log base 10 of p-value. For example, value grater than 1.3 means p-value less than 0.05 that the variant is disrupting splicing. The user can mouse-over the bars to see the exact value for each variant. The output also includes location of each variant as a coordinate as well as how far it is from a particular splice site. 
              <br /><br />
              </div>

  </div>
</body>
@stop 