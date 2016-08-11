#!/bin/bash
awk -F"\t" '{split($27,a,":");} \
	{if ($24 == "intronic_variant")\
		ESEseq = "n/a\tn/a"; else ESEseq = $43"\t"$44;} \
	{print $1"\t"$4"\t"$6"\t"$7"\t"$8"\t"$10"\t"$11"\t"$12"\t"$13"\t"$14"\t"$15"\t"$20"\t"$22"\t"$23"\t"$24"\t"a[3]"\t"ESEseq"\t"$33"\t"$34"\t"$35"\t"$36"\t"$37"\t"$38"\t"$39"\t"$40"\t"$41"\t"$42}' \
	< $1 | awk -F"\t" '$9!="." && $14!="single_exon"{print}' 
