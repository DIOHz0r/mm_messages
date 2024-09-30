(function ($, Drupal) {
  Drupal.behaviors.mmMessages = {
    attach: function (context, settings) {
      // Open users pop-up.
      $('.add-chat-button', context).once('addChatButton').on('click', function () {
        $('#user-selection-popup').show();
      });

      // Handle user select for new chats.
      $('.user-item', context).once('userItem').on('click', function () {
        let userId = $(this).data('user-id');
        loadChatHistory(userId, settings);
        loadChatItem(userId);
        $('.user-item[data-user-id="' + userId + '"]').remove();
        $('#user-selection-popup').hide();
      });

      // Close pop-up.
      $('#close-popup', context).once('closePopup').on('click', function () {
        $('#user-selection-popup').hide();
      });

      // Load chat history on click item.
      $('.chat-list', context).once('chatList').on('click', '.chat-item', function (e) {
        const receiverId = $(this).data('user-id');
        loadChatHistory(receiverId, settings);
        $(this).find('.unread-indicator').remove();
      });
    }
  };

  function loadChatHistory(receiverId, settings) {
    $('#chat-history').html(Drupal.theme.ajaxProgressThrobber(Drupal.t('Loading...')));
    Drupal.ajax({
      url: drupalSettings.mm_messages.chatHistoryUrl,
      wrapper: '#chat-history',
      type: 'GET',
      submit: {
        receiver_id: receiverId
      }
    }).execute();
  }

  function loadChatItem(receiverId) {
    Drupal.ajax({
      url: drupalSettings.mm_messages.chatItem,
      wrapper: '.chat-list',
      type: 'GET',
      submit: {
        receiver_id: receiverId
      }
    }).execute();
  }

})(jQuery, Drupal);
