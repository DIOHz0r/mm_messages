<?php

namespace Drupal\mm_messages\Drush\Commands;

use Drupal\mm_messages\Service\MessageService;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush command file.
 */
class UnreadMessagesCommand extends DrushCommands {

  /**
   * @var \Drupal\mm_messages\Service\MessageService
   */
  protected MessageService $messageService;

  /**
   * Constructs a UnreadMessagesCommand object.
   */
  public function __construct(MessageService $message_service) {
    parent::__construct();
    $this->messageService = $message_service;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('mm_messages.message_service')
    );
  }

  /**
   * Custom Drush command to find unread messages older than 15 minutes.
   *
   * @command mm_messages:unread-messages
   *
   * @aliases mm_unread
   *
   * @usage mm_messages:unread-messages
   *   Search for unread messages and simulate sending via email.
   */
  public function findUnreadMessages(): void {
    $unread_messages = $this->messageService->getUnreadMessages();

    if (empty($unread_messages)) {
      $this->output()->writeln('No unread messages older than 15 minutes.');

      return;
    }

    foreach ($unread_messages as $message) {
      $this->output()->writeln('Sending message '.$message->message_id);
      $sender = User::load($message->sender_id);
      $receiver = User::load($message->receiver_id);
      $email_info = \sprintf(
        'Message from %s to %s: %s (Sent: %s)',
        $sender->getDisplayName(),
        $receiver->getDisplayName(),
        $message->message,
        mm_messages_format_time($message->timestamp)
      );
      $this->output()->writeln($email_info);
    }
    $this->output()->writeln('Unread messages have been processed.');
  }

}
