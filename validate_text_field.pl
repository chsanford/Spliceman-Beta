#!/usr/bin/perl -w 

use strict;
use warnings;
#Using BioPerl
use Bio::DB::Fasta; 
#Arguments and Database 
my $fastaFile = shift;
my $new_file = shift;

open( my $fh, $fastaFile ) or die "$fastaFile: $!";
open( my $fh1, ">", $new_file) or die "$new_file: $!";

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
	
	if(scalar(@fields)!=4){
		print "Incorrectly formatted input\n";
		exit;
	}
else{
	splice @fields, 2, 0, '.';
	print $fh1 $fields[0]."\t".$fields[1]."\t".$fields[2]."\t".$fields[3]."\t".$fields[4]."\n";
}

}