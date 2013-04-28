var Report = function(json, options) {
	this.options = options;
	this.report = json.report;
	var data = json.data;

	var cols_conv = {};
	for (var j in data.cols) {
		switch (data.cols[j]['type']){
			case 'date':
			case 'datetime':
				cols_conv[j] = function(v) { return new Date(v) };
				break;
			default:
		}
	}
	for (var i in data.rows) {
		for (j in cols_conv) {
			data.rows[i].c[j].v = cols_conv[j](data.rows[i].c[j].v);
		}
	}
	this.data = new google.visualization.DataTable(data);
};

Report.prototype.controls = function() {
	var res = [], i = 0;
	for (var field in this.report.controls) {
		res.push(new google.visualization.ControlWrapper({controlType: this.report.controls[field],
			'containerId': this.options.controlElem[i++],
			'options': {'filterColumnLabel': field}
		}));
	}
	return res;
};

Report.prototype.view = function() {
	var controls = this.controls();
	if (controls.length === 0) {
		return new google.visualization[this.report.chartType](document.getElementById(this.options.chartElem));
	}
	var chart = new google.visualization.ChartWrapper({chartType: this.report.chartType,
		containerId: this.options.chartElem,
		options : { displayAnnotations: true }
	});

	return new google.visualization.Dashboard(document.getElementById(this.options.dashElem)).bind(controls, chart);
};