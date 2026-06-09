---
title: "Create a Billing Group"
doc_id: 1738947780033537
path: "API Reference / BC Billing Groups / Create a Billing Group"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Billing Group

Use this endpoint to create a Billing Group in a Business Center. 

Both Agency and Direct Business Centers are able to perform billing group related operations.   

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/bc/billing_group/create/| /v1.3/bc/billing_group/create/
|Request parameter data type|`bc_id`: number<br>`advertiser_ids`: number[] |`bc_id` :string<br>`advertiser_ids`: string[]|
|New request parameter| / |`billing_group_type`: string|
|Response parameter data type |`billing_group_id`: number| `billing_group_id`: string|
```



## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/billing_group/create/

**Method** POST

**Header**

``` xtable
|Field{30%}|Data Type{35%}|Description{35%}|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`.|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID |
|billing_group_name {Required}	|string	| Billing group name. |
|advertiser_ids{Required}| string[]| Ad account IDs that you want to include in the billing group. <br><p><span style="color:darkred"><b>Note</b></span>: If you want to create a billing group for Auction ad accounts, you should add all Auction ad accounts to one Billing Group, except the ad accounts registered in France, Brazil, or Mexico. However, if you want to create a billing group of Reservation ad accounts, you should add one Reservation ad account to one Billing Group. 
|billing_group_emails| string[] |Billing group emails|
|is_primary |bool|Whether to set this billing group as the default billing group for the Business Center. |
|billing_group_type| string|The advertising type of all ad accounts in the billing group. This setting can not be edited. <br>Enum values: `AUCTION`, `RESERVATION`(to be deprecated) <br><p><span style="color:darkred"><b>Note</b></span>:  Starting March 31st, 2024, we're making all brand relevant campaign management processes fulfilled directly on TikTok Ads Manager. You'll manage all brand campaigns in one place - no more juggling between auction and reservation ads. This means our Business Center API won't support reservation account or related billing group creation and management anymore.|
```

### Example

```xcodeblock
(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "billing_group_name": "BILLING_GROUP_NAME",
    "advertiser_ids": [
        "ADVERTISER_IDS"
    ],
    "billing_group_emails": [
        "BILLING_GROUP_EMAILS"
    ],
    "is_primary": "IS_PRIMARY"
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/billing_group/create/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|billing_group_id|string|ID of the billing group that was just created. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code)
```


