<?php

/**
 * @file
 * Contains \Drupal\ckeditor_attachment\Plugin\CKEditorPlugin\Attachment.
 */

namespace Drupal\ckeditor_attachment\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\editor\Entity\Editor;


/**
 * Defines the "drupalimage" plugin.
 *
 * @CKEditorPlugin(
 *   id = "attachment",
 *   label = @Translation("Attachment"),
 *   module = "ckeditor"
 * )
 */
class Attachment extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_attachment') . '/js/plugins/attachment/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {

    return array(
      'Attachment_dialogTitleAdd' => t('Insert File'),
      'Attachment_dialogTitleEdit' => t('Edit File'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Attachment' => array(
        'label' => t('Attachment'),
        'image' => drupal_get_path('module', 'ckeditor_attachment') . '/js/plugins/attachment/image.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $form['#attached']['library'][] = 'ckeditor_attachment/admin';

    // Defaults.
    $config = array('attachment' => '');
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['attachment'])) {
      $config = $settings['plugins']['attachment'];
    }

    $config += array(
      'status' => FALSE,
      'scheme' => file_default_scheme(),
      'directory' => 'inline-attachments',
      'max_size' => '',
      'max_dimensions' => array('width' => '', 'height' => ''),
    );

    $form['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable file uploads'),
      '#default_value' => $config['status'],
      '#attributes' => array(
        'data-editor-attachment' => 'status',
      ),
    );
    $show_if_file_uploads_enabled = array(
      'visible' => array(
        ':input[data-editor-attachment="status"]' => array('checked' => TRUE),
      ),
    );

    // Any visible, writable wrapper can potentially be used for uploads,
    // including a remote file system that integrates with a CDN.
    $options = \Drupal::service('stream_wrapper_manager')->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);
    if (!empty($options)) {
      $form['scheme'] = array(
        '#type' => 'radios',
        '#title' => t('File storage'),
        '#default_value' => $config['scheme'],
        '#options' => $options,
        '#states' => $show_if_file_uploads_enabled,
        '#access' => count($options) > 1,
      );
    }
    // Set data- attributes with human-readable names for all possible stream
    // wrappers, so that ckeditor_attachment.admin's summary rendering
    // can use that.
    foreach (\Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE) as $scheme => $name) {
      $form['scheme'][$scheme]['#attributes']['data-label'] = t('Storage: @name', array('@name' => $name));
    }

    $form['directory'] = array(
      '#type' => 'textfield',
      '#default_value' => $config['directory'],
      '#title' => t('Upload directory'),
      '#description' => t("A directory relative to Drupal's files directory where uploaded images will be stored."),
      '#states' => $show_if_file_uploads_enabled,
    );

    $default_max_size = format_size(file_upload_max_size());
    $form['max_size'] = array(
      '#type' => 'textfield',
      '#default_value' => $config['max_size'],
      '#title' => t('Maximum file size'),
      '#description' => t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', array('@size' => $default_max_size)),
      '#maxlength' => 20,
      '#size' => 10,
      '#placeholder' => $default_max_size,
      '#states' => $show_if_file_uploads_enabled,
    );

    return $form;
  }
}
