//initializes the conversation box with the received messages
function initConversation() {
    let strConv = $('#conv-hist').val();
    if (strConv && strConv.length > 0) {
        let convArr = strConv.split('|');
        for (var i in convArr) {
            let msgObj = JSON.parse(convArr[i]);
            if (msgObj.user === "user") {
                msgObj.message = "<b>Me: </b>" + msgObj.message;
            }
            else msgObj.message = "<b>Yoda: </b>" + msgObj.message;
            let msg = createMsgLi(msgObj.message, msgObj.user);
            $('.conv-frame').append(msg)
        }
    }

}

//handles the button click event
function submitForm() {
    let text = $('#message-inp').val();
    if (text !== "") {
        //ads the message to the conversation
        let msg = createMsgLi(text, 'user');
        $('.conv-frame').append(msg);
        $('#message-inp').val('');

        //sends the message
        sendAjaxMessage(text);
    }
}

//sends the POST request to the server
function sendAjaxMessage(msg) {

    //shows the writing letter
    $('.write-span').show();

    //sends the post request
    let url = "sendMessageToYoda";

    $.ajax({
        url: url,
        type: 'POST',
        data: {'message': msg},
        success: ajaxResponseHandler,
        error: ajaxErrorHandler
    });
}

//returns an span element with innerText and className props
function createMsgLi(text, className) {
    let msg = document.createElement('LI');
    msg.innerHTML = text;
    msg.className = className;

    return msg;
}

//displays the response message
function ajaxResponseHandler(response) {
    //hides the writing letter
    $('.write-span').hide();

    //adds the message to the conversation
    let msg = createMsgLi(response, 'bot');
    $('.conv-frame').append(msg)
}

//logs the error on console
function ajaxErrorHandler(response) {
    //hides the writing letter
    $('.write-span').hide();

    //logs the error
    console.log(response);
}