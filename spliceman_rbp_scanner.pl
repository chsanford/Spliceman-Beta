#!/usr/bin/perl -w 

use strict;
use warnings;
use Bio::DB::Fasta; 
my $sequence_matrix_percentile_file = shift;
my $motif_length_file = shift;
my $conv_from_matrix_to_name_file = shift; 
my $seq_for_an = shift;
my $output_file = shift;
my $db = Bio::DB::Fasta -> new( $sequence_matrix_percentile_file );
my %matrix_length = ();
my %matrix_names = ();

open( my $fh, $seq_for_an ) or die "$seq_for_an: $!";
open( my $fh1, $motif_length_file ) or die "$motif_length_file: $!";
open( my $fh2, '>', $output_file ) or die "Couldn't create the output file: $!";
open( my $fh3, $conv_from_matrix_to_name_file ) or die "$conv_from_matrix_to_name_file: $!";

while ( my $line = <$fh1> ) {
	chomp $line; 
	my @matrix_array = split(/\s+/, $line);
	$matrix_length{$matrix_array[0]} = $matrix_array[1];
}

while ( my $line = <$fh3> ) {
	chomp $line; 
	my @matrix_array = split(/\s+/, $line);

	push (@{$matrix_names{$matrix_array[0]}}, $matrix_array[1]);
}

while ( my $line = <$fh> ) {

	my @rbp_name_final_array = ();
	my @rbp_scores_final_array = ();
	my @abs_value_final_array = ();

	chomp ( $line );
	my @sequences = split( /\s+/, $line );
	my $mut_seq = $sequences[1];
	my $wt_seq = $sequences[0];


	for(keys %matrix_length){
		my $motif_lg = $matrix_length{$_};
		my $matrix = $_;
		my $max_wt = 0;
		my $max_mut = 0;

		for(my $i = 0; $i < $motif_lg; $i++ ){ #correction 05/20/2015
			# my $mut_substr = substr($mut_seq, 8-$motif_lg, $motif_lg);
			# my $wt_substr = substr($wt_seq, 8-$motif_lg, $motif_lg);
			my $mut_substr = substr($mut_seq, 7-$i, $motif_lg); #correction 05/20/2015
			my $wt_substr = substr($wt_seq, 7-$i, $motif_lg); #correction 05/20/2015
			my $wt_score = $db -> seq( $matrix.":".$wt_substr);
			my $mut_score = $db -> seq( $matrix.":".$mut_substr);
			if($wt_score > $max_wt){
				$max_wt = $wt_score;
			}
			if($mut_score > $max_mut){
				$max_mut = $mut_score;
			}
		}
		for(my $r = 0; $r<scalar(@{$matrix_names{$_}}); $r++){
			my $rbp_name = ${$matrix_names{$_}}[$r];
			push(@rbp_name_final_array, $rbp_name);
			push(@rbp_scores_final_array, $max_mut-$max_wt);
			push(@abs_value_final_array, abs($max_mut-$max_wt));
		}
		
	}
	
	my @idx = sort { $rbp_scores_final_array[$a] <=> $rbp_scores_final_array[$b] } 0 .. $#rbp_scores_final_array;
	@rbp_name_final_array = @rbp_name_final_array[@idx];
	@rbp_scores_final_array = @rbp_scores_final_array[@idx];
	print $fh2 join("\t",@rbp_scores_final_array[0..4])."\t".join("\t",@rbp_name_final_array[0..4])."\n";

}


