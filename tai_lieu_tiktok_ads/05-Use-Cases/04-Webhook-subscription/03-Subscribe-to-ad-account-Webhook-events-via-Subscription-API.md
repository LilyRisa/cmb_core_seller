---
title: "Subscribe to ad account Webhook events via Subscription API"
doc_id: 1810521739537409
path: "Use Cases / Webhook subscription / Subscribe to ad account Webhook events via Subscription API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Subscribe to ad account Webhook events via Subscription API

This article walks you through the steps to receive notifications via webhooks for any of the following:
- The data of the reporting metrics `spend`, `impressions`, `clicks`, or `video_play_actions` for one or more ad accounts has changed.
- leads
- ad review status 
- ad fatigue status
- the linking of a TikTok video to a TikTok One (TTO) campaign
- the suspension statuses of ad accounts 
- the occurrence, update, or resolution of any incident involving one or more API products

# Introduction

Webhooks are automated HTTP callbacks that are triggered when specific events occur within the TikTok platform, allowing real-time notifications to be sent to your specified callback URL. This mechanism eliminates the need for constant API polling to retrieve information about asynchronous events. Instead, TikTok proactively sends event data to your system as soon as it becomes available. These notifications are delivered via HTTPS POST in JSON format to the callback URL you've configured for your application. You can use this real-time information to update your system or trigger specific business processes, ensuring your application stays synchronized with TikTok events efficiently and promptly.

**You can make subscriptions through ad account Webhooks to get real-time updates for reporting metric data changes, leads, ad review status, ad fatigue status, TTO video linking status, ad account suspension status, or API service status.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. Note that you need to obtain a Marketing API access token.

# Steps
## Subscribe to reporting metric data changes

To subscribe to reporting metric data changes, call [/subscription/subscribe/](https://business-api.tiktok.com/portal/docs?id=1739092028876801). Enter the callback URL in the `callback_url` field, set the `subscribe_entity` field to `REPORT_DATA_CHANGE`, specify `advertiser_ids`, and optionally define `notify_frequency` to customize the notification timeframe.

When any of the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions` for any of the ad accounts that you are subscribed to has changed within the last notification timeframe, you will receive at most one webhook notification. For example, if you're subscribed to 10 ad accounts with a frequency of every 5 minutes by setting `notify_frequency` to `5_MINUTE` and specifying 10 ad account IDs through `advertiser_ids`, and during the last 5-minute timeframe, the reporting metrics for three ad accounts changed, you'll receive webhook notification containing information about these three ad accounts and their respective changed metrics.

To find the detailed description for these reporting metrics, see [Basic reports > Supported metrics](https://business-api.tiktok.com/portal/docs?id=1751443967255553).

> <span style="color:DodgerBlue">**Note**</span>
> - Subscribing to the reporting metric data changes of ad accounts is currently an allowlist-only feature. If you would like to access it, apply for the allowlisting using the [Allowlist Management](https://business-api.tiktok.com/portal/blog/detail/7102266224578396162) page.
> - To subscribe to the reporting metric data changes of ad accounts, ensure that you have enabled the **Reporting** > **Consolidated Report** permission for your developer app.

### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Content-Type: application/json' \
--data '{
  "app_id": "{{app_id}}",
  "secret": "{{secret}}",
  "subscribe_entity": "REPORT_DATA_CHANGE",
  "callback_url": "{{callback_url}}",
  "subscription_detail": {
    "access_token": "{{access_token}}",
    "advertiser_ids": ["{{advertiser_id}}","{{advertiser_id}}"],
    "notify_frequency": "5_MINUTE"
  }
}'
(/code)
```

Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "subscription_id": "{{subscription_id}}"
    }
}
(/code)
```

If you no longer wish to receive notifications, you can cancel the subscription using [/subscription/unsubscribe/](https://business-api.tiktok.com/portal/docs?id=1739094758789122).

The webhook response contains the following fields.

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|request_id|number|Unique ID for a webhook request.|
|object|integer|The object that is subscribed to.<br>Supported value:<ul><li>`11` = the change in any of the reporting metrics `spend`, `impressions`, `clicks`, and `video_play_actions` for one or more ad accounts.</li></ul>|
|time|integer|A UNIX timestamp indicating when the Event Notification was sent (not when the change that triggered the notification occurred).|
|entry|object[]|An array containing an object describing the details of reporting metric data changes.|
#|data_level|string|The data level of the subscription.<br>Supported value:<ul><li> `advertiser_id`: advertiser ID level.</li></ul>|
#|message|string|An additional message about this subscription. <br>For example, you will receive a message when your access to some of your subscribed ad accounts is revoked.<br>By default, `message` is not returned.|
#|changed_metrics_values|Map (object)|A map showing the details of the reporting metric data changes for each ad account. <br><ul><li> Key (string): The ID of the ad account (`advertiser_id`) that you subscribe to.</li><li> Value (object): The changed metrics for the ad account within the last subscription notification timeframe and the values for the metrics. The metric values are queried based on the day in UTC+0 time when the metric changed.<ul><li>To retrieve the notification timeframe of a subscription, use [/subscription/get/](https://business-api.tiktok.com/portal/docs?id=1739093832125442) and check the returned `notify_frequency`.</li></ul></li></ul>|
```

### Sample response code

```xcodeblock
(code curl http)
{
  "request_id": {{request_id}},
  "object": 11, // For REPORT_DATA_CHANGE, this field will always be 11.
  "entry": [
    {
      "data_level": "advertiser_id",
      "changed_metrics_values": {
        "{{advertiser_id}}": {
          "currency": "USD",
          "impressions": "5906511",
          "spend": "38444.61",
          "video_play_actions": "5790903",
          "adv_timezone": "Etc/GMT+5",
          "clicks": "10783"
        },
        "{{advertiser_id}}": {
          "video_play_actions": "15261037",
          "adv_timezone": "Atlantic/Reykjavik",
          "clicks": "158131",
          "currency": "USD",
          "impressions": "15362608",
          "spend": "39238.96"
        }
      }
    }
  ],
  "time": 1749202502
}
(/code)
```

## Subscribe to leads

Webhooks enable you to integrate Lead Generation ads with your CRM system and receive real-time updates for both Instant Form and direct message leads. For details, see [Lead generation](https://ads.tiktok.com/marketing_api/docs?id=1739289524101122).

To subscribe to a lead, call [/subscription/subscribe/](https://ads.tiktok.com/marketing_api/docs?id=1739092028876801). Enter the callback URL in the `callback_url` field and set the `subscribe_entity` field to `LEAD`. 

- For Instant Form leads, set `lead_source` to `INSTANT_FORM` or leave it unspecified. You have the flexibility to choose between `advertiser_id` or `library_id`, with `page_id` being optional.
	- Using `advertiser_id` will subscribe you to all Instant Form leads from Lead Generation ads using Instant Forms that are associated with the ad account. Including `page_id` will narrow down the notifications to leads from that specific Instant Form within the ad account.
	- Using `library_id` will subscribe you to all Instant Form leads from Lead Generation ads using Instant Forms that are associated with the form library within a Business Center. Including `page_id` will narrow down the notifications to leads from that particular Instant Form within the form library.
- For direct message leads, set `lead_source` to `DIRECT_MESSAGE`. You have the flexibility to choose between `advertiser_id` or `library_id`, with `page_id` not being supported.
	- Using `advertiser_id` will subscribe you to leads from direct messaging ads associated with the ad account.
	- Using `library_id` will subscribe you to leads from direct messaging ads associated with the form library.

Once subscribed, you'll receive webhook notifications every time a new lead is submitted or collected.

> <span style="color: dodgerblue">**Note**</span><br>
> TikTok is committed to providing "at least once delivery" for webhooks. Consequently, it’s possible that webhook endpoints may receive duplicate events. To mitigate potential issues, incorporate safeguards against processing duplicate events by designing your event handling to be idempotent.

### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Content-Type: application/json' \
--data '{
  "app_id": "{{app_id}}",
  "secret": "{{secret}}",
  "subscribe_entity": "LEAD",
  "callback_url": "{{callback_url}}",
  "subscription_detail": {
    "access_token": "{{access_token}}",
    "advertiser_id": "{{advertiser_id}}"
  }
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "subscription_id": "{{subscription_id}}"
    }
}
(/code)
```

If you no longer wish to receive notifications, you can cancel the subscription using [/subscription/unsubscribe/](https://ads.tiktok.com/marketing_api/docs?id=1739094758789122). 

The webhook response contains the following fields:

``` xtable
| Field{25%} | Type {15%}| Description{60%} |
| --- | --- | --- |
| request_id | number | Unique ID for a webhook request |
| object | integer | The object's type. <br><br>Supported value:<ul><li>`1`: lead. </li></ul>|
| entry | object[] | An array containing an object describing the changes. Multiple changes from different objects that are of the same type may be batched together. |
#| id | string | Lead ID. |
#| lead_source | string | The source of the lead. <br><br>Enum values:<ul><li>`INSTANT_FORM`: a lead generated through an Instant Form.</li><li>`DIRECT_MESSAGE`: a lead generated from a direct message of the associated Business Account.</li></ul> |
#| page_id | number | Page ID of the Instant Form. |
#| page_name | string | Page name of the Instant Form. |
#| advertiser_id | number | Advertiser ID. |
#| advertiser_name | string | Advertiser name. |
#| library_id | number | ID of the form library. |
#| campaign_id | number | Campaign ID. |
#| campaign_name | string | Campaign name. |
#| adgroup_id | number | Ad group ID. |
#| adgroup_name | string | Ad group name. |
#| ad_id | number | Ad ID. |
#| ad_name | string | Ad name. |
#| create_time | number | The time when the lead was created, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1743639757. |
#| changes | object[] | An array containing an object describing the changed fields in the Instant Form or direct message lead. |
##| field | string | The changed field. |
##| value | object | Value for the field. |
| time | integer | The time when the event notification was sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: 1743702195. |
```

### Sample response code

```xcodeblock
(code JSON JSON)
{
  "object": 1,
  "entry": [
    {
      "id": {{id}},
      "page_id": {{page_id}},
	  "page_name": "",
      "campaign_id": {{campaign_id}},
      "campaign_name": "{{campaign_name}}",
      "adgroup_id": {{adgroup_id}},
      "adgroup_name": "{{adgroup_name}}",
      "ad_id": {{ad_id}},
      "ad_name": "{{ad_name}}",
      "create_time": 1614239152,
      "changes": [
        {
          "field": "gender",
          "value": "Female"
        },

        {
          "field": "phone_number",
          "value": "15088888888"
        },
        {
          "field": "email",
          "value": "test_email_address@mail.com"
        },
        {
          "field": "address",
          "value": "123 Castro Ave. Mountain View, CA"
        },
        {
          "field": "name",
          "value": "Jane Doe"
        },
        {
          "field": "scheduled_time",
          "value": "Wednesday / 12:00 - 13:00"
        }
      ],
      "advertiser_id": {{advertiser_id}},
      "advertiser_name": "{{advertiser_name}}",
      "library_id": 0,
      "lead_source": "DIRECT_MESSAGE"
    }
  ],
  "time": 1614334356
}
(/code)
```

## Subscribe to the review status of your ad

You can make subscriptions through webhooks to get real-time updates for the review status of your ad. 

To subscribe to ad review status, call [/subscription/subscribe/](https://ads.tiktok.com/marketing_api/docs?id=1739092028876801) , enter the callback URL in the `callback_url` field and specify `AD`/`AD_GROUP`in the `subscribe_entity` field. 

### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Content-Type: application/json' \
--data '{
  "app_id": "{{app_id}}",
  "secret": "{{secret}}",
  "subscribe_entity": "AD_GROUP",
  "callback_url": "{{callback_url}}",
  "subscription_detail": {
    "access_token": "{{access_token}}",
    "advertiser_id": "{{advertiser_id}}",
    "adgroup_id": "{{adgroup_id}}"
  }
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "subscription_id": "{{subscription_id}}"
    }
}
(/code)
```

If you no longer wish to receive notifications, you can cancel the subscription using [/subscription/unsubscribe/](https://ads.tiktok.com/marketing_api/docs?id=1739094758789122). 

> <span style="color: dodgerblue">**Note**</span> <br>You will get notified only when your ad is rejected.

The webhook response contains the following fields. 

``` xtable 
| Field{25%} | Type{15%} | Description{60%} |
| --- | ---| ---|
| request_id | number | Unique ID for a webhook request |
| object | integer | The object's type. <br>Valid values:<ul><li>`2`= the review status of an ad group.</li><li>`3` = the review status of an ad. </li></ul> |
| entry | object[] | An array containing an object describing the reject reasons. Multiple changes from different objects that are of the same type may be batched together. |
#| log_id | string | Log ID |
#| adv_id | numner | Advertiser ID |
#| campaign_id | number | Campaign ID |
#| campaign_name | string | Campaign name |
#| adgroup_id | number | Adgroup ID |
#| adgroup_name | string | Adgroup name |
#| ad_id | number | Ad ID |
#| ad_name | string | Ad name |
#|reject_reason | string[] | Ad Review Reject Reason |
| time | int | A UNIX timestamp indicating when the Event Notification was sent (not when the change that triggered the notification occurred). |
``` 

### Sample response code

```xcodeblock
(code JSON JSON)
{
    "request_id": {{request_id}},
    "object": 2,
    "entry": [
            {
                "log_id": "{{log_id}}",
                "adv_id": {{adv_id}},
                "campaign_id": {{campaign_id}},
                "campaign_name": "{{campaign_name}}",
                "adgroup_id": {{adgroup_id}},
                "adgroup_name": "{{adgroup_name}}",
                "reject_reasons": [
                    "The product/service promoted on the ad/landing page belongs to a prohibited industry of the targeted location(s) in your ad when we take our own business evaluation, user experience and the value of advertisement impact, etc. into consideration.",
                    "The product/service displayed in the ad image/video is not consistent with the product/service on the landing page."
                ]
            }
        ],
    "time": 1652917135
}
(/code)
```

## Subscribe to the fatigue status of your ad 
You can make subscriptions through webhooks to get real-time updates for the fatigue status of your ad. 

To subscribe to fatigue status, call [/subscription/subscribe/](https://ads.tiktok.com/marketing_api/docs?id=1739092028876801). Enter the callback URL in the `callback_url` field, specify the `advertiser_id` field and the value`CREATIVE_FATIGUE` in the `subscribe_entity` field, and pass in `ad_id`, or `adgroup_id`, or neither to subscribe to the fatigue status of an ad, all ads within an ad group, or all ads within an advertiser account, respectively. 
### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Content-Type: application/json' \
--data '{
  "app_id": "{{app_id}}",
  "secret": "{{secret}}",
  "subscribe_entity": "CREATIVE_FATIGUE",
  "callback_url": "{{callback_url}}",
  "subscription_detail": {
    "access_token": "{{access_token}}",
    "advertiser_id": "{{advertiser_id}}"
  }
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "subscription_id": "{{subscription_id}}"
    }
}
(/code)
```

If you no longer wish to receive notifications, you can cancel the subscription using [/subscription/unsubscribe/](https://ads.tiktok.com/marketing_api/docs?id=1739094758789122).

> <span style="color:DodgerBlue">**Note**</span><br>
> You will get notified only when your ad starts to experience Creative Fatigue.

The webhook response will include the following fields.

```xtable
| Field {25%}| Type{15%} | Description{60%} |
|---|---|---|
| request_id | number | Unique ID for a webhook request. |
| object | integer | The object that is subscribed to. <br>Valid value: <ul><li>`8` = the fatigue status of an ad, ads within an ad group, or ads within an advertiser account. </li></ul>|
| entry | object[] | An array containing an object describing the details of Creative Fatigue. |
#| adv_id | number | Advertiser ID. |
#| adgroup_ids | number[] | Returned when you are subscribing to the fatigue status by `advertiser_id`. <br>Ad group IDs. |
#| ad_ids| number[] | Returned when you are subscribing to the fatigue status by `advertiser_id` or `adgroup_id`. <br>Ad IDs. |
#| adgroup_id | number | Returned when you are subscribing to the fatigue status by `ad_id` or `adgroup_id`. <br>Ad group ID. |
#| ad_id | number | Returned when you are subscribing to the fatigue status by `ad_id`.<br>Ad ID. |
#| date | string | Date when Creative Fatigue is detected for the entity (ad, ad group or ad account) that you are subscribing to.<br>Format: `YYYY-MM-DD`. |
| time | integer | A UNIX timestamp indicating when the Event Notification was sent (not when the change that triggered the notification occurred). |
```
### Sample response code
```xcodeblock
(code JSON JSON)
// Subscribe by adv_id
{
    "request_id": {{request_id}},
    "object": 8,
    "entry": [
        {
            "adv_id": {{adv_id}},
            "adgroup_ids": [
                {{adgroup_id}}
            ],
            "ad_ids": [
                {{ad_id}}
            ],
            "date": "2023-03-24"
        }
    ],
    "time": 1684455332
}

// Subscribe by adgroup_id
{
    "request_id": {{request_id}},
    "object": 8,
    "entry": [
        {
            "adv_id": {{adv_id}},
            "adgroup_id": {{adgroup_id}},
            "ad_ids": [
                {{ad_id}}
            ],
            "date": "2023-03-24"
        }
    ],
    "time": 1684455273
}

// Subscribe by ad_id
{
    "request_id": {{request_id}},
    "object": 8,
    "entry": [
        {
            "adv_id": {{adv_id}},
            "adgroup_id": {{adgroup_id}},
            "ad_id": {{ad_id}},
            "date": "2023-03-24"
        }
    ],
    "time": 1684455179
}
(/code)
```

## Subscribe to the linking of a TikTok video to a TTO campaign

To receive real-time updates for the linking of a TikTok video to a TTO campaign, call [/subscription/subscribe/](https://business-api.tiktok.com/portal/docs?id=1739092028876801) and set the `subscribe_entity` field to `TCM_VIDEOS`.

### Example

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Content-Type: application/json' \
--data '{
  "app_id": "{{app_id}}",
  "secret": "{{secret}}",
  "subscribe_entity": "TCM_VIDEOS",
  "callback_url": "{{callback_url}}",
  "subscription_detail": {
    "access_token": "{{access_token}}",
    "tto_tcm_account_id": "{{tto_tcm_account_id}}"
  }
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "subscription_id": "{{subscription_id}}"
    }
}
(/code)
```
To cancel the subscription and stop receiving notifications, use [/subscription/unsubscribe/](https://business-api.tiktok.com/portal/docs?id=1739094758789122).

> <span style="color: dodgerblue">**Note**</span><br>
> You will receive notifications for each video linked to a TTO campaign. Multiple videos linked to the same campaign generate multiple notifications.

The webhook response contains the following fields. 

```xtable
| Field {25%}| Type {15%}| Description {60%}|
|---|---|---|
| request_id | number | Unique ID for a webhook request. |
| object | integer | The object's type.<br><br> Supported value: <ul><li>`10`: the linking of a TikTok video to a TTO campaign.</li></ul>|
| entry | object[] | Details of the video linking event. Multiple changes from different objects that are of the same type may be batched together. |
#| event_type | string | Webhook Event type. <br><br>Supported value:<ul><li>`tto_campaign_webhook`: TTO video linking event.</li></ul> |
#| video_id | integer | ID of the TikTok video that is linked to a TTO campaign. |
#| event_time | string | The time when the event occurred, in [ISO 8601](https://en.wikipedia.org/wiki/ISO_8601) format. <br><br>Example: `2024-11-18T21:57:00.070272Z`. |
#| handle_name | string | Handle name of the creator. <br><br><p><span style="color:darkred"><b>Note</b></span>: If the handle name of the creator cannot be matched, this field will not be returned. |
#| tto_campaign_event_type | string | The specific type of TTO video linking event. <br><br> Supported values:<ul><li> `tto_video_uploaded`: A TikTok video has been uploaded and linked to a TTO campaign, but has not passed review.</li><li>`tto_video_published`: A TikTok video has been uploaded and linked to a TTO campaign, and has passed review. </li></ul>|
#| campaign_id | integer | ID of the TTO campaign. |
#| tto_tcm_account_id | integer | ID of a TTO Creator Marketplace account. |
#| log_id | string | Log ID. |
| time | integer | An Epoch/Unix timestamp in seconds that indicates when the Event Notification was sent, rather than when the triggering change occurred. |
```

### Sample response code

```xcodeblock
(code JSON JSON)
{
  "request_id":"{{request_id}}",
  "object":10,
  "entry": [
    {
      "event_type":"tto_campaign_webhook",
      "video_id":{{video_id}},
      "event_time":"{{event_time}}",
      "handle_name":"{{handle_name}}",
      "tto_campaign_event_type": "tto_video_published",
      "campaign_id":{{campaign_id}},
      "tto_tcm_account_id":{{tto_tcm_account_id}},
      "log_id":"{{log_id}}"
    }
  ],
  "time": 1731948913
}
(/code)
```
## Subscribe to the suspension statuses of ad accounts
To subscribe to the suspension statuses of ad accounts, call [/subscription/subscribe/](https://business-api.tiktok.com/portal/docs?id=1739092028876801). Enter the callback URL in the `callback_url` field and set the `subscribe_entity` field to `AD_ACCOUNT_SUSPENSION`.
- If you want to subscribe to all ad accounts owned by a Business Center, specify `bc_id` simultaneously.
- If you only want to subscribe to specific ad accounts, which can be owned by different Business Centers, specify `advertiser_ids` simultaneously.

When any of the ad accounts that you subscribe to is suspended or unsuspended, you will receive webhook notifications.

> <span style="color: dodgerblue">**Note**</span><br>
> Subscribing to the suspension statuses of ad accounts is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

### Example
#### Subscribe to the suspension statuses of all ad accounts within a Business Center
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Content-Type: application/json' \
--data '{
  "app_id": "{{app_id}}",
  "secret": "{{secret}}",
  "subscribe_entity": "AD_ACCOUNT_SUSPENSION",
  "callback_url": "{{callback_url}}",
  "subscription_detail": {
    "access_token": "{{access_token}}",
    "bc_id": "{{bc_id}}"
  }
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "subscription_id": "{{subscription_id}}"
    }
}
(/code)
```
#### Subscribe to the suspension statuses of certain ad accounts within multiple Business Centers
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Content-Type: application/json' \
--data '{
  "app_id": "{{app_id}}",
  "secret": "{{secret}}",
  "subscribe_entity": "AD_ACCOUNT_SUSPENSION",
  "callback_url": "{{callback_url}}",
  "subscription_detail": {
    "access_token": "{{access_token}}",
    "advertiser_ids": ["{{advertiser_id}}","{{advertiser_id}}"]
  }
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "subscription_id": "{{subscription_id}}"
    }
}
(/code)
```
If you no longer wish to receive notifications, you can cancel the subscription using [/subscription/unsubscribe/](https://business-api.tiktok.com/portal/docs?id=1739094758789122).

The webhook response contains the following fields. 

```xtable
| Field {25%}| Type {15%}| Description {60%}|
|---|---|---|
| advertiser_id | string | Advertiser ID. |
| advertiser_name | string | Advertiser name. |
| owner_bc_id | string | ID of the Business Center that owns the ad account. |
| status | string | The status of the ad account when the webhook was sent. <br><br> Enum values: <ul><li>`STATUS_ENABLE`: Unsuspended. </li><li>`STATUS_LIMIT`: Suspended.</li></ul><br>By the time you receive the webhook, the ad account status may have changed. To retrieve the latest status of the ad account, use [/advertiser/info/](https://business-api.tiktok.com/portal/docs?id=1739593083610113) and check the returned `status`. |
| status_change_time | string | The time when the ad account is suspended or unsuspended, in `YYY-MM-DD HH:MM:SS` format (UTC+0 time). <br><br> Example: `2025-01-01 00:00:01`. |
| suspension_reason | string | Returned only when `status` is `STATUS_LIMIT`. <br><br>The reason for the suspension. |
| subscription_id | string | Subscription ID. |
```

### Sample response code

```xcodeblock
(code JSON JSON)
{
        "owner_bc_id": "{{owner_bc_id}}",
        "advertiser_id": "{{advertiser_id}}",
        "advertiser_name": "{{advertiser_name}}",
        "status_change_time": "2025-02-19 08:32:29",
        "suspension_reason": "{{suspension_reason}}",
        "status": "STATUS_LIMIT",
        "subscription_id": "{{subscription_id}}"
}
(/code)
```

## Subscribe to API service status

To subscribe to the API service status, call [/subscription/subscribe/](https://business-api.tiktok.com/portal/docs?id=1739092028876801).

Provide your callback URL in the `callback_url` field, set `subscribe_entity` to `API_SERVICE_STATUS`, and optionally specify the API products you wish to monitor using the `product_filters` field.
- To subscribe to all API products, leave `product_filters` unspecified.
- To subscribe to specific API products, specify the desired products in `product_filters`.

When an incident involving one or more API products occurs, is updated, or is resolved, you will receive webhook notifications.

### Example

**Request**

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Content-Type: application/json' \
--data '{
  "app_id": "{{app_id}}",
  "secret": "{{secret}}",
  "subscribe_entity": "API_SERVICE_STATUS",
  "callback_url": "{{callback_url}}",
  "subscription_detail": {
        "access_token": "{{access_token}}"
    }
}'
(/code)
```

**Response**

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "subscription_id": "{{subscription_id}}"
    }
}
(/code)
```

If you no longer wish to receive notifications, you can cancel the subscription using [/subscription/unsubscribe/](https://ads.tiktok.com/marketing_api/docs?id=1739094758789122).

The webhook response contains the following fields:

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|request_id|number|Unique ID for a webhook request.|
|object|integer|The object's type.<br><br>Supported value:<ul><li>`14`: the API service status.</li></ul>|
|time|integer|The time when the event notification was sent, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example: `1743702195`.|
|entry|object[]|Details of the event. Multiple changes from different objects that are of the same type may be batched together.|
#|incident_id|string|Incident ID.|
#|incident_title|string|Incident title.|
#|incident_products|string[]|The list of API products affected by the incident.<br><br>Enum values:<ul><li>`BUSINESS_CENTER_API`: Business Center API.</li><li>`CREATIVES_API`: Creatives API.</li><li>`CATALOG_API`: Catalog Management API.</li><li>`TIKTOK_STORE_API`: TikTok Store API.</li><li>`CAMPAIGN_API`: Campaign Management API.</li><li>`REPORTING_API`: Reporting API.</li><li>`AUDIENCE_API`: Audience API.</li><li>`STREAMING_API`: Streaming API.</li><li>`EVENTS_API`: Events API.</li><li>`ACCOUNTS_API`: Accounts API.</li><li>`MENTIONS_API`: Mentions API.</li><li>`TIKTOK_ONE_API`: TikTok One API.</li><li>`DISCOVERY_API`: Discovery API.</li><li>`SPARK_RECOMMEND_API`: Spark Ads Recommendation API.</li><li>`BUSINESS_MESSAGING_API`: Business Messaging API.</li></ul>|
#|incident_event_type|string|The type of event related to the incident.<br><br>Enum values:<ul><li>`CREATE`: The incident occurs.</li><li>`UPDATE`: The incident is updated</li><li>`RESOLVE`: The incident is resolved.</li></ul>|
#|incident_level|string|The disruption level for the incident.<br><br>Enum values:<ul><li>`LOW_DISRUPTION`: Low Disruption. Minor issues like elevated latency or timeouts may occur, but the majority of requests succeed.</li><li>`MAJOR_DISRUPTION`: Major Disruption. Critical service impact where most or all API calls are failing or latency exceeds SLA thresholds globally.</li></ul>|
#|incident_start_time|string|The start time of the incident, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time).|
#|incident_resolve_time|string|The resolution time of the incident, in the format of `YYYY-MM-DD HH:MM:SS` (UTC time).|
#|incident_detail|string|Incident description.|
```

#### Sample response code
```xcodeblock
(code JSON JSON)
{
  "entry": [
    {
      "incident_detail": "We are actively investigating and will provide updates here as they become available.",
      "incident_event_type": "CREATE",
      "incident_id": "{{incident_id}}",
      "incident_level": "LOW_DISRUPTION",
      "incident_products": [
        "BUSINESS_CENTER_API",
        "CREATIVES_API",
        "CATALOG_API",
        "TIKTOK_STORE_API",
        "CAMPAIGN_API",
        "AUDIENCE_API",
        "REPORTING_API",
        "EVENTS_API",
        "STREAMING_API",
        "ACCOUNTS_API",
        "MENTIONS_API",
        "TIKTOK_ONE_API",
        "DISCOVERY_API",
        "SPARK_RECOMMEND_API",
        "BUSINESS_MESSAGING_API"
      ],
      "incident_start_time": "2025-01-01 23:58:59",
      "incident_title": "{{incident_title}}"
    }
  ],
  "object": 14,
  "request_id": {{request_id}},
  "time": 1758931141
}
(/code)
```

# Related docs
[Subscription API](https://ads.tiktok.com/marketing_api/docs?id=1739091185604609)



