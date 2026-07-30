$.ajax({
    url: 'chat.php',
    type: 'POST',
    data: {
        action: 'send',
        message: message,
        senderId: senderId, // ID de l'expéditeur
        receiverId: receiverId // ID du destinataire sélectionné
    },
    success: function() {
        $('#message').val('');
        loadMessages();
    }
});