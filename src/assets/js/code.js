const messagesContainer = $(".messenger-messagingView .m-body"),
    messengerTitleDefault = $(".messenger-headTitle").text(),
    messageInputContainer = $(".messenger-sendCard"),
    messageInput = $("#message-form .m-send"),
    auth_id = $("meta[name=url]").attr("data-auth-user"),
    my_channel_id = $("meta[name=url]").attr("data-auth-channel"),
    url = $("meta[name=url]").attr("content"),
    messengerTheme = $("meta[name=messenger-theme]").attr("content"),
    defaultMessengerColor = $("meta[name=messenger-color]").attr("content"),
    csrfToken = $('meta[name="csrf-token"]').attr("content");

/**
 *-------------------------------------------------------------
 * Global variables
 *-------------------------------------------------------------
 */
var messenger,
  typingTimeout,
  typingNow = 0,
  temporaryMsgId = 0,
  defaultAvatarInSettings = null,
  messengerColor,
  dark_mode,
  messages_page = 1;

const currentChannelId = () => $("meta[name=channel_id]").attr("content");
const setCurrentChannelId = (channel_id) => $("meta[name=channel_id]").attr("content", channel_id);

/**
 *-------------------------------------------------------------
 * Pusher initialization
 *-------------------------------------------------------------
 */
Pusher.logToConsole = chatify.pusher.debug;
const pusher = new Pusher(chatify.pusher.key, {
    encrypted: chatify.pusher.options.encrypted,
    cluster: chatify.pusher.options.cluster,
    wsHost: chatify.pusher.options.host,
    wsPort: chatify.pusher.options.port,
    wssPort: chatify.pusher.options.port,
    forceTLS: chatify.pusher.options.useTLS,
    authEndpoint: chatify.pusherAuthEndpoint,
  auth: {
    headers: {
      "X-CSRF-TOKEN": csrfToken,
    },
  },
});
/**
 *-------------------------------------------------------------
 * Re-usable methods
 *-------------------------------------------------------------
 */
const escapeHtml = (unsafe) => {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
};
function actionOnScroll(selector, callback, topScroll = false) {
  $(selector).on("scroll", function () {
    let element = $(this).get(0);
    const condition = topScroll
      ? element.scrollTop == 0
      : element.scrollTop + element.clientHeight >= element.scrollHeight;
    if (condition) {
      callback();
    }
  });
}
function routerPush(title, url) {
  $("meta[name=url]").attr("content", url);
  return window.history.pushState({}, title || document.title, url);
}
function updateSelectedContact(channel_id) {
  $(document).find(".messenger-list-item").removeClass("m-list-active");
  $(document)
    .find(
      ".messenger-list-item[data-channel=" + (channel_id || currentChannelId()) + "]"
    )
    .addClass("m-list-active");
}
/**
 *-------------------------------------------------------------
 * Global Templates
 *-------------------------------------------------------------
 */
// Loading svg
function loadingSVG(size = "25px", className = "", style = "") {
  return `
<svg style="${style}" class="loadingSVG ${className}" xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 40 40" stroke="#ffffff">
<g fill="none" fill-rule="evenodd">
<g transform="translate(2 2)" stroke-width="3">
<circle stroke-opacity=".1" cx="18" cy="18" r="18"></circle>
<path d="M36 18c0-9.94-8.06-18-18-18" transform="rotate(349.311 18 18)">
<animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur=".8s" repeatCount="indefinite"></animateTransform>
</path>
</g>
</g>
</svg>
`;
}
function loadingWithContainer(className) {
  return `<div class="${className}" style="text-align:center;padding:15px">${loadingSVG(
    "25px",
    "",
    "margin:auto"
  )}</div>`;
}

// loading placeholder for users list item
function listItemLoading(items) {
  let template = "";
  for (let i = 0; i < items; i++) {
    template += `
<div class="loadingPlaceholder">
<div class="loadingPlaceholder-wrapper">
<div class="loadingPlaceholder-body">
<table class="loadingPlaceholder-header">
<tr>
<td style="width: 45px;"><div class="loadingPlaceholder-avatar"></div></td>
<td>
<div class="loadingPlaceholder-name"></div>
<div class="loadingPlaceholder-date"></div>
</td>
</tr>
</table>
</div>
</div>
</div>
`;
  }
  return template;
}

// loading placeholder for avatars
function avatarLoading(items) {
  let template = "";
  for (let i = 0; i < items; i++) {
    template += `
<div class="loadingPlaceholder">
<div class="loadingPlaceholder-wrapper">
<div class="loadingPlaceholder-body">
<table class="loadingPlaceholder-header">
<tr>
<td style="width: 45px;">
<div class="loadingPlaceholder-avatar" style="margin: 2px;"></div>
</td>
</tr>
</table>
</div>
</div>
</div>
`;
  }
  return template;
}

// While sending a message, show this temporary message card.
function sendTempMessageCard(message, id) {
  return `
 <div class="message-card mc-sender" data-id="${id}">
     <div class="message-card-content">
         <div class="message">
             ${message}
             <sub>
                 <span class="far fa-clock"></span>
             </sub>
         </div>
     </div>
 </div>
`;
}
// upload image preview card.
function attachmentTemplate(fileType, fileName, imgURL = null) {
  if (fileType != "image") {
    return (
      `
 <div class="attachment-preview">
     <span class="fas fa-times cancel"></span>
     <p style="padding:0px 30px;"><span class="fas fa-file"></span> ` +
      escapeHtml(fileName) +
      `</p>
 </div>
`
    );
  } else {
    return (
      `
<div class="attachment-preview">
 <span class="fas fa-times cancel"></span>
 <div class="image-file chat-image" style="background-image: url('` +
      imgURL +
      `');"></div>
 <p><span class="fas fa-file-image"></span> ` +
      escapeHtml(fileName) +
      `</p>
</div>
`
    );
  }
}

// Active Status Circle
function activeStatusCircle() {
  return `<span class="activeStatus"></span>`;
}

/**
 *-------------------------------------------------------------
 * Css Media Queries [For responsive design]
 *-------------------------------------------------------------
 */
$(window).resize(function () {
  cssMediaQueries();
});
function cssMediaQueries() {
  if (window.matchMedia("(min-width: 980px)").matches) {
    $(".messenger-listView").removeAttr("style");
  }
  if (window.matchMedia("(max-width: 980px)").matches) {
    $("body")
      .find(".messenger-list-item")
      .find("tr[data-action]")
      .attr("data-action", "1");
    $("body").find(".favorite-list-item").find("div").attr("data-action", "1");
  } else {
    $("body")
      .find(".messenger-list-item")
      .find("tr[data-action]")
      .attr("data-action", "0");
    $("body").find(".favorite-list-item").find("div").attr("data-action", "0");
  }
}

/**
 *-------------------------------------------------------------
 * App Modal
 *-------------------------------------------------------------
 */
let app_modal = function ({
  show = true,
  name,
  data = 0,
  buttons = true,
  header = null,
  body = null,
}) {
  const modal = $(".app-modal[data-name=" + name + "]");
  // header
  header ? modal.find(".app-modal-header").html(header) : "";

  // body
  body ? modal.find(".app-modal-body").html(body) : "";

  // buttons
  buttons == true
    ? modal.find(".app-modal-footer").show()
    : modal.find(".app-modal-footer").hide();

  // show / hide
  if (show == true) {
    modal.show();
    $(".app-modal-card[data-name=" + name + "]").addClass("app-show-modal");
    $(".app-modal-card[data-name=" + name + "]").attr("data-modal", data);
  } else {
    modal.hide();
    $(".app-modal-card[data-name=" + name + "]").removeClass("app-show-modal");
    $(".app-modal-card[data-name=" + name + "]").attr("data-modal", data);
  }
};

/**
 *-------------------------------------------------------------
 * Slide to bottom on [action] - e.g. [message received, sent, loaded]
 *-------------------------------------------------------------
 */
function scrollToBottom(container) {
  $(container)
    .stop()
    .animate({
      scrollTop: $(container)[0].scrollHeight,
    });
}

/**
 *-------------------------------------------------------------
 * click and drag to scroll - function
 *-------------------------------------------------------------
 */
function hScroller(scroller) {
  const slider = document.querySelector(scroller);
  let isDown = false;
  let startX;
  let scrollLeft;

  slider.addEventListener("mousedown", (e) => {
    isDown = true;
    startX = e.pageX - slider.offsetLeft;
    scrollLeft = slider.scrollLeft;
  });
  slider.addEventListener("mouseleave", () => {
    isDown = false;
  });
  slider.addEventListener("mouseup", () => {
    isDown = false;
  });
  slider.addEventListener("mousemove", (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - slider.offsetLeft;
    const walk = (x - startX) * 1;
    slider.scrollLeft = scrollLeft - walk;
  });
}

/**
 *-------------------------------------------------------------
 * Disable/enable message form fields, messaging container...
 * on load info or if needed elsewhere.
 *
 * Default : true
 *-------------------------------------------------------------
 */
function disableOnLoad(disable = true) {
  if (disable) {
    // hide star button
    $(".add-to-favorite").hide();
    // hide send card
    $(".messenger-sendCard").hide();
    // add loading opacity to messages container
    messagesContainer.css("opacity", ".5");
    // disable message form fields
    messageInput.attr("readonly", "readonly");
    $("#message-form button").attr("disabled", "disabled");
    $(".upload-attachment").attr("disabled", "disabled");
  } else {
    // show star button
    if (currentChannelId() != my_channel_id) {
      $(".add-to-favorite").show();
    }
    // show send card
    $(".messenger-sendCard").show();
    // remove loading opacity to messages container
    messagesContainer.css("opacity", "1");
    // enable message form fields
    messageInput.removeAttr("readonly");
    $("#message-form button").removeAttr("disabled");
    $(".upload-attachment").removeAttr("disabled");
  }
}

/**
 *-------------------------------------------------------------
 * Error message card
 *-------------------------------------------------------------
 */
function errorMessageCard(id) {
  messagesContainer
    .find(".message-card[data-id=" + id + "]")
    .addClass("mc-error");
  messagesContainer
    .find(".message-card[data-id=" + id + "]")
    .find("svg.loadingSVG")
    .remove();
  messagesContainer
    .find(".message-card[data-id=" + id + "] p")
    .prepend('<span class="fas fa-exclamation-triangle"></span>');
}

/**
 *-------------------------------------------------------------
 * Fetch id data (user/group) and update the view
 *-------------------------------------------------------------
 */
function IDinfo(channel_id) {
  // clear temporary message id
  temporaryMsgId = 0;
  // clear typing now
  typingNow = 0;
  // show loading bar
  NProgress.start();
  // disable message form
  disableOnLoad();
  if (messenger != 0) {
    // get shared photos
    getSharedPhotos(channel_id);
    // Get info
    $.ajax({
      url: url + "/idInfo",
      method: "POST",
      data: { _token: csrfToken, channel_id },
      dataType: "JSON",
      success: (data) => {
        if (!data?.fetch) {
          NProgress.done();
          NProgress.remove();

          data?.message && alert(data.message)

          return;
        }

        // messenger info
        $(".messenger-infoView").html(data.infoHtml)
        $(".messenger-infoView")
          .find(".avatar-channel")
          .css("background-image", 'url("' + data.channel_avatar + '")');
        $(".header-avatar").css(
          "background-image",
          'url("' + data.channel_avatar + '")'
        );

        // Show shared and actions
        $(".messenger-infoView-btns .delete-conversation").show();
        $(".messenger-infoView-shared").show();

        // fetch messages
        fetchMessages(channel_id, true);

        // focus on messaging input
        messageInput.focus();

        // update info in view
        $(".messenger-infoView .info-name").text(data.fetch.name);
        $(".m-header-messaging .user-name").text(data.fetch.name);

        // Star status
        data.favorite > 0
          ? $(".add-to-favorite").addClass("favorite")
          : $(".add-to-favorite").removeClass("favorite");
        // form reset and focus
        $("#message-form").trigger("reset");
        cancelAttachment();
        messageInput.focus();
      },
      error: () => {
        console.error("Couldn't fetch user data!");
        // remove loading bar
        NProgress.done();
        NProgress.remove();
      },
    });
  } else {
    // remove loading bar
    NProgress.done();
    NProgress.remove();
  }
}

/**
 *-------------------------------------------------------------
 * Send message function
 *-------------------------------------------------------------
 */
function sendMessage() {
  temporaryMsgId += 1;
  let tempID = `temp_${temporaryMsgId}`;
  let hasFile = !!$(".upload-attachment").val();
  const inputValue = $.trim(messageInput.val());
  if (inputValue.length > 0 || hasFile) {
    const formData = new FormData($("#message-form")[0]);
    formData.append("channel_id", currentChannelId());
    formData.append("temporaryMsgId", tempID);
    formData.append("_token", csrfToken);
    $.ajax({
      url: $("#message-form").attr("action"),
      method: "POST",
      data: formData,
      dataType: "JSON",
      processData: false,
      contentType: false,
      beforeSend: () => {
        // remove message hint
        $(".messages").find(".message-hint").hide();
        // append a temporary message card
        if (hasFile) {
          messagesContainer
            .find(".messages")
            .append(
              sendTempMessageCard(
                inputValue + "\n" + loadingSVG("28px"),
                tempID
              )
            );
        } else {
          messagesContainer
            .find(".messages")
            .append(sendTempMessageCard(inputValue, tempID));
        }
        // scroll to bottom
        scrollToBottom(messagesContainer);
        messageInput.css({ height: "42px" });
        // form reset and focus
        $("#message-form").trigger("reset");
        cancelAttachment();
        messageInput.focus();
      },
      success: (data) => {
        if (data.error > 0) {
          // message card error status
          errorMessageCard(tempID);
          console.error(data.error_msg);
        } else {
          // update contact item
          updateContactItem(currentChannelId());
          // temporary message card
          const tempMsgCardElement = messagesContainer.find(
            `.message-card[data-id=${data.tempID}]`
          );
          // add the message card coming from the server before the temp-card
          tempMsgCardElement.before(data.message);
          // then, remove the temporary message card
          tempMsgCardElement.remove();
          // scroll to bottom
          scrollToBottom(messagesContainer);
          // send contact item updates
          sendContactItemUpdates(true);
        }
      },
      error: () => {
        // message card error status
        errorMessageCard(tempID);
        // error log
        console.error(
          "Failed sending the message! Please, check your server response."
        );
      },
    });
  }
  return false;
}

/**
 *-------------------------------------------------------------
 * Fetch messages from database
 *-------------------------------------------------------------
 */
let messagesPage = 1;
let noMoreMessages = false;
let messagesLoading = false;
function setMessagesLoading(loading = false) {
  if (!loading) {
    messagesContainer.find(".messages").find(".loading-messages").remove();
    NProgress.done();
    NProgress.remove();
  } else {
    messagesContainer
      .find(".messages")
      .prepend(loadingWithContainer("loading-messages"));
  }
  messagesLoading = loading;
}
function fetchMessages(id, newFetch = false) {
  if (newFetch) {
    messagesPage = 1;
    noMoreMessages = false;
  }
  if (messenger != 0 && !noMoreMessages && !messagesLoading) {
    const messagesElement = messagesContainer.find(".messages");
    setMessagesLoading(true);
    $.ajax({
      url: url + "/fetchMessages",
      method: "POST",
      data: {
        _token: csrfToken,
        id: id,
        page: messagesPage,
      },
      dataType: "JSON",
      success: (data) => {
        setMessagesLoading(false);
        if (messagesPage == 1) {
          messagesElement.html(data.messages);
          scrollToBottom(messagesContainer);
        } else {
          const lastMsg = messagesElement.find(
            messagesElement.find(".message-card")[0]
          );
          const curOffset =
            lastMsg.offset().top - messagesContainer.scrollTop();
          messagesElement.prepend(data.messages);
          messagesContainer.scrollTop(lastMsg.offset().top - curOffset);
        }
        // trigger seen event
        makeSeen(true);
        // Pagination lock & messages page
        noMoreMessages = messagesPage >= data?.last_page;
        if (!noMoreMessages) messagesPage += 1;
        // Enable message form if messenger not = 0; means if data is valid
        if (messenger != 0) {
          disableOnLoad(false);
        }
      },
      error: (error) => {
        setMessagesLoading(false);
        console.error(error);
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Cancel file attached in the message.
 *-------------------------------------------------------------
 */
function cancelAttachment() {
  $(".messenger-sendCard").find(".attachment-preview").remove();
  $(".upload-attachment").replaceWith(
    $(".upload-attachment").val("").clone(true)
  );
}

/**
 *-------------------------------------------------------------
 * Cancel updating avatar in settings
 *-------------------------------------------------------------
 */
function cancelUpdatingAvatar() {
  $(".upload-avatar-preview").css("background-image", defaultAvatarInSettings);
  $(".upload-avatar").replaceWith($(".upload-avatar").val("").clone(true));
}

/**
 *-------------------------------------------------------------
 * Pusher channels and event listening..
 *-------------------------------------------------------------
 */

// subscribe to the channel
const channelName = "private-chatify";
var clientSendChannel;

function initClientChannel() {
  if (currentChannelId()) {
    clientSendChannel = pusher.subscribe(`${channelName}.${currentChannelId()}`);
  }
}
initClientChannel();

function listenAllContactChannels(){
  // listen to all existing contact channels
  const list = document.querySelectorAll('.listOfContacts .contact-item')
  list.forEach(item => {
    const channelID = item.getAttribute('data-channel')
    const channel = pusher.subscribe(`${channelName}.${channelID}`);
    _listenChannelEvent(channel)
  })
}
function _listenChannelEvent(channel){
  // Listen to messages, and append if data received
  channel.bind("messaging", function (data) {
    if (data.to_channel_id == currentChannelId() && data.from_id != auth_id) {
      $(".messages").find(".message-hint").remove();
      messagesContainer.find(".messages").append(data.message);
      scrollToBottom(messagesContainer);
      makeSeen(true);
      // remove unseen counter for the user from the contacts list
      $(".messenger-list-item[data-channel=" + currentChannelId() + "]")
          .find("tr>td>b")
          .remove();
    }

    playNotificationSound("new_message", !(data.to_channel_id == currentChannelId()));
  });

  // listen to typing indicator
  channel.bind("client-typing", function (data) {
    if (data.to_channel_id == currentChannelId()) {
      data.typing == true
          ? messagesContainer.find(".typing-indicator").show()
          : messagesContainer.find(".typing-indicator").hide();
    }
    // scroll to bottom
    scrollToBottom(messagesContainer);
  });

  // listen to seen event
  channel.bind("client-seen", function (data) {
    if (data.to_channel_id == currentChannelId()) {
      if (data.seen == true) {
        $(".message-time")
            .find(".fa-check")
            .before('<span class="fas fa-check-double seen"></span> ');
        $(".message-time").find(".fa-check").remove();
      }
    }
  });

  // listen to contact item updates event
  channel.bind("client-contactItem", function (data) {
    const channel_id = data.to
    const from_user_id = data.from

    if (data.update) {
      updateContactItem(channel_id);
    } else {
      console.error("Can not update contact item!");
    }
  });

  // listen on message delete event
  channel.bind("client-messageDelete", function (data) {
    $("body").find(`.message-card[data-id=${data.id}]`).remove();
  });

  // listen on delete conversation event
  channel.bind("client-deleteConversation", function (data) {
    if (data.to_channel_id == currentChannelId()) {
      $("body").find(`.messages`).html("");
      $(".messages").find(".message-hint").show();
    }
  });
}


// -------------------------------------
// presence channel [User Active Status]
var activeStatusChannel = pusher.subscribe("presence-activeStatus");

// Joined
activeStatusChannel.bind("pusher:member_added", function (member) {
  setActiveStatus(1);
  $(".messenger-list-item[data-user=" + member.id + "]")
    .find(".activeStatus")
    .remove();
  $(".messenger-list-item[data-user=" + member.id + "]")
    .find(".avatar")
    .before(activeStatusCircle());
});

// Leaved
activeStatusChannel.bind("pusher:member_removed", function (member) {
  setActiveStatus(0);
  $(".messenger-list-item[data-user=" + member.id + "]")
    .find(".activeStatus")
    .remove();
});

function handleVisibilityChange() {
  if (!document.hidden) {
    makeSeen(true);
  }
}

document.addEventListener("visibilitychange", handleVisibilityChange, false);

/**
 *-------------------------------------------------------------
 * Trigger typing event
 *-------------------------------------------------------------
 */
function isTyping(status) {
  return clientSendChannel.trigger("client-typing", {
    from_id: auth_id, // Me
    to_channel_id: currentChannelId(), // Messenger
    typing: status,
  });
}

/**
 *-------------------------------------------------------------
 * Trigger seen event
 *-------------------------------------------------------------
 */
function makeSeen(status) {
  if (document?.hidden) {
    return;
  }
  // remove unseen counter for the user from the contacts list
  $(".messenger-list-item[data-channel=" + currentChannelId() + "]")
    .find("tr>td>b")
    .remove();
  // seen
  $.ajax({
    url: url + "/makeSeen",
    method: "POST",
    data: { _token: csrfToken, channel_id: currentChannelId() },
    dataType: "JSON",
  });
  return clientSendChannel.trigger("client-seen", {
    from_id: auth_id, // Me
    to_channel_id: currentChannelId(), // Messenger
    seen: status,
  });
}

/**
 *-------------------------------------------------------------
 * Trigger contact item updates
 *-------------------------------------------------------------
 */
function sendContactItemUpdates(status) {
  return clientSendChannel.trigger("client-contactItem", {
    from: auth_id, // Me
    to: currentChannelId(), // Channel
    update: status,
  });
}

/**
 *-------------------------------------------------------------
 * Trigger message delete
 *-------------------------------------------------------------
 */
function sendMessageDeleteEvent(messageId) {
  return clientSendChannel.trigger("client-messageDelete", {
    id: messageId,
  });
}
/**
 *-------------------------------------------------------------
 * Trigger delete conversation
 *-------------------------------------------------------------
 */
function sendDeleteConversationEvent() {
  return clientSendChannel.trigger("client-deleteConversation", {
    from: auth_id,
    to: currentChannelId(),
  });
}

/**
 *-------------------------------------------------------------
 * Check internet connection using pusher states
 *-------------------------------------------------------------
 */
function checkInternet(state, selector) {
  let net_errs = 0;
  const messengerTitle = $(".messenger-headTitle");
  switch (state) {
    case "connected":
      if (net_errs < 1) {
        messengerTitle.text(messengerTitleDefault);
        selector.addClass("successBG-rgba");
        selector.find("span").hide();
        selector.slideDown("fast", function () {
          selector.find(".ic-connected").show();
        });
        setTimeout(function () {
          $(".internet-connection").slideUp("fast");
        }, 3000);
      }
      break;
    case "connecting":
      messengerTitle.text($(".ic-connecting").text());
      selector.removeClass("successBG-rgba");
      selector.find("span").hide();
      selector.slideDown("fast", function () {
        selector.find(".ic-connecting").show();
      });
      net_errs = 1;
      break;
    // Not connected
    default:
      messengerTitle.text($(".ic-noInternet").text());
      selector.removeClass("successBG-rgba");
      selector.find("span").hide();
      selector.slideDown("fast", function () {
        selector.find(".ic-noInternet").show();
      });
      net_errs = 1;
      break;
  }
}

/**
 *-------------------------------------------------------------
 * Get contacts
 *-------------------------------------------------------------
 */
let contactsPage = 1;
let contactsLoading = false;
let noMoreContacts = false;
function setContactsLoading(loading = false) {
  if (!loading) {
    $(".listOfContacts").find(".loading-contacts").remove();
  } else {
    $(".listOfContacts").append(
      `<div class="loading-contacts">${listItemLoading(4)}</div>`
    );
  }
  contactsLoading = loading;
}
function getContacts() {
  if (!contactsLoading && !noMoreContacts) {
    setContactsLoading(true);
    $.ajax({
      url: url + "/getContacts",
      method: "GET",
      data: { _token: csrfToken, page: contactsPage },
      dataType: "JSON",
      success: (data) => {
        setContactsLoading(false);
        if (contactsPage < 2) {
          $(".listOfContacts").html(data.contacts);
        } else {
          $(".listOfContacts").append(data.contacts);
        }
        listenAllContactChannels()
        updateSelectedContact();
        // update data-action required with [responsive design]
        cssMediaQueries();
        // Pagination lock & messages page
        noMoreContacts = contactsPage >= data?.last_page;
        if (!noMoreContacts) contactsPage += 1;
      },
      error: (error) => {
        setContactsLoading(false);
        console.error(error);
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Update contact item
 *-------------------------------------------------------------
 */
function updateContactItem(channel_id) {
  $.ajax({
    url: url + "/updateContacts",
    method: "POST",
    data: {
      _token: csrfToken,
      channel_id,
    },
    dataType: "JSON",
    success: (data) => {
      $(".listOfContacts")
        .find(".contact-item[data-channel=" + channel_id + "]")
        .remove();
      if (data.contactItem) $(".listOfContacts").prepend(data.contactItem);
      if (channel_id == currentChannelId()) updateSelectedContact(channel_id);
      // show/hide message hint (empty state message)
      const totalContacts =
        $(".listOfContacts").find(".contact-item")?.length || 0;
      if (totalContacts > 0) {
        $(".listOfContacts").find(".message-hint").hide();
      } else {
        $(".listOfContacts").find(".message-hint").show();
      }
      // update data-action required with [responsive design]
      cssMediaQueries();
    },
    error: (error) => {
      console.error(error);
    },
  });
}

/**
 *-------------------------------------------------------------
 * Get channel_id by user_id
 *-------------------------------------------------------------
 */

function getChannelId(user_id) {
  return $.ajax({
    url: url + "/get-channel-id",
    method: "POST",
    data: { _token: csrfToken, user_id: user_id },
    dataType: "JSON"
  });
}

/**
 *-------------------------------------------------------------
 * Star
 *-------------------------------------------------------------
 */

function star(channel_id) {
  if (currentChannelId() != auth_id) {
    $.ajax({
      url: url + "/star",
      method: "POST",
      data: { _token: csrfToken, channel_id: channel_id },
      dataType: "JSON",
      success: (data) => {
        data.status > 0
          ? $(".add-to-favorite").addClass("favorite")
          : $(".add-to-favorite").removeClass("favorite");
      },
      error: () => {
        console.error("Server error, check your response");
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Get favorite list
 *-------------------------------------------------------------
 */
function getFavoritesList() {
  $(".messenger-favorites").html(avatarLoading(4));
  $.ajax({
    url: url + "/favorites",
    method: "POST",
    data: { _token: csrfToken },
    dataType: "JSON",
    success: (data) => {
      if (data.count > 0) {
        $(".favorites-section").show();
        $(".messenger-favorites").html(data.favorites);
      } else {
        $(".favorites-section").hide();
      }
      // update data-action required with [responsive design]
      cssMediaQueries();
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Get shared photos
 *-------------------------------------------------------------
 */
function getSharedPhotos(channel_id) {
  $.ajax({
    url: url + "/shared",
    method: "POST",
    data: { _token: csrfToken, channel_id: channel_id },
    dataType: "JSON",
    success: (data) => {
      $(".shared-photos-list").html(data.shared);
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Search in messenger
 *-------------------------------------------------------------
 */
let searchPage = 1;
let noMoreDataSearch = false;
let searchLoading = false;
let searchTempVal = "";
function setSearchLoading(loading = false) {
  if (!loading) {
    $(".search-records").find(".loading-search").remove();
  } else {
    $(".search-records").append(
      `<div class="loading-search">${listItemLoading(4)}</div>`
    );
  }
  searchLoading = loading;
}
function messengerSearch(input) {
  if (input != searchTempVal) {
    searchPage = 1;
    noMoreDataSearch = false;
    searchLoading = false;
  }
  searchTempVal = input;
  if (!searchLoading && !noMoreDataSearch) {
    if (searchPage < 2) {
      $(".messenger-tab .search-records").html("");
    }
    setSearchLoading(true);
    $.ajax({
      url: url + "/search",
      method: "GET",
      data: { _token: csrfToken, input: input, page: searchPage },
      dataType: "JSON",
      success: (data) => {
        setSearchLoading(false);
        if (searchPage < 2) {
          $(".messenger-tab .search-records").html(data.records);
        } else {
          $(".messenger-tab .search-records").append(data.records);
        }
        // update data-action required with [responsive design]
        cssMediaQueries();
        // Pagination lock & messages page
        noMoreDataSearch = searchPage >= data?.last_page;
        if (!noMoreDataSearch) searchPage += 1;
      },
      error: (error) => {
        setSearchLoading(false);
        console.error(error);
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * Delete Group Chat
 *-------------------------------------------------------------
 */
function deleteGroupChat(channel_id) {
  $.ajax({
    url: url + "/group-chat/delete",
    method: "POST",
    data: { _token: csrfToken, channel_id: channel_id, user_id: auth_id },
    dataType: "JSON",
    beforeSend: () => {
      // hide delete modal
      app_modal({
        show: false,
        name: "delete-group",
      });
      // Show waiting alert modal
      app_modal({
        show: true,
        name: "alert",
        buttons: false,
        body: loadingSVG("32px", null, "margin:auto"),
      });
    },
    success: (data) => {
      // Hide waiting alert modal
      app_modal({
        show: false,
        name: "alert",
        buttons: true,
        body: "",
      });

      $(".listOfContacts")
          .find(".contact-item[data-channel=" + channel_id + "]")
          .remove();

      // load channel
      routerPush(document.title, `${url}/${my_channel_id}`);
      setCurrentChannelId(my_channel_id);
      updateSelectedContact(my_channel_id);

      // load data from database
      IDinfo(my_channel_id);
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Leave Group Chat
 *-------------------------------------------------------------
 */
function leaveGroupChat(channel_id) {
  $.ajax({
    url: url + "/group-chat/leave",
    method: "POST",
    data: { _token: csrfToken, channel_id: channel_id, user_id: auth_id },
    dataType: "JSON",
    beforeSend: () => {
      // hide delete modal
      app_modal({
        show: false,
        name: "leave-group",
      });
      // Show waiting alert modal
      app_modal({
        show: true,
        name: "alert",
        buttons: false,
        body: loadingSVG("32px", null, "margin:auto"),
      });
    },
    success: (data) => {
      // Hide waiting alert modal
      app_modal({
        show: false,
        name: "alert",
        buttons: true,
        body: "",
      });

      $(".listOfContacts")
          .find(".contact-item[data-channel=" + channel_id + "]")
          .remove();

      // load channel
      routerPush(document.title, `${url}/${my_channel_id}`);
      setCurrentChannelId(my_channel_id);
      updateSelectedContact(my_channel_id);

      // load data from database
      IDinfo(my_channel_id);
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Delete Conversation
 *-------------------------------------------------------------
 */
function deleteConversation(channel_id) {
  $.ajax({
    url: url + "/deleteConversation",
    method: "POST",
    data: { _token: csrfToken, channel_id: channel_id },
    dataType: "JSON",
    beforeSend: () => {
      // hide delete modal
      app_modal({
        show: false,
        name: "delete",
      });
      // Show waiting alert modal
      app_modal({
        show: true,
        name: "alert",
        buttons: false,
        body: loadingSVG("32px", null, "margin:auto"),
      });
    },
    success: (data) => {
      // delete contact from the list
      $(".listOfContacts")
        .find(".contact-item[data-channel=" + channel_id + "]")
        .remove();
      // refresh info
      IDinfo(channel_id);

      if (!data.deleted)
        return alert("Error occurred, messages can not be deleted!");

      // Hide waiting alert modal
      app_modal({
        show: false,
        name: "alert",
        buttons: true,
        body: "",
      });

      sendDeleteConversationEvent();

      // update contact list item
      sendContactItemUpdates(true);
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Delete Message By ID
 *-------------------------------------------------------------
 */
function deleteMessage(id) {
  $.ajax({
    url: url + "/deleteMessage",
    method: "POST",
    data: { _token: csrfToken, id: id },
    dataType: "JSON",
    beforeSend: () => {
      // hide delete modal
      app_modal({
        show: false,
        name: "delete",
      });
      // Show waiting alert modal
      app_modal({
        show: true,
        name: "alert",
        buttons: false,
        body: loadingSVG("32px", null, "margin:auto"),
      });
    },
    success: (data) => {
      $(".messages").find(`.message-card[data-id=${id}]`).remove();
      if (!data.deleted)
        console.error("Error occurred, message can not be deleted!");

      sendMessageDeleteEvent(id);

      // Hide waiting alert modal
      app_modal({
        show: false,
        name: "alert",
        buttons: true,
        body: "",
      });
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Update Settings
 *-------------------------------------------------------------
 */
function updateSettings() {
  const formData = new FormData($("#update-settings")[0]);
  if (messengerColor) {
    formData.append("messengerColor", messengerColor);
  }
  if (dark_mode) {
    formData.append("dark_mode", dark_mode);
  }
  $.ajax({
    url: url + "/updateSettings",
    method: "POST",
    data: formData,
    dataType: "JSON",
    processData: false,
    contentType: false,
    beforeSend: () => {
      // close settings modal
      app_modal({
        show: false,
        name: "settings",
      });
      // Show waiting alert modal
      app_modal({
        show: true,
        name: "alert",
        buttons: false,
        body: loadingSVG("32px", null, "margin:auto"),
      });
    },
    success: (data) => {
      if (data.error) {
        // Show error message in alert modal
        app_modal({
          show: true,
          name: "alert",
          buttons: true,
          body: data.msg,
        });
      } else {
        // Hide alert modal
        app_modal({
          show: false,
          name: "alert",
          buttons: true,
          body: "",
        });

        // reload the page
        location.reload(true);
      }
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Set Active status
 *-------------------------------------------------------------
 */
function setActiveStatus(status) {
  $.ajax({
    url: url + "/setActiveStatus",
    method: "POST",
    data: { _token: csrfToken, status: status },
    dataType: "JSON",
    success: (data) => {
      // Nothing to do
    },
    error: () => {
      console.error("Server error, check your response");
    },
  });
}

/**
 *-------------------------------------------------------------
 * Group Chat Events
 *-------------------------------------------------------------
 */
function groupChatAddingModalInit(){
  const modalGroupChannel = $(".app-modal[data-name=addGroup]")

  let searchPage = 1;
  let noMoreDataSearch = false;
  let searchLoading = false;
  let searchTempVal = "";
  const addedUserIds = []

  const userSearchEl = modalGroupChannel.find(".user-search")
  const searchRecordsEl = modalGroupChannel.find(".search-records")

  // Group button action to show group modal
  $("body").on("click", ".group-btn", function (e) {
    e.preventDefault();
    app_modal({
      show: true,
      name: "addGroup",
    });
  });

  // Group modal [cancel button]
  modalGroupChannel.find(".app-modal-footer .cancel")
    .on("click", function () {
      app_modal({
        show: false,
        name: "addGroup",
      });
    });


  /*
  -----------------------------
  -------- Search User --------
  -----------------------------
  */
  function setSearchLoading(loading = false) {
    if (!loading) {
      searchRecordsEl.find(".loading-search").remove();
    } else {
      searchRecordsEl.append(
        `<div class="loading-search">${listItemLoading(4)}</div>`
      );
    }
    searchLoading = loading;
  }
  function handleUserSearch(input) {
    if (input != searchTempVal) {
      searchPage = 1;
      noMoreDataSearch = false;
      searchLoading = false;
    }
    searchTempVal = input;
    if (!searchLoading && !noMoreDataSearch) {
      if (searchPage < 2) {
        searchRecordsEl.html("");
      }
      setSearchLoading(true);
      $.ajax({
        url: url + "/search-users",
        method: "GET",
        data: { _token: csrfToken, input: input, page: searchPage },
        dataType: "JSON",
        success: (data) => {
          setSearchLoading(false);

          let html = '';
          if(typeof data.records == 'string'){
            html = data.records
          } else {
            data.records.filter(({user, view}) => !addedUserIds.includes(user.id)).forEach(({user, view}) => {
              html += view
            })
          }

          if (searchPage < 2) {
            searchRecordsEl.html(html);
          } else {
            searchRecordsEl.append(html);
          }
          // update data-action required with [responsive design]
          cssMediaQueries();
          // Pagination lock & messages page
          noMoreDataSearch = searchPage >= data?.last_page;
          if (!noMoreDataSearch) searchPage += 1;
        },
        error: (error) => {
          setSearchLoading(false);
          console.error(error);
        },
      });
    }
  }

  const debouncedSearch = debounce(function () {
    const value = userSearchEl.val();
    handleUserSearch(value);
  }, 500);
  userSearchEl.on("keyup", function (e) {
    const value = $(this).val();
    if ($.trim(value).length > 0) {
      userSearchEl.trigger("focus");
      debouncedSearch();
    }
  });


  /*
  ------------------------------------------------
  ------------ Search Result & Submit ------------
  ------------------------------------------------
  */

  /* -------- Add User to group -------- */
  $("body").on("click", ".search-records .user-list-item", function () {
    const userID = $(this).attr("data-user");
    const addedUserView = modalGroupChannel.find('.added-users')

    addedUserView.prepend($(this))
    addedUserIds.push(Number(userID))
  });

  /* -------- Remove User in group -------- */
  $("body").on("click", ".added-users .user-list-item", function () {
    const userID = $(this).attr("data-user");

    addedUserIds.splice(addedUserIds.indexOf(Number(userID)), 1)
    $(this).remove()
  });

  /* -------- Create Group Channel -------- */
  $("#addGroupForm").on("submit", (e) => {
    e.preventDefault();
    createGroupChat();
  });
  function createGroupChat() {
    const addGroupForm = $("#addGroupForm");
    const groupNameVal = $.trim(addGroupForm.find('#group_name').val());
    const avatar = addGroupForm.find('.upload-avatar').prop('files')

    const formData = new FormData();
    formData.append("avatar", avatar ? avatar[0] : null);
    formData.append("group_name", groupNameVal);
    formData.append("user_ids", addedUserIds);
    formData.append("_token", csrfToken);

    $.ajax({
      url: addGroupForm.attr("action"),
      method: "POST",
      data: formData,
      dataType: "JSON",
      processData: false,
      contentType: false,
      beforeSend: () => {
        // close settings modal
        app_modal({
          show: false,
          name: "addGroup",
        });
        // Show waiting alert modal
        app_modal({
          show: true,
          name: "alert",
          buttons: false,
          body: loadingSVG("32px", null, "margin:auto"),
        });
      },
      success: (data) => {
        if (data.error) {
          // Show error message in alert modal
          app_modal({
            show: true,
            name: "alert",
            buttons: true,
            body: data.msg,
          });
        } else {
          // Hide alert modal
          app_modal({
            show: false,
            name: "alert",
            buttons: true,
            body: "",
          });

          const channel_id = data.channel.id

          // pusher subscribe new channel
          const channel_pusher = pusher.subscribe(`${channelName}.${channel_id}`);
          _listenChannelEvent(channel_pusher)

          // update route
          routerPush(document.title, `${url}/${channel_id}`);
          setCurrentChannelId(channel_id);
          updateSelectedContact(channel_id);

          // load data from database
          IDinfo(channel_id);

          setTimeout(()=>{
            updateContactItem(channel_id);
          }, 500)

          // reset form
          addGroupForm.trigger("reset");
          addedUserIds.length = 0
          modalGroupChannel.find('.added-users')?.html("")
          modalGroupChannel.find('.search-records')?.html("")
        }
      },
      error: () => {
        console.error("Server error, check your response");
      },
    });
  }
}

/**
 *-------------------------------------------------------------
 * On DOM ready
 *-------------------------------------------------------------
 */
$(document).ready(function () {
  // get contacts list
  getContacts();

  // get contacts list
  getFavoritesList();

  // group chat modal event
  groupChatAddingModalInit();

  // Clear typing timeout
  clearTimeout(typingTimeout);

  // NProgress configurations
  NProgress.configure({ showSpinner: false, minimum: 0.7, speed: 500 });

  // make message input autosize.
  autosize($(".m-send"));

  // check if pusher has access to the channel [Internet status]
  pusher.connection.bind("state_change", function (states) {
    let selector = $(".internet-connection");
    checkInternet(states.current, selector);
    // listening for pusher:subscription_succeeded - first load
    clientSendChannel.bind("pusher:subscription_succeeded", function () {
      // On connection state change [Updating] and get [info & msgs]
      if ($(".messenger-list-item").find("tr[data-action]").attr("data-action") == "1") {
        $(".messenger-listView").hide();
      }
      currentChannelId() && currentChannelId().length > 2 && IDinfo(currentChannelId());
    });
  });

  // tabs on click, show/hide...
  $(".messenger-listView-tabs a").on("click", function () {
    var dataView = $(this).attr("data-view");
    $(".messenger-listView-tabs a").removeClass("active-tab");
    $(this).addClass("active-tab");
    $(".messenger-tab").hide();
    $(".messenger-tab[data-view=" + dataView + "]").show();
  });

  // click on contact listOfContacts
  $("body").on("click", ".messenger-list-item.contact-item", async function () {
    $(".messenger-list-item").removeClass("m-list-active");
    $(this).addClass("m-list-active");

    const channel_id = $(this).attr("data-channel");

    routerPush(document.title, `${url}/${channel_id}`);
    setCurrentChannelId(channel_id);
    updateSelectedContact(channel_id);

    // load data from database
    IDinfo(channel_id);
  });

  // click on search results
  $("body").on("click", ".messenger-list-item.search-item", async function () {
    $(".messenger-list-item.search-item").removeClass("m-list-active");
    $(this).addClass("m-list-active");

    const userID = $(this).attr("data-user");

    getChannelId(userID).then(res => {
      const {channel_id, type} = res

      // pusher subscribe new channel
      if(type && type === 'new_channel'){
        const channel = pusher.subscribe(`${channelName}.${channel_id}`);
        _listenChannelEvent(channel)
      }

      // update route
      routerPush(document.title, `${url}/${channel_id}`);
      setCurrentChannelId(channel_id);
      updateSelectedContact(channel_id);

      // load data from database
      IDinfo(channel_id);
    }).catch(e => {
      console.log(e)
    })
  });

  // click action for list item [user/group]
  $("body").on("click", ".messenger-list-item", function () {
    if ($(this).find("tr[data-action]").attr("data-action") == "1") {
      $(".messenger-listView").hide();
    }
  });

  // show info side button
  $("body").on("click", ".messenger-infoView nav a , .show-infoSide", function () {
    $(".messenger-infoView").toggle();
  });

  // make favorites card draggable on click to slide.
  hScroller(".messenger-favorites");

  // click action for favorite button
  $("body").on("click", ".favorite-list-item", function () {
    if ($(this).find("div").attr("data-action") == "1") {
      $(".messenger-listView").hide();
    }
    const channel_id = $(this).find("div.avatar").attr("data-channel");
    setCurrentChannelId(channel_id);
    IDinfo(channel_id);
    updateSelectedContact(channel_id);
    routerPush(document.title, `${url}/${channel_id}`);
  });

  // list view buttons
  $(".listView-x").on("click", function () {
    $(".messenger-listView").hide();
  });
  $(".show-listView").on("click", function () {
    routerPush(document.title, `${url}/`);
    $(".messenger-listView").show();
  });

  // click action for [add to favorite] button.
  $(".add-to-favorite").on("click", function () {
    star(currentChannelId());
  });

  // calling Css Media Queries
  cssMediaQueries();

  // message form on submit.
  $("#message-form").on("submit", (e) => {
    e.preventDefault();
    sendMessage();
  });

  // message input on keyup [Enter to send, Enter+Shift for new line]
  $("#message-form .m-send").on("keyup", (e) => {
    // if enter key pressed.
    if (e.which == 13 || e.keyCode == 13) {
      // if shift + enter key pressed, do nothing (new line).
      // if only enter key pressed, send message.
      if (!e.shiftKey) {
        triggered = isTyping(false);
        sendMessage();
      }
    }
  });

  // On [upload attachment] input change, show a preview of the image/file.
  $("body").on("change", ".upload-attachment", (e) => {
    let file = e.target.files[0];
    if (!attachmentValidate(file)) return false;
    let reader = new FileReader();
    let sendCard = $(".messenger-sendCard");
    reader.readAsDataURL(file);
    reader.addEventListener("loadstart", (e) => {
      $("#message-form").before(loadingSVG());
    });
    reader.addEventListener("load", (e) => {
      $(".messenger-sendCard").find(".loadingSVG").remove();
      if (!file.type.match("image.*")) {
        // if the file not image
        sendCard.find(".attachment-preview").remove(); // older one
        sendCard.prepend(attachmentTemplate("file", file.name));
      } else {
        // if the file is an image
        sendCard.find(".attachment-preview").remove(); // older one
        sendCard.prepend(
          attachmentTemplate("image", file.name, e.target.result)
        );
      }
    });
  });

  function attachmentValidate(file) {
    const fileElement = $(".upload-attachment");
    const { name: fileName, size: fileSize } = file;
    const fileExtension = fileName.split(".").pop();
    if (
      !chatify.allAllowedExtensions.includes(
        fileExtension.toString().toLowerCase()
      )
    ) {
      alert("file type not allowed");
      fileElement.val("");
      return false;
    }
    // Validate file size.
    if (fileSize > chatify.maxUploadSize) {
      alert("File is too large!");
      return false;
    }
    return true;
  }

  // Attachment preview cancel button.
  $("body").on("click", ".attachment-preview .cancel", () => {
    cancelAttachment();
  });

  // typing indicator on [input] keyDown
  $("#message-form .m-send").on("keydown", () => {
    if (typingNow < 1) {
      isTyping(true);
      typingNow = 1;
    }
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(function () {
      isTyping(false);
      typingNow = 0;
    }, 1000);
  });

  // Image modal
  $("body").on("click", ".chat-image", function () {
    let src = $(this).css("background-image").split(/"/)[1];
    $("#imageModalBox").show();
    $("#imageModalBoxSrc").attr("src", src);
  });
  $(".imageModal-close").on("click", function () {
    $("#imageModalBox").hide();
  });

  // Search input on focus
  $(".messenger-search").on("focus", function () {
    $(".messenger-tab").hide();
    $('.messenger-tab[data-view="search"]').show();
  });
  $(".messenger-search").on("blur", function () {
    setTimeout(function () {
      $(".messenger-tab").hide();
      $('.messenger-tab[data-view="users"]').show();
    }, 200);
  });
  // Search action on keyup
  const debouncedSearch = debounce(function () {
    const value = $(".messenger-search").val();
    messengerSearch(value);
  }, 500);
  $(".messenger-search").on("keyup", function (e) {
    const value = $(this).val();
    if ($.trim(value).length > 0) {
      $(".messenger-search").trigger("focus");
      debouncedSearch();
    } else {
      $(".messenger-tab").hide();
      $('.messenger-listView-tabs a[data-view="users"]').trigger("click");
    }
  });

  // Delete Group button
  $("body").on("click", ".messenger-infoView-btns .delete-group", function () {
    app_modal({
      name: "delete-group",
    });
  });
  // Leave Group button
  $("body").on("click", ".messenger-infoView-btns .leave-group", function () {
    app_modal({
      name: "leave-group",
    });
  });
  // Delete Conversation button
  $("body").on("click", ".messenger-infoView-btns .delete-conversation", function () {
    app_modal({
      name: "delete",
    });
  });
  // Delete Message Button
  $("body").on("click", ".message-card .actions .delete-btn", function () {
    app_modal({
      name: "delete",
      data: $(this).data("id"),
    });
  });
  // Delete modal [on delete button click]
  $(".app-modal[data-name=delete]")
    .find(".app-modal-footer .delete")
    .on("click", function () {
      const id = $("body")
        .find(".app-modal[data-name=delete]")
        .find(".app-modal-card")
        .attr("data-modal");
      if (id == 0) {
        deleteConversation(currentChannelId());
      } else {
        deleteMessage(id);
      }
      app_modal({
        show: false,
        name: "delete",
      });
    });
  // Delete group modal [on button click]
  $(".app-modal[data-name=delete-group]")
      .find(".app-modal-footer .delete")
      .on("click", function () {
        deleteGroupChat(currentChannelId())
        app_modal({
          show: false,
          name: "delete-group",
        });
      });
  // Leave group modal [on button click]
  $(".app-modal[data-name=leave-group]")
      .find(".app-modal-footer .delete")
      .on("click", function () {
        leaveGroupChat(currentChannelId())

        app_modal({
          show: false,
          name: "leave-group",
        });
      });

  // Delete group modal [on cancel click]
  $(".app-modal[data-name=delete-group]")
      .find(".app-modal-footer .cancel")
      .on("click", function () {
        app_modal({
          show: false,
          name: "delete-group",
        });
      });
  // Leave group modal [on cancel click]
  $(".app-modal[data-name=leave-group]")
      .find(".app-modal-footer .cancel")
      .on("click", function () {
        app_modal({
          show: false,
          name: "leave-group",
        });
      });

  // delete modal [cancel button]
  $(".app-modal[data-name=delete]")
    .find(".app-modal-footer .cancel")
    .on("click", function () {
      app_modal({
        show: false,
        name: "delete",
      });
    });

  // Settings button action to show settings modal
  $("body").on("click", ".settings-btn", function (e) {
    e.preventDefault();
    app_modal({
      show: true,
      name: "settings",
    });
  });

  // on submit settings' form
  $("#update-settings").on("submit", (e) => {
    e.preventDefault();
    updateSettings();
  });
  // Settings modal [cancel button]
  $(".app-modal[data-name=settings]")
    .find(".app-modal-footer .cancel")
    .on("click", function () {
      app_modal({
        show: false,
        name: "settings",
      });
      cancelUpdatingAvatar();
    });
  // upload avatar on change
  $("body").on("change", ".upload-avatar", (e) => {
    // store the original avatar
    if (defaultAvatarInSettings == null) {
      defaultAvatarInSettings = $(".upload-avatar-preview").css(
        "background-image"
      );
    }
    let file = e.target.files[0];
    if (!attachmentValidate(file)) return false;
    let reader = new FileReader();
    reader.readAsDataURL(file);
    reader.addEventListener("loadstart", (e) => {
      $(".upload-avatar-preview").append(
        loadingSVG("42px", "upload-avatar-loading")
      );
    });
    reader.addEventListener("load", (e) => {
      $(".upload-avatar-preview").find(".loadingSVG").remove();
      if (!file.type.match("image.*")) {
        // if the file is not an image
        console.error("File you selected is not an image!");
      } else {
        // if the file is an image
        $(".upload-avatar-preview").css(
          "background-image",
          'url("' + e.target.result + '")'
        );
      }
    });
  });
  // change messenger color button
  $("body").on("click", ".update-messengerColor .color-btn", function () {
    messengerColor = $(this).attr("data-color");
    $(".update-messengerColor .color-btn").removeClass("m-color-active");
    $(this).addClass("m-color-active");
  });
  // Switch to Dark/Light mode
  $("body").on("click", ".dark-mode-switch", function () {
    if ($(this).attr("data-mode") == "0") {
      $(this).attr("data-mode", "1");
      $(this).removeClass("far");
      $(this).addClass("fas");
      dark_mode = "dark";
    } else {
      $(this).attr("data-mode", "0");
      $(this).removeClass("fas");
      $(this).addClass("far");
      dark_mode = "light";
    }
  });

  //Messages pagination
  actionOnScroll(
    ".m-body.messages-container",
    function () {
      fetchMessages(currentChannelId());
    },
    true
  );
  //Contacts pagination
  actionOnScroll(".messenger-tab.users-tab", function () {
    getContacts();
  });
  //Search pagination
  actionOnScroll(".messenger-tab.search-tab", function () {
    messengerSearch($(".messenger-search").val());
  });
});

/**
 *-------------------------------------------------------------
 * Observer on DOM changes
 *-------------------------------------------------------------
 */
let previousMessengerId = currentChannelId();
const observer = new MutationObserver(function (mutations) {
  if (currentChannelId() !== previousMessengerId) {
    previousMessengerId = currentChannelId();
    initClientChannel();
  }
});
const config = { subtree: true, childList: true };

// start listening to changes
observer.observe(document, config);

// stop listening to changes
// observer.disconnect();

/**
 *-------------------------------------------------------------
 * Resize messaging area when resize the viewport.
 * on mobile devices when the keyboard is shown, the viewport
 * height is changed, so we need to resize the messaging area
 * to fit the new height.
 *-------------------------------------------------------------
 */
var resizeTimeout;
window.visualViewport.addEventListener("resize", (e) => {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(function () {
    const h = e.target.height;
    if (h) {
      $(".messenger-messagingView").css({ height: h + "px" });
    }
  }, 100);
});

/**
 *-------------------------------------------------------------
 * Emoji Picker
 *-------------------------------------------------------------
 */
const emojiButton = document.querySelector(".emoji-button");

const emojiPicker = new EmojiButton({
  theme: messengerTheme,
  autoHide: false,
  position: "top-start",
});

emojiButton.addEventListener("click", (e) => {
  e.preventDefault();
  emojiPicker.togglePicker(emojiButton);
});

emojiPicker.on("emoji", (emoji) => {
  const el = messageInput[0];
  const startPos = el.selectionStart;
  const endPos = el.selectionEnd;
  const value = messageInput.val();
  const newValue =
    value.substring(0, startPos) +
    emoji +
    value.substring(endPos, value.length);
  messageInput.val(newValue);
  el.selectionStart = el.selectionEnd = startPos + emoji.length;
  el.focus();
});

/**
 *-------------------------------------------------------------
 * Notification sounds
 *-------------------------------------------------------------
 */
function playNotificationSound(soundName, condition = false) {
  if ((document.hidden || condition) && chatify.sounds.enabled) {
    const sound = new Audio(
      `/${chatify.sounds.public_path}/${chatify.sounds[soundName]}`
    );
    sound.play();
  }
}
/**
 *-------------------------------------------------------------
 * Update and format dates to time ago.
 *-------------------------------------------------------------
 */
function updateElementsDateToTimeAgo() {
  $(".message-time").each(function () {
    const time = $(this).attr("data-time");
    $(this).find(".time").text(dateStringToTimeAgo(time));
  });
  $(".contact-item-time").each(function () {
    const time = $(this).attr("data-time");
    $(this).text(dateStringToTimeAgo(time));
  });
}
setInterval(() => {
  updateElementsDateToTimeAgo();
}, 60000);
