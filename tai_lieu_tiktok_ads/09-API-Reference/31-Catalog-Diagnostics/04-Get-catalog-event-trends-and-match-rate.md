---
title: "Get catalog event trends and match rate"
doc_id: 1780802101608450
path: "API Reference / Catalog Diagnostics / Get catalog event trends and match rate"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get catalog event trends and match rate

Use this endpoint to retrieve the trend of app events or pixel events received, identify the number of events available for retargeting, and obtain the catalog match rate of your catalog.

## Before you start
Ensure that you have bound an event source to a catalog in a Business Center by using [/catalog/eventsource/bind/](https://business-api.tiktok.com/portal/docs?id=1740492491200513) or on TikTok Ads Manager. To learn more about how to bind an event source to your catalog on TikTok Ads Manager, see [How to Create and Manage Catalogs - Connect Event Sources](https://ads.tiktok.com/help/article/create-manage-catalogs?lang=en#anchor-18).

To confirm whether an event source has been bound to a catalog, use [/catalog/eventsource_bind/get/](https://business-api.tiktok.com/portal/docs?id=1740492531343362).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/diagnostic/catalog/eventsource/metric/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| bc_id {Required} | string | Business Center ID. <br><br>To get the list of Business Centers that you can access, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826). |
| catalog_id {Required} | string | Catalog ID. <br><br>To get the catalogs under a Business Center, use [/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610). |
| event_source_type {Required} | string | The event source type. <br><br>Enum values: <ul><li>`APP`: App. Pass `app_id` at the same time. </li><li>`PIXEL`: Pixel. Pass `pixel_code` at the same time. </li></ul>|
| app_id {+Conditional} | string | Required when `event_source_type` is set to `APP`. <br><br>The App ID. <br><br>To retrieve the App IDs that are bound to your catalog (`catalog_id`), use [/catalog/eventsource_bind/get/](https://business-api.tiktok.com/portal/docs?id=1740492531343362). |
| pixel_code {+Conditional} | string | Required when `event_source_type` is set to `PIXEL`. <br><br> The Pixel code. <br><br>To retrieve the Pixel codes that are bound to your catalog (`catalog_id`), use [/catalog/eventsource_bind/get/](https://business-api.tiktok.com/portal/docs?id=1740492531343362).. |
| event_type | string | The event type that you want to retrieve data for. <br><br>Enum values: <ul><li> `VIEW_CONTENT`: View Content.</li><li>`ADD_TO_CART`: Add to Cart.</li><li>`PURCHASE`: Complete Payment.</li></ul><br>Default value: `VIEW_CONTENT`. |
| time_range | string | The time range that you want to retrieve data for. <br><br>Enum values: <ul><li> `YESTERDAY`: Yesterday. </li><li>`LAST_7_DAYS`: Last 7 days. </li><li>`LAST_30_DAYS`: Last 30 days. </li></ul><br>Default value: `LAST_7_DAYS`. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/diagnostic/catalog/eventsource/metric/?bc_id={{bc_id}}&catalog_id={{catalog_id}}&event_source_type=PIXEL&pixel_code={{pixel_code}}&time_range=LAST_7_DAYS&event_type=PURCHASE' \
--header 'Access-Token: {{Access-Token}}' \
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| list | object[] | Information about the trend of events received and the number of events available for retargeting. |
##| available_type | string | The available categories of events. <br><br> Enum values: <ul><li>`EVENT_RECEIVED`: All events. </li><li>`EVENT_WITH_CONTENT_ID`: Events with content IDs.</li><li>`EVENT_WITH_CONTENT_ID_MATCHING_INVENTORY`: Events matching content IDs (Events with content IDs that match inventory).</li></ul>|
##| event_details | object[] | The details of the event category (`available_type`). |
###| date | string | The date, in the format of `"YYYY-MM-DD"`.<br><br>Example: `"2023-12-30"`. |
###| count | string | The number of events that belong to the event category are received on the date. <br><br>Example: `"245076"`.  <br><br>For instance, when `available_type` is `EVENT_WITH_CONTENT_ID_MATCHING_INVENTORY`, a value of `"245076"` for this field represents that 245,076 events with content IDs that match products (SKU ID) in your catalog are received on the date. |
###| percentage | string | The percentage of the events belonging to the event category that are received on the date, out of all events received on the date. <br><br> When `available_type` is `EVENT_WITH_CONTENT_ID_MATCHING_INVENTORY`, the value of this field represents the catalog match rate, which is calculated by dividing the number of signal events that match products in your catalog by the total number of signal events shared with TikTok. <br>We recommend keeping your match rate levels above 90% to ensure optimal ad performance for Video Shopping Ads. <br><br>Example: `"100"`. <br><br><p><span style="color:darkred"><b>Note</b></span>: When `available_type` is `EVENT_RECEIVED`, the value of this field is always `"100"`. |
```

### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "available_type": "EVENT_RECEIVED",
                "event_details": [
                    {
                        "date": "2023-10-04",
                        "percentage": "100",
                        "count": "4086"
                    },
                    {
                        "date": "2023-10-05",
                        "percentage": "100",
                        "count": "4372"
                    },
                    {
                        "date": "2023-10-06",
                        "percentage": "100",
                        "count": "3939"
                    },
                    {
                        "date": "2023-10-07",
                        "percentage": "100",
                        "count": "3017"
                    },
                    {
                        "date": "2023-10-08",
                        "percentage": "100",
                        "count": "2853"
                    },
                    {
                        "date": "2023-10-09",
                        "percentage": "100",
                        "count": "5197"
                    },
                    {
                        "date": "2023-10-10",
                        "percentage": "100",
                        "count": "5050"
                    }
                ]
            },
            {
                "available_type": "EVENT_WITH_CONTENT_ID",
                "event_details": [
                    {
                        "date": "2023-10-04",
                        "percentage": "96",
                        "count": "3960"
                    },
                    {
                        "date": "2023-10-05",
                        "percentage": "97",
                        "count": "4259"
                    },
                    {
                        "date": "2023-10-06",
                        "percentage": "97",
                        "count": "3847"
                    },
                    {
                        "date": "2023-10-07",
                        "percentage": "96",
                        "count": "2899"
                    },
                    {
                        "date": "2023-10-08",
                        "percentage": "95",
                        "count": "2728"
                    },
                    {
                        "date": "2023-10-09",
                        "percentage": "96",
                        "count": "5035"
                    },
                    {
                        "date": "2023-10-10",
                        "percentage": "97",
                        "count": "4908"
                    }
                ]
            },
            {
                "available_type": "EVENT_WITH_CONTENT_ID_MATCHING_INVENTORY",
                "event_details": [
                    {
                        "date": "2023-10-04",
                        "percentage": "37",
                        "count": "1535"
                    },
                    {
                        "date": "2023-10-05",
                        "percentage": "35",
                        "count": "1572"
                    },
                    {
                        "date": "2023-10-06",
                        "percentage": "36",
                        "count": "1450"
                    },
                    {
                        "date": "2023-10-07",
                        "percentage": "33",
                        "count": "1019"
                    },
                    {
                        "date": "2023-10-08",
                        "percentage": "28",
                        "count": "819"
                    },
                    {
                        "date": "2023-10-09",
                        "percentage": "37",
                        "count": "1946"
                    },
                    {
                        "date": "2023-10-10",
                        "percentage": "36",
                        "count": "1867"
                    }
                ]
            }
        ]
    }
}
(/code)
```
