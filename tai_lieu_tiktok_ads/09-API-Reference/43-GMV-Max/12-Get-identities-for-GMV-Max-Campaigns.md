---
title: "Get identities for GMV Max Campaigns"
doc_id: 1822001101474882
path: "API Reference / GMV Max / Get identities for GMV Max Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get identities for GMV Max Campaigns

Use this endpoint to obtain a list of identities that are associated with a TikTok Shop and whether the identities are available for GMV Max Campaigns.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/gmv_max/identity/get/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| advertiser_id {Required} | string | Advertiser ID. |
| store_id {Required} | string | The ID of the TikTok Shop.<br><br> To obtain a TikTok Shop that is available for GMV Max Campaigns, use [/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041) and confirm that the returned `is_gmv_max_available` is `true`. |
| store_authorized_bc_id {Required} | string | ID of the Business Center that is authorized to access the TikTok Shop (`store_id`). |
```

### Example
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/identity/get/?advertiser_id={{advertiser_id}}&store_id={{store_id}}&store_authorized_bc_id={{store_authorized_bc_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| identity_list | object[] | The list of identities associated with the TikTok Shop. |
##| identity_id | string | Identity ID. |
##| identity_type | string | Identity type. <br><br>Enum values: <ul><li>`AUTH_CODE`: Authorized User. This type of identity is created when you use the authorization code to access a TikTok account or a TikTok post.</li><li>`TT_USER`: TikTok User. This type of identity is created when you bind your TikTok For Business account with your TikTok Business Account, or when you bind your TikTok For Business account with your regular TikTok account and then upgrade the account to Business Account.</li><li>`BC_AUTH_TT`: TikTok Account User in Business Center. This type of identity is created when you add a TikTok account to your Business Center, and the TikTok account owner approves your request. </li><li>`TTS_TT`: TikTok Account User for TikTok Shop. This type of identity is created when you set an official TikTok account for the TikTok Shop.</li></ul> |
##|identity_authorized_bc_id | string | Returned only when `identity_type` is `BC_AUTH_TT`. <br><br>The ID of the Business Center that a TikTok Account User in Business Center identity is associated with. |
##| identity_authorized_shop_id | string | Returned for some `BC_AUTH_TT` identities. <br><br>The ID of the TikTok Shop that the TikTok Account User in Business Center identity is associated with. |
##| store_id | string | Returned only when `identity_type` is `TTS_TT`. <br><br>The ID of the TikTok Shop that a TikTok Account User for TikTok Shop identity is associated with. |
##| profile_image | string | Temporary profile image URL for the TikTok account that is associated with the identity. <br><br>Validity period: around 48 hours. The expiration time is included in the URL after the `x-expires` parameter, in the format of an Epoch/Unix timestamp in seconds. <br><br>Once the URL expires, you need to call [/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882) to obtain a new URL. |
##| display_name | string | The display name of the TikTok account that is associated with the identity. |
##| user_name | string | The username of the TikTok account that is associated with the identity. |
##| is_running_custom_shop_ads | bool | Whether the identity is being used in enabled Video Shopping Ads, Product Shopping Ads or Live Shopping Ads. <br><br>Supported values: `true`, `false`. |
##| product_gmv_max_available | boolean | Whether the identity is available for use in Product GMV Max Campaigns. <br><br>Supported values: `true`, `false`. |
##| live_gmv_max_available | boolean | Whether the identity is available for use in LIVE GMV Max Campaigns. <br><br>Supported values: `true`, `false`. |
##| unavailable_reason | string | Returned only when `live_gmv_max_available` is `false`.<br><br>The reason why the identity is not available for LIVE GMV Max Campaigns. <br><br>Enum values: <ul><li>`OCCUPIED`: The identity has been occupied by an enabled LIVE GMV Max Campaign.</li><li>`UNAUTHORIZED`: The identity is not authorized for use in LIVE GMV Max Campaigns.</li></ul>|
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
        "display_name": "{{display_name}}",
        "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
        "identity_id": "{{identity_id}}",
        "identity_type": "BC_AUTH_TT",
        "is_running_custom_shop_ads": false,
        "live_gmv_max_available": false,
        "product_gmv_max_available": true,
        "profile_image": "{{profile_image}}",
        "unavailable_reason": "OCCUPIED",
        "user_name": "{{user_name}}"
      },
      {
        "display_name": "{{display_name}}",
        "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
        "identity_id": "{{identity_id}}",
        "identity_type": "BC_AUTH_TT",
        "is_running_custom_shop_ads": false,
        "live_gmv_max_available": false,
        "product_gmv_max_available": true,
        "profile_image": "{{profile_image}}",
        "unavailable_reason": "UNAUTHORIZED",
        "user_name": "{{user_name}}"
      },
      {
        "display_name": "{{display_name}}",
        "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
        "identity_id": "{{identity_id}}",
        "identity_type": "BC_AUTH_TT",
        "is_running_custom_shop_ads": false,
        "live_gmv_max_available": false,
        "product_gmv_max_available": true,
        "profile_image": "{{profile_image}}",
        "unavailable_reason": "OCCUPIED",
        "user_name": "{{user_name}}"
      },
      {
        "display_name": "{{display_name}}",
        "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
        "identity_id": "{{identity_id}}",
        "identity_type": "BC_AUTH_TT",
        "is_running_custom_shop_ads": false,
        "live_gmv_max_available": true,
        "product_gmv_max_available": true,
        "profile_image": "{{profile_image}}",
        "user_name": "{{user_name}}"
      },
      {
        "display_name": "{{display_name}}",
        "identity_authorized_bc_id": "{{identity_authorized_bc_id}}",
        "identity_id": "{{identity_id}}",
        "identity_type": "BC_AUTH_TT",
        "is_running_custom_shop_ads": false,
        "live_gmv_max_available": false,
        "product_gmv_max_available": true,
        "profile_image": "{{profile_image}}",
        "unavailable_reason": "OCCUPIED",
        "user_name": "{{user_name}}"
      },
      {
        "identity_id": "{{identity_id}}",
        "identity_type": "TTS_TT",
        "is_running_custom_shop_ads": false,
        "live_gmv_max_available": false,
        "product_gmv_max_available": false,
        "store_id": "{{store_id}}",
        "unavailable_reason": "UNAUTHORIZED"
      },
      {
        "display_name": "{{display_name}}",
        "identity_id": "{{identity_id}}",
        "identity_type": "TT_USER",
        "is_running_custom_shop_ads": false,
        "live_gmv_max_available": false,
        "product_gmv_max_available": false,
        "profile_image": "{{profile_image}}",
        "unavailable_reason": "UNAUTHORIZED",
        "user_name": "{{user_name}}"
      }
    ]
  }
}
(/code)
```
