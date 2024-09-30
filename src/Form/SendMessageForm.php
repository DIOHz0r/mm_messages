<?php

namespace Drupal\mm_messages\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\mm_messages\Service\MessageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Mm messages form.
 */
class SendMessageForm extends FormBase {

  /**
   * @var \Drupal\mm_messages\Service\MessageService
   */
  protected MessageService $messageService;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * ChatMessageForm constructor.
   *
   * @param \Drupal\mm_messages\Service\MessageService $messageService
   *   The message service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   */
  public function __construct(
    MessageService $messageService,
    RendererInterface $renderer,
  ) {
    $this->messageService = $messageService;
    $this->renderer = $renderer;
  }

  /**
   * AJAX callback for form submission.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *
   * @throws \Exception
   */
  public function ajaxSubmitCallback(
    array &$form,
    FormStateInterface $form_state,
  ) {
    $response = new AjaxResponse();
    $receiver_id = $form_state->getValue('receiver_id');
    $message = $form_state->getValue('message');

    // Call the service to send the message.
    if (!$receiver_id || !$message) {
      $response->addCommand(
        new HtmlCommand('#form-error', t('Invalid form data'))
      );

      return $response;
    }

    $this->messageService->sendMessage($receiver_id, $message);
    $currentUser = $this->currentUser();
    $currentUser = \Drupal::service('entity_type.manager')->getStorage('user')
      ->load($currentUser->id());
    $avatar = $currentUser->get('user_picture');
    $message_data = [
      '#theme' => 'mm_messages_chat_balloons',
      '#messages' => [
        [
          'sender_id' => $currentUser->id(),
          'sender_name' => $currentUser->getDisplayName(),
          'user_image' => !$avatar->isEmpty() ? $avatar->entity->createFileUrl(
          ) : NULL,
          'message' => $message,
          'timestamp' => time(),
        ],
      ],
      '#current_user' => $currentUser,
    ];
    $message_html = $this->renderer->renderPlain($message_data);
    $response->addCommand(new AppendCommand('', $message_html));
    $response->addCommand(
      new InvokeCommand('textarea[name="message"]', 'val', [''])
    );

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    $user_id = NULL,
  ) {
    $form['message'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Type your message...'),
        'rows' => 2,
      ],
      '#suffix' => '<div class="form-error" id="form-error"></div>',
    ];

    $form['receiver_id'] = [
      '#type' => 'hidden',
      '#value' => $user_id,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
        'wrapper' => 'ajax-wrapper',
        'disable-refocus' => TRUE,
      ],
    ];

    /*
     * I know this token to false is not ok for security reasons however,
     * I put this to quick-solve an issue with JS and AJAX outdated token
     * problem when the form is loaded asynchronously.
     * TODO: fix this.
     */
    $form['#token'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mm_messages.message_service'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'send_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here because we are handling submission via AJAX callback.
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('message'))) {
      $form_state->setErrorByName(
        'message',
        $this->t('Message should not be empty.')
      );
    }
  }

}
