function report_prepForm(){ 
	$('#colorbox #report-problem').ajaxForm({success: 
		function(html){ 
			$.fn.colorbox({
				html: html,
				open: true,
				scrolling: false
			});
			$.fn.colorbox.resize({width: 400, height: 150});
		}
	});
}