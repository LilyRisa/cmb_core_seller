---
title: "Get Billing Groups"
doc_id: 1738944324279297
path: "API Reference / BC Billing Groups / Get Billing Groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get Billing Groups

Use this endpoint to get all Billing Groups in a Business Center. 

Both Agency and Direct Business Centers are able to perform billing group related operations.   

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/billing_group/get/| /v1.3/bc/billing_group/get/|
|Request parameter name |`recon_object` <br> `advertiser_type`|`invoice_group_by` <br>`billing_group_type`|
|Request parameter data type|`bc_id`: number<br>`billing_group_id`: number|`bc_id`: string <br>`billing_group_id`: string|
|New request parameters | / | `status` |
|Response parameter name|`recon_object` <br> `advertiser_type`<br>` advertiser_list`|`invoice_group_by` <br> `billing_group_type`<br> `advertisers`|
|Response parameter data type|`advertiser_id`: number<br>`billing_group_id`: number|` advertiser_id`: string <br>`billing_group_id`: string|
|New response parameters | / | `status` |
```

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/bc/billing_group/get/

**Method** GET

**Header**

``` xtable
|Field{30%}|Data Type{15%}|Description{50%}|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID |
|filtering	|object|	Filtering conditions|
#|status|string|The Billing Group status that you want to filter by. Enum values: `VALID` (valid), `INVALID`(invalid). |
#|invoice_group_by |string|How the invoices in the billing groups are handled. <br>Enum values: `ACCOUNT`: Invoices in this billing groups are combined. <br>`ADVERTISER`: Invoices in this billing group are handled separately. |
#|billed_to_type|string	|Billed-to parties shown on invoices. <br>Enum values: `ACCOUNT`: Invoices are billed to agencies. <br>`ADVERTISER`: Invoices are billed to advertisers. |
#|billing_group_id |string	|Billing Group ID|
#|billing_group_name|string	| Billing group name. |
#|billing_group_type |string|The advertising type of all ad accounts in the billing group. This setting cannot be edited. <br>Enum values: `AUCTION`, `RESERVATION`. |
|page|	number|	Page number. Default value: 1|
|page_size|	number|	Page size, in the range of 1-50. The default value is 10. |
```

### Example

```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "filtering={\"invoice_group_by\": \"INVOICE_GROUP_BY\", \"billed_to_type\": \"BILLED_TO_TYPE\", \"billing_group_id\": \"BILLING_GROUP_ID\", \"billing_group_name\": \"BILLING_GROUP_NAME\", \"billing_group_type\": \"BILLING_GROUP_TYPE\"}" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
https://business-api.tiktok.com/open_api/v1.3/bc/billing_group/get/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
#|list|object[]|List of billing groups.|
##|status|string|The Billing Group status. Enum values: `VALID` (valid), `INVALID`(invalid). |
##|billing_group_id|string|Billing group ID|
##|billing_group_name|string|Billing group name|
##|invoice_object_name|string|Invoice object name|
##|is_primary|bool|Whether it is the default billing group of the Business Center. |
##|advertisers|object[]|List of advertiser accounts in this billing group|
###|advertiser_id|string|Advertiser ID |
###|advertiser_name |string |Advertiser name |
##|billing_group_emails|string[]|Emails of the billing group |
##|invoice_group_by|string|How the invoices in the billing groups are handled. <br>Enum values: `ACCOUNT`: Invoices in this billing groups are combined. <br>`ADVERTISER`: Invoices in this billing group are handled separately. |
##|billed_to_type|string|Billed-to parties shown on invoices. <br>Enum values: `ACCOUNT`: Invoices are billed to agencies. `ADVERTISER`: Invoices are billed to advertisers. |
##|billing_group_type|string|The advertising type of all ad accounts in the billing group. This setting cannot be edited. Enum values: `AUCTION`, `RESERVATION`. |
#|page_info|object|Pagination information |
##|page|number|Current page number. |
##|page_size|number|Page Size |
##|total_number|number|Total number of results|
##|total_page|number|Total pages of results |
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


