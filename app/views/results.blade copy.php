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
    //data format:
    //mutposition
    //beginning
    //end
    //+/1
    //gene
    //L1 distance
    //RBP names
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
            text: "L1 Distances By Mutation Position"
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
                style:{
                    fontSize: "18px"
                }
            },
            title: {
  	        text: 'L1 Distance',
                style:{
                    fontSize: "16px"
                }
            }
        },
        legend: {
		enabled: false
},
        series: [{
            name: "L1 Distance",
            type: "column",
            color: "#39b3d7",
            data: data,
            pointWidth: 20
        }]
    });
}
    
//input: container div for chart,  mutationObj JSON object containing gene name, mutation position, L1 distance
//output: chart added to container
function drawChart(mutationObj){
    mutationObj.container.highcharts({
        title: {
            text: "L1 Distances"
        },
        xAxis: {
            categories: [mutationObj.gene + ": " + mutationObj.mut_pos]
        },
        yAxis: {
            title: {
			text: 'L1 Distance'
		}
        },
        legend: {
		enabled: false
},
        series: [{
            name: "L1 Distance",
            type: "column",
            data: [mutationObj.L1],
            pointWidth: 20
        }]
    });
}

function populateTableGroup(dataGroup){
    var container = dataGroup.tablecontainer;
    var table = $("<table class=rbp-table></table>");
    var headrow = $("<tr></tr>");
    headrow.append("<th class=rbp-table-head>Mutation</th>");
    var header = headrow.append("<th class=rbp-table-head colspan = 5>RBPs</th>");
    table.append(headrow);
    for(var i = 0; i < dataGroup.rbps.length; i++) {
        var newrow = $("<tr class=rbp-row></tr>");
        newrow.append("<th>"+dataGroup.mut_pos[i]+"</th>");
        for(var j = 0; j < dataGroup.rbps[i].length; j++) {
            newrow.append("<td>"+dataGroup.rbps[i][j]+"</td>");
        }
        table.append(newrow);
    }
    container.append(table);
}

function parseSpliceString(ss,ret){
    var res = [];
    for(j = 0; j< ss.length; j++) {
        res[j] = ss[j].split(/\t/);
    }
    var data = [];
    var datagroup = {};
    for(i = 0; i < res.length; i++) {
        if(datagroup.hasOwnProperty(res[i][3])) {
            datagroup[res[i][3]].mut_pos.push(res[i][1]);
            datagroup[res[i][3]].strand.push(res[i][6]);
            datagroup[res[i][3]].terminal.push(res[i][7]);
            datagroup[res[i][3]].l1_dist.push(parseInt(res[i][8]));
            var temp = [];
            for(j = 0; j < 5; j++) {
                temp[j] = res[i][9+j];
            }
            datagroup[res[i][3]].rbps.push(temp);
        } else {
            datagroup[res[i][3]] = {};
            datagroup[res[i][3]].chr1 = res[i][0];

            datagroup[res[i][3]].mut_pos = [];
            datagroup[res[i][3]].mut_pos.push(res[i][1]);

            datagroup[res[i][3]].chr2 = res[i][2];
            datagroup[res[i][3]].beg_exon = res[i][3];
            datagroup[res[i][3]].name = res[i][0] + ": " + res[i][3];
            datagroup[res[i][3]].end_exon = res[i][4];
            datagroup[res[i][3]].gene = res[i][5];
            
            datagroup[res[i][3]].strand = [];
            datagroup[res[i][3]].strand.push(res[i][6]);
            
            datagroup[res[i][3]].terminal = [];
            datagroup[res[i][3]].terminal.push(res[i][7]);

            datagroup[res[i][3]].l1_dist = [];
            datagroup[res[i][3]].l1_dist.push(parseInt(res[i][8]));
            
            datagroup[res[i][3]].rbps = [];
            var temp = [];
            for(j = 0; j < 5; j++) {
                temp[j] = res[i][9+j];
            }
            datagroup[res[i][3]].rbps.push(temp);
        }
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
    if(mutationData == null) {
        $("#visBody").html('<h2 style="color:#2C2C2C; text-align:center;">No Data to Display</h2>');
        return;
    }
    mutationDataGroup = parseSpliceString(mutationData, true);
    
    mutationData = parseSpliceString(mutationData, false);
    var relDists = [];
    var chartData = [];
    
    //test grouping
if(true){
    for(var exon in mutationDataGroup) {
        if(!mutationDataGroup.hasOwnProperty(exon)) {continue;}
        else {
            var type = 0;
            if(mutationDataGroup[exon].terminal[0]=="exon_internal"||mutationDataGroup[exon].terminal[0]=="exon_terminal") {
                type = 0;
	        mutationDataGroup[exon].isExon = true;
            } else {
                if(mutationDataGroup[exon].terminal[0]=="intron_terminal3"){type = 2;}
                else if(mutationDataGroup[exon].terminal[0]=="intron_terminal5"){type = 3;}
                else{type = 1;}

                mutationDataGroup[exon].isExon = false;
            }
	    for(var i = 0; i < mutationDataGroup[exon].l1_dist.length; i++) {
                mut_pos = parseInt(mutationDataGroup[exon].mut_pos[i]);
                beg_exon = parseInt(mutationDataGroup[exon].beg_exon);
                end_exon = parseInt(mutationDataGroup[exon].end_exon);
                var ssdist;
                var ss5;
                var d1 = mut_pos - beg_exon;
                var d2 = end_exon - mut_pos;
                var d3 = end_exon - beg_exon;
                if(mutationDataGroup[exon].strand[i] == "+"){
                    
                    if(type == 0) {
                        ss5 = d1<d2;
                        ssdist = (ss5?d1:d2);
                    } else if(type == 1) {
                        ss5 = d1<d2;
                        ssdist = (ss5?d1:d2);
                    } else if(type == 2){
                        ssdist = (end_exon - mut_pos);
                        ss5 = false;
                    } else if(type == 3) {
                        ssdist = mut_pos - beg_exon;
                        ss5 = true;
                    }
                } else {
                    if(type == 0) {
                        ss5 = d2<d1;
                        ssdist = (ss5?d2:d1);
                    } else if(type == 1) {
                        ss5 = d2<d1;
                        ssdist = (ss5?d2:d1);
                    } else if(type == 2){
                        ssdist = mut_pos - beg_exon;
                        ss5 = true;
                    } else if(type == 3) {
                        ssdist = end_exon - mut_pos;
                        ss5 = false;
                    }                   
                }
                relDist = (type == 0?(ss5?1-ssdist/d3:ssdist/d3):(ss5?ssdist/d3:1-ssdist/d3));
                if(mutationDataGroup[exon].hasOwnProperty("relDist")) {
                    mutationDataGroup[exon].ssdist.push(ssdist);
                    mutationDataGroup[exon].relDist.push(relDist);
                    mutationDataGroup[exon].ss5.push(ss5);
                    mutationDataGroup[exon].label.push(mutationDataGroup[exon].chr1+": "+ mut_pos  +", " + ssdist + "nt from " + (ss5?"5'SS":"3'SS"));
                }else {
                    mutationDataGroup[exon].relDist = [];
                    mutationDataGroup[exon].relDist.push(relDist);
                    mutationDataGroup[exon].ssdist = [];
		    mutationDataGroup[exon].ssdist.push(ssdist);
                    mutationDataGroup[exon].ss5 = [];
                    mutationDataGroup[exon].ss5.push(ss5);
                    mutationDataGroup[exon].label = [];
                    mutationDataGroup[exon].label.push(mutationDataGroup[exon].chr1+": "+ mut_pos  +", " + ssdist + "nt from " + (ss5?"5'SS":"3'SS"));
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
if(false){
    //visualization without grouping
    for(var i=0; i<mutationData.length;i++){
        var relDist;
        var mut_pos = parseInt(mutationData[i].mut_pos);
        var beg_exon = parseInt(mutationData[i].beg_exon);
        var end_exon = parseInt(mutationData[i].end_exon);
        chartData[i] = {};
        relDists[i] = {};
	if(mutationData[i].terminal=="exon_internal"||mutationData[i].terminal=="exon_terminal") {
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
        relDists[i].container = $("<div class='exon-diagram' id=diagram" + i + "></div>");
        $("#visBody").append(relDists[i].container);
        
        chartData[i].L1 = parseInt(mutationData[i].l1_dist);
        chartData[i].gene = mutationData[i].gene;

        chartData[i].mut_pos = parseInt(mutationData[i].mut_pos);
        chartData[i].container = $("<div class='l1chart' id=chart" + i + "></div>")
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
