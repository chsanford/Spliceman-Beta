@extends('layout')

@section('content')

<link rel="stylesheet" type="text/css" href="/dashboard/spliceman_new/spliceman_new.css">
<h1 id="visHead">
    Relevant Results from Dashboard
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

function populateTableGroup(data) {
    var container = 
        $("<div class='table-container'></div>");
    $("#visBody").append(container);

    var vitro_table = $("<table class=rbp-table></table>");
    var vitro_headrow = $("<tr></tr>");
    vitro_toprow = $("<tr></tr>");
    vitro_toprow.append("<th class=rbp-table-head colspan = 10>In Vitro</th>");
    vitro_table.append(vitro_toprow);
    vitro_headrow.append("<th class=rbp-table-head>ID</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>Coordinate</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>Wild Base</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>Mutant Base</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>Number of Wild Unspliced</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>Number of Mutant Unspliced</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>Number of Wild Spliced Annotated</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>Number of Mutant Spliced Annotated</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>Ratio of Mutant to Wild Annotated</th>");
    vitro_headrow.append(
        "<th class=rbp-table-head>P-Score Annotated</th>");
    vitro_table.append(vitro_headrow);

    var vivo_table = $("<table class=rbp-table></table>");
    var vivo_headrow = $("<tr></tr>");
    vivo_toprow = $("<tr></tr>");
    vivo_toprow.append("<th class=rbp-table-head colspan = 10>In Vivo</th>");
    vivo_table.append(vivo_toprow);
    vivo_headrow.append("<th class=rbp-table-head>ID</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>Coordinate</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>Wild Base</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>Mutant Base</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>Number of Wild Unspliced</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>Number of Mutant Unspliced</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>Number of Wild Spliced Annotated</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>Number of Mutant Spliced Annotated</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>Ratio of Mutant to Wild Annotated</th>");
    vivo_headrow.append(
        "<th class=rbp-table-head>P-Score Annotated</th>");
    vivo_table.append(vivo_headrow);
    for(var i = 0; i < data.length; i++) {
        var vitro_newrow = $("<tr class=rbp-row></tr>");
        var vivo_newrow = $("<tr class=rbp-row></tr>");
        vitro_newrow.append("<td>" + data[i].idm + "</td>");
        vivo_newrow.append("<td>" + data[i].idm + "</td>");
        vitro_newrow.append("<td>" + data[i].coord + "</td>");
        vivo_newrow.append("<td>" + data[i].coord + "</td>");
        vitro_newrow.append("<td>" + data[i].ref_plus + "</td>");
        vivo_newrow.append("<td>" + data[i].ref_plus + "</td>");
        vitro_newrow.append("<td>" + data[i].ref_neg + "</td>");
        vivo_newrow.append("<td>" + data[i].ref_neg + "</td>");
        vitro_newrow.append("<td>" + data[i].vit_wu + "</td>");
        vivo_newrow.append("<td>" + data[i].viv_wu + "</td>");
        vitro_newrow.append("<td>" + data[i].vit_mu + "</td>");
        vivo_newrow.append("<td>" + data[i].viv_mu + "</td>");
        vitro_newrow.append("<td>" + data[i].vit_ws_ann + "</td>");
        vivo_newrow.append("<td>" + data[i].viv_ws_ann + "</td>");
        vitro_newrow.append("<td>" + data[i].vit_ms_ann + "</td>");
        vivo_newrow.append("<td>" + data[i].viv_ms_ann + "</td>");
        vitro_newrow.append("<td>" + data[i].vitRmw_ann + "</td>");
        vivo_newrow.append("<td>" + data[i].vivRmw_ann + "</td>");
        vitro_newrow.append("<td>" + data[i].vitP_ann + "</td>");
        vivo_newrow.append("<td>" + data[i].vivP_ann + "</td>");
        vitro_table.append(vitro_newrow);
        vivo_table.append(vivo_newrow);
    }
    //container.append(table); 
    container.append(vitro_table);
    container.append("<br>");
    container.append(vivo_table);

}

function parseSpliceString(data) {
    console.log(data);
    IDM_INDEX = 0;
    COORD_INDEX = 1;
    REF_PLUS_INDEX = 2;
    REF_NEG_INDEX = 3;
    VIT_WU_INDEX = 4;
    VIT_MU_INDEX = 5
    VIT_WS_ANN_INDEX = 6;
    VIT_MS_ANN_INDEX = 7;
    VITRMW_ANN_INDEX = 8;
    VITP_ANN_INDEX = 9;
    VIV_WU_INDEX = 10;
    VIV_MU_INDEX = 11;
    VIV_WS_ANN_INDEX = 12;
    VIV_MS_ANN_INDEX = 13;
    VIVRMW_ANN_INDEX = 14;
    VIVP_ANN_INDEX = 15;

    ss = data.split(/\n/);
    var res = [];
    for (j in ss) {
        if (/\S/.test(ss[j]) && j != 0) {
            res.push(ss[j].split(/\t/));
            console.log(ss[j]);
        }
    }
    var data = [];
    for (i = 0; i < res.length; i++) {
        data[i] = {};
        data[i].idm = res[i][IDM_INDEX];
        data[i].coord = res[i][COORD_INDEX];
        data[i].ref_plus = res[i][REF_PLUS_INDEX];
        data[i].ref_neg = res[i][REF_NEG_INDEX];
        data[i].vit_wu = res[i][VIT_WU_INDEX];
        data[i].vit_mu = res[i][VIT_MU_INDEX];
        data[i].vit_ws_ann = res[i][VIT_WS_ANN_INDEX];
        data[i].vit_ms_ann = res[i][VIT_MS_ANN_INDEX];
        data[i].vitRmw_ann = res[i][VITRMW_ANN_INDEX];
        data[i].vitP_ann = res[i][VITP_ANN_INDEX];
        data[i].viv_wu = res[i][VIV_WU_INDEX];
        data[i].viv_mu = res[i][VIV_MU_INDEX];
        data[i].viv_ws_ann = res[i][VIV_WS_ANN_INDEX];
        data[i].viv_ms_ann = res[i][VIV_MS_ANN_INDEX];
        data[i].vivRmw_ann = res[i][VIVRMW_ANN_INDEX];
        data[i].vivP_ann = res[i][VIVP_ANN_INDEX];
	}
    console.log(data);
    return data;
}

function drawVisualization(){
    <?php 
    $data = Session::get('message');
    ?>
	dashboardData = <?php echo json_encode($data)?>;
    if (dashboardData == null) {
        $("#visBody").html(
            '<h2 style="color:#2C2C2C; text-align:center;">No Data to Display</h2>');
        return;
    }
    data = parseSpliceString(dashboardData);
    console.log(data);
    populateTableGroup(data);
}

</script>    
@stop