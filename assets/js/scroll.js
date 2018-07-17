/**
 * @file
 * Scroll to result widget top on AJAX submit.
 */

(function ($, Drupal) {
  Drupal.behaviors.scrollToResultTop = {
    attach: function (context, settings) {
      var InlineEntityFormAddButtonId = $("[data-drupal-selector='edit-results-form-inline-entity-form-actions-ief-add-save']").attr('id');
      var InlineEntityFormAddCancelButtonId = $("[data-drupal-selector='edit-results-form-inline-entity-form-actions-ief-add-cancel']").attr('id');
      var InlineEntityFormEditButtonId = $("[data-drupal-selector='edit-results-form-inline-entity-form-entities-0-form-actions-ief-edit-save']").attr('id');
      var InlineEntityFormEditCancelButtonId = $("[data-drupal-selector='edit-results-form-inline-entity-form-entities-0-form-actions-ief-edit-cancel']").attr('id');
      if (typeof InlineEntityFormAddButtonId !== 'undefined') {
        var selector = '#' + InlineEntityFormAddButtonId;
        var instance = _.findWhere(Drupal.ajax.instances, {selector: selector});
        if (typeof instance !== 'undefined') {
          instance.options.complete = function (form_values, element, options) {
            $('html,body').animate({
              scrollTop: $("#edit-results-wrapper").offset().top - 200
            });
          }
        }
      }
      if (typeof InlineEntityFormAddCancelButtonId !== 'undefined') {
        var selector = '#' + InlineEntityFormAddCancelButtonId;
        var instance = _.findWhere(Drupal.ajax.instances, {selector: selector});
        if (typeof instance !== 'undefined') {
          instance.options.complete = function (form_values, element, options) {
            $('html,body').animate({
              scrollTop: $("#edit-results-wrapper").offset().top - 200
            });
          }
        }
      }
      if (typeof InlineEntityFormEditButtonId !== 'undefined') {
        var selector = '#' + InlineEntityFormEditButtonId;
        var instance = _.findWhere(Drupal.ajax.instances, {selector: selector});
        if (typeof instance !== 'undefined') {
          instance.options.complete = function (form_values, element, options) {
            $('html,body').animate({
              scrollTop: $("#edit-results-wrapper").offset().top - 200
            });
          }
        }
      }
      if (typeof InlineEntityFormEditCancelButtonId !== 'undefined') {
        var selector = '#' + InlineEntityFormEditCancelButtonId;
        var instance = _.findWhere(Drupal.ajax.instances, {selector: selector});
        if (typeof instance !== 'undefined') {
          instance.options.complete = function (form_values, element, options) {
            $('html,body').animate({
              scrollTop: $("#edit-results-wrapper").offset().top - 200
            });
          }
        }
      }
    }
  }
})(jQuery, Drupal);
