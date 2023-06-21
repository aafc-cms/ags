<?php

namespace Drupal\wxt_overrides\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure WxT Overrides settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wxt_overrides_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wxt_overrides.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $blog_hide_link = $this->config('wxt_overrides.settings')->get('blog_hide_link');
    if (is_null($blog_hide_link)) {
      $blog_hide_link = FALSE;
    }
    $blog_hide_link = (integer) $blog_hide_link;
    $form['blog_hide_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide blog link.'),
      '#default_value' => $blog_hide_link,
    ];
    $blog_hide_tags = $this->config('wxt_overrides.settings')->get('blog_hide_tags');
    if (is_null($blog_hide_tags)) {
      $blog_hide_tags = FALSE;
    }
    $blog_hide_tags = (integer) $blog_hide_tags;
    $form['blog_hide_tags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide blog tags.'),
      '#default_value' => $blog_hide_tags,
    ];
    $blog_anon = $this->config('wxt_overrides.settings')->get('blog_anon');
    if (is_null($blog_anon)) {
      $blog_anon = FALSE;
    }
    $blog_anon = (integer) $blog_anon;
    $form['blog_anon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Anonymous role blog comments only.'),
      '#default_value' => $blog_anon,
    ];
    $form['example'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Example'),
      '#default_value' => $this->config('wxt_overrides.settings')->get('example'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('example') != 'example') {
      $form_state->setErrorByName('example', $this->t('The value is not correct.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('wxt_overrides.settings')
      ->set('example', $form_state->getValue('example'))
      ->set('blog_hide_link', $form_state->getValue('blog_hide_link'))
      ->set('blog_hide_tags', $form_state->getValue('blog_hide_tags'))
      ->set('blog_anon', $form_state->getValue('blog_anon'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
