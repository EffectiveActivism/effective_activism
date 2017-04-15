/**
 * @file
 * Autocomplete for location fields.
 */

(function ($, Drupal) {

  /**
   * Overrides core function to not split address in terms.
   *
   * @function Drupal.effective_activism.location.autocomplete.splitValues
   *
   * @param {string} value
   *   The value being entered by the user.
   *
   * @return {Array}
   *   Array containing value.
   */
  function locationAutocompleteSplitValues(value) {
    return [value];
  }

  /**
   * Overrides core function to not return quoted string.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {bool}
   *   Returns false to indicate the event status.
   */
  function locationSelectHandler(event, ui) {
    event.target.value = ui.item.value;
    // Return false to tell jQuery UI that we've filled in the value already.
    return false;
  }

  Drupal.autocomplete.splitValues = locationAutocompleteSplitValues;
  Drupal.autocomplete.options.select = locationSelectHandler;

})(jQuery, Drupal);
