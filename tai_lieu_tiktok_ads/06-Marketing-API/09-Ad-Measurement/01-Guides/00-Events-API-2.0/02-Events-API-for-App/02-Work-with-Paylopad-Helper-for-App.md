---
title: "Work with Paylopad Helper for App"
doc_id: 1819581869993025
path: "Marketing API / Ad Measurement / Guides / Events API 2.0 / Events API for App / Work with Paylopad Helper for App"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Work with Paylopad Helper for App

This article walks you through the steps of using the Payload Helper for App to construct your payload for reporting App Events using Events API 2.0.

## Introduction
The Payload Helper for App is a user interface provided by TikTok API for Business to simplify the process of creating payloads for the Events API 2.0 for App. This tool ensures that all necessary fields are included before data is sent to TikTok. It supports generating sample code in cURL, Python, and Node.js formats.

## Prerequisites
Before using the Payload Helper for App, familiarize yourself with the [Setup guide for App](https://business-api.tiktok.com/portal/docs?id=1771101111730178) to learn about the available parameters for reporting App Events using Events API 2.0.

## Steps

### Access the Payload Helper

Directly navigate to [Payload Helper](https://business-api.tiktok.com/portal/docs?id=1797165504155649). The tool takes a few seconds to load, and no login is required.

### Use the Payload Helper

#### 1. Change event source to app
To access App Events fields, change your Event source to `app`. By default,  `event_source` is set to `web` and the other fields are left empty. You will need to fill in other required fields to create a complete and effective payload.


#### 2. Input event data
Input the following three required event data fields:
  - `event_source_id`: Enter a TikTok App ID.
    - To obtain a TikTok App ID, refer to [Generate your TikTok App ID](https://business-api.tiktok.com/portal/docs?id=1771101111730178#item-link-Generate%20your%20TikTok%20App%20ID).
  - `event`: Select the Standard Event name from the dropdown menu for Standard Events, or enter a custom name for Custom Events.
  - `event_time`: Enter the event time in UTC+0 as a Unix timestamp measured in seconds. Example: `1723455457`.

#### 3. Add app parameters

Your **App ID** will be in either reverse DNS format (Android), or short numerical string (iOS) typically present in App Store or Play Store URLs.

#### 4. Add user parameters

Input at least one of the following device identifier fields: `idfa`, `gaid`.

Optionally, you may also input other optional user parameters including `email`, `phone`, and `external_id`. Click the **Hash** button available in the UI for SHA-256 hashing to protect user privacy. For instance, you can SHA-256 hash the email address `example_user@example.com` into `c6239ca1f8fe9b3d73e1352629e55c3ff0c587c8897903463247fe6ce6ef747d`.

Additional fields including `ip`, `user_agent`, and `att_status` are available depending on your needs.
  

#### 5. (Optional) Add property data
Select options for `currency` and `content_type` from the dropdown menu.

Optionally, expand property fields by adding `contents`, `value`, `query`, and `description`, depending on your needs.

Note that if you specify `content_type`, we recommend specifying the `content_id` within `contents` simultaneously according to the following rules:
  - If `content_type` is set to `product`, specify `content_id` as a SKU ID (`sku_id`) within your catalog.
  - If `content_type` is set to `product_group`, specify `content_id` as a product SPU ID (`item_group_id`) within your catalog.
 
  
#### 6. Generate sample code
Once you have filled in all the necessary information, you can see an auto-generated JSON object. You can generate sample code in cURL, Python, or Node.js format by clicking **Generate Code**.

Replace the placeholder value `<<ACCESS TOKEN>>` with your actual access token in the sample code to test it.

