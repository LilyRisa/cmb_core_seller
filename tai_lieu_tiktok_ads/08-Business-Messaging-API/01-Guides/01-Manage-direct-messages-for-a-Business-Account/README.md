---
title: "Manage direct messages for a Business Account"
doc_id: 1832184236919810
path: "Business Messaging API / Guides / Manage direct messages for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage direct messages for a Business Account

This article walks you through the steps for receiving and responding to messages, managing conversations, as well as handling media files in direct messages using Business Messaging API. 

## Introduction
Managing direct messages for a Business Account is a key feature of the Business Messaging API, enabling businesses to interact effectively with TikTok users. 

**You can use Business Messaging API to streamline communication by automating message handling, retrieving conversation records, and efficiently managing media files. These capabilities help businesses provide timely responses, maintain organized conversations, and deliver a seamless messaging experience to users.**

## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To manage direct messages for a Business Account, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- You have followed the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1832184159540418) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945) to obtain a TikTok account access token and the Application specific unique ID for the Business Account.

## Steps
### Receive messages
- When a TikTok user sends a message to the business, you'll receive a Webhook notification.
- The initial message from the TikTok user will trigger a conversation event along with a unique  `conversation_id`.  

### Respond to messages
- You can respond to the TikTok user by making a request to [/business/message/send/](https://business-api.tiktok.com/portal/docs?id=1832184403754242) using the `conversation_id` provided in the webhook notification.  
- The type of messages that you can send include:
  - Text message.
  - Image message.
  - Message templates, including:
    - **Q&A button card**: A card showing a question and buttons that represent answers. When users click one of the buttons, the text content of the button will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card.<br>
<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6434ef376a9c12a0c9fa9dcb15fb86f7" alt="Q&A button card" width="20%" style="color:#808080" />
    - **Q&A text link card**: A card showing a question and interactive text links that represent answers to the question. When users click one of the text links, the text content of the text link will be sent to the conversation thread on behalf of the user as an answer to the pre-set question on the card. <br>
<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f82a26ea4712feb966ac0f2e1a5e0cb0" alt="Q&A text link card" width="20%" style="color:#808080" />
  - TikTok post message.
  - Sender actions within the messaging session, such as:
    - **Typing**: The sender is preparing a reply. This displays a “Typing” icon in the messaging window, which will automatically disappear after five seconds.
    - **Marking as read**: The sender marks all messages in the session as read, displaying a “Seen” icon for those messages.
	
> <span style="color:DodgerBlue">**Note**</span><br>
> - You are prohibited from initiating a conversation or messaging any TikTok user who has not started a conversation with you.
> - For a Business Account's non-mutual follow conversations handled via the Business Messaging API, certain messaging limits apply. Learn more about [messaging limits](https://business-api.tiktok.com/portal/docs?id=1854823717597185).

### Manage conversations
- You can retrieve a list of conversations by using [/business/message/conversation/list/](https://business-api.tiktok.com/portal/docs?id=1832184415059970), which will return up to 100 conversation records.
- To obtain a list of messages within a conversation, you can call [/business/message/content/list/](https://business-api.tiktok.com/portal/docs?id=1832184425841170) with the relevant `conversation_id`. This fetches the 20 most recent messages.  

### Handle media
- **To send images**:  
	1. Upload images for messages using [/business/message/media/upload/](https://business-api.tiktok.com/portal/docs?id=1832184437720193). A successful upload will return a `media_id`.  
> <span style="color:DodgerBlue">**Note**</span><br>
> - The `media_id` is valid for 30 days. Once the Media ID expires, you need to re-upload the image to obtain a new Media ID. 
> - Sending and receiving image attachments via API are only available when both the sender and receiver are in countries that support image attachments in direct messages. Image sharing is not available in all markets. 
> - You can use [/business/message/capabilities/get/](https://business-api.tiktok.com/portal/docs?id=1836061597865986) to query if the Business Account can send a media attachment to a conversation.

	2. Pass the returned `media_id` to `media_id` in [/business/message/send/](https://business-api.tiktok.com/portal/docs?id=1832184403754242) to send an image message to a user. 

- **To download images**:  
	- Use [/business/message/media/download/](https://business-api.tiktok.com/portal/docs?id=1832184455450626) with the `media_id`, `message_id`, and `conversation_id`. The API returns a `download_url` for downloading the image. 
