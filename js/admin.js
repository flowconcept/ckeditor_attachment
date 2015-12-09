/**
 * @file
 * CKEditor 'drupalimage' plugin admin behavior.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Provides the summary for the "drupalimage" plugin settings vertical tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behaviour to the "drupalimage" settings vertical tab.
   */
  Drupal.behaviors.ckeditorAttachmentSettingsSummary = {
    attach: function () {
      $('[data-ckeditor-plugin-id="attachment"]').drupalSetSummary(function (context) {
        var root = 'input[name="editor[settings][plugins][attachment]';
        var $status = $(root + '[status]"]');
        var $maxFileSize = $(root + '[max_size]"]');
        var $scheme = $(root + '[scheme]"]:checked');

        var maxFileSize = $maxFileSize.val() ? $maxFileSize.val() : $maxFileSize.attr('placeholder');

        if (!$status.is(':checked')) {
          return Drupal.t('Uploads disabled');
        }

        var output = '';
        output += Drupal.t('Uploads enabled, max size: @size', {'@size': maxFileSize});
        if ($scheme.length) {
          output += '<br />' + $scheme.attr('data-label');
        }
        return output;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
