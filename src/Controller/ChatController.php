<?php

namespace Drupal\mm_messages\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\mm_messages\Form\SendMessageForm;
use Drupal\mm_messages\Service\MessageService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Mm messages routes.
 */
class ChatController extends ControllerBase {

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @var \Drupal\mm_messages\Service\MessageService
   */
  protected MessageService $messageService;

  /**
   * @param \Drupal\mm_messages\Service\MessageService $message_service
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(
    MessageService $message_service,
    FormBuilderInterface $form_builder,
    AccountProxyInterface $current_user,
  ) {
    $this->messageService = $message_service;
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
  }

  /**
   * Chat main content.
   */
  public function chatPage(): array {
    $chats = $this->messageService->getUserChats();
    $exclude = array_map(static function($obj) {
      return $obj['receiver_id'];
    }, $chats);
    $new_chats = $this->messageService->findNewAvailableChats($exclude);

    return [
      '#theme' => 'mm_messages_chat',
      '#chats' => $chats,
      '#chats_available' => $new_chats,
      '#attached' => [
        'drupalSettings' => [
          'mm_messages' => [
            'chatItem' => Url::fromRoute('mm_messages.load_chat_item')
              ->toString(),
            'chatHistoryUrl' => Url::fromRoute('mm_messages.load_chat_history')
              ->toString(),
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mm_messages.message_service'),
      $container->get('form_builder'),
      $container->get('current_user')
    );
  }

  /**
   * Load and render the chat history between users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function loadChatHistory(Request $request) {
    $receiver_id = $request->get('receiver_id');
    $messages = $this->messageService->loadChatHistory($receiver_id);
    if (!$messages) {
      $user = $this->entityTypeManager()->getStorage('user')
        ->load($receiver_id);
      $receiver_name = $user->getDisplayName();
    }
    $form = $this->formBuilder->getForm(SendMessageForm::class, $receiver_id);
    $build = [
      '#theme' => 'mm_messages_chat_history',
      '#messages' => $messages,
      '#receiver_name' => $receiver_name ?? NULL,
      '#form' => $form,
    ];
    $output = \Drupal::service('renderer')->render($build);
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand('#chat-history', ['#markup' => $output])
    );

    return $response;
  }

  /**
   * Load the chat info for sidebar.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function loadChatItem(Request $request) {
    $receiver_id = $request->get('receiver_id');
    $user = $this->entityTypeManager()->getStorage('user')
      ->load($receiver_id);
    $avatar = $user->get('user_picture');
    $build = [
      '#theme' => 'mm_messages_chat_item',
      '#chat' => [
        'receiver_id' => $receiver_id,
        'user_name' => $user->getDisplayName(),
        'user_avatar' => !$avatar->isEmpty() ? $avatar->entity->createFileUrl(
        ) : NULL,
        'last_message' => $this->t('New chat started!'),
        'timestamp' => time(),
        'unread' => FALSE,
      ],
    ];
    $output = \Drupal::service('renderer')->render($build);
    $response = new AjaxResponse();
    $response->addCommand(new PrependCommand('.chat-list', $output));

    return $response;
  }

}
