---
title: "Disable an ad account"
doc_id: 1752349244331009
path: "API Reference / BC Assets / Disable an ad account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Disable an ad account

Use this endpoint to disable an ad account in the Business Center. You need to be an Admin user of the Business Center. 

Please disable an ad account only when you no longer want to use the account, because once disabled, the ad account will be closed and you cannot restore the account to normal status ("Approved" status).

## Before you start
You can disable an ad account when the following conditions are all met:
- You are an Admin user of the Business Center.
- The ad account you want to disable is owned by the Business Center.
- The ad account is not disabled or suspended.
- The ad account has no ads that are being delivered within 3 days.
- The ad account has no unpaid bills.
- The ad account has no unfinished transfers.
- If the Payment Method of the ad account is Automatic Payment, then the ad account should have no unbilled cost.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/disable/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|The content type of the request. <br>Allowed value: `"application/json"` |
```

**Parameters**

``` xtable
|Field{25%}|Data Type{15%}|Description{60%}|
|---|---|---|
|bc_id {Required} | string | Business Center ID. |
| advertiser_ids{Required} | string[] | IDs of the ad accounts in the Business Center that you want to disable. Max size =1. <br><p><span style="color:darkred"><b> Important </b></span>: If an account in the Business Center is disabled, the balance of the ad account will be transferred to the Business Center that the ad account belongs to. |
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST ' https://business-api.tiktok.com/open_api/v1.3/bc/advertiser/disable/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data-raw '{
    "bc_id": "{{bc_id}}",
    "advertiser_ids": ["{{advertiser_id}}"]
}'
(/code)
```


## Response

``` xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#| disabled_advertiser_ids |string[]| IDs of the ad accounts that have been successfully disabled. |
#| failed_infos | map<string, string[]>| Information about the ad accounts that have not been successfully disabled.<br>Key: The ID of the ad account (`advertiser_id`) that has not been successfully disabled. <br>Value: The reason or reasons why the attempt to disable the ad account failed. Enum values: <ul><li> `DELIVERING`: The ad account has ads that are being delivered within 3 days.</li><li>`UNPAID_BILL`: The ad account has unpaid bills.</li><li>`SUSPENDED`:  The ad account is being suspended.</li><li>`UNFINISHED_TRANSFER` : The ad account has unfinished transfers.</li><li>`AUTOPAY_UNBILLED`: The ad account whose Payment Method is Automatic Payment has unbilled cost. </li></ul>|
|request_id|string|The log ID of a request, which uniquely identifies the request. |
```


