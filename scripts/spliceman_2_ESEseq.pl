#!/usr/bin/perl -w 

###################################Fairbrother LAB##############################################
################################################################################################
###################### ESEseq Converter: genome.fa+ VCF => ESEseq ##############################
################################################################################################
####################################### Clayton Sanford ########################################
################################################################################################
##################### Usage: perl spliceman_2_ESEseq.pl ESEseq_table.csv input_file ############
################################################################################################
################################################################################################

use strict;
use warnings;
#Using BioPerl
use Bio::DB::Fasta; 
use Text::ParseWords;
use List::Util;
use CGI::Carp 'fatalsToBrowser';
#Arguments and Database 
my $ESEseq_table = shift;
my $output_spliceman = shift;
#my $db = Bio::DB::Fasta -> new( $hg19_L1_distance );

my @data;
open( my $csv, $ESEseq_table) or die "$ESEseq_table: $!";
while (my $line = <$csv>) {
    chomp $line;
    my @fields = Text::ParseWords::parse_line(',', 0, $line);
    push @data, \@fields;
}

open( my $fh, $output_spliceman ) or die "$output_spliceman: $!";

my $ESESEQ_ENHANCER = 0.5;
my $ESESEQ_SUPPRESSOR = -0.5;

my $HEXEMER_COLUMN = 1;
my $ESESEQ_COLUMN = 23;
my $SPLICING_EFFECT_COLUMN = 24;

while ( my $line = <$fh> ) {

 	chomp ( $line );

 	my @line_array=split("\t",$line);

 	my $sequence = $line_array[0];
 	#obtains versions of the 11-mer sequence without and with the point mutation
 	my $wild_sequence = substr($sequence, 0, 5).substr($sequence, 6, 1).substr($sequence, 10);
 	my $mut_sequence = substr($sequence, 0, 5).substr($sequence, 8, 1).substr($sequence, 10);

 	# finds all six possible hexemers contained in the 11-mer for both wild and mutant types
 	my @wild_subsequences = ();
 	my @mut_subsequences = ();
 	my @wild_ESEseq = ();
 	my @mut_ESEseq = ();
 	my $sum_ESEseq = 0;
 	for (my $i = 0; $i < 6; $i++) {
 		$wild_subsequences[$i] = substr($wild_sequence, $i, 6);
 		$mut_subsequences[$i] = substr($mut_sequence, $i, 6);
 		for (my $j = 0; $j < @data; $j++) {
 			if ($wild_subsequences[$i] eq $data[$j][$HEXEMER_COLUMN]) {
 				if ($data[$j][$SPLICING_EFFECT_COLUMN] eq 'N') {
 					$wild_ESEseq[$i] = 0;
 				}
 				else {
 					$wild_ESEseq[$i] = $data[$j][$ESESEQ_COLUMN];
 				}
 			}
 		}
 		#computes the sum of the differences of the ESEseq scores between mutant and wild types
 		$sum_ESEseq = $sum_ESEseq + $mut_ESEseq[$i] - $wild_ESEseq[$i];
 	}

 	#assigns a letter corresponding to whether the mutation is a splicing enhancer or suppressor, or has no net effect
 	my $splicing_effect = "?";
 	if ($sum_ESEseq > $ESESEQ_ENHANCER) {
 		$splicing_effect = "?";
 	}
 	elsif ($sum_ESEseq < $ESESEQ_SUPPRESSOR) {
 		$splicing_effect = "?";
 	}

 	#prints data into a text file to be used later

 	print $sum_ESEseq."\t".$splicing_effect."\t".join("\t", @line_array)."\n";

 	#print $sum_ESEseq."\t".$splicing_effect."\t".$line_array[1]."\t".$line_array[2]."\t".$line_array[3]."\t".$line_array[4]."\n";
}

close $fh;
close $csv;