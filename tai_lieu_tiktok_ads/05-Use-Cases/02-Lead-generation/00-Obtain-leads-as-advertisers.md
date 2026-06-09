---
title: "Obtain leads as advertisers"
doc_id: 1806817494948930
path: "Use Cases / Lead generation / Obtain leads as advertisers"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Obtain leads as advertisers

This article walks you through the steps to obtain leads from the Lead Generation campaign as advertisers.

# Introduction
When you create a Lead Generation campaign, you will be able to increase conversions and drive web traffic. Lead Generation can help you collect meaningful signals from people interested in your business. **You can use API to quickly access leads data in one of the following ways:**
 - **Download leads**
- **Subscribe to leads via webhooks**


# Prerequisites
- You've gained access to Tiktok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937&rid=7llhcla7zmh) for details. 

	- To set up a webhook for leads, you need relevant permissions for the endpoints listed in the "Steps" section. See [API Reference](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521) to find out the required permissions for each endpoint and see [Update app permissions](https://ads.tiktok.com/marketing_api/docs?id=1738855280338946&rid=7llhcla7zmh) to find out how to configure permissions.    
  
- You've Lead Generation ads. See [Create Lead Generation ads](https://ads.tiktok.com/marketing_api/docs?id=1739470742024193) for detailed creation steps.

# Steps
## Download leads

> <span style="color: DodgerBlue">**Note**</span><br>To protect customer information, only ad account admins can create and query lead download tasks or download the leads. 
1. Make a GET request to the [/page/lead/task/](https://ads.tiktok.com/marketing_api/docs?id=1739052936772610) endpoint to create a leads download task. 
2. When the task status is `SUCCEED`, make a GET request to the [/page/lead/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1739053563877378) endpoint to download the leads. 

##  Subscribe to leads via webhooks
Webhooks enable you to integrate Lead Generation ads with your CRM system and receive real-time updates for both Instant Form and direct message leads.

### Create a subscription
To learn about how to subscribe to lead webhooks, see [Subscribe to leads](https://business-api.tiktok.com/portal/docs?id=1810521739537409#item-link-Subscribe%20to%20leads).


### Test the subscritpion

After the subscription, you can follow the procedure below to make an end to end testing for leads generation.  
Please note that you can only create one testing lead for each page. You have to delete the existing testing lead before creating a new one.

1. Make a POST request to the [/page/lead/mock/create/](https://ads.tiktok.com/marketing_api/docs?id=1739047132614722) endpoint to create a test lead.  
2. Make a GET request to the [/page/lead/mock/get/](https://ads.tiktok.com/marketing_api/docs?id=1739048460493826) endpoint to get the test lead.
3. Make a POST request to the [/page/lead/mock/delete/](https://ads.tiktok.com/marketing_api/docs?id=1739049313198081) endpoint to delete the test lead.


### Additional notes

#### Validate payloads

We sign all event notification payloads with a **HMAC-SHA256** signature and include the signature in the request's **X-Open-Signature header**. We recommend that you validate the payload.  

To validate the payload:

1. Generate a **HMAC-SHA256** signature by inputting the payload and your app's **App Secret**.

Here is an example of how to generate the signature.

Sample payload:
```xcodeblock
(code JSON JSON)
{
   "request_id":{{request_id}},"
   object":{{object}},
   "entry": [
    {
      "id": {{id}},
      "page_id":{{page_id}},
      "page_name":"hello_world",
      "campaign_id":{{campaign_id}},
      "campaign_name":"{{campaign_name}}",
      "adgroup_id":{{adgroup_id}},
      "adgroup_name":"{{adgroup_name}}",
      "ad_id":{{ad_id}},
	  "ad_name":"{{ad_name}}",
      "create_time":{{create_time}},
      "changes": [
       
        {
          "field": "phone_number",
          "value": "{{phone_number}}"
        },
		
        {
          "field": "email",
          "value": "{{email}}"
        },
   
        {
          "field": "name",
          "value": "{{name}}"
        },
      ]
    }
  ],
  "time": {{time}}
}
(/code)
```
Sample App Secret: `5b5c09c880c9af2c69e39b18024f255d3****`

Input both your payload and your app's App Secret into an **HMAC-SHA256** encryption function to get an encoded result string. This string is the signature. For example, you might get: `48fa5d1c81597afc6d15bf5aa588f45df8f0b22d0c0d66d8a2977b368a3f****`.

2. Compare your signature to the signature in the **X-Open-Signature header**. If the signatures match, the payload is genuine.

Please note that we generate the signature using an *escaped unicode* version of the payload, with lowercase hex digits. If you just calculate against the decoded bytes, you will end up with a different signature. For example, the string äöå should be escaped to \u00e4\u00f6\u00e5.

#### Respond to event notifications

Your endpoint should respond to all event notifications with 200 OK HTTPS.

#### Frequency

Event notifications are aggregated and sent in a batch with a **maximum** of 1000 updates. However batching cannot be guaranteed so be sure to adjust your servers to handle each webhook individually.  
If any update sent to your server fails, the API will retry immediately, then try a few more times with decreasing frequency over the next 24 hours. Your server should handle deduplication in these cases. Unacknowledged responses will be dropped after 24 hours.

#### Post-subscription operations

If you do not need to subscribe to leads for a page any more, you can cancel the subscription. You can get all subscriptions for an advertiser. For details, see [Get subscriptions](https://ads.tiktok.com/marketing_api/docs?rid=a1l2363kywo&id=1739093832125442) and [Cancel a subscription](https://ads.tiktok.com/marketing_api/docs?rid=a1l2363kywo&id=1739094758789122).

#### Get form subscription and lead download logs

You can use the [/changelog/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1738811219460097) endpoint to get the change logs for activities like subscribing to a form, unsubscribing from a form, and downloading leads from a form. Use the following data field settings:
* Pass in the page IDs to `object_ids` array.
* Set `object_type` to `INSTANT_FORM`.
* Set `operation_types` to a combination of `DOWNLOAD_LEADS`, `SUBSCRIBE_FORM`, and `UNSUBSCRIBE_FORM`. 

# Related docs
- [Leads API](https://ads.tiktok.com/marketing_api/docs?id=1739047069984834)
- [Lead Generation Guide](https://ads.tiktok.com/help/article/build-instant-form?lang=en) on TikTok Business Help Center
