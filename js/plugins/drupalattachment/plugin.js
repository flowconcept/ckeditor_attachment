(function ($, Drupal, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('drupalattachment',
  {

    init: function (editor) {

      editor.addCommand('drupalattachment', new CKEDITOR.dialogCommand('drupalattachment', {
        allowedContent: 'a[!href,!class,!data-entity-type,!data-entity-uuid]',
/*        requiredContent: 'a[href,data-entity-type,data-entity-uuid]',*/
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor, data) {
          var dialogSettings = {
            title: 'Insert attachment',
            dialogClass: 'editor-attachment-dialog'
          };
          var dialogSaveCallback = function (data) {
            var element = new CKEDITOR.dom.element( 'a' );
            element.setAttributes(data.attributes);
            element.setAttribute('target', '_blank');
            element.setAttribute('class', 'attachment');
            element.setHtml(data.linktext);
            editor.insertElement(element);
          };
          Drupal.ckeditor.openDialog(editor, Drupal.url('ckeditor_attachment/dialog/attachment/' + editor.config.drupal.format), {}, dialogSaveCallback, dialogSettings);
        }
      }));

      editor.ui.addButton('DrupalAttachment', {
        label: 'Attachment',
        toolbar: '',
        command: 'drupalattachment',
        icon: this.path + 'icons/attachment.png'
      });

    }
  });

})(jQuery, Drupal, CKEDITOR);
