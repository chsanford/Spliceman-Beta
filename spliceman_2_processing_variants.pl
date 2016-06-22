#!/usr/bin/perl -w 

###################################Fairbrother LAB##############################################
################################################################################################
###################### Spliceman Converter: genome.fa+ VCF => Spliceman ########################
################################################################################################
####################################### Kamil Cygan ############################################
################################################################################################
##################### Usage: perl vcf_fasta.pl fasta_reference_file vcf_file ###################
################################################################################################
################################################################################################

use strict;
use warnings;
#Using BioPerl
use Bio::DB::Fasta; 
#Arguments and Database 
my $hg19_L1_distance = shift;
my $output_spliceman = shift;
my $db = Bio::DB::Fasta -> new( $hg19_L1_distance );
open( my $fh, $output_spliceman ) or die "$output_spliceman: $!";


while ( my $line = <$fh> ) {

	chomp ( $line );
	my @line_array=split("\t",$line);
	my $sequence_for_spliceman = $db -> seq($line_array[0],0,$db -> length($line_array[0]));
	my @array = split(/\s+/, $sequence_for_spliceman);
	
	print join(":",@array)."\t".$line_array[1]."\t".$line_array[2]."\t".$line_array[3]."\t".$line_array[4]."\n";
}