---
title: "Set up Advanced Matching"
doc_id: 1749819614433282
path: "Marketing API / Ad Measurement / Guides / Events API 1.0 / Events API : Web / Set up Advanced Matching"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Set up Advanced Matching

This guide is intended for implementing Advanced Matching with the TikTok Events API. For details of Advanced Matching, see [here](https://ads.tiktok.com/help/article?aid=10007891&redirected=1).

# Best Practices
As a best practice, we encourage you to send both email and phone numbers for all visitor events. We also recommend capturing visitors' emails and phone numbers early in the funnel for best results. This increases the probability of matching customers with people on TikTok.


# Steps
1. Navigate to the event (or event code) you want to implement Advanced Matching with.
3. In the `context.user` object, include the available PIIs (phone number and email) when sending the payload to TikTok.

**Example**

Here is an example curl request for an "Initiate Checkout" event with hashed phone number and email parameters added to the payload:
```xcodeblock
(code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/pixel/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "pixel_code": "BU35TSQHT2A1QT375GMG",
  "event": "InitiateCheckout",
  "event_id":"1616318632825_357",
  "timestamp": "2020-09-17T19:49:27Z",
  "context": {
    "ad": {
      "callback": "123ATXSfe"
    },
    "page": {
      "url": "http://demo.mywebsite.com/purchase",
      "referrer": "http://demo.mywebsite.com"
    },
    "user": {
      "external_id": "f0e388f53921a51f0bb0fc8a2944109ec188b59172935d8f23020b1614cc44bc",
      "phone_number": "2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea",
      "email": "dd6ff77f54e2106661089bae4d40cdb600979bf7edc9eb65c0942ba55c7c2d7f",
      "ttp":"94e2a4j9-h3j5-k2h5-98cc-c84a745mk098"
    },
    "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.109 Safari/537.36",
    "ip": "13.57.97.131"
  },
  "properties": {
    "contents": [
     {
        "price": 8,
        "quantity": 2,
        "content_id": "1077218",
        "content_name": "running shoes",
        "content_category": "Shoes > Sneakers > running shoes",
        "brand": "your_brand_name"
      },
      {
        "price": 30,
        "quantity": 1,
        "content_id": "1197218",
        "content_name": "running shoes",
        "content_category": "Shoes > Sneakers > running shoes",
        "brand": "your_brand_name"
      }
    ],
    "content_type": "product",
    "currency": "USD",
    "value": 46.00
  }
}
'
(/code)
```

3. Once you've added the additional parameters, use the [Test Event Function](https://ads.tiktok.com/marketing_api/docs?id=1739584863252481) to check if the PIIs are sent successfully.

Once TikTok receives these PIIs, TikTok will use these to match customers with people on TikTok.
