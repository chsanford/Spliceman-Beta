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
my $fastaFile = shift;
my $vcfFile = shift;
my $output_spliceman = shift;
my $output_RBPs = shift;
my $output_bed = shift;
my $db = Bio::DB::Fasta -> new( $fastaFile );
# my $counter = 1;

# my $sequence = $db -> seq( "chr1",11579865,11579875 );

# print $sequence."\n";

# print "\nConnecting to fasta database\n\n";

#Open / Create files
open( my $fh, $vcfFile ) or die "$vcfFile: $!";
open( my $fh1, '>', $output_spliceman ) or die "Couldn't create the spliceman_output file: $!";
open( my $fh2, '>', $output_RBPs ) or die "Couldn't create the RBPs_output file: $!";
open( my $fh3, '>', $output_bed ) or die "Couldn't create the RBPs_output file: $!";


# print "Deleting header lines from .vcf file\n\n";

while ( my $line = <$fh> ) {

	chomp ( $line );

#Delete the line if it starts with # sign
	if ( $line =~ /^\s*#/ ) {
		next;
	}

	if ( $line =~ /^\s*\"/ ) {
		next;
	}



#Feed columns of the tab-file (vcf) into array
	my @fields = split( /\s+/, $line );

	if($fields[0] =~ /^[+-]?\d+$/){
		$fields[0] = "chr".$fields[0];
	}

	# $fields[0] = lc($fields[0]);

	if (index($fields[0], "chr") != -1) {
	} 
	else{
		print "Incorrectly formatted input\n";
		exit;
	}
	if ($fields[1] =~ /^[+-]?\d+$/ ) {
	} 
	else {
    	print "Incorrectly formatted input\n";
    	exit;
	}
	if ($fields[2] =~ /^[+-]?\d+$/ ) {
		print "Incorrectly formatted input\n";
    	exit; 
	} 
	if ($fields[3] =~ /^[+-]?\d+$/ ) {
		print "Incorrectly formatted input\n";
    	exit; 
	} 
	



#check if it is a SNP
	if ( ($fields[4] eq "A" || $fields[4] eq "C" || $fields[4] eq "G" || $fields[4] eq "T" || $fields[4] eq "a" || $fields[4] eq "c" || $fields[4] eq "g" || $fields[4] eq "t") && ($fields[3] eq "A" || $fields[3] eq "C" || $fields[3] eq "G" || $fields[3] eq "T" || $fields[3] eq "a" || $fields[3] eq "c" || $fields[3] eq "g" || $fields[3] eq "t") ) {
	}
	else{
		next;
	}



#check if the mutation is not too close to the beginning of FASTA sequence
	if ( $fields[1] > 7 ) {

#get the sequence from FASTA file 5 nucleotides from each side of the mutation
		my $sequence_for_spliceman = $db -> seq( $fields[0],$fields[1]-5,$fields[1]+5 );
		my $sequence_for_RBP = $db -> seq( $fields[0],$fields[1]-7,$fields[1]+7 );

#check if the sequence exists in the FASTA file
			if ( !defined ( $sequence_for_spliceman ) ) {
			print "sequence_for_spliceman $fields[0] not found.\n";
			exit;
		}

#check if the mutation is not too close to the end of FASTA sequence_for_spliceman
			# if( length( $sequence_for_spliceman ) < 11 ) {
			# 	print "Fasta reference sequence_for_spliceman to short for a variant on your list!!!\n\n";
			# 	next;
			# }

#Feed the sequence_for_spliceman into array for editing 
		my @fasta_sequence_for_spliceman = split ( //,$sequence_for_spliceman );
		my @fasta_sequence_for_RBP = split ( //,$sequence_for_RBP );

#Check if you are in the right place in the reference - sanity check
		if (uc($fields[3]) eq uc($fasta_sequence_for_spliceman[5])){
			# print "Yes! Reference sequence_for_spliceman at position\t $fields[1]\t is a match!\n"
		}
		else {
			print "Please enter valid GRCh37/hg19 coordinates.\n";
			exit;
		}

#record the mutation in the sequence_for_spliceman
		$fasta_sequence_for_spliceman[5] = "($fasta_sequence_for_spliceman[5]/$fields[4])";
		my $wt_sequence_RBP = $sequence_for_RBP;
		my $mut_sequence_RBP = join("",@fasta_sequence_for_RBP[0..6]).$fields[4].join("",@fasta_sequence_for_RBP[8..14]);

#Convert the sequence_for_spliceman array back to a string
		my $final_fasta_seq = join( "",@fasta_sequence_for_spliceman );

#Print the sequence_for_spliceman to the output file
		# print $fh1 ">$fields[0]\t$fields[1]\n".uc($final_fasta_seq)."\n";
		print $fh1 uc($final_fasta_seq)."\t".$fields[2]."\t".$fields[0]."\t".$fields[1]."\t".$fields[3]."\t".$fields[4]."\n";
		print $fh2 uc($wt_sequence_RBP)."\t".uc($mut_sequence_RBP)."\t".$fields[2]."\t".$fields[0]."\t".$fields[1]."\t".$fields[3]."\t".$fields[4]."\n";
		print $fh3 $fields[0]."\t".($fields[1]-1)."\t".$fields[1]."\t".$fields[2]."\t".$fields[0]."\t".$fields[1]."\t".$fields[3]."\t".$fields[4]."\n";
		}
		# else {
		# 	print "It was not possible to convert this variant, because the mutation is
		# 	located to close to the end of reference sequence_for_spliceman!";	
		#  }
		 # $counter = $counter + 1;
	}

#Close the files 
close ( $fh ) or die "Couldn't close the vcf file: $!";
close ( $fh1 ) or die "Couldn't close the spliceman output file: $!";
close ( $fh2 ) or die "Couldn't close the RBPs_output file: $!";
close ( $fh3 ) or die "Couldn't close the RBPs_output file: $!";


#Show that the algorithm completed all tasks
# print "Convertion completed!\n\n";


