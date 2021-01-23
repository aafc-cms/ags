<?php

namespace Drupal\linkchecks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides an example block.
 *
 * @Block(
 *   id = "linkchecks_example",
 *   admin_label = @Translation("Example"),
 *   category = @Translation("linkchecks")
 * )
 */
class ExampleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $html = '<div id="links-report"></div><div id="request-log"></br></div>';
    $build['content'] = [
      '#markup' => $html,
      '#attached' => [
        'library' => [
          'linkchecks/linkcheckblock',
        ],
      ]
    ];
    
    return $build;
  }
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $formState) {
    $config = $this->getConfiguration();
    return $form;
  }
	
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState) {
    $this->configuration['linkchecks_example'] =
    $formState->getValue('linkchecks_example');
  }

}
