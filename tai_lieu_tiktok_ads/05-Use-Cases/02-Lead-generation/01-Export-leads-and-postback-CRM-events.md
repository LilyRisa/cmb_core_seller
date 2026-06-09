---
title: "Export leads and postback CRM events"
doc_id: 1806817505894402
path: "Use Cases / Lead generation / Export leads and postback CRM events"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Export leads and postback CRM events

This article walks you through the steps to export leads and postback CRM (Customer Relationship Management) events as CRM partners.


# Introduction
As a CRM partner, you can integrate with TikTok Lead Generation, allowing your customers to export leads directly into your CRM without any coding requirements.


# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. 
  - To export leads and postback CRM events, you need relevant permissions. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946) to find out how to configure permissions.  <br>Ensure your developer app has the following permission scopes:
    - **Ad Account Management** > **Ad Account Information**
    - **Creative Management** > **Instant Page Management**
    - **Lead Management** > **Leads Retrieval**
    - **Lead Management** > **Test Leads**
    - **CRM Event Management** > **Read CRM Event Sets**
    - **Measurement** > **Report Conversion Event**

- You can use [/user/info/](https://business-api.tiktok.com/portal/docs?id=1739665513181185) to obtain the details of users to personalize the experience for them.
- You have obtained a list of Instant Forms within your ad account by calling [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) with the following parameters:
  - `business_type` set to `LEAD_GEN` 
  - `status` set to `PUBLISHED`
- You have retrieved the fields of Instant Forms for field mapping by calling [/page/field/get/](https://business-api.tiktok.com/portal/docs?id=1739054080455681).

# Steps
To set up the integration with TikTok Lead Generation ads, follow the following steps:

## 1. Access leads data
Choose one of the following options:

### Option 1: Receive leads via webhooks
**Subscribe to Instant Forms or direct message leads** by calling [/subscription/subscribe/](https://business-api.tiktok.com/portal/docs?id=1739092028876801). To learn more about the steps, see [Subscribe to leads](https://business-api.tiktok.com/portal/docs?id=1810521739537409#item-link-Subscribe%20to%20leads). Once the subscription is complete, you can receive real-time updates for lead generation.

> <span style="color:DodgerBlue">**Note**</span><br>
> If at a later time you want to cancel a subscription, use [/subscription/unsubscribe/](https://business-api.tiktok.com/portal/docs?id=1739094758789122).

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/subscription/subscribe/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "app_id": "{{app_id}}", 
    "secret": "{{secret}}",
    "subscription_detail": {
        "advertiser_id": "{{advertiser_id}}", 
        "access_token": "{{access_token}}", 
        "page_id": "{{page_id}}"
    }, 
    "subscribe_entity": "LEAD", 
    "callback_url": "{{callback_url}}" // when there is a new lead, our 
    // service will send a post reqeust containing lead data to this callback url
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "subscription_id": "{{subscription_id}}"
    },
    "request_id": "{{request_id}}"
}
(/code)
```

### Option 2:  Download leads via tasks
i. **Create a lead download task** by calling [/page/lead/task/](https://business-api.tiktok.com/portal/docs?id=1739052936772610). Note that due to data compliance requirements, the leads of advertisers are stored separately in three regions: Europe (including European Economic Area (EEA), Switzerland and the U.K.), the U.S., and the rest of the world. 

For detailed information on configuring the headers for lead tasks across different regions, refer to the following code examples:

**Create a download task for European leads**
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/?advertiser_id={{advertiser_id}}&page_id={{page_id}}' \
--header 'Access-Token: {{Access-Token}}' \
<span style="background-color: green;"> --header 'x-lead-region: eu'</span>
(/code)
```

**Create a download task for US leads**
```xcodeblock
(code http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/?advertiser_id={{advertiser_id}}&page_id={{page_id}}' \
--header 'Access-Token: {{Access-Token}}' \
<span style="background-color: green;"> --header 'x-lead-region: us'</span>
(/code)
```

**Create a download task for other leads**
```xcodeblock
(code http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/?advertiser_id={{advertiser_id}}&page_id={{page_id}}' \
--header 'Access-Token: {{Access-Token}}' 
(/code)
```

Successful response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "status": "CREATED",
        "task_id": "{{task_id}}"
    }
}
(/code)
```

ii. When the task `status` is `SUCCEED`, **download leads** by calling [/page/lead/task/download/](https://business-api.tiktok.com/portal/docs?id=1739053563877378). For detailed information on configuring the headers for downloading the leads for different regions, refer to the following code examples:

**Download leads for Europe**
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/download/?advertiser_id={{advertiser_id}}&task_id={{task_id}}' \
--header 'Access-Token: {{Access-Token}}' \
<span style="background-color: green;">--header 'x-lead-region: eu'</span>
(/code)
```

**Download leads for the U.S.**
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/download/?advertiser_id={{advertiser_id}}&task_id={{task_id}}' \
--header 'Access-Token: {{Access-Token}}' \
<span style="background-color: green;">--header 'x-lead-region: us'</span>
(/code)
```

**Download leads for the rest of the world**
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/download/?advertiser_id={{advertiser_id}}&task_id={{task_id}}' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```


##  2. Test your leads
**Test your leads** by using the following endpoints:
- [/page/lead/mock/create/](https://business-api.tiktok.com/portal/docs?id=1739047132614722)
- [/page/lead/mock/get/](https://business-api.tiktok.com/portal/docs?id=1739048460493826)
- [/page/lead/mock/delete/](https://business-api.tiktok.com/portal/docs?id=1739049313198081)

## 3. Postback CRM events
Using the CRM Events API to send back events data to TikTok might yield higher-quality leads that are more likely to convert. 

Follow the following steps to postback CRM events:

i. **Retrieve CRM Event Sets within your ad account** by calling [/crm/list/](https://business-api.tiktok.com/portal/docs?id=1780896521680898). 

ii. **Send back your CRM events data** by calling [/event/track/](https://business-api.tiktok.com/portal/docs?id=1771101303285761). Set `event_source` to `crm` and set `event_source_id` to one of the CRM Event Set IDs obtained from step i.



Example
```xcodeblock
(code curl http)
curl --location  --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{Access-Token}}' \ 
--header 'Content-Type: application/json' \
--data '{
    "event_source": "crm",
    "event_source_id": "CCM52VJC77U1F3L1CJRG", // target crm event set id
    "partner_name": "Name of the Partner", //name of the CRM
    "data": [
        {
            "event": "qualified", // the status of one lead
            "event_time": 1687673101, // timestamp for the status change
            "lead": {
                "lead_id": "lead_id", // lead_id will pass to crm when syncing
                "lead_event_source": "Lead_gen_partner_name" // name of the CRM 
            }
        }
    ]
}'
(/code)
```

## 4. List your CRM on TikTok Ads Manager
After you have completed the preceding steps, you can grow your customer base by increasing your visibility on TikTok.

Fill out the [TikTok Ads Manager Listing form](https://ads.tiktok.com/leads_management/crm_listing_form?lane_id=master) to make your CRM platform discoverable for advertisers on TikTok Ads Manager.
