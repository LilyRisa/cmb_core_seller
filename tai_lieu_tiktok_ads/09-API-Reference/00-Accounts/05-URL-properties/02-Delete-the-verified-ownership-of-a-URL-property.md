---
title: "Delete the verified ownership of a URL property"
doc_id: 1769325351919617
path: "API Reference / Accounts / URL properties / Delete the verified ownership of a URL property"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Delete the verified ownership of a URL property

Use this endpoint to delete an advertiser account's verified ownership of a URL property (domain or URL prefix).

To learn about the workflow for adding and verifying URL properties, refer to [Manage URL properties](https://ads.tiktok.com/marketing_api/docs?id=1769324038780930).

> <span style="color:DarkRed">**Important**</span><br>
> Starting October 15th, 2025, you need to also specify your developer app secret to be able to use this endpoint.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/property/delete/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| app_id {Required} | string | ID of your developer application. <br><br>To find the <strong>App ID</strong>, navigate to <strong>[My Apps](https://business-api.tiktok.com/portal/apps)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>. |
|secret{Required}|string| Secret of your developer app. <br><br>To find the <b>Secret</b>, navigate to [My Apps](https://ads.tiktok.com/marketing_api/apps/) > <b>App Detail</b> > <b>Basic Information</b>. |
| url_property_meta {Required} | object | Information about the URL property that you want to delete the verified ownership of.|
#| property_type {Required} | integer | Type of the URL property.<br>Enum values：<ul><li>1: Domain.</li><li>2: URL prefix. </li></ul>|
#| url {Required} | string | An owned URL that you want to delete the verified ownership of. Pass in the URL according to the `property_type` that you specify. |
```
### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/business/property/delete/' \
--header 'Content-Type: application/json' \
--data '{
    "app_id": "{{app_id}}",
	"secret": "{{secret}}",
    "url_property_meta": {
        "property_type": {{property_type}},
        "url": "{{url}}"
    }
}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.<br>Empty object (`{}`) for successful actions.|
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {}
}
(/code)
```
