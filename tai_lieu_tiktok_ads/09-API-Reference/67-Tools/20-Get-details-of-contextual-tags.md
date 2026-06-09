---
title: "Get details of contextual tags"
doc_id: 1747747180830722
path: "API Reference / Tools / Get details of contextual tags"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get details of contextual tags

Use this endpoint to get the detailed information of contextual tags. In your query, you need to specify the advertiser ID and contextual tag ID that you get from the response of [/tool/contextual_tag/get/](https://ads.tiktok.com/marketing_api/docs?id=1747747118654465).

See [here](https://ads.tiktok.com/marketing_api/docs?id=1745292519923713) to find out how to use contextual targeting. 
><span style="color:DodgerBlue">**Note**</span><br> Contextual targeting is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.


## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/tool/contextual_tag/info/

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
| advertiser_id {required} | string | Advertiser ID. |
| contextual_tag_ids {required} | string[] | Contextual tag IDs.|
```

### Example
```xcodeblock
(code curl http)
curl --location --get 'https://ads.tiktok.com/open_api/v1.3/tool/contextual_tag/info/?advertiser_id={{advertiser_id}}&contextual_tag_ids=[%2251%22]' \
--header 'Access-Token: {{access-token}}' \
--header 'Content-Type: application/json' \
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
#| contextual_tag_list | object[] | Contextual tag list. <br><p><span style="color:darkred"><b>Note</b></span>: Contextual targeting is currently an allowlist-only feature. The returned value will be empty(`[]`) if you don't apply for the allowlisting. |
##| contextual_tag_id | string | Contextual tag ID. <br><p><span style="color:darkred"><b>Note</b></span>: Currently contextual tags can only be used together with audience targeting settings that target the following countries: <ul><li>General tags: `US` (the United States), `CA` (Canada), `GB` (the United Kingdom), `DE` (Germany), `FR` (France), `IT` (Italy) , `ES` (Spain), `JP` (Japan), `KR` (South Korea), `AU` (Australia)，`BR` (Brazil), `MX`(Mexico).</li><li>Premium tags: `US` (the United States), `CA` (Canada), `GB` (the United Kingdom), `DE` (Germany), `FR` (France), `IT` (Italy) , `ES` (Spain), `JP` (Japan), `KR` (South Korea), `AU` (Australia)，`BR` (Brazil), `MX`(Mexico).</li></ul> |
##| name | string | Contextual tag name. |
##| type | string | Contextual tag category. Enum values: `GENERAL` (general category), `PREMIUM`(premium category). |
##| status | string | Contextual tag status. Enum: `ONLINE`, `OFFLINE`. Only `ONLINE` tags can be used to create ads. |
##| description | string | Contextual tag description. |
##| objective_types | string[] | The advertising objectives that the contextual tag supports. |
##| region_codes | string[] | The country or region codes that the contextual tag supports. |
```

### Example
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "contextual_tag_list": [
            {
                "content_lineup_type": "MAX_PULSE",
                "description": "{{description}}",
                "objective_types": [
                    "RF_REACH"
                ],
                "name": "Max Pulse",
                "contextual_tag_id": "51",
                "type": "PREMIUM",
                "region_codes": [
                    "US",
                    "CA",
                    "BR"
                ],
                "status": "ONLINE"
            }
        ]
    }
}
(/code)
```
