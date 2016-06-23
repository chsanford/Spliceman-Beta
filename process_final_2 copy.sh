   #!/bin/bash
   awk -F"\t" '{split($12,a,":");}{print $1"\t"$2"\t"$4"\t"$5"\t"$6"\t"$7"\t"$9"\t"$10"\t"a[3]"\t"$18"\t"$19"\t"$20"\t"$21"\t"$22}' < $1 | awk -F"\t" '$3=="." || $8=="single_exon"{print $1"\t"$2}' | (echo 'The following variants fall outside gene boundaries, please remove them from the dataset:'; cat - ) #| sed -e '1i\chromosome_mutation\tmutation_pos\tfeature_chromosome\tfeature_start\tfeature_end\tgene\tstrand\tfeature\tL1_distance\tRBP_1\tRBP_2\tRBP_3\tRBP_4\tRBP_5' - #| awk -F "\t" -f tab2json.awk -
