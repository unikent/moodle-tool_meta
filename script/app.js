$(document).ready(function() {
	var selectedRows = [];

    $.fn.dataTableExt.oApi.fnGetFilteredNodes = function ( oSettings ) {
		var anRows = [];
		for ( var i=0, iLen=oSettings.aiDisplay.length ; i<iLen ; i++ ){
				var nRow = oSettings.aoData[ oSettings.aiDisplay[i] ].nTr;
				anRows.push( nRow );
		}
		return anRows;
	};

	oTable = $('.index_course_table_wrap #coursetable').dataTable({
		"bAutoWidth": false,
		"aoColumns": [
		    { "bSortable": true },
                    { "bSortable": true }
		],
		"aoColumnDefs": [
			{ "bSearchable": true, "bVisible": false, "aTargets": [ 0 ] }
		],
		"sDom": 'rt<"coursetable_pages"lp>'
	});

	$('.index_course_table_wrap #search_box').keyup(function() {
		if($(this).val().length > 1) {
			oTable.fnFilter($(this).val());
		} else {
			oTable.fnFilter('');
		}
	});

	aTable = $('.add_course_table_wrap #coursetable').dataTable( {
		"bAutoWidth": false,
		"aoColumns": [
			{'sClass': 'shortname', "bSortable": false },
			{'sClass': 'name', "bSortable": true },
                        {'sClass': 'enrols', "bSortable": true }
		],
		"aoColumnDefs": [
			{ "bSearchable": true, "bVisible": false, "aTargets": [ 0 ] },
			{ "bSearchable": false, "bVisible": true, "aTargets": [ 1 ] },
			{ "bSearchable": false, "bVisible": true, "aTargets": [ 2 ] }
		],
		"sDom": 'rt<"coursetable_pages"lp>'
	});

	$('.add_course_table_wrap #search_box').keyup(function() {
		if($(this).val().length > 1) {
			aTable.fnFilter($(this).val());
		} else {
			aTable.fnFilter('');
		}
	});

    $('.index_course_table_wrap #coursetable tbody tr').live('click', function(e){
    	if(e.target === $('.course_link',this)[0]){
			return true;
		}
        window.location = $(this).attr('href');
        return false;
    });

    $('.add_course_table_wrap #coursetable tbody tr').live('click', function(e){
    	if(e.target === $('.course_link',this)[0]){
			return true;
		}

        c = Number($(this).attr('course'));

		if($(this).hasClass('selected')) {
			removeCourse(c);
			$(this).removeClass('selected');
		} else {
			addCourse(c);
			$(this).addClass('selected');
		}
        
        return false;
    });

    $('#sel').click(function() {

    	$(this).addClass('hidden');

    	$('#desel').removeClass('hidden');

    	var rows = aTable.fnGetFilteredNodes();
    	$.each(rows, function(i,r) {
    		var c = Number($(r).attr('course'));

    		addCourse(c);

    		$(r).addClass('selected');
    	});
    });

    $('#desel').click(function() {
    	$(this).addClass('hidden');

    	$('#sel').removeClass('hidden');

    	var rows = aTable.fnGetNodes()
    	$.each(rows, function(i, r) {
    		var c = Number($(r).attr('course'));
    		removeCourse(c);

    		$(r).removeClass('selected');
    	});
    });

    function addCourse(c) {
    	if(_.indexOf(selectedRows, c) === -1) {
			selectedRows.push(c);
		}
    }

    function removeCourse(c) {
		selectedRows = _.reject(selectedRows, function(i){ return i === c});
    }


    $('#add_enrol').click(function() {
    	$('#courses').val(JSON.stringify(selectedRows));
    	$('#meta_enrol_sub').click();
    });
});
