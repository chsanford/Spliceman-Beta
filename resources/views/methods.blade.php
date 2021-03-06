
@extends('layout')

@section('content')


<body>
  <div class="container">
    <h1><b>Methods and Interpretations</b></h1><br />
              It was previously demonstrated that splicing elements are positional dependent. We exploited this relationship
              between location and function by comparing positional distributions between all possible 4,096 hexamers. The 
              distance measure used in this study found that point mutations that produced higher distances disrupted splicing, 
              whereas point mutations with smaller distances generally had no effect on splicing. Reasoning the idea that splicing 
              elements have signature positional distributions around constitutively spliced exons, we introduced Spliceman - 
              an online tool that predicts how likely distant mutations around annotated splice sites were to disrupt splicing.
              <br><br>
              
              The computational methods and algorithms are explained below:<br /><br /><br />
              <h3><b>1. Constructing exon databases</b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              Each exon/intron database was built from RefSeq annotations of the following assemblies stored
              at the UCSC Table Browser. 
              <br /><br />
              <ul>
                <li>Human (hg19) - 197,082 exons</li>
              </ul>
              Duplicated entries were screened and removed, and each sequence entry was divided into two distinct regions: <br /><br />
              <ul>
                <li>two 200-nucleotide intronic flanks and</li>
                <li>two 100-nucleotide exonic flanks</li>
              </ul>
              on each side of the splice sites (ss) (see figure below; intronic regions are represented in line and exonic region in box). In the case that intronic or exonic length was less than 400 or 200 
              nucleotides, respectively, the sequence was divided by half and each half was assigned to its nearest splice site.<br /><br />
              
              <center><img height="150" width="275" src="http://fairbrother.biomed.brown.edu/spliceman/images/methods/exon_length.png"><br /></center>
              </div>
              
              <br /><br /><br />
              <h3><b>2. Generating profiles:</b></h3>
              <div style="padding: 0px 0px 0px 20px;">
              <b><i>2.1 Why hexamers?</b></i><br />
              RNA binding proteins typically contain one to four RNA recognition motif domains so that motifs recovered are expected to be of heterogeneous 
              length. It is therefore unlikely that there is a single word size that is appropriate for all motifs presented in the data. 
              Previous implementations of dictionary methods illustrated how a smaller word size choice was generally self-correcting.
              Our analysis of prior SELEX studies indicated RNA binding proteins recognized motifs between the length of 6 and 10 nucleotides. 
              For these reasons, as well as computation efficiency, we selected hexamers for the analysis presented in this tool.
              <br /><br />
              
              <b><i>2.2 Counting hexamers</i></b><br />
              The algorithm traversed each position of the two following regions: upstream 3'ss and downstream 5'ss as illustrated in the figure above. 
              For each hexamer, the counting algorithm generated two vectors of 300 nucleotides, and each vector contained several pieces of information:
              <br /><br />
              <ul>
                <li>300 positions relative to splice sites,</li>
                <li>frequencies on each position,</li>
                <li>raw counts on each position, and</li>
                <li>the depth of the exon database on each position (mostly due to short exons, we keep track of the depth of the database on each position to generate positional frequencies).</li>
              </ul>
              Combining the two vectors - we called it the feature vector - would quantify the signature of a hexamer on positions relative to splice sites.
              For instance, plotting the positional frequencies of a feature vector for hexamer GCTGGG would produce a frequency plot as shown below: <br /><br />
              
              <center><img height="250" width="400" src="http://fairbrother.biomed.brown.edu/spliceman/images/methods/hexamer_profile.png"><br /><br /></center>
              
              Repeating this procedure for 4,096 times generated a feature vector for each hexamer.
              Because overlapping occurrences of internally repetitive words can occur more frequently than complex words, 
              overlapping occurrences of any words were counted as a single occurrence in a window of 11. For example, a run of 11 A's (i.e. AAAAAAAAAAA) 
              was counted as a single occurrence at the position where it was first observed.</i><br /><br /><br />
              
              </div>
              <b><h3>3. Computing distance matrix</b></h3><br />
              <div style="padding: 0px 0px 0px 20px;">
              This tool uses the L1 distance metric to qualify the "closeness" between two feature vectors (i.e. two hexamers). An obvious choice for distance metric 
              is the Euclidance distance; however, the sharp peaks created by the splice site hexamers themselves dominated the comparison 
              and prevented the detection of more subtle signals. This was remedied by using the Manhattan distance, also referred to as the city block distance or
              simply L1 distance. <br /><br />
              The L1 distance metric, as illustrated in the equation below, was calculated as the sum of the absolute value of the differences in normalized counts between the two feature vectors at each of the 
              600 positions.<br /><br />
              
              <center><img src="http://fairbrother.biomed.brown.edu/spliceman/images/methods/L1.png"><br /><br /></center>
              
              where <i>p</i> and <i>q</i> represent the normalized counts of two feature vectors at position <i>i</i> from -200 to 399.
              </div>
              
              <br /><br />
              
              <b><h3>4. Calculating percentile ranks</b></h3><br />
              <div style="padding: 0px 0px 0px 20px;">
              To allow standardized comparisons among L1 distances, 
              we converted these two variables into percentile ranks. This was archived by binning all L1 distances into 100 
              intervals (from 1 to 100) and assigning each L1 distance to its corresponding bin (i.e. a comparison between two hexamers that resulted 
              in low L1 distance would be assigned with a low percentile rank). The higher the percentile rank, the more likely the 
              point mutation is to disrupt splicing. 
              </div>
              
              <br /><br />

  </div>
</body>

@stop