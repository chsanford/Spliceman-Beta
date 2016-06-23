   #!/bin/bash
   awk -F"\t" '{split($16,a,":");}{print $1"\t"$3"\t"$4"\t"$8"\t"$9"\t"$10"\t"$11"\t"$13"\t"$14"\t"$15"\t"a[3]}' < $1 | awk -F"\t" '$8!="." && $14!="single_exon"{print}' #| sed -e '1i\chromosome_mutation\tmutation_pos\tfeature_chromosome\tfeature_start\tfeature_end\tgene\tstrand\tfeature\tL1_distance\tRBP_1\tRBP_2\tRBP_3\tRBP_4\tRBP_5' - #| awk -F "\t" -f tab2json.awk -
