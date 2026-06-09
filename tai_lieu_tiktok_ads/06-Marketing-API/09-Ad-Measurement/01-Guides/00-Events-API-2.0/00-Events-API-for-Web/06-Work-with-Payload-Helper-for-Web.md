---
title: "Work with Payload Helper for Web"
doc_id: 1807346079965186
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for Web / Work with Payload Helper for Web"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Work with Payload Helper for Web

This article walks you through the steps of using the Payload Helper for Web to construct your payload for reporting Web Events using Events API 2.0.

## Introduction
The Payload Helper for Web is a user interface provided by TikTok API for Business to simplify the process of creating payloads for the Events API 2.0 for Web. This tool ensures that all necessary fields are included before data is sent to TikTok. It supports generating sample code in cURL, Python, and Node.js formats.

## Prerequisites
Before using the Payload Helper for Web, familiarize yourself with the [Setup guide for Web](https://business-api.tiktok.com/portal/docs?id=1771100865818625) to learn about the available parameters for reporting Web Events using Events API 2.0.

## Steps

### Access the Payload Helper

Directly navigate to [Payload Helper](https://business-api.tiktok.com/portal/docs?id=1797165504155649). The tool takes a few seconds to load, and no login is required.

### Use the Payload Helper

By default,  `event_source` is set to `web` and the other fields are left empty. You will need to fill in other required fields to create a complete and effective payload.
 
<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/faeebc3a3eb5795e539360385e637241" alt="Payload Helper UI" width="60%" style="color:#808080" />

#### 1. Input event data
Input the following three required event data fields:
  - `event_source_id`: Enter a Pixel Code. 
    - To obtain a Pixel Code, refer to [FAQs - Where can I find the Pixel Code](https://business-api.tiktok.com/portal/docs?id=1771101001228290#item-link-Q2:%20Where%20can%20I%20find%20the%20Pixel%20Code?).
  - `event`: Select the Standard Event name from the dropdown menu for Standard Events, or enter a custom name for Custom Events.
  - `event_time`: Enter the event time in UTC+0 as a Unix timestamp measured in seconds. Example: `1723455457`.
  
Optionally, expand event data fields by adding `event_id` and `limited_data_use`, depending on your needs.

#### 2. Add user data
Input at least one of the following fields: `email`,` phone`, and `external_id`.  Click the **Hash** button available in the UI for SHA-256 hashing to protect user privacy. For instance, you can SHA-256 hash the email address `example_user@example.com` into `c6239ca1f8fe9b3d73e1352629e55c3ff0c587c8897903463247fe6ce6ef747d`.

Optionally, expand user fields by adding `ttclid`, `ttp`, `ip`, and `user_agent`, depending on your needs.
  

#### 3. (Optional) Add property data
Select options for `currency` and `content_type` from the dropdown menu.

Optionally, expand property fields by adding `contents`, `value`, `query`, and `description`, depending on your needs.

Note that if you specify `content_type`, we recommend specifying the `content_id` within `contents` simultaneously according to the following rules:
  - If `content_type` is set to `product`, specify `content_id` as a SKU ID (`sku_id`) within your catalog.
  - If `content_type` is set to `product_group`, specify `content_id` as a product SPU ID (`item_group_id`) within your catalog.
  
#### 4. Add page data
Set `url` to the full browser URL where the event happened.

Optionally, expand page fields by adding `referrer`, depending on your needs.
  
#### 5. Generate sample code
Once you have filled in all the necessary information, you can see an auto-generated JSON object. You can generate sample code in cURL, Python, or Node.js format by clicking **Generate Code**.

Replace the placeholder value `<<ACCESS TOKEN>>` with your actual access token in the sample code to test it.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/485743ccc700930bcf41ed4b395941c8" alt="sample code in cURL, Python, or Node.js format" width="60%" style="color:#808080" />
