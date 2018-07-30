/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.datetimepicker = {
    attach: function (context, settings) {

      // Setting the current language for the calendar.
      var language = drupalSettings.path.currentLanguage;
      var startDayWeek = 1;
      var dateFormat = 'Y-m-d H:i';
      var allowTimepicker = true;
      var step = 15;
      var hoursFormat = 'H:i';
      var format = 'd.m.Y';

      $(context).find('input.datetimepicker').once('datePicker').each(function () {
        var input = $(this);
        $("#" + input.attr('id')).datetimepicker({
          lang: language,
          format: dateFormat,
          formatTime: hoursFormat,
          lazyInit: true,
          timepicker: allowTimepicker,
          todayButton: true,
          dayOfWeekStart: startDayWeek,
          step: step,
          formatDate: format,
          onChangeDateTime: function (dp,$input) {
            var startDateInput = $('input.datetimepicker[name="start_date[0][value]"]');
            var endDateInput = $('input.datetimepicker[name="end_date[0][value]"]');
            var startDate = new Date(startDateInput.val());
            var endDate = new Date(endDateInput.val());
            if (startDate > endDate) {
              if (startDateInput.is(input)) {
                endDateInput.val(startDate.getFullYear() + '-' + ('0' + (startDate.getMonth() + 1)).slice(-2) + '-' + ('0' + startDate.getDate()).slice(-2) + ' ' + ('0' + (startDate.getHours() + 1)).slice(-2) + ':' + ('0' + startDate.getMinutes()).slice(-2));
              }
            }
          }
        });
      });
    },
  };

})(jQuery, Drupal, drupalSettings);
