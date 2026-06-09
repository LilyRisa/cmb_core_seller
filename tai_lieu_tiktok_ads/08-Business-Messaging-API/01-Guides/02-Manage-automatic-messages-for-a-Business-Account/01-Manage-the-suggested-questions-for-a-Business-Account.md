---
title: "Manage the suggested questions for a Business Account"
doc_id: 1832184277518338
path: "Business Messaging API / Guides / Manage automatic messages for a Business Account / Manage the suggested questions for a Business Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage the suggested questions for a Business Account

This article walks you through the steps for managing the suggested questions for a Business Account using Business Messaging API. 

## Introduction
Suggested questions are frequently asked questions displayed at the beginning of a chat. These questions allow users to select a query and receive a preset answer automatically, streamlining communication and reducing response times. Each Business Account can customize up to three suggested questions.

**You can use Business Messaging API to customize suggested questions to address common inquiries efficiently. This feature helps businesses save time and ensure consistent responses to frequently asked questions.**

## Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To manage the suggested questions for a Business Account, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  
- You have followed the steps in [Authorization](https://business-api.tiktok.com/portal/docs?id=1832184159540418) and [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945) to obtain a TikTok account access token and the Application specific unique ID for the Business Account.

## Steps

To manage the suggested questions for a Business Account, follow these steps:

1. **Enable the suggestions questions setting** using [/business/message/auto_message/status/update/](https://business-api.tiktok.com/portal/docs?id=1832184607786242).
- Set `auto_message_type` to `SUGGESTED_QUESTION` and `operation_status` to `ENABLE`.

2. **Create one or more suggested questions** for a Business Account using [/business/message/auto_message/create/](https://business-api.tiktok.com/portal/docs?id=1832184584869890).
- Set `auto_message_type` to `SUGGESTED_QUESTION` and specify the `suggested_question` object.

3. **Verify the statuses of suggested questions** using [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).
- Set `auto_message_type` to `SUGGESTED_QUESTION` and confirm that the `audit_status` of each suggested question is `APPROVED` and `operation_status` is `ENABLE`.
   <br><p><span style="color:darkred"><b>Note</b></span>: If `audit_status` of a suggested question is `REVIEWING`, you need to wait for some time before rechecking the review status. The exact waiting time may vary depending on the content and other factors, but normally ranges from a few seconds to 2-3 hours.

4. **Handle potential issues**.
- If `audit_status` is `REJECTED`:
	1. Update the suggested question using [/business/message/auto_message/update/](https://business-api.tiktok.com/portal/docs?id=1832184596566017).
	2. Recheck the status with [/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377).
- If `operation_status` is `DISABLE`:
	- Enable the suggested questions setting by following the instruction in Step 1.
