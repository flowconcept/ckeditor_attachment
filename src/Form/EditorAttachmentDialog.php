<?php

/**
 * @file
 * Contains \Drupal\ckeditor_attachment\Form\EditorAttachmentDialog.
 */

namespace Drupal\ckeditor_attachment\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides an attachment dialog for text editors.
 */
class EditorAttachmentDialog extends FormBase {

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Constructs a form object for image dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   */
  public function __construct(EntityStorageInterface $file_storage) {
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('file')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_attachment_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // This form is special, in that the default values do not come from the
    // server side, but from the client side, from a text editor. We must cache
    // this data in form state, because when the form is rebuilt, we will be
    // receiving values from the form, instead of the values from the text
    // editor. If we don't cache it, this data will be lost.
    if (isset($form_state->getUserInput()['editor_object'])) {
      // By convention, the data that the text editor sends to any dialog is in
      // the 'editor_object' key. And the attachment dialog for text editors expects
      // that data to be the attributes for an <img> element.
      $file_element = $form_state->getUserInput()['editor_object'];
      $form_state->set('file_element', $file_element);
      $form_state->setCached(TRUE);
    }
    else {
      // Retrieve the attachment element's attributes from form state.
      $file_element = $form_state->get('file_element') ?: [];
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-attachment-dialog-form">';
    $form['#suffix'] = '</div>';

    $editor = editor_load($filter_format->id());

    // Construct strings to use in the upload validators.
    $config = array();
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['attachment'])) {
      $config = $settings['plugins']['attachment'];
    }
    $max_filesize = min(Bytes::toInt($config['max_size']), file_upload_max_size());

    $existing_file = isset($file_element['data-entity-uuid']) ? \Drupal::entityManager()->loadEntityByUuid('file', $file_element['data-entity-uuid']) : NULL;
    $fid = $existing_file ? $existing_file->id() : NULL;

    $form['fid'] = array(
      '#title' => $this->t('File'),
      '#type' => 'managed_file',
      '#upload_location' => $config['scheme'] . '://' . $config['directory'],
      '#default_value' => $fid ? array($fid) : NULL,
      '#upload_validators' => array(
        'file_validate_extensions' => array($config['extensions']),
        'file_validate_size' => array($max_filesize),
      ),
      '#required' => TRUE,
    );

    $form['attributes']['href'] = array(
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => isset($file_element['href']) ? $file_element['href'] : '',
      '#maxlength' => 2048,
      '#required' => TRUE,
    );

    // If the editor has attachment uploads enabled, show a managed_file form item,
    // otherwise show a (file URL) text form item.
    if ($config['status']) {
      $form['attributes']['href']['#access'] = FALSE;
      $form['attributes']['href']['#required'] = FALSE;
    }
    else {
      $form['fid']['#access'] = FALSE;
      $form['fid']['#required'] = FALSE;
    }

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['save_modal'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => '::submitForm',
        'event' => 'click',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid = $form_state->getValue(array('fid', 0));
    if (!empty($fid)) {
      $file = $this->fileStorage->load($fid);
      $file_url = file_create_url($file->getFileUri());
      // Transform absolute attachment URLs to relative URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url = file_url_transform_relative($file_url);
      $form_state->setValue(array('attributes', 'href'), $file_url);
      $form_state->setValue(array('attributes', 'data-entity-uuid'), $file->uuid());
      $form_state->setValue(array('attributes', 'data-entity-type'), 'file');
    }

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#editor-attachment-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
