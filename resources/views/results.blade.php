@extends('layout')

@section('content')

<link rel="stylesheet" type="text/css" href="/dashboard/spliceman_new/spliceman_new.css">
<h1 id="visHead">
    Spliceman Results
</h1>
<div id="visBody"></div>
@stop
@section('script')
<script>

$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip(); 
});
$(function() {
	drawVisualization();
});
    //input: container div for diagram, array of reldists, whether to draw intron or exon
    //output: diagram added to container
function drawDiagram(relDistArray){
    var canvas = Raphael(relDistArray.container.get(0),"100%","100%");
    var color = "#39b3d7";
    
    if(relDistArray.isExon) {
        addIntron(100,200,100,2,canvas,[], '');
        addExon(200,400,100,50, canvas, color, relDistArray.name, [relDistArray.dist]);
        addIntron(400, 500, 100, 2, canvas, [], '');
    } else {
        addExon(100, 200, 100, 50, canvas, color, '', []);
        addIntron(200, 400, 100, 2, canvas, [relDistArray.dist], relDistArray.name);
        addExon(400, 500, 100, 50, canvas, color, '', []);
    } 
}

function drawDiagramGroup(dataGroup) {	
    var canvas = Raphael(dataGroup.diagramcontainer.get(0),"100%","100%");
    var color = "#39b3d7";
//function addText(center,vertical,text,canvas,size){
    if(dataGroup.isExon) {
        addIntron(100,200,100,4,canvas,[], '');
        addExon(200,400,75,50, canvas, color, dataGroup.gene + " - " + dataGroup.name, dataGroup.relDist);
        addIntron(400, 500, 100, 4, canvas, [], '');
    } else {
        addExon(100, 200, 75, 50, canvas, color, '', []);
        addIntron(200, 400, 100, 6, canvas, dataGroup.relDist, dataGroup.gene + " - " + dataGroup.name);
        addExon(400, 500, 75, 50, canvas, color, '', []);
    }
}

function drawChartGroup(dataGroup) {
    var sort = dataGroup.relDist;
    var name = dataGroup.label;
    var data = dataGroup.l1_dist;
    var items = [];


    data.sort(function(a,b){
        var i1 = data.indexOf(a);
        var i2 = data.indexOf(b);
        return sort[i1]-sort[i2];
    });

    name.sort(function(a,b){
        var i1 = name.indexOf(a);
        var i2 = name.indexOf(b);
        return sort[i1]-sort[i2];
    });
    dataGroup.chartcontainer.highcharts({

        title: {
            text: "absolute value of log10(p-values) By Mutation Position"
        },
        xAxis: {
            labels: {
                style:{
                    width: '130px',
                    fontSize: "14px"
                }
            },
            categories: name
        },
        yAxis: {
            labels: {
                style: {
                    fontSize: "18px"
                }
            },
            title: {
  	        text: 'absolute value of log10(p-value)',
                style:{
                    fontSize: "16px"
                }
            }
        },
        legend: {
            enabled: false
        },
        series: [{
            name: "absolute value of log10(p-value)",
            type: "column",
            data: data, 
            zones: [{value: 1.3, color: "#39b3d7"}, {color: "#e96d63"}], 
            pointWidth: 20}]

           
        
    });
}
    
//input: container div for chart,  mutationObj JSON object containing gene name, mutation position, absolute value of log10(p-value)
//output: chart added to container
function drawChart(mutationObj){
    mutationObj.container.highcharts({
        title: {
            text: "absolute value of log10(p-values)"
        },
        xAxis: {
            categories: [mutationObj.gene + ": " + mutationObj.mut_pos]
        },
        yAxis: {
            title: {
			text: 'absolute value of log10(p-value)'
		}
        },
        legend: {
		enabled: false
},
        series: [{
            name: "absolute value of log10(p-value)",
            type: "column",
            data: mutationObj.L1,
            pointWidth: 20
        }]
    });
}

function populateTableGroup(dataGroup){
    var container = dataGroup.tablecontainer;
    var table = $("<table class=rbp-table></table>");
    var headrow = $("<tr></tr>");

    headrow.append("<th class=rbp-table-head>Mutation</th>");
    var header = headrow.append(
        "<th class=rbp-table-head colspan = 5>RBPs with Motifs</th>");
    if (dataGroup.isExon) {
        headrow.append("<th class=rbp-table-head>ESEseq</th>");
    }
    table.append(headrow);
    for(var i = 0; i < dataGroup.rbps.length; i++) {
        var newrow = $("<tr class=rbp-row></tr>");
        newrow.append("<th>" + dataGroup.mut_pos[i] + ":<br> " + 
            dataGroup.ref_base[i] + " &rarr; " + dataGroup.mut_base[i] + "</th>");
        for(var j = 0; j < dataGroup.rbps[i].length; j++) {
            newrow.append(
                "<td>" + dataGroup.rbps[i][j] + "<br>" +
                "<a href = http://fairbrother.biomed.brown.edu/beta/img/motifs/" + 
                dataGroup.motifs[i][j] + 
                "_fwd.png>" +
                "<img height='40' width='80' src=img/motifs/" + 
                dataGroup.motifs[i][j] + 
                "_fwd.png></td>");
        }
        if (dataGroup.isExon) {
            newrow.append("<td>" + dataGroup.ESEseq[i] + "</td>")
        }
        table.append(newrow);
    }
    container.append(table); 
}

function parseSpliceString(data,ret){
    CHR1_INDEX = 0;
    MUT_POS_INDEX = 1;
    CHR2_INDEX = 2;
    REF_BASE_INDEX = 3;
    MUT_BASE_INDEX = 4;
    BEG_EXON_INDEX = 5;
    END_EXON_INDEX = 6;
    GENE_INDEX = 7;
    STRAND_INDEX = 8;
    TERMINAL_INDEX = 9;
    L1_PERCENTILE_INDEX = 10;
    ESESEQ_INDEX = 11;
    ENHANCER_SUPPRESSOR_INDEX = 12;
    RBPS_INDEX = 13;
    MOTIFS_INDEX = 18;

    NUMBER_OF_RBPS = 5;

    ss = data.split(/\n/);
    var res = [];
    for(j = 0; j < ss.length; j++) {
        res[j] = ss[j].split(/\t/);
        console.log(res[j]);
    }
    var data = [];
    var datagroup = {};
    for(i = 0; i < res.length; i++) {
        if(datagroup.hasOwnProperty(res[i][BEG_EXON_INDEX])) {
            datagroup[res[i][BEG_EXON_INDEX]].mut_pos.push(
                res[i][MUT_POS_INDEX]);
            datagroup[res[i][BEG_EXON_INDEX]].strand.push(
                res[i][STRAND_INDEX]);
            datagroup[res[i][BEG_EXON_INDEX]].terminal.push(
                res[i][TERMINAL_INDEX]);
            datagroup[res[i][BEG_EXON_INDEX]].ref_base.push(
                res[i][REF_BASE_INDEX]);
            datagroup[res[i][BEG_EXON_INDEX]].mut_base.push(
                res[i][MUT_BASE_INDEX]);
            datagroup[res[i][BEG_EXON_INDEX]].ESEseq.push(
                res[i][ESESEQ_INDEX]);
            datagroup[res[i][BEG_EXON_INDEX]].enhancer_suppressor.push(
                res[i][ENHANCER_SUPPRESSOR_INDEX]);
            datagroup[res[i][BEG_EXON_INDEX]].l1_dist.push(
                Math.abs(Math.log10((100 - parseInt(res[i][L1_PERCENTILE_INDEX]))/100)));
            var rbps_temp = [];
            var motifs_temp = [];
            for(j = 0; j < 5; j++) {
                rbps_temp[j] = res[i][RBPS_INDEX + j];
                motifs_temp[j] = res[i][MOTIFS_INDEX + j];
            }
            datagroup[res[i][BEG_EXON_INDEX]].rbps.push(rbps_temp);
            datagroup[res[i][BEG_EXON_INDEX]].motifs.push(motifs_temp);
        } else {
            datagroup[res[i][BEG_EXON_INDEX]] = {};
            datagroup[res[i][BEG_EXON_INDEX]].chr1 = res[i][CHR1_INDEX];
            datagroup[res[i][BEG_EXON_INDEX]].mut_pos = [];
            datagroup[res[i][BEG_EXON_INDEX]].mut_pos.push(
                res[i][MUT_POS_INDEX]);
            datagroup[res[i][BEG_EXON_INDEX]].chr2 = res[i][CHR2_INDEX];
            datagroup[res[i][BEG_EXON_INDEX]].beg_exon = res[i][BEG_EXON_INDEX];
            datagroup[res[i][BEG_EXON_INDEX]].name = 
                res[i][CHR1_INDEX] + ": " + res[i][BEG_EXON_INDEX];
            datagroup[res[i][BEG_EXON_INDEX]].end_exon = res[i][END_EXON_INDEX];
            datagroup[res[i][BEG_EXON_INDEX]].gene = res[i][GENE_INDEX];
            
            datagroup[res[i][BEG_EXON_INDEX]].strand = [];
            datagroup[res[i][BEG_EXON_INDEX]].strand.push(res[i][STRAND_INDEX]);
            
            datagroup[res[i][BEG_EXON_INDEX]].terminal = [];
            datagroup[res[i][BEG_EXON_INDEX]].terminal.push(
                res[i][TERMINAL_INDEX]);

            datagroup[res[i][BEG_EXON_INDEX]].ref_base = [];
            datagroup[res[i][BEG_EXON_INDEX]].ref_base.push(
                res[i][REF_BASE_INDEX]);

            datagroup[res[i][BEG_EXON_INDEX]].mut_base = [];
            datagroup[res[i][BEG_EXON_INDEX]].mut_base.push(
                res[i][MUT_BASE_INDEX]);

            datagroup[res[i][BEG_EXON_INDEX]].ESEseq = [];
            datagroup[res[i][BEG_EXON_INDEX]].ESEseq.push(
                res[i][ESESEQ_INDEX]);

            datagroup[res[i][BEG_EXON_INDEX]].enhancer_suppressor = [];
            datagroup[res[i][BEG_EXON_INDEX]].enhancer_suppressor.push(
                res[i][ENHANCER_SUPPRESSOR_INDEX]);

            datagroup[res[i][BEG_EXON_INDEX]].l1_dist = [];
            datagroup[res[i][BEG_EXON_INDEX]].l1_dist.push(
                Math.abs(Math.log10((100 - parseInt(res[i][L1_PERCENTILE_INDEX]))/100)));
            
            datagroup[res[i][BEG_EXON_INDEX]].rbps = [];
            datagroup[res[i][BEG_EXON_INDEX]].motifs = [];
            var rbps_temp = [];
            var motifs_temp = [];
            for(j = 0; j < NUMBER_OF_RBPS; j++) {
                rbps_temp[j] = res[i][RBPS_INDEX + j];
                motifs_temp[j] = res[i][MOTIFS_INDEX + j];
            }
            datagroup[res[i][BEG_EXON_INDEX]].rbps.push(rbps_temp);
            datagroup[res[i][BEG_EXON_INDEX]].motifs.push(motifs_temp);
        }
        data[i] = {};
        data[i].chr1 = res[i][CHR1_INDEX];
        data[i].mut_pos = res[i][MUT_POS_INDEX];
        data[i].chr2 = res[i][CHR2_INDEX];
        data[i].beg_exon = res[i][BEG_EXON_INDEX];
        data[i].end_exon = res[i][END_EXON_INDEX];
        data[i].gene = res[i][GENE_INDEX];
        data[i].strand = res[i][STRAND_INDEX];
        data[i].terminal = res[i][TERMINAL_INDEX];
        data[i].ref_base = res[i][REF_BASE_INDEX];
        data[i].mut_base = res[i][MUT_BASE_INDEX];
        data[i].ESEseq = res[i][ESESEQ_INDEX];
        data[i].enhancer_suppressor = res[i][ENHANCER_SUPPRESSOR_INDEX];
        data[i].l1_dist = 
            Math.abs(Math.log10((100 - res[i][L1_PERCENTILE_INDEX])/100));
        data[i].rbps = [];
        data[i].motifs = [];
        for(j = 0; j < NUMBER_OF_RBPS; j++) {
            data[i].rbps[j] = res[i][RBPS_INDEX + j];
            data[i].motifs[j] = res[i][MOTIFS_INDEX + j];
	}
        
    }
    if(ret) {
        return datagroup;
    } else {
        return data;
    }
    return data;
}
function drawVisualization(){
<?php 
$data = Session::get('message');
?>
	mutationData = <?php echo json_encode($data)?>;
    console.log(mutationData);
    if(mutationData == null) {
        $("#visBody").html(
            '<h2 style="color:#2C2C2C; text-align:center;">No Data to Display</h2>');
        return;
    }
    mutationDataGroup = parseSpliceString(mutationData, true);
    console.log(mutationDataGroup);
    mutationData = parseSpliceString(mutationData, false);
    console.log(mutationData);
    var relDists = [];
    var chartData = [];
    
    //test grouping
if(true){
    EXON_TYPE = 0;
    OTHER_TYPE = 1;
    INTRON_TERMINAL3_TYPE = 2;
    INTRON_TERMINAL5_TYPE = 3;

    for(var exon in mutationDataGroup) {
        if(!mutationDataGroup.hasOwnProperty(exon)) {continue;}
        else {
            var type = 0;
            if (mutationDataGroup[exon].terminal[0]=="exon_internal" 
                || mutationDataGroup[exon].terminal[0]=="exon_terminal") {
                type = EXON_TYPE;
                mutationDataGroup[exon].isExon = true;
            } else {
                if (mutationDataGroup[exon].terminal[0]=="intron_terminal3") {
                    type = INTRON_TERMINAL3_TYPE;
                } else if(mutationDataGroup[exon].terminal[0]=="intron_terminal5") {
                    type = INTRON_TERMINAL5_TYPE;
                } else {
                    type = OTHER_TYPE;
                }

                mutationDataGroup[exon].isExon = false;
            }
	    for (var i = 0; i < mutationDataGroup[exon].l1_dist.length; i++) {
                mut_pos = parseInt(mutationDataGroup[exon].mut_pos[i]);
                beg_exon = parseInt(mutationDataGroup[exon].beg_exon);
                end_exon = parseInt(mutationDataGroup[exon].end_exon);
                var ssdist;
                var ss5;
                var d1 = mut_pos - beg_exon;
                var d2 = end_exon - mut_pos;
                var d3 = end_exon - beg_exon;
                if (mutationDataGroup[exon].strand[i] == "+") {
                    switch (type) {
                        case EXON_TYPE:
                            ss5 = d2 < d1;
                            ssdist = Math.min(d1, d2);
                            break;
                        case OTHER_TYPE:
                            ss5 = d1 < d2;
                            ssdist = Math.min(d1, d2);
                            break;
                        case INTRON_TERMINAL3_TYPE:
                            ssdist = d2;
                            ss5 = false;
                            break;
                        case INTRON_TERMINAL5_TYPE:
                            ssdist = d1;
                            ss5 = true;
                            break;
                    }
                } else {
                    switch (type) {
                        case EXON_TYPE:
                            ss5 = d1 < d2;
                            ssdist = Math.min(d1, d2);
                            break;
                        case OTHER_TYPE:
                            ss5 = d2 < d1;
                            ssdist = Math.min(d1, d2);
                            break;
                        case INTRON_TERMINAL3_TYPE:
                            ssdist = d1;
                            ss5 = true;
                            break;
                        case INTRON_TERMINAL5_TYPE:
                            ssdist = d2;
                            ss5 = false;
                            break;
                    }                 
                }
                relDist = (type == 0?
                    (ss5?1-ssdist/d3:ssdist/d3):(ss5?ssdist/d3:1-ssdist/d3));
                if(mutationDataGroup[exon].hasOwnProperty("relDist")) {
                    mutationDataGroup[exon].ssdist.push(ssdist);
                    mutationDataGroup[exon].relDist.push(relDist);
                    mutationDataGroup[exon].ss5.push(ss5);
                    mutationDataGroup[exon].label.push(
                        mutationDataGroup[exon].chr1 + ": " + mut_pos  + ", "
                         + ssdist + "nt from " + (ss5?"5'SS":"3'SS"));
                }else {
                    mutationDataGroup[exon].relDist = [];
                    mutationDataGroup[exon].relDist.push(relDist);
                    mutationDataGroup[exon].ssdist = [];
                    mutationDataGroup[exon].ssdist.push(ssdist);
                    mutationDataGroup[exon].ss5 = [];
                    mutationDataGroup[exon].ss5.push(ss5);
                    mutationDataGroup[exon].label = [];
                    mutationDataGroup[exon].label.push(
                        mutationDataGroup[exon].chr1+ ": " + mut_pos  + ", "
                         + ssdist + "nt from " + (ss5?"5'SS":"3'SS"));
                }
            }
        }
    }
    for(var exon in mutationDataGroup) {
        if(!mutationDataGroup.hasOwnProperty(exon)) {continue;}
        else {
            //make containerdivs

            mutationDataGroup[exon].diagramcontainer = $("<div class='exon-diagram' id=gdiagram" + i + "></div>");
            $("#visBody").append(mutationDataGroup[exon].diagramcontainer);
            drawDiagramGroup(mutationDataGroup[exon]);
            mutationDataGroup[exon].tablecontainer = $("<div class='table-container' id=rbptable" + i + "></div>");
            $("#visBody").append(mutationDataGroup[exon].tablecontainer);
            populateTableGroup(mutationDataGroup[exon]);
            mutationDataGroup[exon].chartcontainer = $("<div class='l1chart' id=gchart" + i + "></div>")
            $("#visBody").append(mutationDataGroup[exon].chartcontainer);
            drawChartGroup(mutationDataGroup[exon]);
            //call draw functions
        }
    }
}
if (false) {
    //visualization without grouping
    for(var i=0; i<mutationData.length;i++){
        var relDist;
        var mut_pos = parseInt(mutationData[i].mut_pos);
        var beg_exon = parseInt(mutationData[i].beg_exon);
        var end_exon = parseInt(mutationData[i].end_exon);
        chartData[i] = {};
        relDists[i] = {};
    	if (mutationData[i].terminal == "exon_internal"
            || mutationData[i].terminal == "exon_terminal") {
    	    relDists[i].isExon = true;
        } else {
    	    relDists[i].isExon = false;
        }
        
        //calculate reldist
        if(mutationData[i].strand == "+"){
            relDist = (mut_pos - beg_exon)/(end_exon-beg_exon);
        } else {
            relDist = 1 - (mut_pos - beg_exon)/(end_exon-beg_exon);
        }
        
        relDists[i].dist = relDist;
	    relDists[i].name = mutationData[i].gene + ": " + mutationData[i].mut_pos; 
        relDists[i].container =
            $("<div class='exon-diagram' id=diagram" + i + "></div>");
        $("#visBody").append(relDists[i].container);
        
        chartData[i].L1 = parseInt(mutationData[i].l1_dist);
        chartData[i].gene = mutationData[i].gene;

        chartData[i].mut_pos = parseInt(mutationData[i].mut_pos);
        chartData[i].container =
            $("<div class='l1chart' id=chart" + i + "></div>")
        $("#visBody").append(chartData[i].container);
    }
    for(var i = 0; i < mutationData.length; i++) {
        drawDiagram(relDists[i]);
        drawChart(chartData[i]);
        
    }
}
}

function addText(center,vertical,text,canvas,size){
            return canvas.text(center,vertical,text).attr('font-size',size).attr('fill','#2c2c2c').attr('font-weight','700').attr('font-family', 'inherit');
        }
    
        function addExon(start, end, vertical, height, canvas, color, text, mutLocation){
            var test = canvas.rect(start,vertical,end-start,height).attr("fill",color).attr("stroke-width",3).attr("stroke",color);
            if(mutLocation.length > 0){
                for(var i = 0; i < mutLocation.length; i++){
                    var loc = mutLocation[i];
                    var temp = canvas.path("M" + ((end-start)* loc + start) + " " + (vertical-2) + "l" + 0 + " " + (height+4)).attr("stroke-width", 5).attr("fill", "#AAAAAA").attr("stroke","#cf2c2c");
                }
            }
	    canvas.text(start + .5 * (end-start), 35, text).attr("fill", "#2c2c2c").attr("font-size", 15).attr("font-weight", 700).attr('font-family', 'inherit');
            return test;

        }
        function addIntron(start, end, vertical,width,canvas, mutLocations, text){
            var temp = canvas.path("M"+start+" " + vertical+ "l"+(end-start)+" 0").attr("stroke-width",width).attr("stroke","#000000")
            if(mutLocations.length > 0) {
                for(var i = 0; i < mutLocations.length; i++) {
                    var loc = mutLocations[i];
                    var mutLine = canvas.path("M" + ((end - start) * loc + start) + " " + (vertical - 2*width) + "l" + 0 + " " + (4*width)).attr("stroke-width", 5).attr("fill", "#aaaaaa").attr("stroke", "#e96d63").attr("stroke-opacity", .5);
                }
            }
	    canvas.text(start + .5 * (end-start), vertical - 30, text).attr("fill", "#2c2c2c").attr("font-size", 15).attr("font-weight", 700).attr('font-family', 'inherit');
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
@stop
