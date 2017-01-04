var StudentClassYears = (function($) {

  "use strict";

  var template;
  var classData = [];
  var $schoolYearInput;
  var $classesContainer;
  var $spinner;
  var $editableFields;
  var $submit;
  var $generateStatsButton;

  var init = function() {
    var request;

    _.templateSettings = {
      interpolate: /\{\{(.+?)\}\}/g
    };

    template = _.template($('#class-row-template').html());
    $schoolYearInput = $('#campaign_year');
    $classesContainer = $('#class-rows');
    $generateStatsButton = $('#refresh-stats-button');
    $spinner = $('.spinner', '#hkr_dnrs_class_years'); // $('#class-years-spinner');
    $editableFields = $('.class-count', '#hkr_dnrs_class_years');
    $submit = $('#publish');

    bindEvents();

    disableForm();
    request = getData($schoolYearInput.val());

    request.done(function() {
      render(classData);
      enableForm();
    });
  };

  var getData = function(schoolYear, refresh) {
    refresh = refresh || false;

    var jsonUrl = ajaxurl + "?action=hkr_dnrs_get_class_years&school_year=" + schoolYear;

    if (refresh) {
      jsonUrl += "&refresh=true";
    }

    return $.getJSON(jsonUrl, function(data) {
      classData = data;
    });
  };

  var updateData = function(schoolYear, classYear, classData) {
    var newData = {};

    newData[classYear] = classData;

    var args = {
      action: "hkr_dnrs_update_class_years",
      school_year: schoolYear,
      new_data: newData
    };

    return $.getJSON(ajaxurl, args, function(data) {
      classData = data;
    });
  }

  var render = function(data) {
    var classRows = data.map(function(studentClass) {
      return template({
        year: studentClass.year,
        count: studentClass.student_count,
        gave_count: studentClass.gave_count,
        gave_percent: studentClass.student_count ? Math.round(studentClass.gave_count / studentClass.student_count * 100) : 0,
      });
    });

    // render rows
    $classesContainer.html(classRows.join(' '));
  };

  var bindEvents = function() {
    $schoolYearInput.change(function() {
      var request;

      disableForm();
      request = getData($schoolYearInput.val());

      request.done(function() {
        render(classData);
        enableForm();
      });
    });

    $(document).on('change', '.class-count', function() {
      var $input = $(this),
          schoolYear = $schoolYearInput.val(),
          classYear = $input.data('class-year'),
          request;

      classData = classData.map(function(studentClass) {
        if (studentClass.year != classYear) {
          return studentClass;
        }

        return Object.assign({}, studentClass, { student_count: $input.val() });
      });

      render(classData);
      focusClassRow(+classYear - 1);
    });

    $generateStatsButton.click(function() {
      var request;

      disableForm();
      request = getData($schoolYearInput.val(), true);

      request.done(function() {
        classData = classData.map(function(studentClass) {
          // use current input value instead of overwriting with saved data
          var inputVal = $('#class-of-' + studentClass.year + '-count').val();
          var studentCount = (inputVal === '0' || inputVal === '') ? studentClass.student_count : +inputVal;

          return Object.assign({}, studentClass, { student_count: studentCount });
        });

        render(classData);
        enableForm();
      });

      return false;
    });
  };

  var focusClassRow = function(classYear) {
    var $row = $('.class-row[data-class-year="' + classYear + '"]');

    if ($row.length) {
      $row.find('.class-count').focus().select();
    }
  };

  var disableForm = function() {
    $spinner.addClass('is-active');
    $editableFields.attr('readonly', 'readonly');
    $submit.attr('disabled', 'disabled');
  };

  var enableForm = function() {
    $spinner.removeClass('is-active');
    $editableFields.attr('readonly', '');
    $submit.attr('disabled', null);
  };

  return {
    init: init
  };

})(jQuery);

(function($) {

  "use strict";

  $(document).ready(function() {
    StudentClassYears.init();
  });

})(jQuery);

if (typeof Object.assign != 'function') {
  Object.assign = function (target, varArgs) { // .length of function is 2
    'use strict';
    if (target == null) { // TypeError if undefined or null
      throw new TypeError('Cannot convert undefined or null to object');
    }

    var to = Object(target);

    for (var index = 1; index < arguments.length; index++) {
      var nextSource = arguments[index];

      if (nextSource != null) { // Skip over if undefined or null
        for (var nextKey in nextSource) {
          // Avoid bugs when hasOwnProperty is shadowed
          if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
            to[nextKey] = nextSource[nextKey];
          }
        }
      }
    }
    return to;
  };
}