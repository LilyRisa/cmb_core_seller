---
title: "Events API 2.0"
doc_id: 1771100779668482
path: "Marketing API / Ad Measurement / Guides / Events API 2.0"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Events API 2.0

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.

> <span style="color:DodgerBlue">**Note**</span><br>
> The Events API 2.0 ([/event/track/](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761)) is now the recommended endpoint for reporting events from all sources, including web, app, offline and CRM (Customer relationship management) systems. This endpoint provides a unified API schema to help advertisers simplify and streamline their integration.
<br><br>To ensure a smooth transition, the following six Events API 1.0 endpoints will be sunset in H2 2024:<ul><li>[/pixel/track/](https://ads.tiktok.com/marketing_api/docs?id=1740858531237890) and [/pixel/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740858565852225)</li><li>[/offline/track/](https://ads.tiktok.com/marketing_api/docs?id=1758428013689857) and [/offline/batch/](https://ads.tiktok.com/marketing_api/docs?id=1758428053652482)</li><li>[/app/track/](https://ads.tiktok.com/marketing_api/docs?id=1740859196043266) and [/app/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740859218541569)</li></ul>
<br>We highly recommend that new users directly integrate with the Events API 2.0 endpoint, and existing Events API 1.0 users start upgrading to the Events API 2.0 as soon as possible. By doing so, you can take advantage of the benefits of the latest version. 

## Introduction
TikTok for Business offers many tools that let you share the actions people take on your website with TikTok. By sharing these events with TikTok, you can better optimize your campaigns and measure ad performance.

TikTok’s Events API is a secure server-to-server integration that allows advertisers to share the actions customers take on their **websites**, **apps**,  **offline**  or **CRM** systems directly with TikTok.

The Events API 2.0 ([/event/track/](https://business-api.tiktok.com/portal/docs?id=1771101303285761)) is now the recommended endpoint for reporting events from all sources, including web, app, offline, and CRM. This endpoint provides a unified API schema to help advertisers simplify and streamline their integration.

## Authentication
To authenticate Events API calls, you need an access token. You can generate the access token directly in Events Manager (**recommended**) or with your own developer app if you already have one. To learn about how to obtain an access token, refer to [Events API 2.0 - Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058).

## Parameters overview
Below is a high-level overview of Events API 2.0 payload schema. For a complete list of parameters and detailed information about each one,  refer to [API reference - Events 2.0](https://ads.tiktok.com/marketing_api/docs?id=1771101276978178).

Note that different event sources (such as web, app, offline, and CRM systems) have different sets of required and recommended fields, as well as best practices. For more information on the specific requirements for each event source type, refer to the appropriate setup guide:
- [Setup guide for Web](https://ads.tiktok.com/marketing_api/docs?id=1771100865818625)
- [Setup guide for Offline](https://ads.tiktok.com/marketing_api/docs?id=1771101027431425)
- [Setup guide for App](https://ads.tiktok.com/marketing_api/docs?id=1771101111730178)
- [Setup guide for CRM](https://ads.tiktok.com/marketing_api/docs?id=1835052338764993)
```xtable
| Field{25%} | Data Type{15%} | Note{60%} |
|---|---|---|
| event_source {Required} | string | Currently, the enum values are `web`, `app`, `offline` and `crm`. |
| event_source_id {Required} | string   |  An Event Source ID that is used to measure events.|
| data {Required} | object[]   |  An object array of the events you want to report. |
#| event {Required} | string | Conversion event name. It can be either a standard event or a custom event. |
#| event_time{Required} | integer | Unix timestamp of when the event occurred. |
#| event_id | string | Unique ID for setting up deduplication. <br>To learn more about deduplication, refer to [Events API 2.0 - Events Deduplication](https://ads.tiktok.com/marketing_api/docs?id=1771100965992450). |
#| limited_data_use | boolean | This field is only supported when `event_source` is `web` or `app`. <br>Limited data processing setting.<br>To learn more about the Limited Data Use feature, refer to [Events API 2.0 - Limited Data Use](https://ads.tiktok.com/marketing_api/docs?id=1771101204435970). |
#| user | object | <strong>Information about the user.</strong><br>To view the list of fields included in the user object, refer to the "[`user` parameters](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761#item-link-user%20parameters)" section. |
#| properties | object |<strong> Information about the product or order.</strong> <br>To view the list of fields included in the properties object, refer to the "[`properties` parameters](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761#item-link-properties%20parameters)" section. |
#| page {+Conditional} | object | <strong>Information about the web page</strong> - Required for reporting web events. <br>To view the list of fields included in the page object, refer to the "[`page` parameters](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761#item-link-page%20parameters)" section. |
#| app {+Conditional} | object |<strong> Information about the app </strong>- Required for reporting app events. <br>To view the list of fields included in the app object, refer to the "[`app` parameters](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761#item-link-app%20parameters)" section. |
#| ad {+Conditional} | object | <strong>Information about the ad</strong> - Only for reporting app events.<br>To view the list of fields included in the ad object, refer to the "[`ad` parameters](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761#item-link-ad%20parameters)" section. |
#| lead {+Conditional} | object | <strong>Information about the lead</strong> - Required for reporting CRM events.<br>To view the list of fields included in the `lead` object, refer to the "[`lead` parameters](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761#item-link-lead%20parameters)"section. |
```

## Single event vs. Batched event
The `/event/track/` endpoint accepts both a single event (where a single object is passed to the `data` parameter ) or a batch of multiple events (where multiple objects are passed to the `data` parameter). Note that at most 1,000 events can be sent in a single request.

Although you can send a batch of events, it is **highly recommended** to send a single event at a time, as soon as the event is received on the advertiser's server. This approach allows TikTok to best utilize the event for measuring and optimizing ad campaign performance.

### Example: Sending a single event
Below is an example of sending a minimal web event through the Events API using the cURL POST command. 

**Prerequisite**: You will need a Pixel ID and a valid access token to replace the `{{Access-Token}}` and `{{Pixel-Code}}` in the sample API payload. To find out how to obtain an access token and a Pixel ID, refer to [Setup guide for Web](https://ads.tiktok.com/marketing_api/docs?id=1771100865818625).

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
  "event_source": "web",
  "event_source_id": "{{Pixel-Code}}",
  "data": [
    {
      "event": "Purchase",
      "event_time": 1684574395
      "user": {
        "email": "848a771458438fc2ec420560d769fb9b9b86851ee338ec56517baabd79d3bb4f",
        "phone": "2f9d2b4df907e5c9a7b3434351b55700167b998a83dc479b825096486ffcf4ea",
        "ip": "139.177.225.245",
        "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.1 Safari/605.1.15"
      },
      "page": {
        "url": "http://www.example.com/purchase?q=hello"
      }
    }
  ]
}'
(/code)
```

If the event is received successfully by TikTok, the API will return a response with a `200` HTTP status code and an API response code of `0`. Here's an example of a successful API response:

```xcodeblock
(code curl http)
HTTP 1.1 200 OK
{
  "message": "OK",
  "code": 0,
  "request_id": "{{request_id}}",
  "data":{} 
}
(/code)
```

If the request fails, you will get a `4XX` or `5XX` HTTP status code with detailed API error code and message in the response body explaining the reasons for the failure. See [Events API 2.0 - Responses and errors](https://ads.tiktok.com/marketing_api/docs?id=1771101162079234) for more details.

## API limits
The Events API 2.0 (`/event/track/`) has a rate limit of 1,000 QPS (queries per second) per advertiser account (when using Events Manager to generate the access token) or developer app (when using your own developer app to generate the access token). See [Rate limits](https://ads.tiktok.com/marketing_api/docs?id=1740029171730433) for more details.

If your business requires more QPS than the default rate limit of 1,000, please contact your TikTok representative.

## Related docs
- [About Events API](https://ads.tiktok.com/help/article/events-api?lang=en)
- [Events API for Web](https://ads.tiktok.com/marketing_api/docs?id=1771100865818625)
- [Events API for Offline](https://ads.tiktok.com/marketing_api/docs?id=1771101027431425)
- [Events API for App](https://ads.tiktok.com/marketing_api/docs?id=1771101111730178)
- [Events API for CRM](https://ads.tiktok.com/marketing_api/docs?id=1835052243486786)
- [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1771101130925058)
- [Parameters](https://ads.tiktok.com/marketing_api/docs?id=1771101151059969)
- [Responses and errors](https://ads.tiktok.com/marketing_api/docs?id=1771101162079234)
- [Supported events](https://ads.tiktok.com/marketing_api/docs?id=1771101186666498)
- [Limited data use](https://ads.tiktok.com/marketing_api/docs?id=1771101204435970)
- [Changelog](https://ads.tiktok.com/marketing_api/docs?id=1771101231937537)
- [Report App, Web, Offline, or CRM Events](https://ads.tiktok.com/marketing_api/docs?id=1771101303285761)
