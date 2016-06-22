<html>
    <head>
        <link rel="stylesheet" type="text/css" href="./spliceman_new.css">
        <script src="/dashboard/public/scripts/jquery/jquery-2.1.1.js" type="text/javascript"></script>
        <script src="../public/scripts/raphael/raphael.js"></script>
        <script src="../public/scripts/raphael/g.raphael-min.js"></script>
        <script src="../public/scripts/raphael/g.pie-min.js"></script>
	   <script src="/dashboard/public/scripts/highstocks//highstock.js"></script>
    </head>
    <body>
        <div id="header">
            <a class="header-title" href="http://fairbrother.biomed.brown.edu/">
                The Fairbrother Lab
            </a>
        </div>
        <div id="visBody">
            
        </div>
    </body>
<script>
    //data format:
    //mutposition
    //beginning
    //end
    //+/1
    //gene
    //L1 distance
    //RBP names
    drawVisualization();
    //input: container div for diagram, array of reldists, whether to draw intron or exon
    //output: diagram added to container
function drawDiagram(relDistArray){
    console.log(relDistArray);
    var canvas = Raphael(relDistArray.container.get(0),"100%","100%");
    if(relDistArray.isExon) {
        addIntron(100,200,100,2,canvas,[]);
        addExon(200,400,50,100, canvas, "#aaaaaa", "asdf", [relDistArray.dist]);
        addIntron(400, 500, 100, 2, canvas, []);
    } else {
        addExon(100, 200, 50, 100, canvas, "#aaaaaa", "asdf", []);
        addIntron(200, 400, 100, 2, canvas, [relDistArray.dist]);
        addExon(400, 500, 50, 100, canvas, "#aaaaaa", "asdf", []);
    }
    
    
}
    
//input: container div for chart,  mutationObj JSON object containing gene name, mutation position, L1 distance
//output: chart added to container
function drawChart(mutationObj){
    console.log(mutationObj);
    mutationObj.container.highcharts({
        title: {
            text: ""
        },
        xAxis: {
            categories: [mutationObj.gene + ": " + mutationObj.mut_pos]
        },
        yAxis: {
            title: "L1 Distance"
        },
        
        series: [{
            name: "L1 Distances",
            type: "column",
            data: [mutationObj.L1],
            pointWidth: 20
        }]
    });
}
    
function parseSpliceString(ss){
    var res = [];
    for(j = 0; j< ss.length; j++) {
        res[j] = ss[j].split(/\t/);
    }
    var data = [];
    for(i = 0; i < res.length; i++) {
        data[i] = {};
        data[i].chr1 = res[i][0];
        data[i].mut_pos = res[i][1];
        data[i].chr2 = res[i][2];
        data[i].beg_exon = res[i][3];
        data[i].end_exon = res[i][4];
        data[i].gene = res[i][5];
        data[i].strand = res[i][6];
        data[i].terminal = res[i][7];
        data[i].l1_dist = res[i][8];
        data[i].rbps = [];
        for(j = 0; j < 5; j++) {
            data[i].rbps[j] = res[i][9+j];
        }
        
    }
    return data;
}
function drawVisualization(){
    var mutationData = <?php echo json_encode($_POST)?>;
    //testing
    console.log(mutationData);
    temp = mutationData;
    mutationData = [];
    mutationData[0] = temp;
    console.log(mutationData.length);
    console.log(<?php echo $_SESSION['data'] ?>);
    //mutationData = parseSpliceString(mutationData);
    console.log("stuffs");
    console.log(mutationData[0]);
    var relDists = [];
    var chartData = [];
    for(var i=0; i<mutationData.length;i++){
        console.log("here");
        //var terminal = mutationData[i].terminal
        //terminal: 0 = internal, 1 = terminal, 2 = intron
        var terminal = i%3;
        var isExon = false;
        var relDist;
        var mut_pos = parseInt(mutationData[i].mut_pos);
        var beg_exon = parseInt(mutationData[i].beg_exon);
        var end_exon = parseInt(mutationData[i].end_exon);
        chartData[i] = {};
        relDists[i] = {};
        //temporary test alternate between exon/intron
        relDists[i].isExon = i%2==1;
        
        //calculate reldist
        if(mutationData[i].strand == "+"){
            relDist = (mut_pos - beg_exon)/(end_exon-beg_exon);
        } else {
            relDist = 1 - (mut_pos - beg_exon)/(end_exon-beg_exon);
        }
        switch(terminal){
            //if
            case 0:
            case 1:
                isExon = true;
                break;
            case 2:
                isExon = false;
        }
        
        relDists[i].dist = relDist;
        relDists[i].container = $("<div class='exon-diagram' id=diagram" + i + "></div>");
        $("#visBody").append(relDists[i].container);
        
        chartData[i].L1 = parseInt(mutationData[i].l1_dist);
        chartData[i].gene = mutationData[i].gene_name;
        chartData[i].mut_pos = parseInt(mutationData[i].mut_pos);
        chartData[i].container = $("<div id=chart" + i + "></div>")
        $("#visBody").append(chartData[i].container);
    }
    console.log(relDists);
    console.log(chartData);
    for(var i = 0; i < mutationData.length; i++) {
        console.log("here2");
        drawDiagram(relDists[i]);
        drawChart(chartData[i]);
        
    }
}

function addText(center,vertical,text,canvas,size){
            return canvas.text(center,vertical,text).attr('font-size',size).attr('fill','#e96d63').attr('font-weight','700');
        }
    
        function addExon(start, end, vertical, height, canvas, color, text, mutLocation){
            var test = canvas.rect(start,vertical,end-start,height).attr("fill",color).attr("stroke-width",3).attr("stroke",color);
            if(mutLocation.length > 0){
                for(var i = 0; i < mutLocation.length; i++){
                    var loc = mutLocation[i];
                    var temp = canvas.path("M" + ((end-start)* loc + start) + " " + (vertical-2) + "l" + 0 + " " + (height+4)).attr("stroke-width", 2).attr("fill", "#AAAAAA").attr("stroke","#e96d63").attr("cursor","pointer");
                }
            }
            return test;

        }
        function addIntron(start, end, vertical,width,canvas, mutLocations){
            var temp = canvas.path("M"+start+" " + vertical+ "l"+(end-start)+" 0").attr("stroke-width",width).attr("stroke","#000000")
            if(mutLocations.length > 0) {
                for(var i = 0; i < mutLocations.length; i++) {
                    var loc = mutLocations[i];
                    var mutLine = canvas.path("M" + ((end - start) * loc + start) + " " + (vertical - 5) + "l" + 0 + " " + (10)).attr("stroke-width", 2).attr("fill", "#aaaaaa").attr("stroke", "#e96d63").attr("cursor","pointer");
                    console.log("M" + ((end - start) * loc + start) + " " + (vertical - 5) + "l" + 0 + " " + 5);
                }
            }
            return temp;
        }
        function addSplice(start,end,vertical,height,width,canvas, down){
            var animatePath = function(path, duration, attributes) {
                if (!duration) duration = 1500;
                if (!attributes) attributes = {};
                var len = path.getTotalLength();
                var previous;
                path.hide();
                $(path.node).animate({
                        'to': 1
                    }, {
                        'duration': duration,
                        'step': function(pos, fx) {
                            var offset = len * fx.pos;
                            var subpath = path.getSubpath(0, offset);
                            if (previous) previous.remove();
                            if(!(path.id)) return;
                            previous = path.paper.path(subpath).attr(attributes);
                        },
                        'complete': function() {
                            previous.remove();
                            path.show();
                        }
                    }
                );
            };
            var segLen = end-start;
            var temppath = canvas.path("M" + start+" "+vertical +" l"+segLen/2 + " "+(down?height:-height)+" l"+segLen/2+ " " + (down?-height:height)).attr("stroke-width",width).attr("stroke","#ff8020")
            //var temppath = canvas.path("M" + start+" "+vertical).attr("stroke-width",width).attr("stroke","#ff8020").hide();
            //temppath.animate({path:("M" + start+" "+vertical +" l"+segLen/2 + " "+(down?height:-height)+" l"+segLen/2+ " " + (down?-height:height))},5000)
            animatePath(temppath,200,{
                'stroke-width':width,
                'stroke':"#ff8020"
            });
            //return canvas.path("M" + start+" "+vertical +" l"+segLen/2 + " "+(down?height:-height)+" l"+segLen/2+ " " + (down?-height:height)).attr("stroke-width",width).attr("stroke","#ff8020")
            return temppath;
        }
        function addArrow(start,end,vertical,height,width,canvas){
            var segLen = end-start;
            return canvas.path("M" + start+" "+vertical +" l"+segLen + " "+0+" l"+"-"+segLen/5+ " " + "-"+segLen/10 + " m"+segLen/5 + " " + segLen/10 + " l" + "-" + segLen/5 + " " + segLen/10).attr("stroke-width",width).attr("stroke","#000")
        }
        
        
        function addAltSplice(alt,canvas,down){
            switch(alt){
                case 3:
                    return addSplice(150,200,50,30,2,canvas, down);
                    break;
                case 5:
                    return addSplice(300,350,50,30,2,canvas, down);
                    break;  
            }
        }
</script>    
</html>
