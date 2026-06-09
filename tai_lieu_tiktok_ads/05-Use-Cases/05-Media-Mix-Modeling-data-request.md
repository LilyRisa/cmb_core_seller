---
title: "Media Mix Modeling data request"
doc_id: 1825298892472513
path: "Use Cases / Media Mix Modeling data request"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Media Mix Modeling data request

This article walks you through the steps to access Media Mix Modeling data through API.

# Introduction
Media Mix Modeling (MMM) is a measurement technique that leverages time-series modeling to understand the business impacts (prices) of all traditional and digital marketing touchpoints. By accounting for operations such as pricing, external factors, and economic factors, MMM can quantify ROI across an advertiser’s media channels and provide insight to future media spend planning.

**You can use MMM API to access MMM data and this helps you streamline your MMM data request experience, and elevate operational efficiency and scalability.**

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://ads.tiktok.com/marketing_api/docs?id=1735713609895937) for details. Ensure that you complete [Marketing API authorization](https://business-api.tiktok.com/portal/docs?id=1738373141733378) and [authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).
  - To create Traffic ads, you need relevant permissions. See [API Reference](https://business-api.tiktok.com/portal/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://business-api.tiktok.com/portal/docs?id=1738855280338946) to find out how to configure permissions.  <br>Ensure that your developer app has the permission scope **MMM Data Report** under **Reporting**.
- Accessing MMM data through API requires setting up ad accounts in Business Center first.
  1. Add the ad accounts to the Business Center. You can either create ad accounts in a Business Center using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) or [request access to ad accounts in Business Center](https://ads.tiktok.com/help/article/request-access-to-ad-accounts-in-business-center).
  2. Ensure that you have access to the ad accounts that you want to use to access MMM data for. To do this, call [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) and set `asset_type` to `ADVERTISER`.
<br>If the returned `advertiser_role` for the ad account is not `ADMIN`, `OPERATOR`, or `ANALYST`, you don't have access to these ad accounts. Ask one admin for the Business Center to share the ad accounts with you and grant access using [/bc/asset/assign/](https://ads.tiktok.com/marketing_api/docs?id=1739438211077121). The Business Center admin needs to specify `asset_type` as `ADVERTISER` and `advertiser_role` as `ADMIN`, `OPERATOR`, or `ANALYST`.

# Steps
1. **Create an MMM data request** using [/mmm/api/create/](https://business-api.tiktok.com/portal/docs/create-an-mmm-data-request/v1.3).

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/mmm/api/create/' \
--header 'Access-Token: {{Access-Token}}'
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_ids": [
        "{{advertiser_id}}"
    ],
    "request_info": {
        "targets": [
            {
                "advertiser_id": "{{advertiser_id}}"
            }
        ],
        "from_date": "2024-01-01",
        "to_date": "2024-01-03",
        "country_code": [
            "JP","US","CA"
        ],
        "media_type": "All"
    },
    "email": "{{email}}"
}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "mmm_request_id": "{{mmm_request_id}}"
    }
}
(/code)
```
2. **Check the data request status** using [/mmm/api/check/](https://business-api.tiktok.com/portal/docs/check-the-status-of-an-mmm-data-request/v1.3).

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/mmm/api/check/?mmm_request_id={{mmm_request_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "status": "completed - ready for download"
    }
}
(/code)
```

3. Once you confirm the `status` of the data request is `completed - ready for download`, **obtain the data download URL** using [/mmm/api/download/](https://business-api.tiktok.com/portal/docs/obtain-the-download-url-for-mmm-data/v1.3).

To learn about the available MMM data fields, see [MMM templated data schema](https://bytedance.sg.larkoffice.com/sheets/YDwDsKe5whGQWdtaZVrlD9BHgIc).

**Example**

Request
```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/mmm/api/download/?mmm_request_id={{mmm_request_id}}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "download_url": "{{download_url}}"
    }
}
(/code)
```

