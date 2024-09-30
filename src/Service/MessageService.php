<?php

namespace Drupal\mm_messages\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Service description.
 */
class MessageService {

  /**
   * The current user.
   */
  protected AccountInterface $account;

  /**
   * The database connection.
   */
  protected Connection $connection;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    Connection $connection,
    AccountInterface $account,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->connection = $connection;
    $this->account = $account;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param array $exclude
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function findNewAvailableChats(array $exclude) {
    // Exclude Anonymous user by default.
    $exclude[] = 0;
    $storage = $this->entityTypeManager->getStorage('user');
    $query = $storage->getQuery();
    $query->condition('uid', array_unique($exclude), 'NOT IN');
    $result = $query->execute();
    $users = $storage->loadMultiple($result);
    $user_data = [];

    foreach ($users as $user) {
      $avatar = $user->get('user_picture');
      $user_data[] = [
        'uid' => $user->id(),
        'user_name' => $user->getDisplayName(),
        'user_image' => !$avatar->isEmpty() ? $avatar->entity->createFileUrl(
        ) : NULL,
      ];
    }

    return $user_data;
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getUnreadMessages(): array {
    // 15 minutes
    $threshold = \Drupal::time()->getRequestTime() - 900;

    return $this->connection->select('mm_messages', 'm')
      ->fields(
        'm',
        ['message_id', 'sender_id', 'receiver_id', 'message', 'timestamp']
      )
      ->condition('is_read', 0)
      ->condition('timestamp', $threshold, '<')
      ->execute()
      ->fetchAll();
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getUserChats(): array {
    $current_uid = $this->account->id();
    $query = $this->connection->select('mm_messages', 'm')
      ->fields('m', ['message', 'timestamp', 'receiver_id', 'is_read'])
      ->condition('m.sender_id', $current_uid)
      ->groupBy('m.receiver_id')
      ->orderBy('m.timestamp', 'DESC');

    $results = $query->execute()->fetchAll();
    $receives = array_map(static function($obj) {
      return $obj->receiver_id;
    }, $results);
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple(
      $receives
    );

    $chats = [];

    foreach ($results as $result) {
      $user = $users[$result->receiver_id];
      $avatar = $user->get('user_picture');
      $chats[] = [
        'receiver_id' => $user->id(),
        'user_name' => $user->getDisplayName(),
        'user_avatar' => !$avatar->isEmpty() ? $avatar->entity->createFileUrl(
        ) : NULL,
        'last_message' => $result->message,
        'timestamp' => $result->timestamp,
        'unread' => !$result->is_read,
      ];
    }

    return $chats;
  }

  /**
   * @param $user_id
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadChatHistory($user_id): array {
    $current_uid = $this->account->id();
    $query = $this->connection->select('mm_messages', 'm')
      ->fields('m', ['sender_id', 'receiver_id', 'message', 'timestamp'])
      ->condition('sender_id', [$current_uid, $user_id], 'IN')
      ->condition('receiver_id', [$current_uid, $user_id], 'IN')
      ->orderBy('timestamp');
    $messages = $query->execute()->fetchAll();
    $users = $this->entityTypeManager->getStorage('user')
      ->loadMultiple([$current_uid, $user_id]);

    foreach ($messages as $msg) {
      $user = $users[$msg->sender_id];
      $avatar = $user->get('user_picture');
      $msg->sender_name = $user->getDisplayName();
      $msg->user_image = !$avatar->isEmpty() ? $avatar->entity->createFileUrl(
      ) : NULL;
    }

    return $messages;
  }

  /**
   * @param $user_id
   *
   * @return void
   * @throws \Exception
   */
  public function markAsRead($user_id): void {
    $current_uid = $this->account->id();
    $this->connection->update('mm_messages')
      ->fields(['is_read' => 1])
      ->condition('receiver_id', $current_uid)
      ->condition('sender_id', $user_id)
      ->execute();
  }

  /**
   * @param $receiver_id
   * @param $message
   *
   * @return void
   * @throws \Exception
   */
  public function sendMessage($receiver_id, $message): void {
    $sender_id = $this->account->id();

    $this->connection->insert('mm_messages')
      ->fields([
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'message' => $message,
        'timestamp' => time(),
      ])
      ->execute();
  }

}
