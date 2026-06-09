---
title: "Get the available regions for a Showcase via identity"
doc_id: 1759233561597954
path: "API Reference / Showcase / Get the available regions for a Showcase via identity"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the available regions for a Showcase via identity

Use this endpoint to get the countries or regions that a Showcase can target by specifying the identity that is bound to the Showcase, and has permission of the Showcase.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/showcase/region/get/

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
| advertiser_id {Required} | string | Advertiser ID. |
| identity_id {Required} | string | The ID of the identity that is bound to a Showcase and the identity needs to have permission of the Showcase. |
| identity_type {Required} | string | Identity type. <br><br>Enum values: <ul><li>`TT_USER`: TikTok User.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. </li></ul> See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) to learn more about different identity types. |
| identity_authorized_bc_id  {+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`. <br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with.|
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/showcase/region/get/?identity_id={{identity_id}}&identity_type={{identity_type}}&advertiser_id={{advertiser_id}}' \
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
#| region_codes | string[] | The list of country or region codes that the Showcase (identity) can target. <br>For example, ["ID"]. |
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
        "region_codes": [
            "ID"
        ]
    }
}
(/code)
```

