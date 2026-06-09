---
title: "SDK"
doc_id: 1764231376750658
path: "SDK"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# SDK

The API for Business SDK is the ultimate solution for developers looking to streamline the integration process and enhance productivity. Our SDK, launching in phases, includes APIs for Authentication, Campaign Creation, Reporting, Business Center and more, enabling developers to integrate with our API for Business products faster and in a more standardized way. 

**Visit [the Github repository page for the API for Business SDK](https://github.com/tiktok/tiktok-business-api-sdk) for source code and installation guides in [README.md](https://github.com/tiktok/tiktok-business-api-sdk/blob/main/README.md).**


# What is API for Business SDK 

API for Business SDK provides a comprehensive collection of client libraries that enable our developers to build software to integrate with Business API faster and in a more standardized way.

# Why API for Business SDK
- **Faster and smoother integration**: The API for Business SDK helps you streamline your integration process and thus reduce your integration time.
- **Shorter development cycle**: The API for Business SDK is designed to inform, equip, and provide shortcuts to development, allowing developers to focus on the product they have planned to create. 
- **Easier code maintenance**:  The API for Business SDK makes it easy for you to maintain code by providing pre-built components, functions and libraries that can be reused across different projects. This saves time and effort in developing and maintaining software applications while ensuring consistency and quality in the codebase.

# What is the estimated SDK integration time? 

Based on current integration developer data and client interview, we have found that the SDK integration time has been reduced by about 40% when compared to the integration time of common API endpoints in the **Reporting** use case.
> <span style="color: DodgerBlue">**Note**</span><br>The results are not absolute and only for reference. The integration time has to be considered on a case by case basis because the time needed is subject to many factors like developers' relevant product knowledge and previous integration experience. Once we gather more information on SDK integration time across more use cases, we will update this document accordingly.

``` xtable
| Use case{40%}|SDK integration time{30%}|Common API integration time {30%} |
|---|---|---|
| <b>Reporting<b>    |              32 hours            |             45 hours            |

```

# What programming interfaces are supported?
We currently support the following languages: Python, JavaScript , and Java.

# What API endpoints are supported?
We currently support the following endpoints in the SDK:

- Authentication
 <br> /v1.3/oauth2/access_token/ 
 <br>/v1.3/oauth2/advertiser/get/ 
 
- Campaign Management
<br> /v1.3/campaign/create/
<br> /v1.3/campaign/get/
<br> /v1.3/campaign/update/
<br> /v1.3/campaign/status/update/

- Ad group Management
<br>/v1.3/adgroup/create/
<br>/v1.3/adgroup/get/
<br>/v1.3/adgroup/update/
<br>/v1.3/adgroup/status/update/
<br>/v1.3/adgroup/quota/

- Ad Management
<br>/v1.3/ad/create/
<br>/v1.3/ad/get/
<br>/v1.3/ad/update/
<br>/v1.3/ad/status/update/

- ACO
<br>/v1.3/ad/aco/create/
<br>/v1.3/ad/aco/get/
<br>/v1.3/ad/aco/update/
<br>/v1.3/ad/aco/material_status/update/

- Reporting 
<br>/v1.3/report/integrated/get/

- Business Center 
<br>/v1.3/bc/get/
<br>/v1.3/bc/image/upload/
<br>/v1.3/bc/advertiser/create/
<br>/v1.3/advertiser/update/
<br>/v1.3/bc/asset/get/

- Business Center Payment
<br>/v1.3/bc/transfer/
<br>/v1.3/advertiser/balance/get/
<br>/v1.3/advertiser/transaction/get/
<br>/v1.3/bc/balance/get/
<br>/v1.3/bc/transaction/get/

- Creative Management
<br>/v1.3/creative/portfolio/create/

- File
<br>/v1.3/file/video/ad/upload/
<br>/v1.3/file/video/ad/info/
<br>/v1.3/file/image/ad/upload/
<br>/v1.3/file/image/ad/info/
<br>/v1.3/file/video/ad/search/

- Identity 
<br>/v1.3/identity/create/ 
<br>/v1.3/identity/get/
<br>/v1.3/identity/video/get/

- Tool
<br>/v1.3/tool/targeting/search/
<br>/v1.3/tool/targeting/info/
<br>/v1.3/tool/region/
<br>/v1.3/search/region/
<br>/v1.3/tool/interest_category/
<br>/v1.3/tool/interest_keyword/recommend/
<br>/v1.3/tool/interest_keyword/get/
<br>/v1.3/tool/action_category/
<br>/v1.3/tool/hashtag/recommend/
<br>/v1.3/tool/hashtag/get/
<br>/v1.3/tool/targeting_category/recommend/
<br>/v1.3/tool/os_version/
<br>/v1.3/tool/language/
<br>/v1.3/tool/contextual_tag/get/
<br>/v1.3/tool/contextual_tag/info/
<br>/v1.3/tool/device_model/
<br>/v1.3/tool/carrier/
<br>/v1.3/tool/targeting/list/
<br>/v1.3/tool/bid/recommend/
<br>/v1.3/tool/vbo_status/
<br>/v1.3/tool/brand_safety/partner/authorize/status/
<br>/v1.3/tool/url_validate/
<br>/v1.3/tool/phone_region_code/
<br>/v1.3/tool/timezone/
<br>/v1.3/tool/open_url/


- Audience
<br>/v1.3/dmp/custom_audience/file/upload/
<br>/v1.3/dmp/custom_audience/create/
<br>/v1.3/dmp/custom_audience/update/
<br>/v1.3/dmp/custom_audience/list/
<br>/v1.3/dmp/custom_audience/get/
<br>/v1.3/dmp/custom_audience/rule/create/
<br>/v1.3/dmp/custom_audience/lookalike/create/
<br>/v1.3/dmp/custom_audience/lookalike/update/
<br>/v1.3/dmp/custom_audience/delete/
<br>/v1.3/dmp/custom_audience/share/
<br>/v1.3/dmp/custom_audience/share/cancel/
<br>/v1.3/dmp/custom_audience/share/log/
<br>/v1.3/dmp/custom_audience/apply/
<br>/v1.3/dmp/custom_audience/apply/log/
<br>/v1.3/dmp/saved_audience/create/
<br>/v1.3/dmp/saved_audience/list/
<br>/v1.3/dmp/saved_audience/delete/


- App
<br>/v1.3/app/list/ 
<br>/v1.3/app/optimization_event/

- Events 
<br>/v1.3/pixel/track/
<br>/v1.3/pixel/batch/


