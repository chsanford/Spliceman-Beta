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
	my $sequence_for_spliceman = $db -> seq($line,0,$db -> length($line));
	my @array = split(/\s+/, $sequence_for_spliceman);
	print join(":",@array)."\n";
}