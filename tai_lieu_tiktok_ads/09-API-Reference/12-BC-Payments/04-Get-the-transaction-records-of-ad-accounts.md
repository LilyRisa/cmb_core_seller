---
title: "Get the transaction records of ad accounts"
doc_id: 1739939116353538
path: "API Reference / BC Payments / Get the transaction records of ad accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the transaction records of ad accounts

Use this endpoint to get the transaction records of ad accounts in the Business Center. This endpoint only returns the transaction records of ad accounts that the Business Center has administrator rights over.

Both Agency and Direct Business Centers are able to perform payment-related operations. 

> <span style="color: dodgerblue">**Note**</span><br>Only users with Finance Manager or Finance Analyst permission can manage payments and invoices in a Business Center. You can use [/bc/member/invite/](https://ads.tiktok.com/marketing_api/docs?id=1739939455765505) and [/bc/member/update/](https://ads.tiktok.com/marketing_api/docs?id=1739696704424961) to configure or update access settings for members.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/advertiser/transaction/get/ | /v1.3/advertiser/transaction/get/|
|Request parameter name|`trans_type` |`transfer_type` |
|Request parameter data type |`bc_id`: number |`bc_id`: string|
|Request parameter name and data type|`is_group`: number|`summary_by_account`: boolean |
|Request parameter hierarchy |`start_date` <br> `end_date` |`start_date` (in `filtering`) <br>`end_date` (in `filtering`)  |
|Request parameter name|`list` <br>`trans_type`|`transaction_list`<br>`transfer_type`|
|Response parameter data type |`advertiser_id`: number|`advertiser_id`: string |
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/advertiser/transaction/get/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required}|string| Business Center ID. <br><p><span style="color:darkred"><b>Note</b></span>: You can only get the transaction records of ad accounts under a Business Center whose `type` is not `NORMAL`. You can use [/bc/get/](https://ads.tiktok.com/marketing_api/docs?id=1737115687501826) to get the Business Center type via `type` in the response. |
|filtering |object| Filtering conditions |
#|transfer_type |string| Billing type. Enum values: `TRANS_TYPE_TRANSFER`(transfer), `TRANS_TYPE_TAX`(tax), `TRANS_TYPE_COST`(consumption), `TRANS_TYPE_PAYMENT` (payment). Default value:  `TRANS_TYPE_TRANSFER`. <br><p><span style="color:darkred"><b>Note</b></span>:  `funds_type` returned in response will be `null` when you set `transfer_type` as `TRANS_TYPE_TAX` or `TRANS_TYPE_COST`, because these billing types don't involve funds type settings.|
#|funds_type |string[]| Fund type. Allowed types: `FUNDS_TYPE_CASH`(cash), `FUNDS_TYPE_GRANT`(coupon/voucher). The default is to select both types. |
#|summary_by_account |boolean| Whether to summarize by account. `false` means no summary; `true` means summary. Default value: `false`. |
#|keyword |string| Search keywords, you can search for ad account name or ad account ID. |
#|start_date |string|  Transaction record search start time, (UTC+0) format：`2020-10-12`. The default date is 90 days ago.|
#|end_date |string| Transaction record search end time, (UTC+0) format：`2020-11-12`. The default date is the same day. <br><p><span style="color:darkred"><b>Note</b></span>: The largest time gap allowed is one year, so please specify a date within one year after the `start_date` as `end_date`.|
|page |number|Current number of pages. Default value: `1`. Value range : ≥ 1.|
|page_size |number|Page size. Default value: `10`. Value range: 1-50.|
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "bc_id=BC_ID" \
--data-urlencode "end_date=END_DATE" \
--data-urlencode "filtering={\"funds_type\": [\"FUNDS_TYPE\"], \"summary_by_account\": \"SUMMARY_BY_ACCOUNT\", \"keyword\": \"KEYWORD\", \"transfer_type\": \"TRANSFER_TYPE\"}" \
--data-urlencode "page=PAGE" \
--data-urlencode "page_size=PAGE_SIZE" \
--data-urlencode "start_date=START_DATE" \
https://business-api.tiktok.com/open_api/v1.3/advertiser/transaction/get/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Return data.|
#|transaction_list |object[]| Ad Account transaction record. |
##|advertiser_id |string| Ad Account ID. |
##|advertiser_name |string| Ad Account Name. |
##|amount |number| Transaction amount, kept to two decimal places. |
##|currency |string| Transaction currency, the value range is shown in [Appendix-Currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433). |
##|date |string| Trading time, (UTC+0) format：`2020-10-12 00:00:00`. |
##|funds_type|string| Type of funding. Enum values：`FUNDS_TYPE_CASH`(cash), `FUNDS_TYPE_GRANT`(coupon/voucher) .|
##|transfer_type |string| Billing type. Enum values: `TRANS_TYPE_TRANSFER`(transfer), `TRANS_TYPE_TAX`(consumption), `TRANS_TYPE_COST`(tax).|
##|timezone|string| Trading time zone. For enum values, see [Appendix-Time Zone](https://ads.tiktok.com/marketing_api/docs?id=1737586324313089). |
#|page_info|object| Pagination information. |
##|page |number| Current page number. |
##|page_size |number| Page size. |
##|total_number |number| Total number of results.|
##|total_page |number| Total number of pages. |
|request_id |string|The log id of the request, which uniquely identifies a request. |
```
### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```
