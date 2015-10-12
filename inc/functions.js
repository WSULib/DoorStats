

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
            text: "Transactions per Location"
        },
        xAxis: {
            type: 'datetime'
        },       

        series: date_series
    });


}


// HighCharts - Transaction Breakdown
function transBreakdown(raw_data){
    
    // format data
    var trans_breakdown_data = [];
    for (var trans_type in raw_data) {
        if (raw_data.hasOwnProperty(trans_type)) {
            var temp_data = [ trans_type, parseInt(raw_data[trans_type]) ];
            trans_breakdown_data.push(temp_data);
        }
    }

    $('#transBreakdown').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false
        },
        title: {
            text: 'Transaction Type Breakdown'
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
            name: 'Transaction Breakdown',
            data: trans_breakdown_data
        }]
    });


}

// HighCharts - Busiest Hours
function busiestHours(raw_data){

	// PREPARE DATA
	// series are horizontal color segments, in our case reference types
	var reftype_series = [{
            name: 'Directional',
            data: []
        }, {
            name: 'Brief',
            data: []
        }, {
            name: 'Extended',
            data: []
        },
        {
            name: 'Consultation',
            data: []
        }
    ]
	var x_axis_hours = [8,9,10,11,12,13,14,15,16,17,18,19,20,21,22];

	// iterate through x axis hours
	for (var i=0; i < x_axis_hours.length; i++){

		// check if hour data exists
		if ( raw_data[x_axis_hours[i]] ){

			// loop through series reftypes
			for (var j=0; j < reftype_series.length; j++){
				if (raw_data[x_axis_hours[i]][reftype_series[j]['name']]){
					reftype_series[j]['data'].push( parseInt(raw_data[x_axis_hours[i]][reftype_series[j]['name']]) );	
				}
				else {
					reftype_series[j]['data'].push( 0 );	
				}
				
			}

		}

		// else, fill with zeros
		else {
			for (var j=0; j < reftype_series.length; j++){
				reftype_series[j]['data'].push( 0 );
			}
		}

	}

	// render chart
    $('#busiestHoursChart').highcharts({
        chart: {
            type: 'column'
        },
        title: {
            text: 'Busiest Hours per Day'
        },
        xAxis: {
            categories: x_axis_hours
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Total Transactions'
            },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                }
            }
        },
        legend: {
            align: 'right',
            x: -30,
            verticalAlign: 'top',
            y: 25,
            floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
            borderColor: '#CCC',
            borderWidth: 1,
            shadow: false
        },
        tooltip: {
            formatter: function () {
                return '<b>' + this.x + '</b><br/>' +
                    this.series.name + ': ' + this.y + '<br/>' +
                    'Total: ' + this.point.stackTotal;
            }
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: {
                    enabled: true,
                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                    style: {
                        textShadow: '0 0 3px black'
                    }
                }
            }
        },

        series: reftype_series

    });

}


// HighCharts - Busiest Hours
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
	dow_series.push({name:"Transactions",data:dataPoints})

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
                text: 'Total Transactions'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>{point.y} transactions</b></td></tr>',
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



























