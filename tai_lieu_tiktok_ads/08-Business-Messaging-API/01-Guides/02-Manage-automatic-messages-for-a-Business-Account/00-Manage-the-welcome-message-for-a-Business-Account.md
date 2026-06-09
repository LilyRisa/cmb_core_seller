---
title: "Manage the welcome message for a Business Account"
doc_id: 1832184264385538
path: "Business Messaging API / Guides / Manage automatic messages for a Business Account / Manage the welcome message for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage the welcome message for a Business Account

This article walks you through the steps for managing the welcome message for a Business Account using Business Messaging API. 

## Introduction
Welcome messages are the first interaction users have when they initiate contact with your Business Account. They set the tone for your communication and help establish a positive user experience. With the Business Messaging API, you can create a new welcome message or modify the default one to suit your brand's voice. Each Business Account can have one customized welcome message.

**You can use Business Messaging API to greet users in a personalized and engaging way, fostering stronger connections and enhancing the overall user experience.**

## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To manage the welcome message for a Business Account, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- You have followed the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1832184159540418) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945) to obtain a TikTok account access token and the Application specific unique ID for the Business Account.

## Steps
To manage the welcome message for a Business Account, follow these steps:

### Option 1: Create a new welcome message

1. **Set up a new welcome message** using [/business/message/auto_message/create/](https://business-api.tiktok.com/portal/docs?id=1832184584869890).
- Set `auto_message_type` to `WELCOME_MESSAGE` and specify the `welcome_message` object.

2. **Verify the status of the welcome message** using [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).
- Set `auto_message_type` to `WELCOME_MESSAGE` and confirm that the `audit_status` of the message is `APPROVED` and `operation_status` is `ENABLE`.
 <br><p><span style="color:darkred"><b>Note</b></span>: If `audit_status` of the welcome message is `REVIEWING`, you need to wait for some time before rechecking the review status. The exact waiting time may vary depending on the content and other factors, but normally ranges from a few seconds to 2-3 hours.

3. **Handle potential issues**.
- If `audit_status` is `REJECTED`:
	1. Update the welcome message using [/business/message/auto_message/update/](https://business-api.tiktok.com/portal/docs?id=1832184596566017).
	2. Recheck the status with [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).
- If `operation_status` is `DISABLE`:
	- Enable the welcome message setting using [/business/message/auto_message/status/update/](https://business-api.tiktok.com/portal/docs?id=1832184607786242) and set `operation_status` to `ENABLE`.

### Option 2: Modify the default welcome message

1. **Enable the default welcome message**.
- Call [/business/message/auto_message/status/update/](https://business-api.tiktok.com/portal/docs?id=1832184607786242) and set `operation_status` to `ENABLE`.
<br><p><span style="color:darkred"><b>Note</b></span>: If you enable the welcome message for a Business Account for the first time, the default welcome message will be: `"Hi there,\nThanks for contacting {{display_name}}! Feel free to ask a question here or leave us a message and we'll get back to you shortly.\n\nThank you!"`
  
2. **Retrieve the default message** using [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).
  
3. **Update the message** using [/business/message/auto_message/update/](https://business-api.tiktok.com/portal/docs?id=1832184596566017).
  
4. **Verify the status of the updated welcome message**.
 - Call `/business/message/auto_message/get/` again. Confirm that the `audit_status` of the updated message is `APPROVED` and `operation_status` is `ENABLE`.
