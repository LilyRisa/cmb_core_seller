---
title: "Unlink a TikTok account from an ad account in Business Center"
doc_id: 1855027260369921
path: "API Reference / BC Assets / Unlink a TikTok account from an ad account in Business Center"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Unlink a TikTok account from an ad account in Business Center

Use this endpoint to unlink a TikTok account from an ad account in a Business Center.

Once the linkage is removed, Business Center members with access to the ad account that is previously linked to the TikTok account can no longer use the TikTok account for ad delivery from the ad account.

## Before you start
- You need to be an Admin user of the Business Center. To confirm your permission to a Business Center, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and confirm whether the returned `user_role` is `ADMIN`.
- Ensure you [have previously linked a TikTok account to an ad account in Business Center](https://business-api.tiktok.com/portal/docs?id=1846868953025538).

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/asset/advertiser/unassign/

**Method** POST

**Header**

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token.<br><br> For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br><br>Allowed value: `application/json`.|
```

**Parameters**

```xtable
|Field{30%}|Type{15%}|Description{55%}||
|---|---|---|
|bc_id {Required}|string|Business Center ID.<br><br>To obtain the list of Business Centers that you can access, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826).|
|asset_id {Required}|string|The ID of a TikTok account in a Business Center.<br><br>To obtain the list of TikTok accounts within a Business Center, use [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `TT_ACCOUNT` or `MANAGED_BUSINESS_ACCOUNT`.|
|asset_type {Required}|string|Asset type.<br><br>Enum values:<ul><li>`TT_ACCOUNT`: TikTok account as an ad delivery asset. You can run ads from these TikTok accounts.</li><li>`MANAGED_BUSINESS_ACCOUNT`: TikTok account for Business Account management. You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul>|
|advertiser_id {Required}|string|The ID of an ad account within the same Business Center to unlink the TikTok account from.<br><br>To obtain the list of ad accounts already linked to a TikTok account within a Business Center, use [/bc/asset/advertiser/assigned/](https://business-api.tiktok.com/portal/docs?id=1855027294743554).|
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/bc/asset/advertiser/unassign/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "bc_id": "{{bc_id}}",
    "asset_type": "MANAGED_BUSINESS_ACCOUNT",
    "asset_id": "{{asset_id}}",
    "advertiser_id": "{{advertiser_id}}"
}'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
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
