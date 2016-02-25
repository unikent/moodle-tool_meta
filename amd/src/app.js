// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    tool_meta
 * @copyright  2016 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module tool_meta/app
  */
define(['jquery', 'tool_meta/jquery.dataTables', 'tool_meta/dataTables.bootstrap'], function($, dt, dtb) {
    var selectedRows = [];

    function addCourse(c) {
        if (_.indexOf(selectedRows, c) === -1) {
            selectedRows.push(c);
        }
    }

    function removeCourse(c) {
        selectedRows = _.reject(selectedRows, function(i) { return i === c});
    }

    return {
        init: function() {

            var oTable = $('.index_course_table_wrap #coursetable').DataTable({
                "autoWidth": false,
                "columns": [
                    { "orderable": true },
                    { "orderable": true }
                ],
                "columnDefs": [
                    { "searchable": true, "visible": false, "targets": [ 0 ] }
                ],
                "dom": 'rt<"coursetable_pages"lp>'
            });

            $('.index_course_table_wrap #search_box').on('change paste keyup', function() {
                if ($(this).val().length > 1) {
                    oTable.search($(this).val(), false, true);
                } else {
                    oTable.search('');
                }

                oTable.draw();
            });

            $(document).on('click', '.index_course_table_wrap #coursetable tbody tr', function(e) {
                if (e.target === $('.course_link',this)[0]){
                    return true;
                }
                window.location = $(this).attr('href');
                return false;
            });

            var aTable = $('.add_course_table_wrap #coursetable').DataTable( {
                "autoWidth": false,
                "columns": [
                    {'className': 'shortname', "orderable": false },
                    {'className': 'name', "orderable": true },
                    {'className': 'enrols', "orderable": true }
                ],
                "columnDefs": [
                    { "searchable": true, "visible": false, "targets": [ 0 ] },
                    { "searchable": false, "visible": true, "targets": [ 1 ] },
                    { "searchable": false, "visible": true, "targets": [ 2 ] }
                ],
                "dom": 'rt<"coursetable_pages"lp>'
            });

            $('.add_course_table_wrap #search_box').on('change paste keyup', function() {console
                if ($(this).val().length > 1) {
                    aTable.search($(this).val(), false, true);
                } else {
                    aTable.search('');
                }

                aTable.draw();
            });

            $(document).on('click', '.add_course_table_wrap #coursetable tbody tr', function(e) {
                if (e.target === $('.course_link',this)[0]){
                    return true;
                }

                var c = Number($(this).attr('course'));

                if ($(this).hasClass('selected')) {
                    removeCourse(c);
                    $(this).removeClass('selected');
                    $(this).find('td.name .fa').remove();
                } else {
                    addCourse(c);
                    $(this).addClass('selected');
                    $(this).find('td.name').prepend('<i class="fa fa-check"></i>');
                }

                return false;
            });

            $('#sel').click(function() {
                $(this).addClass('hidden');
                $('#desel').removeClass('hidden');

                var rows = aTable.rows();
                $.each(rows, function(i,r) {
                    var c = Number($(r).attr('course'));
                    addCourse(c);

                    $(r).addClass('selected');
                });
            });

            $('#desel').click(function() {
                $(this).addClass('hidden');
                $('#sel').removeClass('hidden');

                var rows = aTable.rows()
                $.each(rows, function(i, r) {
                    var c = Number($(r).attr('course'));
                    removeCourse(c);

                    $(r).removeClass('selected');
                });
            });

            $('#add_enrol').click(function() {
                $('#courses').val(JSON.stringify(selectedRows));
                $('#meta_enrol_sub').click();
            });
        }
    };
});
