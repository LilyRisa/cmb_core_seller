---
title: "Create a CRM Event Set"
doc_id: 1861366342223874
path: "API Reference / Events 1.0 / CRM Events / Create a CRM Event Set"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a CRM Event Set

Use this endpoint to create a CRM (Customer relationship management) Event Set within an advertiser account. The maximum number of CRM Event Sets allowed within an advertiser account is 50.

> <span style="color:DarkRed">**Important**</span><br>
> By using Events API endpoints, you agree to the [TikTok Business Products (Data) Terms](https://ads.tiktok.com/i18n/official/policy/business-products-terms) and that you will not share sensitive data with TikTok.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/crm/create/

**Method** POST

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br>Allowed format: `application/json`.  |
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| name {Required} | string | The name for the CRM Event Set that you want to create within the ad account.<br><br>Length limit: 40 characters.<br>Leading and trailing spaces will be trimmed, and the resulting name must not be empty.<br><br><p><span style="color:darkred"><strong>Note</strong></span>: Duplicate CRM Event Set names are not allowed.</p> |
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/crm/create/' \\
--header 'Access-Token: {{Access-Token}}' \\
--header 'Content-Type: application/json' \\
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "name": "{{name}}"
}'
(/code)
```

## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| event_set_id | string | The ID of the CRM Event Set. |
#| name | string | The name of the CRM Event Set. |
#| create_time | string | The time when the CRM Event Set was created, in [ISO-8601](https://en.wikipedia.org/wiki/ISO_8601) format.<br><br>Example: `2026-01-01T00:00:01Z`. |
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
        "event_set_id": "{{event_set_id}}",
        "name": "{{name}}",
        "create_time": "{{create_time}}"
    }
}
(/code)
```

