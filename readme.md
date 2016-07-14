# Spliceman 2

## About

Spliceman 2 is a web application that assesses the likelihood of a 
substitution mutation to 
affect RNA splicing. It has been shown that mutations that heavily change the
positional distribution of sequences are more likely to affect splicing, so
it computes the L1 distance between the sequences before and after mutation 
and estimate the likelihood of it affecting splicing. It also computes the 
change in ESEseq score for exons, where larger changes are associated with 
affecting the likelihood of splicing. It finds the most common RBPs for the
sequences and the motifs that correspond. The tool is intended to aid in 
analyzing the potential for mutations that affect splicing on a large scale.

## Usage

### Uploading Data
To use Spliceman 2, go to the URL 
http://fairbrother.biomed.brown.edu/beta/upload. Data can be inputted in two 
ways.

For small inputs, data can be entered into the text box. One mutation can be 
expressed on each line. Include the chromosome number (written as "chrN" where
N is the number or simply as "N"), the location of the mutation on the 
chromosome as an 
integer, the base pair of the wild type, and the base pair of the mutant type,
with each input separated by white space.

For larger inputs, enter the data in a VCF file. The first column must be the
the chromosome number (writted as "chrN" or "N"), the second the 
location of the mutation on the chromosome, the third an optional id for the
mutation (ie "rs84825") or ".", the fourth the base pair of the wild type,
and the fifth the base pair of the mutant type. All following columns are
disregarded and need not be removed from VCF files. All rows not of this format
are disregarded; these do not need to be manually removed for the file to be
processed successfully.

The "Load Sample Data" button loads
two simple mutations to the text box that can be submitted.
The "Reset Values" button resets to allow for the input of different data. 
"Recommend Variants for Submission" brings users to a different page where
variants can be suggested to the Fairbrother lab to be added. "Process 
Sequences" proceeds to the next step.

### Processing Data

After submitting data, users are moved to an intermediate page. The URL of
this page is unique to each job. Thus, a user can return to this page at any time to check on their results. The page tracks progress in the pipeline that
processes input. The pipeline will run regardless of whether the page is open.

Once the process is complete, two or three buttons will appear on the page.
The "Download Results" button downloads a text file containing the outputs of
the pipeline.

### Viewing Results.

If the results are downloaded, the downloaded file organizes the data in
columns, where each row corresponds to one inputted mutation. 
- The entries in the five columns are the same as those from the
input file. 
- Columns 6 and 7 are the start and end locations of the intron or
exon that contains the mutation. 
- Column 8 is the gene that the mutation lies
on. 
- Column 9 is "+" if the sequence is read positively and "-" if read 
negatively. 
- Column 10 classifies the sequence containing the mutation between
"intron", "intron_terminal3", "intron_terminal5", "exon_terminal3",
"exon_terminal5", and "exon_internal". 
- Column 11 is the percentile of the
change in L1-distance. 
- If the mutation is on an exon, column 12 is the total change in ESEseq scores
and column 13 is an estimation of whether that scores affects splicing, where
"N" means "no effect," "S" means "suppressor," and "E" means "enhancer." If it
lies on an intron, both columns are "n/a".
- Columns 14 thru 18 are the five most likely RBPs in order.
- Columns 19 thru 23 are the motifs corresponding to each of the RBPs in the
preceding columns.

If the results are visualized, mutations are grouped by intron and exon. For
each intron or exon, there are three elements of the visualization. 
- An image displays the relative location of the mutations on the intron or 
exon.
- A table presents the corresponding RBPs and motifs to each mutation. If on an
exon, then ESEseq scores are also displayed in the table.
- A chart is used to compare the absolute values of the logs of the p-values
of the L1-distance percentiles. The most significant values have red bars, 
while all others have blue.

## Code Structure

Spliceman 2 is implemented using the Laravel framework. This section explains
which parts of the Laravel structure are used for different parts of the 
project.

### 'app/Http/routes.php'

## Database