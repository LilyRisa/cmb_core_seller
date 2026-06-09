---
title: "Permission scope"
doc_id: 1753986142651394
path: "Appendix / Permission scope"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Permission scope

TikTok API for Business permissions consist of three levels: first level, second level, and third level. If an access token grants you a first-level permission, then you can access all the endpoints related to the second-level and third-level permissions under the first-level permission with the access token. You can find out the permission hierarchy by navigating to <strong>My Apps</strong> > App > <strong>App Detail</strong> > <strong>Authorization</strong> > <strong>Scope of permission</strong> on TikTok API For Business [portal](https://ads.tiktok.com/marketing_api/homepage). 

After you have obtained `scope`, which represents the permission scope of an access token, from the response of [/oauth2/access_token/](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162) or [/oauth/token/](https://ads.tiktok.com/marketing_api/docs?id=1739965703387137), you can see the tables below to find out the permission that a scope ID (value of `scope`) corresponds to, and the corresponding endpoint. 

Note that "/" in the <strong>Corresponding endpoint</strong> columns of the tables below means that no API endpoint path is related to the permission at the current level. For instance, a scope ID that corresponds to a second-level permission may have corresponding endpoints at the third level but none at the second-level.

## First-level permission
``` xtable
| Scope ID{25%} | First-level permission name{35%} | Corresponding endpoint{40%} |
|---|---|---|
| 1 | Ad Account Management | / |
| 2 | Ads Management | / |
| 3 | Audience Management | / |
| 4 | Reporting | / |
| 5 | Measurement | / |
| 6 | Creative Management | / |
| 7 | App Management | / |
| 8 | Pixel Management | / |
| 9 | DPA Catalog Management | / |
| 10000 | Reach & Frequency | / |
| 12000000 | Lead Management | / |
| 13000000 | TikTok Creator Marketplace (TCM) | / |
| 14000000 | TikTok Creator | / |
| 15000000 | Ad Comments | / |
| 16000000 | TikTok business plugin | / |
| 17000000 | Automated Rules | / |
| 18000000 |TikTok Accounts| / |
| 19000000 | Onsite Commerce Store | /commerce/store/create/ |
| 19000000 | Onsite Commerce Store | /commerce/store/bind_to_catalog/ |
| 19000000 | Onsite Commerce Store | /commerce/window/update/ |
| 19000000 | Onsite Commerce Store | /commerce/store/get/ |
| 19000000 | Onsite Commerce Store | /commerce/store/product/get/ |
| 19000000 | Onsite Commerce Store | /commerce/store/trusted_create/ |
| 19000000 | Onsite Commerce Store | /store/list/ |
| 7043626160646946818 | Offline Events Management | / |
| 7050363942434013185 | Ad Diagnosis | / |
| 7294180478502830081 | CRM Event Management | / |
```
 ## Second-level permission
``` xtable
| Scope ID{20%} | Parent first-level scope ID {20%}| Second-level permission name{30%} | Corresponding endpoint {30%}|
|---|---|---|---|
| 10 | 1 | Ad Account Information | / |
| 12 | 1 | Terms and Agreements | / |
| 13 | 1 | Pangle Block List Management | / |
| 14 | 1 | Pangle Audience Packages | / |
| 15 | 1 | Business Center | / |
| 20 | 2 | Campaign | / |
| 21 | 2 | Ad Group | / |
| 22 | 2 | Ad | / |
| 23 | 2 | Change Log | / |
| 24 | 2 | Split Test | / |
| 7133464587805982722 | 2 | Search Ads | / |
| 30 | 3 | Read Custom Audiences | /dmp/custom_audience/list/ |
| 30 | 3 | Read Custom Audiences | /dmp/custom_audience/get/ |
| 30 | 3 | Read Custom Audiences | /dmp/saved_audience/list/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/file/upload/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/create/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/update/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/delete/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/rule/create/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/lookalike/create/ |
| 31 | 3 | Create and Update Custom Audiences | /segment/audience/ |
| 31 | 3 | Create and Update Custom Audiences | /segment/mapping/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/apply/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/apply/log/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/custom_audience/lookalike/update/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/saved_audience/delete/ |
| 31 | 3 | Create and Update Custom Audiences | /dmp/saved_audience/create/ |
| 32 | 3 | Share Custom Audiences | /dmp/custom_audience/share/cancel/ |
| 32 | 3 | Share Custom Audiences | /dmp/custom_audience/share/log/ |
| 32 | 3 | Share Custom Audiences | /dmp/custom_audience/share/ |
| 40 | 4 | Ad Account Level | / |
| 41 | 4 | Campaign Level | / |
| 42 | 4 | Ad Group Level | / |
| 43 | 4 | Ad Level | / |
| 44 | 4 | Consolidated Report | /report/integrated/get/ |
| 44 | 4 | Consolidated Report | /report/task/download/ |
| 44 | 4 | Consolidated Report | /report/task/create/ |
| 44 | 4 | Consolidated Report | /report/task/check/ |
| 44 | 4 | Consolidated Report | /report/subscription/subscribe/ |
| 44 | 4 | Consolidated Report | /report/subscription/unsubscribe/ |
| 44 | 4 | Consolidated Report | /report/subscription/update/ |
| 44 | 4 | Consolidated Report | /report/subscription/get/ |
| 44 | 4 | Consolidated Report | /campaign/spc/report/get/ |
| 7043626160646815746 | 4 | Ad Insight Report | /report/ad_benchmark/get/ |
| 7043626160646815746 | 4 | Ad Insight Report | /report/video_performance/get/ |
| 7231011733974859777 | 4 | Creative Fatigue Insight | /creative_fatigue/get/ |
| 50 | 5 | Report App Event | /app/track/ |
| 50 | 5 | Report App Event | /app/batch/ |
| 51 | 5 | Report Pixel Event | /pixel/track/ |
| 51 | 5 | Report Pixel Event | /pixel/batch/ |
| 7224195155857866754 | 5 | Report Conversion Event | /event/track/ |
| 60 | 6 | Image Management | / |
| 61 | 6 | Video Management | / |
| 62 | 6 | Video Template | /video_template/task/create/ |
| 62 | 6 | Video Template | /video_template/status/get/ |
| 63 | 6 | Music management | / |
| 64 | 6 | Creative tool | /creative/status/get/ |
| 64 | 6 | Creative tool | /creative/quick_optimization/create/ |
| 64 | 6 | Creative tool | /creative/smart_video/create/ |
| 64 | 6 | Creative tool | /creative/ads_preview/create/ |
| 64 | 6 | Creative tool | /creative/video_soundtrack/create/ |
| 64 | 6 | Creative tool | /creative/image/edit/ |
| 64 | 6 | Creative tool | /creative/asset/share/ |
| 65 | 6 | Get Creative Report | /creative/report/get/ |
| 66 | 6 | Playable Ads | / |
| 67 | 6 | Creative Material Verification | / |
| 68 | 6 | Instant Page Management | / |
| 69 | 6 | TikTok Posts Management | / |
| 6110000 | 6 | Creative Recommendation | / |
| 7050363942434078721 | 6 | Dynamic Scene | / |
| 7130572512626360322 | 6 | Smart Fix | / |
| 7176833266039685122 | 6 | File Name Check | / |
| 70 | 7 | App Management | / |
| 80 | 8 | Pixel Management | / |
| 90 | 9 | Catalog Management | / |
| 91 | 9 | Product Management | / |
| 92 | 9 | DPA Set Management | / |
| 94 | 9 | Catalog Video Management | / |
| 95 | 9 | Template | / |
| 96 | 9 | Event Source Bind | / |
| 97 | 9 | Feed | / |
| 7224195155857915906 | 9 | Diagnostic | / |
| 100000 | 10000 | Create and Update Ad Groups | /adgroup/rf/create/ |
| 100000 | 10000 | Create and Update Ad Groups | /adgroup/rf/update/ |
| 100000 | 10000 | Create and Update Ad Groups | /adgroup/rf/estimated/info/ |
| 100001 | 10000 | Inventory and Orders | /rf/inventory/estimate/ |
| 100001 | 10000 | Inventory and Orders | /rf/order/cancel/ |
| 100002 | 10000 | Tools | /rf/contract/query/ |
| 100002 | 10000 | Tools | /rf/delivery/timezone/ |
| 12010000 | 12000000 | Leads Retrieval | / |
| 12020000 | 12000000 | Test Leads | / |
| 13010000 | 13000000 | Create and Update TCM Account | /tcm/order/create/v2/ |
| 13020000 | 13000000 | Read TCM Account | /tcm/creator/discover/ |
| 13020000 | 13000000 | Read TCM Account | /tcm/similar/creator/ |
| 13020000 | 13000000 | Read TCM Account | /tcm/report/get/ |
| 13030000 | 13000000 | Get Creators Onboarding Status and Invite Creators to TCM | /tcm/creator/invite/ |
| 13030000 | 13000000 | Get Creators Onboarding Status and Invite Creators to TCM | /tcm/creator/status/get/ |
| 13040000 | 13000000 | Send Spark Ads Invitation to Creators and Get the Status | /tcm/spark_ad/apply/ |
| 13040000 | 13000000 | Send Spark Ads Invitation to Creators and Get the Status | /tcm/spark_ad/status/get/ |
| 13040000 | 13000000 | Send Spark Ads Invitation to Creators and Get the Status | /tcm/tt_video/status/ |
| 13040000 | 13000000 | Send Spark Ads Invitation to Creators and Get the Status | /tcm/tt_video/apply/ |
| 14010000 | 14000000 | Creator User | / |
| 14020000 | 14000000 | Creator Media | / |
| 14030000 | 14000000 | Creator Order | / |
| 15010000 | 15000000 | Ad Comment Management | / |
| 15020000 | 15000000 | Blocked Word Management | / |
| 17010000 | 17000000 | Create and Update Automated Rules | /optimizer/rule/create/ |
| 17010000 | 17000000 | Create and Update Automated Rules | /optimizer/rule/update/ |
| 17010000 | 17000000 | Create and Update Automated Rules | /optimizer/rule/batch_bind/ |
| 17010000 | 17000000 | Create and Update Automated Rules | /optimizer/rule/update/status/ |
| 17020000 | 17000000 | Read Automated Rules and their Execution Results | /optimizer/rule/get/ |
| 17020000 | 17000000 | Read Automated Rules and their Execution Results | /optimizer/rule/list/ |
| 17020000 | 17000000 | Read Automated Rules and their Execution Results | /optimizer/rule/result/get/ |
| 17020000 | 17000000 | Read Automated Rules and their Execution Results | /optimizer/rule/result/list/ |
| 18010000 | 18000000 | Business User | / |
| 18020000 | 18000000 | Business Media | /business/video/list/ |
| 18030000 | 18000000 | Business Comment | / |
| 7044139020472942593 | 18000000 | Business Content | / |
| 7050363942434095105 | 7043626160646946818 | Create/Manage Offline Events | /offline/create/ |
| 7050363942434095105 | 7043626160646946818 | Create/Manage Offline Events | /offline/update/ |
| 7050363942434095105 | 7043626160646946818 | Create/Manage Offline Events | /offline/delete/ |
| 7050363942434127873 | 7043626160646946818 | Report Offline Events One by One | /offline/track/ |
| 7068127621543919617 | 7043626160646946818 | Report Offline Events in Bulk | /offline/batch/ |
| 7224195155857833986 | 7043626160646946818 | Read Offline Events | /offline/get/ |
| 7050363942434029569 | 7050363942434013185 | Get and Adopt Diagnosis Suggestion | /tool/diagnosis/get/ |
| 7294180478502846465 | 7294180478502830081 | Read CRM Event Sets | /crm/list/ |
```
## Third-level permission
``` xtable
| Scope ID  {20%}| Parent second-level scope ID {22%} | Third-level permission name{29%}| Corresponding endpoint {29%}|
|---|---|---|---|
| 100 | 10 | Read Ad Account Information | /advertiser/info/ |
| 100 | 10 | Read Ad Account Information | /oauth2/advertiser/get/ |
| 120 | 12 | Terms and Agreements | /term/get/ |
| 120 | 12 | Terms and Agreements | /term/check/ |
| 120 | 12 | Terms and Agreements | /term/confirm/ |
| 130 | 13 | Pangle Block List Management | /pangle_block_list/get/ |
| 130 | 13 | Pangle Block List Management | /pangle_block_list/update/ |
| 140 | 14 | Pangle Audience Packages | /pangle_audience_package/get/ |
| 150 | 15 | Read Business Center | /bc/get/ |
| 150 | 15 | Read Business Center | /bc/balance/get/ |
| 150 | 15 | Read Business Center | /bc/transaction/get/ |
| 151 | 15 | Create Ad Account | /bc/image/upload/ |
| 151 | 15 | Create Ad Account | /bc/advertiser/create/ |
| 151 | 15 | Create Ad Account | /advertiser/update/ |
| 151 | 15 | Create Ad Account | /bc/advertiser/disable/ |
| 151 | 15 | Create Ad Account | /bc/advertiser/qualification/get/ |
| 152 | 15 | Ad Account Balance and Transaction | /bc/transfer/ |
| 152 | 15 | Ad Account Balance and Transaction | /advertiser/balance/get/ |
| 152 | 15 | Ad Account Balance and Transaction | /advertiser/transaction/get/ |
| 153 | 15 | Business Center Asset | /bc/asset/get/ |
| 153 | 15 | Business Center Asset | /bc/asset/admin/get/ |
| 153 | 15 | Business Center Asset | /bc/asset/assign/ |
| 153 | 15 | Business Center Asset | /bc/asset/unassign/ |
| 153 | 15 | Business Center Asset | /bc/asset/partner/get/ |
| 153 | 15 | Business Center Asset | /bc/asset/member/get/ |
| 153 | 15 | Business Center Asset | /bc/asset/admin/delete/ |
| 153 | 15 | Business Center Asset | /bc/pixel/link/update/ |
| 153 | 15 | Business Center Asset | /bc/pixel/link/get/ |
| 153 | 15 | Business Center Asset | /bc/pixel/transfer/ |
| 153 | 15 | Business Center Asset | /asset/bind/quota/ |
| 155 | 15 | Business Center Partner Management | /bc/partner/asset/get/ |
| 155 | 15 | Business Center Partner Management | /bc/partner/delete/ |
| 155 | 15 | Business Center Partner Management | /bc/partner/add/ |
| 155 | 15 | Business Center Partner Management | /bc/partner/get/ |
| 155 | 15 | Business Center Partner Management | /bc/partner/asset/delete/ |
| 156 | 15 | Business Center Member Management | /bc/member/delete/ |
| 156 | 15 | Business Center Member Management | /bc/member/update/ |
| 156 | 15 | Business Center Member Management | /bc/member/get/ |
| 156 | 15 | Business Center Member Management | /bc/member/invite/ |
| 157 | 15 | Business Center Billing Group Management | /bc/billing_group/update/ |
| 157 | 15 | Business Center Billing Group Management | /bc/billing_group/get/ |
| 157 | 15 | Business Center Billing Group Management | /bc/billing_group/create/ |
| 157 | 15 | Business Center Billing Group Management | /bc/billing_group/advertiser/list/ |
| 7078191955347259394 | 15 | Invoice Management | /bc/invoice/task/create/ |
| 7078191955347259394 | 15 | Invoice Management | /bc/invoice/get/ |
| 7078191955347259394 | 15 | Invoice Management | /bc/invoice/download/ |
| 7078191955347259394 | 15 | Invoice Management | /bc/invoice/task/get/ |
| 7078191955347259394 | 15 | Invoice Management | /bc/invoice/unpaid/get/ |
| 7078191955347259394 | 15 | Invoice Management | /bc/invoice/task/list/ |
| 7153155400966176769 | 15 | Asset Group | /bc/asset_group/create/ |
| 7153155400966176769 | 15 | Asset Group | /bc/asset_group/get/ |
| 7153155400966176769 | 15 | Asset Group | /bc/asset_group/update/ |
| 7153155400966176769 | 15 | Asset Group | /bc/asset_group/list/ |
| 7153155400966176769 | 15 | Asset Group | /bc/asset_group/delete/ |
| 200 | 20 | Read Campaigns | /campaign/get/ |
| 200 | 20 | Read Campaigns | /campaign/quota/get/ |
| 200 | 20 | Read Campaigns | /campaign/quota/info/ |
| 200 | 20 | Read Campaigns | /campaign/spc/get/ |
| 200 | 20 | Read Campaigns | /campaign/copy/task/check/ |
| 201 | 20 | Create and Update Campaigns | /campaign/create/ |
| 201 | 20 | Create and Update Campaigns | /campaign/update/ |
| 201 | 20 | Create and Update Campaigns | /campaign/status/update/ |
| 201 | 20 | Create and Update Campaigns | /campaign/spc/create/ |
| 201 | 20 | Create and Update Campaigns | /campaign/spc/update/ |
| 201 | 20 | Create and Update Campaigns | /campaign/copy/task/create/ |
| 210 | 21 | Read Ad Groups | /adgroup/get/ |
| 210 | 21 | Read Ad Groups | /adgroup/review_info/ |
| 210 | 21 | Read Ad Groups | /adgroup/quota/ |
| 211 | 21 | Create and Update Ad Groups | /adgroup/create/ |
| 211 | 21 | Create and Update Ad Groups | /adgroup/update/ |
| 211 | 21 | Create and Update Ad Groups | /ad/audience_size/estimate/ |
| 211 | 21 | Create and Update Ad Groups | /adgroup/status/update/ |
| 211 | 21 | Create and Update Ad Groups | /adgroup/budget/update/ |
| 220 | 22 | Read Ads | /ad/get/ |
| 220 | 22 | Read Ads | /ad/review_info/ |
| 220 | 22 | Read Ads | /ad/aco/get/ |
| 220 | 22 | Read Ads | /showcase/product/get/ |
| 220 | 22 | Read Ads | /showcase/identity/get/ |
| 220 | 22 | Read Ads | /showcase/region/get/ |
| 221 | 22 | Create and Update Ads | /ad/create/ |
| 221 | 22 | Create and Update Ads | /ad/update/ |
| 221 | 22 | Create and Update Ads | /ad/aco/create/ |
| 221 | 22 | Create and Update Ads | /ad/aco/update/ |
| 221 | 22 | Create and Update Ads | /ad/status/update/ |
| 221 | 22 | Create and Update Ads | /ad/aco/material_status/update/ |
| 230 | 23 | Change Log | /changelog/task/create/ |
| 230 | 23 | Change Log | /changelog/task/download/ |
| 230 | 23 | Change Log | /changelog/task/check/ |
| 240 | 24 | Split Test | /split_test/promote/ |
| 240 | 24 | Split Test | /split_test/result/get/ |
| 240 | 24 | Split Test | /split_test/create/ |
| 240 | 24 | Split Test | /split_test/end/ |
| 240 | 24 | Split Test | /split_test/update/ |
| 7133464587805999106 | 7133464587805982722 | Negative Keywords | /search_ad/negative_keyword/download/ |
| 7133464587805999106 | 7133464587805982722 | Negative Keywords | /search_ad/negative_keyword/add/ |
| 7133464587805999106 | 7133464587805982722 | Negative Keywords | /search_ad/negative_keyword/delete/ |
| 7133464587805999106 | 7133464587805982722 | Negative Keywords | /search_ad/negative_keyword/update/ |
| 7133464587805999106 | 7133464587805982722 | Negative Keywords | /search_ad/negative_keyword/get/ |
| 600 | 60 | Read Image Library | /file/image/ad/search/ |
| 600 | 60 | Read Image Library | /file/image/ad/info/ |
| 601 | 60 | Create and Update Images | /file/image/ad/upload/ |
| 601 | 60 | Create and Update Images | /file/image/ad/update/ |
| 610 | 61 | Read Video Library | /file/video/ad/search/ |
| 610 | 61 | Read Video Library | /file/video/ad/info/ |
| 611 | 61 | Create and Update Videos | /file/video/ad/upload/ |
| 611 | 61 | Create and Update Videos | /file/video/ad/update/ |
| 612 | 61 | Generate Thumbnails for Videos | /file/video/suggestcover/ |
| 630 | 63 | Read music library | /file/music/get/ |
| 631 | 63 | Upload music file | /file/music/upload/ |
| 660 | 66 | Read Playable Ads | /playable/get/ |
| 660 | 66 | Read Playable Ads | /playable/validate/ |
| 661 | 66 | Create/Manage Playable Ads | /playable/upload/ |
| 661 | 66 | Create/Manage Playable Ads | /playable/save/ |
| 661 | 66 | Create/Manage Playable Ads | /playable/delete/ |
| 680 | 68 | Instant Page Management | /page/get/ |
| 690 | 69 | Query TikTok Posts | /tt_video/info/ |
| 691 | 69 | Authorize TikTok Posts | /tt_video/authorize/ |
| 691 | 69 | Authorize TikTok Posts | /tt_video/unbind/ |
| 691 | 69 | Authorize TikTok Posts | /tt_video/list/ |
| 692 | 69 | Create Identity | /identity/create/ |
| 693 | 69 | Query Identity | /identity/get/ |
| 693 | 69 | Query Identity | /identity/info/ |
| 693 | 69 | Query Identity | /identity/video/get/ |
| 693 | 69 | Query Identity | /identity/video/info/ |
| 693 | 69 | Query Identity | /identity/music/authorization/ |
| 693 | 69 | Query Identity | /identity/live/get/ |
| 7176842096345694209 | 69 | Delete Identity | /identity/delete/ |
| 6110100 | 6110000 | CTA Recommendation | /creative/portfolio/create/ |
| 6110100 | 6110000 | CTA Recommendation | /creative/portfolio/get/ |
| 6110100 | 6110000 | CTA Recommendation | /creative/cta/recommend/ |
| 6110100 | 6110000 | CTA Recommendation | /creative/portfolio/list/ |
| 6110200 | 6110000 | Smart Text | /creative/smart_text/generate/ |
| 6110200 | 6110000 | Smart Text | /creative/smart_text/feedback/ |
| 7043626160646864898 | 7050363942434078721 | Core Functions | /dynamic_scene/material/submit/ |
| 7043626160646864898 | 7050363942434078721 | Core Functions | /dynamic_scene/task/create/ |
| 7043626160646864898 | 7050363942434078721 | Core Functions | /dynamic_scene/get/ |
| 7043626160646864898 | 7050363942434078721 | Core Functions | /dynamic_scene/task/get/ |
| 7043626160646864898 | 7050363942434078721 | Core Functions | /dynamic_scene/report/get/ |
| 7132385893100437506 | 7130572512626360322 | Fix Task management | /video/fix/task/create/ |
| 7132385893100437506 | 7130572512626360322 | Fix Task management | /video/fix/task/get/ |
| 700 | 70 | Read Apps | /app/list/ |
| 700 | 70 | Read Apps | /app/info/ |
| 700 | 70 | Read Apps | /app/optimization_event/ |
| 700 | 70 | Read Apps | /app/optimization_event/retargeting/ |
| 701 | 70 | Create and Update Apps | /app/create/ |
| 701 | 80 | Create and Update Apps | /app/update/ |
| 800 | 80 | Read Pixels | /pixel/list/ |
| 801 | 80 | Create and Update Pixels | /pixel/create/ |
| 801 | 80 | Create and Update Pixels | /pixel/update/ |
| 801 | 80 | Create and Update Pixels | /pixel/event/create/ |
| 801 | 80 | Create and Update Pixels | /pixel/event/update/ |
| 801 | 80 | Create and Update Pixels | /pixel/event/delete/ |
| 801 | 80 | Create and Update Pixels | /pixel/instant_page/event/ |
| 802 | 80 | Pixel Level Reporting | /pixel/event/stats/ |
| 900 | 90 | Read Catalogs | /catalog/get/ |
| 900 | 90 | Read Catalogs | /catalog/lexicon/get/ |
| 900 | 90 | Read Catalogs | /catalog/overview/ |
| 900 | 90 | Read Catalogs | /catalog/location_currency/get/ |
| 900 | 90 | Read Catalogs | /catalog/available_country/get/ |
| 901 | 90 | Create and Update Catalog | /catalog/create/ |
| 901 | 90 | Create and Update Catalog | /catalog/update/ |
| 901 | 90 | Create and Update Catalog | /catalog/delete/ |
| 901 | 90 | Create and Update Catalog | /catalog/capitalize/ |
| 910 | 91 | Read Products | /catalog/product/get/ |
| 910 | 91 | Read Products | /catalog/product/log/ |
| 911 | 91 | Create and Update Product | /catalog/product/upload/ |
| 911 | 91 | Create and Update Product | /catalog/product/delete/ |
| 911 | 91 | Create and Update Product | /catalog/product/file/ |
| 911 | 91 | Create and Update Product | /catalog/product/update/ |
| 920 | 92 | Get Catalog Set | /catalog/set/get/ |
| 920 | 92 | Get Catalog Set | /catalog/set/product/get/ |
| 921 | 92 | Post Catalog Set | /catalog/set/create/ |
| 921 | 92 | Post Catalog Set | /catalog/set/update/ |
| 921 | 92 | Post Catalog Set | /catalog/set/delete/ |
| 940 | 94 | Get Catalog Video | /catalog/video_package/get/ |
| 941 | 94 | Post Catalog Video | /catalog/video_package/update/ |
| 941 | 94 | Post Catalog Video | /catalog/video_package/create/ |
| 941 | 94 | Post Catalog Video | /catalog/video_package/delete/ |
| 950 | 95 | Template | /catalog/template/upload/ |
| 950 | 95 | Template | /catalog/template_preview/create/ |
| 960 | 96 | Read Event Source Bind | /catalog/eventsource_bind/get/ |
| 960 | 96 | Read Event Source Bind | /diagnostic/catalog/eventsource/metric/ |
| 960 | 96 | Read Event Source Bind | /diagnostic/catalog/eventsource/issue/ |
| 961 | 96 | Create and Delete Event Source Bind | /catalog/eventsource/bind/ |
| 961 | 96 | Create and Delete Event Source Bind | /catalog/eventsource/unbind/ |
| 970 | 97 | Get Feed | /catalog/feed/get/ |
| 970 | 97 | Get Feed | /catalog/feed/log/ |
| 971 | 97 | Post Feed | /catalog/feed/create/ |
| 971 | 97 | Post Feed | /catalog/feed/update/ |
| 971 | 97 | Post Feed | /catalog/feed/delete/ |
| 971 | 97 | Post Feed | /catalog/feed/switch/ |
| 12010100 | 97 | Leads Retrieval | /page/field/get/ |
| 12010100 | 12010000 | Leads Retrieval | /page/lead/get/ |
| 12020100 | 12020000 | Test Leads | /page/lead/mock/create/ |
| 12020100 | 12020000 | Test Leads | /page/lead/mock/get/ |
| 12020100 | 12020000 | Test Leads | /page/lead/mock/delete/ |
| 12020100 | 12020000 | Test Leads | /page/field/get/ |
| 12020100 | 12020000 | Test Leads | /page/lead/task/download/ |
| 12020100 | 12020000 | Test Leads | /page/lead/task/ |
| 14010100 | 14010000 | Get Creator Basic Info | /|
| 14010200 | 14010000 | Get Creator Insights | /tcm/creator/public/ |
| 14010200 | 14010000 | Get Creator Insights | /tcm/creator/authorized/ |
| 14020100 | 14020000 | Get Creator Media | /tcm/creator/public/video/list/ |
| 14020100 | 14020000 | Get Creator Media | /tcm/creator/authorized/video/list/ |
| 15010100 | 15010000 | Read Comments | /comment/list/ |
| 15010100 | 15010000 | Read Comments | /comment/reference/ |
| 15010100 | 15010000 | Read Comments | /comment/task/create/ |
| 15010100 | 15010000 | Read Comments | /comment/task/check/ |
| 15010100 | 15010000 | Read Comments | /comment/task/download/ |
| 15010200 | 15010000 | Write Comments | /comment/status/update/ |
| 15010200 | 15010000 | Write Comments | /comment/delete/ |
| 15010200 | 15010000 | Write Comments | /comment/post/ |
| 15020100 | 15020000 | Read Blocked Words | /blockedword/list/ |
| 15020100 | 15020000 | Read Blocked Words | /blockedword/task/create/ |
| 15020100 | 15020000 | Read Blocked Words | /blockedword/task/check/ |
| 15020100 | 15020000 | Read Blocked Words | /blockedword/task/download/ |
| 15020100 | 15020000 | Read Blocked Words | /blockedword/check/ |
| 15020200 | 15020000 | Write Blocked Words | /blockedword/create/ |
| 15020200 | 15020000 | Write Blocked Words | /blockedword/update/ |
| 15020200 | 15020000 | Write Blocked Words | /blockedword/delete/ |
| 18010100 | 18010000 | Get Business User Basic Info | /business/get/ |
| 18030100 | 18030000 | Get Business Comment | /business/comment/reply/list/ |
| 18030100 | 18030000 | Get Business Comment | /business/comment/list/ |
| 18030200 | 18030000 | Manage Business Comment | /business/comment/create/ |
| 18030200 | 18030000 | Manage Business Comment | /business/comment/delete/ |
| 18030200 | 18030000 | Manage Business Comment | /business/comment/hide/ |
| 18030200 | 18030000 | Manage Business Comment | /business/comment/reply/create/ |
| 18030200 | 18030000 | Manage Business Comment | /business/comment/like/ |
| 7050363942433996801 | 7044139020472942593 | Video Publish | /business/video/publish/ |
| 7050363942433996801 | 7044139020472942593 | Video Publish | /business/hashtag/suggestion/ |
```
