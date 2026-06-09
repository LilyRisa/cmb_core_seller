---
title: "Get identities with Showcase permission under an ad account"
doc_id: 1759233549899778
path: "API Reference / Showcase / Get identities with Showcase permission under an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get identities with Showcase permission under an ad account

Use this endpoint to get identities with Showcase permission under an ad account.

Note that only identities with Showcase permission can be used to create Video Shopping Ads with products from Showcase. See [here](https://ads.tiktok.com/marketing_api/docs?id=1759232259360770) to learn more about the detailed steps.


## Request

**Endpoint**  https://business-api.tiktok.com/open_api/v1.3/showcase/identity/get/

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
```
### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/showcase/identity/get/?advertiser_id={{advertiser_id}}' \
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
#| identity_list | object[] | The list of identities with Showcase permission. 
##| identity_id | string | The ID of the identity that the Showcase is bound to. |
##| identity_type | string | Identity type. Enum values: <ul><li>`TT_USER`: TikTok User.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. </li></ul> See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) to learn more about different identity types. |
##| identity_authorized_bc_id | string | Returned when `identity_type` is `BC_AUTH_TT`. <br> ID of the Business Center that a Showcase, which is bound to a TikTok Account User in Business Center identity, is associated with. |
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
        "identity_list": [
            {
                "identity_type": "BC_AUTH_TT",
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}"
            },
            {
                "identity_type": "BC_AUTH_TT",
                "identity_id": "{{identity_id}}",
                "identity_authorized_bc_id": "{{identity_authorized_bc_id}}"
            }
        ]
    }
}
(/code)
```

