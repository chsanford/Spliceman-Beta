   #!/bin/bash
   awk -F"\t" '{split($17,a,":");} \
   {if ($15 == "intron") ESEseq = "n/a\tn/a"; else ESEseq = $18"\t"$19;} \
   {print $1"\t"$3"\t"$4"\t"$7"\t"$8"\t"$10"\t"$11"\t"$12"\t"$14"\t"$15"\t"a[3]"\t"ESEseq"\t"$20"\t"$21"\t"$22"\t"$23"\t"$24"\t"$25"\t"$26"\t"$27"\t"$28"\t"$29}' < $1 | awk -F"\t" '$9!="." && $14!="single_exon"{print}' #| sed -e '1i\chromosome_mutation\tmutation_pos\tfeature_chromosome\tfeature_start\tfeature_end\tgene\tstrand\tfeature\tL1_distance\tRBP_1\tRBP_2\tRBP_3\tRBP_4\tRBP_5' - #| awk -F "\t" -f tab2json.awk -