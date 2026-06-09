---
title: "Sandbox accounts"
doc_id: 1738855331457026
path: "Get Started / Sandbox accounts"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Sandbox accounts

With TikTok API for Business sandbox accounts, you can easily test your integration without impacting your real TikTok For Business account. 

> <span style="color:DodgerBlue">**Note**</span><br>
> If you have created a sandbox account before November 4th, 2020, you must create a new sandbox account as old advertiser IDs and access tokens are no longer valid.


With TikTok sandbox accounts, developers can:
* Use most of the API endpoints that are currently provided in the production environment of the TikTok API for Business. See <a href="#item-link-Endpoints supported in sandbox accounts">here</a> for endpoints supported in sandbox accounts.
* Pull mock reporting data.
* Create a sandbox account for each developer application created.

> <span style="color:DodgerBlue">**Note**</span><br>
> <ul><li>Sandbox account permissions you have at the application level will be automatically reflected as the permissions on the sandbox account.</li><li>In the sandbox environment, the rate limits for each endpoint are as follows: one QPS (Queries Per Second), 30 QPM (Queries Per Minute), and 1000 QPD (Queries Per Day). For example, if you make requests to five different endpoints, you can send a maximum of 5 requests (one request to each endpoint) within one second.


## Base URL for the sandbox environment

The URL for API endpoints are in the format of `<base_url>`/`<api_version>`/`<endpoint>`/

The base URL for the **Sandbox** environment is `https://sandbox-ads.tiktok.com/open_api`

The base URL for the **Production** environment is `https://business-api.tiktok.com/open_api`

The base URL for the Production environment is used in the description of each endpoint. To make requests to Sandbox endpoints, replace the base URL with the Sandbox base URL.

After you set up your Sandbox account, you can make API calls to the endpoints in the Sandbox environment.

##  Setting up your sandbox account

Setting up your Sandbox Account is easy. As mentioned above, Sandbox Accounts can be created for each Application you have created through the TikTok API for Business. To create a Sandbox account, follow the instructions below.

1. Navigate to the [TikTok API for Business Homepage](https://ads.tiktok.com/marketing_api/homepage) and click **Go to my apps**.
  
<img src='https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b43389515cb85285c6bd4e3232634f60' width='60%'>
  
2. Click your Developer Application you would like to create a Sandbox Account under. 

<img src='https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/09792ca7f0b77ccc86dcba29d772159d' width='60%'>
  
3. Click **Create a Sandbox Ad Account**. 

<img src='https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/23fd5a7c9514787a47f0fb4542ec6432' width='60%'>

4. Specify the details for your sandbox account, including name, ad account country, ad account currency, and ad account timezone. Then click **Create**.

<img src='https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/df8f69e3acc0389daae0e7f9f51e0525' width='40%'>

5. Once created, developers can generate access tokens and see your advertiser ID. 

<img src='https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/13bd1d8386d7bea8fa60429adb9845d8' width='60%'>


# Mock data for synchronous reporting

To make it easier for developers to test our Reporting capabilities, we populated mock data in all sandbox accounts.

The mock data is only for testing and demonstration purposes. The data format is the same as real data, but the data is not based on your actual ad campaign settings. For example, when running an audience report to get country codes for locations that have spend data, the returned locations may be different from the locations where the ads were actually deployed.

Note that the mock data does not support asynchronous reports.

To use the mock data, make a `GET` request to the `https://sandbox-ads.tiktok.com/open_api/v1.3/report/integrated/get/` endpoint.  We recommend that you have at least one campaign, one ad group and one ad in your sandbox account. The mock data may only return default values (for instance `"0"`) for the queried metrics or return empty results (`"list": []`).

### Supported settings

* Time range: 2020/12/08 - 2020/12/19.
* `service_type`: `AUCTION`.
* `report_type`: `BASIC`, `AUDIENCE`.
* `data_level`: `AUCTION_CAMPAIGN`, `AUCTION_ADGROUP`, `AUCTION_AD`.
* `dimensions`: All `dimensions` supported for the `report_type`.
* `metrics`：All `metrics` supported for the `report_type`. Some metrics may return 0 or -.
* `filters`：All `filters` supported for the `report_type`.
* `lifetime`:  `true` is supported.
* `order_field` & `order_type`: Ordering is NOT supported.

### Example
  
Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://sandbox-ads.tiktok.com/open_api/v1.3/report/integrated/get/?advertiser_id={{advertiser_id}}&report_type=BASIC&dimensions=["adgroup_id"]&service_type=AUCTION&data_level=AUCTION_ADGROUP&start_date=2022-12-11&end_date=2023-12-11&metrics=["spend"]&page_size=1' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
Response
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "total_number": 1,
            "page": 1,
            "page_size": 1,
            "total_page": 1
        },
        "list": [
            {
                "metrics": {
                    "spend": "0"
                },
                "dimensions": {
                    "adgroup_id": "{{adgroup_id}}"
                }
            }
        ]
    }
}
(/code)
```
## Endpoints supported in sandbox accounts
The following list summarizes the supported endpoints in sandbox accounts.

><span style="color:DodgerBlue">**Note**</span><br>
The sandbox environment is upgrading, and the long-term plan is to support all endpoints in sandbox accounts.

```xtable
| Endpoints supported in sandbox accounts |
|---|
|/ad/create/|
|/ad/get/ |
| /ad/update/|
| /ad/status/update/|
|/adgroup/create/|
| /adgroup/get/|
| /adgroup/update/|
| /adgroup/status/update/|
| /campaign/create/ |
| /campaign/get/ |
| /campaign/update/ |
| /campaign/status/update/|
| /file/image/ad/upload/ |
| /file/video/ad/upload/ |
| /report/integrated/get/ (GET) |
| /term/check/ |
| /term/confirm/ |
| /term/get/ |
| /user/info/ |
| /identity/create/ |
| /identity/get/ |
| /identity/delete/ |
```


