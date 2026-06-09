---
title: "Manage the chat prompts for a Business Account"
doc_id: 1832184291198977
path: "Business Messaging API / Guides / Manage automatic messages for a Business Account / Manage the chat prompts for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage the chat prompts for a Business Account

This article walks you through the steps for managing the chat prompts for a Business Account using Business Messaging API. 

## Introduction
Chat prompts are interactive buttons displayed above the input box, designed to encourage user engagement. When users tap a chat prompt, the system automatically generates a corresponding question, helping users start conversations easily. Each Business Account can customize up to six chat prompts.

**You can use Business Messaging API to customize chat prompts to guide users toward specific topics or actions. This feature improves user interaction, simplifies conversation initiation, and helps businesses drive meaningful engagement with their audience.**

## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To manage the chat prompts for a Business Account, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- You have followed the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1832184159540418) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945) to obtain a TikTok account access token and the Application specific unique ID for the Business Account.

## Steps

To manage the chat prompts for a Business Account, follow these steps:

1. **Enable the chat prompts setting** using [/business/message/auto_message/status/update/](https://business-api.tiktok.com/portal/docs?id=1832184607786242).
- Set `auto_message_type` to `CHAT_PROMPT` and `operation_status` to `ENABLE`.

> <span style="color:DodgerBlue">**Note**</span><br>
> For certain Registered Business Accounts with access to chat prompts, the chat prompt setting is enabled by default. To verify whether the Business Account has the chat prompt setting enabled by default, use [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377) and set `auto_message_type` to `CHAT_PROMPT`.

2. **Create one or more chat prompts** for a Business Account using [/business/message/auto_message/create/](https://business-api.tiktok.com/portal/docs?id=1832184584869890).
- Set `auto_message_type` to `CHAT_PROMPT` and specify the `chat_prompt` object.

3. **Verify the statuses of chat prompts** using [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).
- Set `auto_message_type` to `CHAT_PROMPT` and confirm that the `audit_status` of each chat prompt is `APPROVED` and `operation_status` is `ENABLE`.<br><p><span style="color:darkred"><b>Note</b></span>: If `audit_status` of a chat prompt is `REVIEWING`, you need to wait for some time before rechecking the review status. The exact waiting time may vary depending on the content and other factors, but normally ranges from a few seconds to 2-3 hours.

4. **Handle potential issues**.
- If `audit_status` is `REJECTED`:
	1. Update the chat prompt using [/business/message/auto_message/update/](https://business-api.tiktok.com/portal/docs?id=1832184596566017).
	2. Recheck the status with [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).
- If `operation_status` is `DISABLE`:
     - Enable the chat prompt setting by following the instruction in Step 1.
	 

