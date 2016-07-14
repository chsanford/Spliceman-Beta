NR == 1 {sep=""}
NR > 2 {sep=","}
{
        if (NR == 1) {
                split($0, tags);
                if (EC == "") EC = "\"";
        }
        else {
                split($0, vals);
                jrec = sep"{";
                for (i = 1; i <= NF; ++i) {
                        if (vals[i] ~ /[^0-9.]/)
                                jrec = jrec EC tags[i] EC ":" EC vals[i] EC;
                        else
                                jrec = jrec EC tags[i] EC ":" vals[i];
                        if (i < NF)
                                jrec = jrec ", ";
                        else
                                jrec = jrec "}";
                }
                print jrec;
        }
}