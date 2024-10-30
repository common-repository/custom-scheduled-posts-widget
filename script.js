jQuery(document).ready(function($){

    
    $('#cuschost_start_date').datepicker();
    $('#cuschost_end_date').datepicker();
    $( "#cuschost_start_date" ).change(function() {
			$('#cuschost_start_date').datepicker("option", "dateFormat", "yy-mm-dd" );
		});
    $( "#cuschost_end_date" ).change(function() {
			$('#cuschost_end_date').datepicker("option", "dateFormat", "yy-mm-dd" );
		});
    
    
});