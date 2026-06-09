---
title: "Get the available TikTok Series within an ad account"
doc_id: 1863332009162754
path: "API Reference / Tools / Get the available TikTok Series within an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the available TikTok Series within an ad account

Use this endpoint to retrieve a list of available TikTok Series from a specified TikTok account (identity) that can be promoted by an ad account.

Series offers exclusive and premium video packages produced by creators. Access to Series is available to purchase through the creator's profile and links attached to TikTok videos.
> <span style="color: DodgerBlue">**Note**</span><br>
> If you would like to create TikTok Series within your TikTok account, please contact your TikTok representative.


## Request
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/identity/native_series/get/


**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162). |
```

**Parameters**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID.|
|identity_id {Required}|string|Identity ID.<br><br>To get the list of identities under your ad account, use [/identity/get/](https://business-api.tiktok.com/portal/docs/get-the-identity-list/v1.3).|
|identity_type {Required}|string|Identity type.<br><br>Enum values: `TT_USER`, `BC_AUTH_TT`. <br>See [Identities](https://business-api.tiktok.com/portal/docs/identities/v1.3) for details.|
|identity_authorized_bc_id {+Conditional}|string|Required when `identity_type` is `BC_AUTH_TT`.<br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with.|
|cursor|integer|The cursor for paginating results.<br><br>If `has_more` in the response is `true`, use the returned cursor in your subsequent request to fetch the next set of data.<br><br>Default value: 0.|
|count|number|The maximum number of Series to return in the response.<br><br>Value range: 1-100.<br>Default value: 10.|
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/native_series/get/?advertiser_id={{advertiser_id}}&identity_type=BC_AUTH_TT&identity_id={{identity_id}}&identity_authorized_bc_id={{identity_authorized_bc_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|-|-|-|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs/return-codes-appendix/v1.3).|
|request_id|string| The log ID of the request, which uniquely identifies a request.|
|data|object|Returned data.|
#|list | object[] | The list of Series that you can access from the identity (`identity_id`). |
##|native_series_id | string | The ID of the TikTok Series. |
##|native_series_name | string | The name of the TikTok Series. |
##|native_series_cover_url | string | The URL to the cover image of the TikTok Series. |
##|native_series_total_episode | number | The total number of episodes in the TikTok Series. |
##|native_series_total_duration | number | The combined duration of all episodes in the TikTok Series, measured in seconds. |
#|cursor | integer | The cursor for paginating results.<br><br>If `has_more` in the response is `true`, use this cursor in your subsequent request to fetch the next set of data. |
#|has_more | boolean | Whether more data is available. |
```
### Example
```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "cursor": {{cursor}},
        "has_more": false,
        "list": [
            {
                "native_series_cover_url": "{{native_series_cover_url}}",
                "native_series_id": "{{native_series_id}}",
                "native_series_name": "{{native_series_name}}",
                "native_series_total_duration": 31,
                "native_series_total_episode": 2
            },
            {
                "native_series_cover_url": "{{native_series_cover_url}}",
                "native_series_id": "{{native_series_id}}",
                "native_series_name": "{{native_series_name}}",
                "native_series_total_duration": 153,
                "native_series_total_episode": 6
            },
            {
                "native_series_cover_url": "{{native_series_cover_url}}",
                "native_series_id": "{{native_series_id}}",
                "native_series_name": "{{native_series_name}}",
                "native_series_total_duration": 84,
                "native_series_total_episode": 1
            },
            {
                "native_series_cover_url": "{{native_series_cover_url}}",
                "native_series_id": "{{native_series_id}}",
                "native_series_name": "{{native_series_name}}",
                "native_series_total_duration": 84,
                "native_series_total_episode": 1
            },
            {
                "native_series_cover_url": "{{native_series_cover_url}}",
                "native_series_id": "{{native_series_id}}",
                "native_series_name": "{{native_series_name}}",
                "native_series_total_duration": 116,
                "native_series_total_episode": 1
            },
            {
                "native_series_cover_url": "{{native_series_cover_url}}",
                "native_series_id": "{{native_series_id}}",
                "native_series_name": "{{native_series_name}}",
                "native_series_total_duration": 147,
                "native_series_total_episode": 2
            },
            {
                "native_series_cover_url": "{{native_series_cover_url}}",
                "native_series_id": "{{native_series_id}}",
                "native_series_name": "{{native_series_name}}",
                "native_series_total_duration": 179,
                "native_series_total_episode": 2
            },
            {
                "native_series_cover_url": "{{native_series_cover_url}}",
                "native_series_id": "{{native_series_id}}",
                "native_series_name": "{{native_series_name}}",
                "native_series_total_duration": 28,
                "native_series_total_episode": 1
            }
        ]
    }
}
(/code)
```


