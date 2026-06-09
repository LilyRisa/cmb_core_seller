---
title: "Get info about an identity"
doc_id: 1740218453385217
path: "API Reference / Identity / Get info about an identity"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get info about an identity

Use this endpoint to retrieve details about an identity.

## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/identity/info/| /v1.3/identity/info/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
|Response parameter name|`profile_image` | `profile_image_url`|
|New response parameters| / | `can_manage_message`<br>`is_gpppa`<br>`ads_only_mode`<br>`username`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/identity/info/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID. |
| identity_id {Required} | string | Identity ID. |
| identity_type{Required}  | string | Identity type. Enum values: `CUSTOMIZED_USER`, `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) for details. |
| identity_authorized_bc_id {+Conditional}| string |ID of the Business Center that a TikTok Account User in Business Center identity is associated with. Required when `identity_type` is `BC_AUTH_TT`. |
```

### Example

```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/info/?advertiser_id={{advertiser_id}}&identity_type=TT_USER&identity_id={{identity_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#| identity_info | object | Identity information. |
##| identity_id | string | Identity ID. |
##| identity_type | string | Identity type. Enum values: `CUSTOMIZED_USER`, `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097)  for details. |
##| identity_authorized_bc_id | string | ID of the Business Center that a TikTok Account User in Business Center identity is associated with.|
##|ads_only_mode|boolean|Returned only when `identity_type` is `BC_AUTH_TT`.<br><br>Whether the "Show through ads only" mode is enabled for the identity.<br><br>Supported values: `true`, `false`.<br><br>When this field is `true`, you cannot set `dark_post_status` to `OFF` while creating ads (through [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522), [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354), or [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362)) or updating ads (through [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921), [/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018), or [/campaign/spc/update/](https://business-api.tiktok.com/portal/docs?id=1767334250066945)) to allow your post to appear on your TikTok profile and be eligible to receive organic traffic.</p>|
##| username | string | The username (handle name) of the identity. |
##|is_gpppa|boolean|Whether the TikTok account is a [Government, Politician, and Political Party Account](https://support.tiktok.com/en/using-tiktok/growing-your-audience/government-politician-and-political-party-accounts) (GPPPA).<br><br>Supported values: `true`, `false`.<br><br>When this field is `true`, the TikTok account cannot be used to [create Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298).|
##| can_push_video | boolean | Whether the `BC_AUTH_TT` or `TT_USER` identity can create or edit videos. <br><br> Supported values: `true`, `false`.|
##| can_pull_video | boolean | Whether the `BC_AUTH_TT` or `TT_USER` identity can get all videos under the TikTok account. <br><br> Supported values: `true`, `false`.|
##| can_use_live_ads | boolean | Whether the `BC_AUTH_TT`or `TT_USER` identity can access the live room. <br><br> Supported values: `true`, `false`.|
##| can_manage_message | boolean | Whether the `BC_AUTH_TT` or `TT_USER` identity can manage direct messages.<br><br> Supported values: `true`, `false`.|
##| display_name | string | Display name. |
##| profile_image_url | string | Profile image URL. |
##| available_status | string | Availability of the identity. Only valid for `TT_USER` and `BC_AUTH_TT` type of identities. Enum values: `AVAILABLE`, `NO_VALID_BIND_ACCOUNT`, `SCOPE_UNAVAILABLE`, `IS_PRIVATE_ACCOUNT`, `NOT_BUSINESS_ACCOUNT`. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
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
        "identity_info": {
			"available_status": "AVAILABLE",
            "can_manage_message": true,
            "can_pull_video": true,
            "can_push_video": true,
            "can_use_live_ads": true,
            "display_name": "{{display_name}}",
            "identity_id": "{{identity_id}}",
            "identity_type": "TT_USER",
            "profile_image_url": "{{profile_image_url}}",
            "is_gpppa": false,
            "username": "{{username}}"
        }
    }
}
(/code)
```


