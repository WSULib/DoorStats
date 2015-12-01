

// if localstrorage available, make toggles sticky
if(lsTest() === true){

	// editTable
	$(document).ready(function () {
	    $('#toggle_table').click(function () {
	        $("#transactions_table").slideToggle(function(){
	        	localStorage.setItem('toggle_table', $("#transactions_table").is(':visible'));	
	        });                
	    });    
	    if (localStorage.getItem('toggle_table') == 'false') {
	        $('#transactions_table').hide()
	    }
	});

	//statsGraph
	$(document).ready(function () {
	    $('#toggle_graph').click(function () {
	        $("#table_wrapper").slideToggle(function(){
	        	localStorage.setItem('toggle_graph', $("#table_wrapper").is(':visible'));	
	        });                
	    });    
	    if (localStorage.getItem('toggle_graph') == 'false') {
	        $("#table_wrapper").hide()
	    }
	});

}

// else, simple toggles
else {
	// editTable
	$(document).ready(function () {
	    $('#toggle_table').click(function () {
	        $("#transactions_table").slideToggle();
        });
	});

	//statsGraph
	$(document).ready(function () {
	    $('#toggle_graph').click(function () {
	        $("#table_wrapper").slideToggle();
        });
	});
}


// localstorage test
function lsTest(){
    var test = 'test';
    try {
        localStorage.setItem(test, test);
        localStorage.removeItem(test);
        return true;
    } catch(e) {
        return false;
    }
}

// temporarily set user cookie
function userCookie(value) {
	document.cookie="user_group="+value+"";
}

// temporarily set user cookie
function userCookie2() {
	var value = $('input[name="radio_button"]:checked').val();
	document.cookie="user_group="+value+"";
}

// REPORTING
$('#ALL_checkbox').on('click', function() {
	alert('firing');
    $('.checkbox').not(this).prop('checked', false);  
});


function loadingCSV(working_msg, finish_msg){
	$("#csv_button").html(working_msg);
	$("#csv_button").toggleClass('btn-success');
	setTimeout(function(){ 
		$("#csv_button").html(finish_msg);
		$("#csv_button").toggleClass('btn-success');
	}, 2000);
}


/* Quick Reports functions */

// HighCharts - Transactions Per Date
function transPerLocation(raw_data, date_start){

    // prep data
    var date_series = [];

    for (var location in raw_data) {
        if (raw_data.hasOwnProperty(location)) {

            temp_chunk = {
                name: location,
                data: raw_data[location]
            }

            for (var i=0; i < temp_chunk['data'].length; i++){

                // convert to ints
                temp_chunk['data'][i][1] = parseInt(temp_chunk['data'][i][1]);

                // convert to Dates
                var date_comps = temp_chunk['data'][i][0].split('-');
                var year = parseInt(date_comps[0]);
                var month = parseInt(date_comps[1].replace(/^0+/, '')) - 1;
                var day = parseInt(date_comps[2].replace(/^0+/, ''));
                temp_chunk['data'][i][0] = Date.UTC(year, month, day);
                
            }

            date_series.push(temp_chunk);
        }
    }

    // date components
    var date_comps = date_start.split('-');
    var year = parseInt(date_comps[0]);
    var month = parseInt(date_comps[1].replace(/^0+/, '')) - 1;
    var day = parseInt(date_comps[2].replace(/^0+/, ''));

    $('#transPerLocation').highcharts({
        chart: {
            type:"spline"
        },        
        title: {
            text: "Foot Traffic at each Gate"
        },
        xAxis: {
            type: 'datetime'
        },       

        series: date_series
    });


}


// HighCharts - Gate Breakdown
function gateBreakdown(raw_data){
    
    // format data
    var gate_breakdown_data = [];
    for (var gate_type in raw_data) {
        if (raw_data.hasOwnProperty(gate_type)) {
            var temp_data = [ gate_type, parseInt(raw_data[gate_type]) ];
            gate_breakdown_data.push(temp_data);
        }
    }

    $('#gateBreakdown').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false
        },
        title: {
            text: 'Gate Visit Breakdown'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Gate Breakdown',
            data: gate_breakdown_data
        }]
    });


}


// HighCharts - Building Breakdown
function buildingBreakdown(raw_data){
    
    // format data
    var building_breakdown_data = [];
    for (var building_type in raw_data) {
        if (raw_data.hasOwnProperty(building_type)) {
            var temp_data = [ building_type, parseInt(raw_data[building_type]) ];
            building_breakdown_data.push(temp_data);
        }
    }

    $('#buildingBreakdown').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false
        },
        title: {
            text: 'Building Visit Breakdown'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Building Breakdown',
            data: building_breakdown_data
        }]
    });


}


// HighCharts - Busiest DOW
function busiestDOW(raw_data){

	// console.log("busiesetDOW raw_data:",raw_data);

	// PREPARE DATA
	
	// simple x axis
	var x_axis_days = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

	// series data
	dow_series = []
	dataPoints = []
	for (var i=0; i < x_axis_days.length; i++){
		// check if hour data exists
		if ( raw_data[x_axis_days[i]] ){
			dataPoints.push( parseInt(raw_data[x_axis_days[i]]) )
		}
		else {
			dataPoints.push(0);
		}
	}
	dow_series.push({name:"Visits",data:dataPoints})

	// console.log(dow_series);

	// render chart
    $('#busiestDOWChart').highcharts({
        chart: {
            type: 'column'
        },
        title: {
            text: 'Busiest Days per Week'
        },        
        xAxis: {
            categories: x_axis_days,
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Visits'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>{point.y} visits</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: dow_series
    });

}

function busiestHours(raw_data){

    // console.log("busiesetDOW raw_data:",raw_data);

    // PREPARE DATA
    
    // simple x axis
    var x_axis_days = ["0","1","2","3","4","5","6","7","8","9","10","11","12","13","14","15","16","17","18","19","20","21","22","23","24"];

    // series data
    dow_series = []
    dataPoints = []
    for (var i=0; i < x_axis_days.length; i++){
        // check if hour data exists
        if ( raw_data[x_axis_days[i]] ){
            dataPoints.push( parseFloat(raw_data[x_axis_days[i]]) )
        }
        else {
            dataPoints.push(0);
        }
    }
    dow_series.push({name:"Visits",data:dataPoints})

    // console.log(dow_series);

    // render chart
    $('#busiestHoursChart').highcharts({
        chart: {
            type: 'column'
        },
        title: {
            text: 'Busiest Hours Per Day'
        },        
        xAxis: {
            categories: x_axis_days,
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Visits'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>{point.y:.1f} visits</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0
            }
        },
        series: dow_series
    });

}

























