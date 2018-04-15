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
        });
      });
    },
  };

})(jQuery, Drupal, drupalSettings);
