(function ($, Drupal, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add( 'attachment',
  {
    init: function (editor)
    {
      console.log('init');
      editor.addCommand('attachment', new CKEDITOR.dialogCommand('attachment', {
        allowedContent: 'a[!href,class,!data-entity-type,!data-entity-uuid]',
        requiredContent: 'a[href,class,data-entity-type,data-entity-uuid]',
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor, data) {
          var dialogSettings = {
            title: editor.config.drupalImage_dialogTitle,
            dialogClass: 'editor-attachment-dialog'
          };
          Drupal.ckeditor.openDialog(editor, Drupal.url('editor/dialog/image/' + editor.config.drupal.format), {}, {}, dialogSettings);
          //Drupal.ckeditor.openDialog(editor, Drupal.url('editor/dialog/image/' + editor.config.drupal.format), data.existingValues, data.saveCallback, dialogSettings);
        }
      }));

      editor.ui.addButton('Attachment', {
        label: 'Attachment',
        command: 'attachment',
        icon: this.path + '/image.png'
      });
    }
  });


})(jQuery, Drupal, CKEDITOR);
