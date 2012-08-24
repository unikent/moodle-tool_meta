$(document).ready(function() {
	oTable = $('.index_course_table_wrap #coursetable').dataTable( {
		"bAutoWidth": false,
		"aoColumns": [
			{ "bSortable": true },
	      	{ "bSortable": true },
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
			oTable.fnFilter('')
		}
	});

	aTable = $('.add_course_table_wrap #coursetable').dataTable( {
		"bAutoWidth": false,
		"aoColumns": [
			{'sClass': 'name', "bSortable": true },
	      	{'sClass': 'enrols', "bSortable": true },
		],

		"sDom": 'rt<"coursetable_pages"lp>'
	});

	$('.add_course_table_wrap #search_box').keyup(function() {
		if($(this).val().length > 1) {
			aTable.fnFilter($(this).val());
		} else {
			aTable.fnFilter('')
		}
	});

    $('#coursetable tbody tr').live('click', function(e){
    	if(e.target === $('.course_link',this)[0]){
			return true;
		}
        window.location = $(this).attr('href');
        return false;
    });

    $('input').placeholder();

} );