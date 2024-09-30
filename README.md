# mm_messages Module

## Description

The `mm_messages` module provides a simple chat system integrated with Drupal,
allowing users to engage in one-on-one conversations. The module offers
AJAX-based chat functionality with a user-friendly interface that dynamically
loads chat history and allows adding new chats.

## Requirements

- Drupal 9.x or higher
- Basic knowledge of Drupal's module system.

## Installation

1. Download or clone the `mm_messages` module into your Drupal `modules/custom`
   directory:

   ```bash
   cd modules/custom
   git clone https://github.com/DIOHz0r/mm_messages.git
   ```

2. Enable the module using Drush:

   ```bash
   drush en mm_messages
   ```

3. Clear the cache:

   ```bash
   drush cr
   ```

## Usage

1. Once installed and enabled, visit the `/mm_messages/chats` page.
2. The chat page will display two sections:
  - A list of chats on the left.
  - A chat history section on the right.
3. Users can click on existing chats to load the chat history or start new chats
   by selecting users from a pop-up list.
4. The chat form is dynamically loaded with AJAX for easy interaction.

## Features Overview

### Chat List

- Displays a list of chats the current user (sender) has with other users (
  receivers).
- Users can click on a chat to load the conversation history.

### Chat History

- The chat history between the selected sender and receiver is loaded on the
  right when a chat is selected.

### Add New Chat

- Users can start a new chat with any user not already in the chat list by
  clicking the "Add New Chat" button.

### Send Message Form

- The message form appears in the chat history section, allowing users to send
  new messages via AJAX.

## Drush Command

The module also provides a custom Drush command to simulate sending unread
messages that are older than 15 minutes.

```bash
drush mm_messages:send-unread-emails
```

This command simulates the process of sending notifications for unread messages.
