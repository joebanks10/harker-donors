var StudentClassYears = (function($) {

  "use strict";

  var template;
  var $schoolYearInput;
  var $generateStatsButton;

  var init = function() {
    _.templateSettings = {
      interpolate: /\{\{(.+?)\}\}/g
    };

    template = _.template($('#class-row-template').html());
    $schoolYearInput = $('#school_year');
    $generateStatsButton = $('#refresh-stats-button');

    bindEvents();
    renderRows($schoolYearInput.val());
  };

  var renderRows = function(schoolYear, refresh) {
    var jsonUrl = ajaxurl + "?action=hkr_dnrs_class_years&school_year=" + schoolYear;
    var $spinner = $('.spinner', '#hkr_dnrs_class_years'); // $('#class-years-spinner');
    var $fields = $('.class-count', '#hkr_dnrs_class_years');
    var $submit = $('#publish');

    if (refresh) {
      jsonUrl += "&refresh=true";
    }

    // update dom before sending request
    $spinner.addClass('is-active');
    $fields.attr('readonly', 'readonly');
    $submit.attr('disabled', 'disabled');

    // get data
    $.getJSON(jsonUrl, function(data) {
      var classRows = data.map(function(studentClass) {
        return template({
          year: studentClass.year,
          count: studentClass.student_count || '',
          gave_count: studentClass.gave_count || '',
          gave_percent: studentClass.gave_percent || ''
        });
      });

      // render rows
      $('#class-rows').html(classRows.join(' '));

      // restore initial state of dom
      $spinner.removeClass('is-active');
      $fields.attr('readonly', '');
      $submit.attr('disabled', null);
    });
  };

  var bindEvents = function() {
    $schoolYearInput.change(function() {
      renderRows($schoolYearInput.val());
    });

    $generateStatsButton.click(function() {
      renderRows($schoolYearInput.val(), true);
      
      return false;
    });
  };

  return {
    init: init,
    renderRows: renderRows
  };

})(jQuery);

(function($) {

  "use strict";

  $(document).ready(function() {
    StudentClassYears.init();
  });

})(jQuery);
