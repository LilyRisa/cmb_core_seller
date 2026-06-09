---
title: "Get live videos under an identity"
doc_id: 1785238052792321
path: "API Reference / Identity / Get live videos under an identity"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get live videos under an identity

Use this endpoint to get live videos under an identity. 

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/identity/live/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|Request message type. <br>Allowed value: `application/json`.|
```

**Parameters**

``` xtable
| Field | Data Type | Description |
|---|---|---|
| advertiser_id {Required} | string | Advertiser ID.  |
| identity_id {Required} | string | Identity ID. |
| identity_type {Required} | string | Identity type.  Enum values: `TT_USER`, `BC_AUTH_TT`. See Identities for details. |
| identity_authorized_bc_id {+Conditional} | string | Required when `identity_type` is `BC_AUTH_TT`. ID of the Business Center that a TikTok Account User in Business Center identity is associated with.  |
| cursor | number | Timestamp cursor.  Live videos before the timestamp will be returned in reverse-chronological order.  In the initial request, you can set it to `0`. The response will include a `has_more` flag and a `cursor`. If `has_more` is `true`, use the returned `cursor` in your subsequent request to obtain the following page of data. |
```

### Example

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/live/get/?advertiser_id={{advertiser_id}}&identity_type=TT_USER&identity_id={{identity_id}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
| Field | Data Type | Description |
|---|---|---|
| code | number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
| message | string | Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097). |
| request_id | string | The log ID of a request, which uniquely identifies the request. |
| data | object | Returned data. |
#| cursor | string | Timestamp cursor. The time value of the last item returned according to the current request. You need to use this cursor value in your subsequent request to get the next live videos. |
#| has_more | bool | Whether more data is available.  |
#| live_list | object[] | List of live videos.  |
##| live_id | string | Live video ID. |
##| finish_timestamp | string | Finish timestamp for a live video.  Example: `2023-12-07 08:13:44`. |
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
        "live_list": [
            {
                "live_id": "{{live_id}}",
                "finish_timestamp": "2023-12-07 12:41:27"
            }
        ],
        "cursor": "1686195429",
        "has_more": true
    }
}
(/code)
```


