---
title: "API Reference"
doc_id: 1735713875563521
path: "API Reference"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# API Reference

## Request URL format
Request URLs are in the format of `<base_url>`/`<api_version>`/`<endpoint>`.
- base_url: https://business-api.tiktok.com/open_api
- api_version: The current version is "v1.3".
- endpoint: Endpoints that are shown in the table below and referred to in our API docs.

## List of available endpoints

```xtable
|Category{20%}|Endpoint{25%}|Description{30%}|Scope of permission{25%}|
|--|--|--|--|
|Accounts|-|-|-|
#|-|[/tt_user/token_info/get/](https://business-api.tiktok.com/portal/docs?id=1765927978092545)|Get the authorized TikTok account permission scopes via access token. |No permission needed.|
#|-|[/business/get/](https://business-api.tiktok.com/portal/docs?id=1762228399168514)|Get profile data of a TikTok account. |TikTok Accounts > Business User > Get Business User Basic info|
#|-|[/business/video/list/](https://business-api.tiktok.com/portal/docs?id=1762228421622786)|Get post data of a TikTok account.|TikTok Accounts > Business Media|
#|-|[/business/benchmark/](https://business-api.tiktok.com/portal/docs?id=1822388944791617)|Get benchmarks for a business category.|TikTok Accounts > Business Benchmark|
#|-|[/business/video/settings/](https://business-api.tiktok.com/portal/docs?id=1816387951979521)|Get the post privacy settings of a TikTok account.|TikTok Accounts > Business Content > Video Publish|
#|-|[/business/comment/list/](https://business-api.tiktok.com/portal/docs?id=1760232109619202)|Access all the comments (along with related information) - both public and hidden - that have been created against a specific organic video posted by an owned TikTok account.|TikTok Accounts > Business Comment > Get Business Comment|
#|-|[/business/comment/reply/list/](https://business-api.tiktok.com/portal/docs?id=1762228430145538)|Access all replies to a specific comment (along with related information) - both public and hidden - that have been created against a comment on an organic video posted by an owned TikTok account.|TikTok Accounts > Business Comment > Get Business Comment|
#|-|[/business/comment/create/](https://business-api.tiktok.com/portal/docs?id=1762228442072066)|Create a new comment on an organic video posted by an owned TikTok account.|TikTok Accounts > Manage Business Comment|
#|-|[/business/comment/image/upload/](https://business-api.tiktok.com/portal/docs?id=1856212334897154)| Upload an image for a new comment or for a reply to an existing comment. |TikTok Accounts > Manage Business Comment|
#|-|[/business/comment/reply/create/](https://business-api.tiktok.com/portal/docs?id=1762228448779266)|Create a reply to an existing comment on an organic video posted by an owned TikTok account.|TikTok Accounts > Manage Business Comment|
#|-|[/business/comment/like/](https://business-api.tiktok.com/portal/docs?id=1762228456608770)|Like/unlike an existing comment on an organic video posted by an owned TikTok account.|TikTok Accounts > Manage Business Comment|
#|-|[/business/comment/hide/](https://business-api.tiktok.com/portal/docs?id=1762228474532865)|Hide/unhide an existing comment on an organic video posted by an owned TikTok account.|TikTok Accounts > Manage Business Comment|
#|-|[/business/comment/delete/](https://business-api.tiktok.com/portal/docs?id=1762228483080193)|Delete an owned comment on an organic video posted by an owned TikTok account.|TikTok Accounts > Manage Business Comment|
#|-|[/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234)|Publish a public video to an owned TikTok account, specifying the video file to be published, the video caption, and the enabled forms of user engagement (comments/stitches/duets).|TikTok Accounts > Business Content > Video Publish|
#|-|[/business/photo/publish/](https://business-api.tiktok.com/portal/docs?id=1803630424390658)|Publish a photo post to an owned account.|TikTok Accounts > Business Content > Photo Publish|
#|-|[/business/publish/status/](https://business-api.tiktok.com/portal/docs?id=1816387106635778)|Get the publishing status of a TikTok post.|TikTok Accounts > Business Content > Video Publish|
#|-|[/business/hashtag/suggestion/](https://business-api.tiktok.com/portal/docs?id=1762228503821314)|Get recommended hashtags for TikTok accounts.|TikTok Accounts > Business Content > Video Publish |
#|-|[/business/post/authorize/setting/](https://business-api.tiktok.com/portal/docs?id=1815051741912066)|Enable or disable the ad authorization setting for a TikTok post.|TikTok Accounts > Auth Code Management|
#|-|[/business/post/authorize/](https://business-api.tiktok.com/portal/docs?id=1815051784582210)|Extend the authorization validity period of a TikTok post.|TikTok Accounts > Auth Code Management|
#|-|[/business/post/authorize/status/](https://business-api.tiktok.com/portal/docs?id=1815051801257986)|Get the authorization status of a TikTok post.|TikTok Accounts > Auth Code Management|
#|-|[/business/post/authorize/delete/](https://business-api.tiktok.com/portal/docs?id=1815051817073665)|Delete the authorization code of a TikTok post.|TikTok Accounts > Auth Code Management|
#|-|[/business/property/add/](https://business-api.tiktok.com/portal/docs?id=1769325280876545)|Add a URL property to an ad account.|No permission needed|
#|-|[/business/property/verify/](https://business-api.tiktok.com/portal/docs?id=1769325308162050)|Check the URL property verification result.|No permission needed|
#|-|[/business/property/delete/](https://business-api.tiktok.com/portal/docs?id=1769325351919617)|Delete the verified ownership of a URL property.|No permission needed|
#|-|[/business/property/list/](https://business-api.tiktok.com/portal/docs?id=1769325368603650)|Get the list of added URL properties under an ad account.|No permission needed |
#|-|[/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1810521279479810)|Create or update a TikTok account Webhook configuration.|No permission needed|
#|-|[/business/webhook/list/](https://business-api.tiktok.com/portal/docs?id=1810521289122818)|Get TikTok account Webhook configurations.|No permission needed|
#|-|[/business/webhook/delete/](https://business-api.tiktok.com/portal/docs?id=1810521297052673)|Delete a TikTok account Webhook configuration.|No permission needed |
|Ads|-|-|-|
#|-|[/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770)|Get all ads or use filters to get certain ads. |Ads Management > Ad > Read Ads|
#|-|[/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354)|Create an ad. |Ads Management > Ad > Create and Update Ads|
#|-|[/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018)|Update an ad. |Ads Management > Ad > Create and Update Ads|
#|-|[/ad/status/update/](https://business-api.tiktok.com/portal/docs?id=1739953422970882)|Update ad status. |Ads Management > Ad > Create and Update Ads|
|Ad Account|-|-|-|
#|-|[/oauth2/advertiser/get/](https://business-api.tiktok.com/portal/docs?id=1738455508553729)|Get a list of advertisers that have granted you the permission to manage their accounts on their behalf. |Ad Account Management > Ad Account Information > Read Ad Account Information|
#|-|[/advertiser/info/](https://business-api.tiktok.com/portal/docs?id=1739593083610113)|Get advertiser information such as account name, email and contacts.|Ad Account Management > Ad Account Information > Read Ad Account Information|
|Ad Comments|-|-|-|
#|-|[/comment/list/](https://business-api.tiktok.com/portal/docs?id=1738086301876225)|Get comments for your ads.| Ad Comments > Ad Comment Management > Read Comments|
#|-|[/comment/reference/](https://business-api.tiktok.com/portal/docs?id=1738086824859650)|Get related comments.|Ad Comments > Ad Comment Management > Read Comments|
#|-|[/comment/status/update/](https://business-api.tiktok.com/portal/docs?id=1738086844585985)|Use this endpoint to change the status of a list of comments from public to hidden, or vice versa. |Ad Comments > Ad Comment Management > Write Comments|
#|-|[/comment/post/](https://business-api.tiktok.com/portal/docs?id=1738957702411265)|Reply to comments.|Ad Comments > Ad Comment Management > Write Comments|
#|-|[/comment/delete/](https://business-api.tiktok.com/portal/docs?id=1738957772267522)|For users who added comments to TikTok ads that are created by users linked to Customized User or TTBA types of identities, they can delete their own comments.|Ad Comments > Ad Comment Management > Write Comments|
#|-|[/comment/task/create/](https://business-api.tiktok.com/portal/docs?id=1738088144215041)|Create comment export task.|Ad Comments > Ad Comment Management > Read Comments|
#|-|[/comment/task/check/](https://business-api.tiktok.com/portal/docs?id=1738088242659330)|Get comment export status.|Ad Comments > Ad Comment Management > Read Comments|
#|-|[/comment/task/download/](https://business-api.tiktok.com/portal/docs?id=1738088341958658)|Download exported comments.|Ad Comments > Ad Comment Management > Read Comments|
|Ad Comments - Blocked Words|-|-|-|
#|-|[/blockedword/create/](https://business-api.tiktok.com/portal/docs?id=1739029218470913)|Add words to the block list.|Ad Comments > Blocked Word Management > Write Blocked Words|
#|-|[/blockedword/update/](https://business-api.tiktok.com/portal/docs?id=1739029234526209)|Replace a blocked word with another word.|Ad Comments > Blocked Word Management > Write Blocked Words|
#|-|[/blockedword/check/](https://business-api.tiktok.com/portal/docs?id=1739029247961089)|Check if a list of words is blocked.|Ad Comments > Blocked Word Management > Read Blocked Words|
#|-|[/blockedword/list/](https://business-api.tiktok.com/portal/docs?id=1739029260837889)|Get the list of blocked words for an ad account.|Ad Comments > Blocked Word Management > Read Blocked Words|
#|-|[/blockedword/delete/](https://business-api.tiktok.com/portal/docs?id=1739029277346817)|Delete one or more blocked words.|Ad Comments > Blocked Word Management > Write Blocked Words|
#|-|[/blockedword/task/create/](https://business-api.tiktok.com/portal/docs?id=1739029296119809)|Create a task to export blocked words.|Ad Comments > Blocked Word Management > Read Blocked Words|
#|-|[/blockedword/task/check/](https://business-api.tiktok.com/portal/docs?id=1739029311687681)|Check the status of the export task.|Ad Comments > Blocked Word Management > Read Blocked Words|
#|-|[/blockedword/task/download/](https://business-api.tiktok.com/portal/docs?id=1739029325203457)|Download the exported blocked words.|Ad Comments > Blocked Word Management > Read Blocked Words|
|Ad Diagnosis|-|-|-|
#|-|[/tool/diagnosis/get/](https://business-api.tiktok.com/portal/docs?id=1738674986981378)|Get diagnosis, including possible issues and suggestions for correction or improvements, for your active ad groups. |Ad Diagnosis > Get and Adopt Diagnosis Suggestion|
|Ad Groups|-|-|-|
#|-|[/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1739314558673922)|Get all ad groups or use filters to get certain ad groups. |Ads Management > Ad Group > Read Ad Groups|
#|-|[/adgroup/quota/](https://business-api.tiktok.com/portal/docs?id=1768463039162369)|Get the dynamic quota on active ad groups. |Ads Management > Ad Group > Read Ad Groups|
#|-|[/ad/audience_size/estimate/](https://business-api.tiktok.com/portal/docs?id=1740302379236353)|Get estimated audience size. |Ads Management > Ad Group > Create and Update Ad Groups|
#|-|[/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1739499616346114)|Create an ad group. |Ads Management > Ad Group > Create and Update Ad Groups|
#|-|[/adgroup/update/](https://business-api.tiktok.com/portal/docs?id=1739586761631745)|Update an ad group. |Ads Management > Ad Group > Create and Update Ad Groups|
#|-|[/adgroup/status/update/](https://business-api.tiktok.com/portal/docs?id=1739591716326402)|Update ad group status. |Ads Management > Ad Group > Create and Update Ad Groups|
#|-|[/adgroup/budget/update/](https://business-api.tiktok.com/portal/docs?id=1739591133130817)|Update ad group budget. |Ads Management > Ad Group > Create and Update Ad Groups|
|Ad Review|-|-|-|
#|-|[/adgroup/review_info/](https://business-api.tiktok.com/portal/docs?id=1740554277061634)|Get ad group review information. |Ads Management > Ad Group> Read Ad Groups|
#|-|[/ad/review_info/](https://business-api.tiktok.com/portal/docs?id=1740559669226498)|Get ad review information. |Ads Management > Ad > Read Ads|
#|-|[/adgroup/appeal/](https://business-api.tiktok.com/portal/docs?id=1740571821728770)|Appeal the rejection decision if your ad group was rejected during review. |No permission needed.|
|Audience|-|-|-|
#|-|[/dmp/custom_audience/file/upload/](https://business-api.tiktok.com/portal/docs?id=1739940567842818)|Upload audience files.|Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/custom_audience/create/](https://business-api.tiktok.com/portal/docs?id=1739940570793985)|Create audience by file.|Audience Management > Create and Update Custom Audiences|
#|-|[/segment/audience/](https://business-api.tiktok.com/portal/docs?id=1739940583739393)|You can use this endpoint to create or delete audience segments.|Audience Management > Create and Update Custom Audiences|
#|-|[/segment/mapping/](https://business-api.tiktok.com/portal/docs?id=1739940585975809)|You can use this endpoint to add mappings to audience segments.|Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/custom_audience/update/](https://business-api.tiktok.com/portal/docs?id=1739940572667906)|Update details of an audience.|Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/custom_audience/list/](https://business-api.tiktok.com/portal/docs?id=1739940506015746)|Get all audiences.|Audience Management > Read Custom Audiences|
#|-|[/dmp/custom_audience/get/](https://business-api.tiktok.com/portal/docs?id=1739940507792385)|Get audience details.|Audience Management > Read Custom Audiences|
#|-|[/dmp/custom_audience/rule/create/](https://business-api.tiktok.com/portal/docs?id=1739940509849601)|Create audience by rule.|Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/custom_audience/lookalike/create/](https://business-api.tiktok.com/portal/docs?id=1739940511844353)|Create lookalike audience.|Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/custom_audience/lookalike/update/](https://business-api.tiktok.com/portal/docs?id=1758616888158209)|Manually refresh a Lookalike Audience.|Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/custom_audience/delete/](https://business-api.tiktok.com/portal/docs?id=1739940539757569)|Delete an audience.|Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/custom_audience/share/](https://business-api.tiktok.com/portal/docs?id=1740245803888706)|Ad account owners, or users in Admin or Operator roles can share custom audiences with other advertisers.|Audience Management > Share Custom Audiences|
#|-|[/dmp/custom_audience/share/cancel/](https://business-api.tiktok.com/portal/docs?id=1740245829978113)|Ad account owners, or users in Admin or Operator roles can stop sharing custom audiences with other advertisers.|Audience Management > Share Custom Audiences|
#|-|[/dmp/custom_audience/share/log/](https://business-api.tiktok.com/portal/docs?id=1740245827044354)|Ad account owners, or users in Admin or Operator roles can get the sharing log of a custom audience. |Audience Management > Share Custom Audiences|
#|-|[/dmp/custom_audience/apply/](https://business-api.tiktok.com/portal/docs?id=1758615097524225)|Apply audience to or disconnect audience from multiple ad groups.|Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/custom_audience/apply/log/](https://business-api.tiktok.com/portal/docs?id=1758615737388033)|Get the latest application log of custom audiences. |Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/saved_audience/create/](https://business-api.tiktok.com/portal/docs?id=1780154541898754)|Create a Saved Audience. |Audience Management > Create and Update Custom Audiences|
#|-|[/dmp/saved_audience/list/](https://business-api.tiktok.com/portal/docs?id=1780154619404290)|Get the details of Saved Audiences. |Audience Management > Read Custom Audiences|
#|-|[/dmp/saved_audience/delete/](https://business-api.tiktok.com/portal/docs?id=1780154666312706)|Delete Saved Audiences. |Audience Management > Create and Update Custom Audiences|
#|-|[/audience/insight/info/](https://business-api.tiktok.com/portal/docs?id=1797016593782785)|Get details of potential audiences.|Audience Management > Read Custom Audiences|
#|-|[/audience/insight/overlap/](https://business-api.tiktok.com/portal/docs?id=1797023590780930)|Get details of audience overlap.|Audience Management > Read Custom Audiences|
|Authentication|-|-|No permission needed.|
#|-|[/oauth2/access_token/](https://business-api.tiktok.com/portal/docs?id=1739965703387137#item-link-/oauth2/access_token/)|Obtain a long-term access token with `Content-Type` as `application/json`.||
#|-|[/oauth/token/](https://business-api.tiktok.com/portal/docs?id=1739965703387137#item-link-/oauth/token/)|Obtain a long-term access token with `Content-Type` as `application/x-www-form-urlencoded` or `application/json`.||
#|-|[/oauth2/revoke_token/](https://business-api.tiktok.com/portal/docs?id=1739965949088770)|Revoke a long-term access token.||
#|-|[/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041)|Obtain a short-term access token.||
#|-|[/tt_user/oauth2/refresh_token/](https://business-api.tiktok.com/portal/docs?id=1833997679342594)|Renew a short-term access token.||
#|-|[/tt_user/oauth2/revoke/](https://business-api.tiktok.com/portal/docs?id=1833997665030145)|Revoke a short-term access token.||
|Automated Rules|-|-|-|
#|-|[/optimizer/rule/create/](https://business-api.tiktok.com/portal/docs?id=1738767670852610)|Create an automated rule.|Automated Rules > Create and Update Automated Rules|
#|-|[/optimizer/rule/get/](https://business-api.tiktok.com/portal/docs?id=1738768750822466)|Get rules by rule IDs.|Automated Rules > Read Automated Rules|
#|-|[/optimizer/rule/list/](https://business-api.tiktok.com/portal/docs?id=1738768861976578)|Get rules based on the values of certain filters.|Automated Rules > Read Automated Rules|
#|-|[/optimizer/rule/result/list/](https://business-api.tiktok.com/portal/docs?id=1738769076676609)|Get rule results.|Automated Rules > Read Automated Rules|
#|-|[/optimizer/rule/result/get/](https://business-api.tiktok.com/portal/docs?id=1738769098314754)|Get result details.|Automated Rules > Read Automated Rules|
#|-|[/optimizer/rule/update/](https://business-api.tiktok.com/portal/docs?id=1738769123170306)|Update an automated rule.|Automated Rules > Create and Update Automated Rules|
#|-|[/optimizer/rule/update/status/](https://business-api.tiktok.com/portal/docs?id=1738769144119298)|Turn on, turn off, or delete a group of rules.|Automated Rules > Create and Update Automated Rules|
#|-|[/optimizer/rule/batch_bind/](https://business-api.tiktok.com/portal/docs?id=1738769164954626)|Bind objects to rules.|Automated Rules > Create and Update Automated Rules|
|BC Management|-|-|-|
#|-|[/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826)| Get Business Centers that you have access to.|Ad Account Management > Business Center > Read Business Center|
#|-|[/changelog/get/](https://business-api.tiktok.com/portal/docs?id=1820767460168705)| Get the activity log of a Business Center.|Ads Management > Change Log > Change Log|
|BC Payments|-|-|-|
#|-|[/bc/transfer/](https://business-api.tiktok.com/portal/docs?id=1739939095321601)|Process payments (recharge money to or deduct money from an ad account in a Business Center.|Ad Account Management > Business Center > Ad Account Balance and Transaction|
#|-|[/advertiser/balance/get/](https://business-api.tiktok.com/portal/docs?id=1739939106470913)|Get ad account balance and budget.|Ad Account Management > Business Center > Ad Account Balance and Transaction|
#|-|[/bc/balance/get/](https://business-api.tiktok.com/portal/docs?id=1739939128198145)|Get the balance of a Business Center.|Ad Account Management > Business Center > Read Business Center|
#|-|[/bc/account/transaction/get/](https://business-api.tiktok.com/portal/docs?id=1792849810925569)|Get the transaction records of a BC or ad accounts|Ad Account Management > Business Center > Ad Account Balance and Transaction|
#|-|[/advertiser/transaction/get/](https://business-api.tiktok.com/portal/docs?id=1739939116353538)|Get transaction records of an ad account.|Ad Account Management > Business Center > Ad Account Balance and Transaction|
#|-|[/bc/transaction/get/](https://business-api.tiktok.com/portal/docs?id=1739939140408322)|Get translaction records of a Business Center.|Ad Account Management > Business Center > Read Business Center|
#|-|[/bc/account/budget/changelog/get/](https://business-api.tiktok.com/portal/docs?id=1830173979548674)|Get the budget change history of an ad account.|Ad Account Management > Business Center > Ad Account Balance and Transaction|
#|-|[/bc/account/cost/get/](https://business-api.tiktok.com/portal/docs?id=1829079287639041)|Get the cost records of a BC and ad accounts.|Ad Account Management > Business Center > Ad Account Balance and Transaction|
|BC Payment Portfolios|-|-|-|
#|-|[/payment_portfolio/get/](https://business-api.tiktok.com/portal/docs?id=1839151931301954)|Get Payment Portfolios.|Payment Portfolio > Read|
#|-|[/payment_portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1839151986039809)|Create a Payment Portfolio.|Payment Portfolio > Write|
#|-|[/payment_portfolio/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1839151992566081)|Link ad accounts to a Payment Portfolio.|Payment Portfolio > Write|
#|-|[/payment_portfolio/credit_line/update/](https://business-api.tiktok.com/portal/docs?id=1839151998450754)|Allocate the credit line to Payment Portfolios.|Payment Portfolio > Write|
#|-|[/payment_portfolio/advertiser/get/](https://business-api.tiktok.com/portal/docs?id=1839152005599234)|Get ad accounts linked to a Payment Portfolio.|Payment Portfolio > Read|
#|-|[/payment_portfolio/user/get/](https://business-api.tiktok.com/portal/docs?id=1839152031645121)|Get authorized users for a Payment Portfolio.|Payment Portfolio > Read|
|BC Assets|-|-|-|
#|-|[/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721)| Create an ad account under a Business Center.|Ad Account Management > Business Center > Create Ad Account|
#|-|[/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434)|Update an ad account.|Ad Account Management > Business Center > Create Ad Account|
#|-|[/bc/advertiser/disable/](https://business-api.tiktok.com/portal/docs?id=1752349244331009)|Disable ad accounts.|Ad Account Management > Business Center > Create Ad Account|
#|-|[/bc/image/upload/](https://business-api.tiktok.com/portal/docs?id=1739938996913218)| Upload certificate images for an ad account.|Ad Account Management > Business Center > Create Ad Account|
#|-|[/bc/advertiser/qualification/get/](https://business-api.tiktok.com/portal/docs?id=1770118680584194)| Get qualifications within a Business Center.|Ad Account Management > Business Center > Create Ad Account|
#|-|[/bc/advertiser/unionpay_info/check/](https://business-api.tiktok.com/portal/docs?id=1813772238056449)| Check the UnionPay verification requirement for a business license.|Ad Account Management > Business Center > Create Ad Account|
#|-|[/bc/advertiser/unionpay_info/submit/](https://business-api.tiktok.com/portal/docs?id=1813772274039809)| Submit UnionPay verification for a business license.|Ad Account Management > Business Center > Create Ad Account|
#|-|[/bc/oa/create/](https://business-api.tiktok.com/portal/docs?id=1855027199571138)|Create an Organization Account in a Business Center.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401)|Get assets of a Business Center.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/admin/get/](https://business-api.tiktok.com/portal/docs?id=1739433007779841)|Get assets of a Business Center as admins.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121)|Assign assets to a user.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/unassign/](https://business-api.tiktok.com/portal/docs?id=1739448126749698)|Revoke the access to an asset from a user.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/account/authorization/](https://business-api.tiktok.com/portal/docs?id=1846868897541122)|Obtain a TikTok account ad delivery authorization URL.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/advertiser/assign/](https://business-api.tiktok.com/portal/docs?id=1846868953025538)|Link a TikTok account to an ad account in Business Center.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/advertiser/unassign/](https://business-api.tiktok.com/portal/docs?id=1855027260369921)|Unlink a TikTok account from an ad account in Business Center.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/advertiser/assigned/](https://business-api.tiktok.com/portal/docs?id=1855027294743554)|Get ad accounts linked to a TikTok account in Business Center.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/pixel/transfer/](https://business-api.tiktok.com/portal/docs?id=1753994928261122)|Transfer Pixel from Advertiser to BC.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/pixel/link/update/](https://business-api.tiktok.com/portal/docs?id=1753994950327297)|Use this endpoint to link and unlink pixel to advertiser accounts.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/pixel/link/get/](https://business-api.tiktok.com/portal/docs?id=1753994966965249)|Use this endpoint to get a list of ad accounts that have been linked to the request pixel.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/partner/get/](https://business-api.tiktok.com/portal/docs?id=1739663268166722)|Get partners by assets.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/member/get/](https://business-api.tiktok.com/portal/docs?id=1739659558931458)|Get members by assets.|Ad Account Management > Business Center > Business Center Asset|
#|-|[/bc/asset/admin/delete/](https://business-api.tiktok.com/portal/docs?id=1739657251102722)|Delete assets. |Ad Account Management > Business Center > Business Center Asset|
#|-|[/asset/bind/quota/](https://business-api.tiktok.com/portal/docs?id=1739659584022529)|Understand how many ads an asset has been binded to, and how many more ads this asset can be binded to.|Ad Account Management > Business Center > Business Center Asset|
|BC Billing Group|-|-|-|
#|-|[/bc/billing_group/create/](https://business-api.tiktok.com/portal/docs?id=1738947780033537)|Create a billing group in a Business Center. |Ad Account Management > Business Center > Business Center Billing Group Management|
#|-|[/bc/billing_group/update/](https://business-api.tiktok.com/portal/docs?id=1738947843447809)|Update settings of a billing group.|Ad Account Management > Business Center > Business Center Billing Group Management|
#|-|[/bc/billing_group/get/](https://business-api.tiktok.com/portal/docs?id=1738944324279297)|Get all billing groups in a Business Center.|Ad Account Management > Business Center > Business Center Billing Group Management|
#|-|[/bc/billing_group/advertiser/list/](https://business-api.tiktok.com/portal/docs?id=1738458108705794)|Get all billing groups in a Business Center.|Ad Account Management > Business Center > Business Center Billing Group Management|
|BC Asset Groups|-|-|-|
#|-|[/bc/asset_group/create/](https://business-api.tiktok.com/portal/docs?id=1749001658583105)|Create an Asset Group in your Business Center.|Ad Account Management > Business Center > Asset Group|
#|-|[/bc/asset_group/update/](https://business-api.tiktok.com/portal/docs?id=1749001662156801)|Update assets, members or the name of an Asset Group.|Ad Account Management > Business Center > Asset Group|
#|-|[/bc/asset_group/list/](https://business-api.tiktok.com/portal/docs?id=1749001665910786)|Get all Asset Groups in your Business Center.|Ad Account Management > Business Center > Asset Group|
#|-|[/bc/asset_group/get/](https://business-api.tiktok.com/portal/docs?id=1749001669670913)|Get the assets or members of an Asset Group in a Business Center.|Ad Account Management > Business Center > Asset Group|
#|-|[/bc/asset_group/delete/](https://business-api.tiktok.com/portal/docs?id=1749001673406466)|Remove members' access to an Asset Group.|Ad Account Management > Business Center > Asset Group|
|BC Partners|-|-|-|
#|-|[/bc/partner/get/](https://business-api.tiktok.com/portal/docs?id=1739662727395330)|Get partners.|Ad Account Management > Business Center > Business Center Partner Management|
#|-|[/bc/partner/add/](https://business-api.tiktok.com/portal/docs?id=1739662756510721)|Add partner to Business Center.|Ad Account Management > Business Center > Business Center Partner Management|
#|-|[/bc/partner/delete/](https://business-api.tiktok.com/portal/docs?id=1739662779301890)|Delete partner.|Ad Account Management > Business Center > Business Center Partner Management|
#|-|[/bc/partner/asset/delete/](https://business-api.tiktok.com/portal/docs?id=1739662800930817)|Cancel asset sharing.|Ad Account Management > Business Center > Business Center Partner Management|
#|-|[/bc/partner/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739662828320769)|Get partner assets.|Ad Account Management > Business Center > Business Center Partner Management|
|BC Members|-|-|-|
#|-|[/bc/member/get/](https://business-api.tiktok.com/portal/docs?id=1739939404802049)|Get BC members.|Ad Account Management > Business Center > Business Center Member Management|
#|-|[/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505)|Invite members to BC.|Ad Account Management > Business Center > Business Center Member Management|
#|-|[/bc/member/update/](https://business-api.tiktok.com/portal/docs?id=1739696704424961)|Update member information.|Ad Account Management > Business Center > Business Center Member Management|
#|-|[/bc/member/delete/](https://business-api.tiktok.com/portal/docs?id=1739939482851329)|Delete a member or revoke a member invitation.|Ad Account Management > Business Center > Business Center Member Management|
|BC Invoice|-|-|-|
#|-|[/bc/invoice/get/](https://business-api.tiktok.com/portal/docs?id=1740295890332674)|Finance Managers and Finance Analysts of a Business Center account can use this endpoint to get invoices of their Business Center accounts.|Ad Account Management > Business Center > Invoice Management|
#|-|[/bc/invoice/unpaid/get/](https://business-api.tiktok.com/portal/docs?id=1740295904843777)|Finance Managers and Finance Analysts of a Business Center account can use this endpoint to get total unpaid amount of their Business Center accounts.|Ad Account Management > Business Center > Invoice Management|
#|-|[/bc/invoice/download/](https://business-api.tiktok.com/portal/docs?id=1754000198430722)|Finance Managers and Finance Analysts of a Business Center account can use this endpoint to download invoices via synchronous downloads.|Ad Account Management > Business Center > Invoice Management|
#|-|[/bc/invoice/task/create/](https://business-api.tiktok.com/portal/docs?id=1754000448122882)|Create an asynchronous download task.|Ad Account Management > Business Center > Invoice Management|
#|-|[/bc/invoice/task/get/](https://business-api.tiktok.com/portal/docs?id=1754000480307265)|Check whether the asynchronous download task (`BILLING_REPORT` type) has completed.|Ad Account Management > Business Center > Invoice Management|
#|-|[/bc/invoice/task/list/](https://business-api.tiktok.com/portal/docs?id=1754000504437762)|Check whether the asynchronous download task (`INVOICE_LIST` or `INVOICE_BATCH` type) has completed.|Ad Account Management > Business Center > Invoice Management|
|BC Reporting|-|-|-|
#|-|[/bc/advertiser/attribute/](https://business-api.tiktok.com/portal/docs?id=1775752357139457)|Get currencies and registration areas for ad accounts.|Ad Account Management > Business Center > Create Ad Account|
|Brand Safety|-|-|-|
#|-|[/tiktok_inventory_filters/get/](https://business-api.tiktok.com/portal/docs?id=1830112550443073)|Get the Brand Safety Hub settings of an ad account. |Brand Safety > TikTok Inventory Filters|
#|-|[/tiktok_inventory_filters/update/](https://business-api.tiktok.com/portal/docs?id=1830112569774274)|Set or update the Brand Safety Hub settings of an ad account. |Brand Safety > TikTok Inventory Filters|
|Business Messaging|-|-|-|
#|-|[/business/message/send/](https://business-api.tiktok.com/portal/docs?id=1832184403754242)|Send a message to a conversation. | Business Messaging > Business Messaging Send |
#|-|[/business/message/conversation/list/](https://business-api.tiktok.com/portal/docs?id=1832184415059970)|Get a list of conversations. |Business Messaging > Business Messaging Read|
#|-|[/business/message/content/list/](https://business-api.tiktok.com/portal/docs?id=1832184425841170)|Get a list of messages. |Business Messaging > Business Messaging Read|
#|-|[/business/message/media/upload/](https://business-api.tiktok.com/portal/docs?id=1832184437720193)|Upload an image.| Business Messaging > Business Messaging Send |
#|-|[/business/message/media/download/](https://business-api.tiktok.com/portal/docs?id=1832184455450626)|Download an image from a message. |Business Messaging > Business Messaging Read|
#|-|[/business/message/capabilities/get/](https://business-api.tiktok.com/portal/docs?id=1836061597865986)|Check the capability of a Business Account for a conversation. |Business Messaging > Business Messaging Read|
#|-|[/business/message/direct_reply/update/](https://business-api.tiktok.com/portal/docs?id=1843697551083522)|Enable or disable Comment-to-Message for a Business Account. |Business Messaging > Business Messaging Send|
#|-|[/business/message/direct_reply/get/](https://business-api.tiktok.com/portal/docs?id=1843697576682497)|Get the Comment-to-Message setting of a Business Account. |Business Messaging > Business Messaging Read|
#|-|[/business/webhook/update/](https://business-api.tiktok.com/portal/docs?id=1832184470423554)|Create a Business Messaging Webhook configuration. |No permission needed|
#|-|[/business/webhook/list/](https://business-api.tiktok.com/portal/docs?id=1832184488625234)|Get a Business Messaging Webhook configuration. |No permission needed|
#|-|[/business/webhook/delete/](https://business-api.tiktok.com/portal/docs?id=1832184497985537)|Delete a Business Messaging Webhook configuration. |No permission needed|
#|-|[/business/message/auto_message/create/](https://business-api.tiktok.com/portal/docs?id=1832184584869890)|Create an automatic message for a Business Account. | Business Messaging > Auto Message Setting |
#|-|[/business/message/auto_message/update/](https://business-api.tiktok.com/portal/docs?id=1832184596566017)|Update the automatic message for a Business Account. | Business Messaging > Auto Message Setting |
#|-|[/business/message/auto_message/status/update/](https://business-api.tiktok.com/portal/docs?id=1832184607786242)|Turn on or turn off an automatic message for a Business Account. | Business Messaging > Auto Message Setting |
#|-|[/business/message/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1832184619749377)|Get the automatic messages for a Business Account. | Business Messaging > Auto Message Setting |
#|-|[/business/message/auto_message/delete/](https://business-api.tiktok.com/portal/docs?id=1832184630106386)|Delete the automatic message for a Business Account. | Business Messaging > Auto Message Setting |
#|-|[/business/message/auto_message/sort/](https://business-api.tiktok.com/portal/docs?id=1832184641212433)|Sort the automatic message for a Business Account. | Business Messaging > Auto Message Setting |
|Campaign|-|-|-|
#|-|[/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986)|Get all campaigns or use filters to get certain campaigns. |Ads Management > Campaign > Read Campaigns|
#|-|[/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1739318962329602)|Create a campaign. |Ads Management > Campaign > Create and Update Campaigns|
#|-|[/campaign/update/](https://business-api.tiktok.com/portal/docs?id=1739320422086657)|Update a campaign. |Ads Management > Campaign > Create and Update Campaigns|
#|-|[/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1739320994354178)|Update campaign status. |Ads Management > Campaign > Create and Update Campaigns|
#|-|[/campaign/quota/get/](https://business-api.tiktok.com/portal/docs?id=1739324098894850)|Get iOS14 campaign quota. |Ads Management > Campaign > Read Campaigns|
#|-|[/campaign/quota/info/](https://business-api.tiktok.com/portal/docs?id=1752256376677378)|Get the quota for an iOS 14 Dedicated Campaign per ad network.|Ads Management > Campaign > Read Campaigns|
#|-|[/campaign/copy/task/create/](https://business-api.tiktok.com/portal/docs?id=1788434394151938)|Create an asynchronous copy task for a Manual Campaign.|Ads Management > Campaign > Create and Update Campaigns|
#|-|[/campaign/copy/task/check/](https://business-api.tiktok.com/portal/docs?id=1788434463507458)|Get the results of an asynchronous copy task for a Manual Campaign.|Ads Management > Campaign > Read Campaigns|
|Catalogs|-|-|-|
#|-|[/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961)|Create a catalog by specifying information such as name, targeted locations, and currency.|DPA Catalog Management > Catalog Management > Create and Update Catalogs|
#|-|[/catalog/update/](https://business-api.tiktok.com/portal/docs?id=1740306544966657)|Use this endpoint to update the name of a catalog. The catalog must be under a Business Center. |DPA Catalog Management > Catalog Management > Create and Update Catalogs|
#|-|[/catalog/delete/](https://business-api.tiktok.com/portal/docs?id=1740310064588801)|Delete a catalog.|DPA Catalog Management > Catalog Management > Create and Update Catalogs|
#|-|[/catalog/get/](https://business-api.tiktok.com/portal/docs?id=1740315452868610)|Get all catalogs or a particular catalog.|DPA Catalog Management > Catalog Management > Read Catalogs|
#|-|[/catalog/lexicon/get/](https://business-api.tiktok.com/portal/docs?id=1740488375815169)|Get the lexicon for your catalog. A lexicon means a list of variables that you can use in ad texts when promoting your catalog.|DPA Catalog Management > Catalog Management > Read Catalogs|
#|-|[/catalog/capitalize/](https://business-api.tiktok.com/portal/docs?id=1740490222539778)|If your catalogs are still under your ad account, use this endpoint to migrate them to your Business Center.|DPA Catalog Management > Catalog Management > Create and Update Catalogs|
#|-|[/catalog/available_country/get/](https://business-api.tiktok.com/portal/docs?id=1740491257516034)|the countries and regions that ads for a catalog can be delivered to.|DPA Catalog Management > Catalog Management > Read Catalogs|
#|-|[/catalog/location_currency/get/](https://business-api.tiktok.com/portal/docs?id=1740491571747841)|Get the list of locations (country or region abbreviations) that are supported by Catalog API and the corresponding currencies for each location.|DPA Catalog Management > Catalog Management > Read Catalogs|
#|-|[/catalog/overview/](https://business-api.tiktok.com/portal/docs?id=1740492470201345)|You can get the number of products in different audit status (approved, rejected, and processing) in a catalog.|DPA Catalog Management > Catalog Management > Read Catalogs|
|Catalog Event Sources|-|-|-|
#|-|[/catalog/eventsource/bind/](https://business-api.tiktok.com/portal/docs?id=1740492491200513)|Bind app or website event sources to a catalog in a Business Center.|DPA Catalog Management > Event Source Bind > Create and Delete Event Source Bind|
#|-|[/catalog/eventsource/unbind/](https://business-api.tiktok.com/portal/docs?id=1740492512449538)|Unbind event sources from a catalog.|DPA Catalog Management > Event Source Bind > Create and Delete Event Source Bind|
#|-|[/catalog/eventsource_bind/get/](https://business-api.tiktok.com/portal/docs?id=1740492531343362)|Get event source binding information.|DPA Catalog Management > Event Source Bind > Read Event Source Bind |
|Catalog Feeds|-|-|-|
#|-|[/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)|Create a feed.|DPA Catalog Management > Feed > Post Feed|
#|-|[/catalog/feed/get/](https://business-api.tiktok.com/portal/docs?id=1740665183073281)|Get all feeds or a particular feed.|DPA Catalog Management > Feed > Get Feed|
#|-|[/catalog/feed/update/](https://business-api.tiktok.com/portal/docs?id=1740665197662210)|Update a feed.|DPA Catalog Management > Feed > Post Feed|
#|-|[/catalog/feed/delete/](https://business-api.tiktok.com/portal/docs?id=1740665210863617)|Delete a feed.|DPA Catalog Management > Feed > Post Feed|
#|-|[/catalog/feed/log/](https://business-api.tiktok.com/portal/docs?id=1740665225631810)|Get the last 10 operations of a feed.|DPA Catalog Management > Feed > Get Feed|
#|-|[/catalog/feed/switch/](https://business-api.tiktok.com/portal/docs?id=1750349624999937)|Update the schedule status of a feed|DPA Catalog Management > Feed > Post Feed|
|Catalog Products|-|-|-|
#|-|[/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161)|Upload products via file URL.|DPA Catalog Management > Product Management > Create and Update Product|
#|-|[/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153)|Upload products.|DPA Catalog Management > Product Management > Create and Update Product|
#|-|[/catalog/product/update/](https://business-api.tiktok.com/portal/docs?id=1740562287852546)|Update products in bulk.|DPA Catalog Management > Product Management > Create and Update Product|
#|-|[/catalog/product/delete/](https://business-api.tiktok.com/portal/docs?id=1740562489236481)|Delete products in bulk.|DPA Catalog Management > Product Management > Create and Update Product|
#|-|[/catalog/product/get/](https://business-api.tiktok.com/portal/docs?id=1740564136678402)|Get a list of products in your product catalog.|DPA Catalog Management > Product Management > Read Products|
#|-|[/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889)| Use this endpoint to find out  if a product was uploaded or deleted successfully, and what to do if it failed.|DPA Catalog Management > Product Management > Read Products|
|Catalog Product Sets|-|-|-|
#|-|[/catalog/set/get/](https://business-api.tiktok.com/portal/docs?id=1740570556295169)|Get product sets.|DPA Catalog Management > Get Catalog Set|
#|-|[/catalog/set/product/get/](https://business-api.tiktok.com/portal/docs?id=1740571478441986)|Get products in a product set.|DPA Catalog Management > DPA Set Management > Get Catalog Set|
#|-|[/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257)|Create a product set by conditions.|DPA Catalog Management > DPA Set Management > Post Catalog Set|
#|-|[/catalog/set/upload/](https://business-api.tiktok.com/portal/docs?id=1846770644217858)|Create a product set by file.|DPA Catalog Management > DPA Set Management > Post Catalog Set|
#|-|[/catalog/set/update/](https://business-api.tiktok.com/portal/docs?id=1740572974725122)|Update a product set.|DPA Catalog Management > DPA Set Management > Post Catalog Set|
#|-|[/catalog/set/delete/](https://business-api.tiktok.com/portal/docs?id=1740573143966722)|Delete product sets.|DPA Catalog Management > DPA Set Management > Post Catalog Set|
|Catalog Videos|-|-|-|
#|-|[/catalog/video/file/](https://business-api.tiktok.com/portal/docs?id=1803655037415489)|Upload catalog videos via a file URL.|DPA Catalog Management > Catalog Video Management > Post Catalog Video|
#|-|[/catalog/video/log/](https://business-api.tiktok.com/portal/docs?id=1803655061642242)|Get the catalog video handling log.|DPA Catalog Management > Catalog Video Management > Get Catalog Video|
#|-|[/catalog/video/get/](https://business-api.tiktok.com/portal/docs?id=1803655082498050)|Get the uploaded catalog videos within a catalog.|DPA Catalog Management > Catalog Video Management > Get Catalog Video|
#|-|[/catalog/video/delete/](https://business-api.tiktok.com/portal/docs?id=1803655103069185)|Delete uploaded catalog videos.|DPA Catalog Management > Catalog Video Management > Post Catalog Video|
|Catalog Video Templates|-|-|-|
#|-|[/catalog/video_package/get/](https://business-api.tiktok.com/portal/docs?id=1740574099715073) |Get video packages.|DPA Catalog Management > Catalog Video Management > Get Catalog Video|
#|-|[/catalog/video_package/create/](https://business-api.tiktok.com/portal/docs?id=1740665273039873)<br>{-Deprecated}|Create video package.|DPA Catalog Management > Catalog Video Management > Post Catalog Video|
#|-|[/catalog/video_package/update/](https://business-api.tiktok.com/portal/docs?id=1740665303669761)<br>{-Deprecated}|Update video package.|DPA Catalog Management > Catalog Video Management > Post Catalog Video|
#|-|[/catalog/video_package/delete/](https://business-api.tiktok.com/portal/docs?id=1740665332455426)<br>{-Deprecated}|Delete video package.|DPA Catalog Management > Catalog Video Management > Post Catalog Video|
#|-|[/catalog/template/upload/](https://business-api.tiktok.com/portal/docs?id=1708594874651650)<br>{-Deprecated}|Upload a custom video template to Business Center and associate it with a group of catalogs.|DPA Catalog Management > Template > Template|
#|-|[/catalog/template_preview/create/](https://business-api.tiktok.com/portal/docs?id=1708423201310721)<br>{-Deprecated}|Preview a video template.|DPA Catalog Management > Template > Template|
|Catalog Diagnostics|-|-|-|
#|-|[/diagnostic/catalog/](https://business-api.tiktok.com/portal/docs?id=1771117232728066)|Get synchronous catalog product diagnostic information.|DPA Catalog Management > Diagnostic > Diagnostic|
#|-|[/diagnostic/catalog/product/task/create/](https://business-api.tiktok.com/portal/docs?id=1771117279175682)|Create an asynchronous download task for catalog product diagnostic information.|DPA Catalog Management > Diagnostic > Diagnostic|
#|-|[/diagnostic/catalog/product/task/get/](https://business-api.tiktok.com/portal/docs?id=1771117294731266)|Download asynchronous catalog product diagnostic information.|DPA Catalog Management > Diagnostic > Diagnostic|
#|-|[/diagnostic/catalog/eventsource/issue/](https://business-api.tiktok.com/portal/docs?id=1780799405041665)|Get catalog event source diagnostic information.|DPA Catalog Management > Event Source Bind > Read Event Source Bind|
#|-|[/diagnostic/catalog/eventsource/metric/](https://business-api.tiktok.com/portal/docs?id=1780802101608450)|Get catalog event trends and match rate.|DPA Catalog Management > Event Source Bind > Read Event Source Bind|
|Catalog Insights|-|-|-|
#|-|[/catalog/insight/filter/get/](https://business-api.tiktok.com/portal/docs?id=1805640864553985)|Get filters for catalog product insights.|DPA Catalog Management > Catalog Insight > Catalog Insight|
#|-|[/catalog/insight/product/get/](https://business-api.tiktok.com/portal/docs?id=1805640886872066)|Get trending catalog products.|DPA Catalog Management > Catalog Insight > Catalog Insight|
#|-|[/catalog/insight/category/get/](https://business-api.tiktok.com/portal/docs?id=1805640900563969)|Get trending catalog product categories.|DPA Catalog Management > Catalog Insight > Catalog Insight|
|Change Log|-|-|-|
#|-|[/changelog/task/create/](https://business-api.tiktok.com/portal/docs?id=1738811219460097)|Initiate a task to download change logs for an ad account based on the parameters that you specify. |Ads Management > Change Log > Change Log|
#|-|[/changelog/task/check/](https://business-api.tiktok.com/portal/docs?id=1739916077352961)|Check whether a log download file has been completed or not. |Ads Management > Change Log > Change Log|
#|-|[/changelog/task/download/](https://business-api.tiktok.com/portal/docs?id=1739924165710849)|Get the log file that has been downloaded. |Ads Management > Change Log > Change Log|
|Creative Insights|-|-|-|
#|-|[/report/ad_benchmark/get/](https://business-api.tiktok.com/portal/docs?id=1738824501176321)|Use this endpoint to get benchmarks about the performance of an ad.|Reporting > Ad Insight Report|
#|-|[/report/video_performance/get/](https://business-api.tiktok.com/portal/docs?id=1738825259075586)|Get in-second performance data about a video.|Reporting > Ad Insight Report|
|Creative Portfolios|-|-|-|
#|-|[/creative/portfolio/create/](https://business-api.tiktok.com/portal/docs?id=1739091950439426)|Create a portfolio.|Creative Management > Creative Recommendation > CTA Recommendation|
#|-|[/creative/portfolio/get/](https://business-api.tiktok.com/portal/docs?id=1739092113671170)|Get a portfolio by ID.|Creative Management > Creative Recommendation > CTA Recommendation|
#|-|[/creative/portfolio/list/](https://business-api.tiktok.com/portal/docs?id=1766324010279938)|Get portfolios within an ad account.|Creative Management >  Creative Recommendation > CTA Recommendation|
#|-|[/creative/portfolio/delete/](https://business-api.tiktok.com/portal/docs?id=1834560049625089)|Delete portfolios.|Creative Management > Creative Recommendation > CTA Recommendation|
|Creative reports|-|-|-|
#|-|[/creative/report/get/](https://business-api.tiktok.com/portal/docs?id=1740662135093314)|Use this endpoint to run a report of creative assets. |Creative Management > Get Creative Report|
|Creative tools|-|-|-|
#|-|[/creative/status/get/](https://business-api.tiktok.com/portal/docs?id=1739351979503618)<br>{-deprecated}|Get the task status of the asynchronous creative endpoints.|Creative Management > Creative tool|
#|-|[/creative/image/edit/](https://business-api.tiktok.com/portal/docs?id=1739067107903489)|Edit an image according to the size you want as well as apply creative trimmings.|Creative Management > Creative tool|
#|-|[/creative/ads_preview/create/](https://business-api.tiktok.com/portal/docs?id=1739403070695426)|Preview an ad or a creative.|Creative Management > Creative tool|
#|-|[/creative/video_soundtrack/create/](https://business-api.tiktok.com/portal/docs?id=1739089635852289)<br>{-deprecated}|Create a Smart Video Soundtrack task.|Creative Management > Creative tool|
#|-|[/creative/quick_optimization/create/](https://business-api.tiktok.com/portal/docs?id=1739355078757377)<br>{-deprecated}|Create a video optimization task simply and quickly.|Creative Management > Creative tool|
#|-|[/creative/smart_video/create/](https://business-api.tiktok.com/portal/docs?id=1739352294717441)<br>{-deprecated}|Generate smart video.|Creative Management > Creative tool|
#|-|[/creative/asset/share/](https://business-api.tiktok.com/portal/docs?id=1773192725768193&rid=nmsgaz98b9)|Share creative assets with other advertiser accounts.|Creative Management > Creative tool|
#|-|[/creative/asset/delete/](https://business-api.tiktok.com/portal/docs?id=1797202997456897)|Delete creative assets.|Creative Management > Creative tool|
#|-|[/creative/smart_text/generate/](https://business-api.tiktok.com/portal/docs?id=1739084248002626)|Use smart text.|Creative Management > Creative Recommendation > Smart Text|
#|-|[/creative/smart_text/feedback/](https://business-api.tiktok.com/portal/docs?id=1739084248002626)|Send back the text being used.|Creative Management > Creative Recommendation > Smart Text|
#|-|[/creative/cta/recommend/](https://business-api.tiktok.com/portal/docs?id=1739362202742785)|You can get recommended CTAs that you can use to create or update your ads. |Creative Management > Creative Recommendation > CTA Recommendation|
#|-|[/video/fix/task/create/](https://business-api.tiktok.com/portal/docs?id=1741468875279361)|Use this endpoint to create a task to detect and fix video issues.|Creative Management  > Smart Fix > Fix Task management|
#|-|[/video/fix/task/get/](https://business-api.tiktok.com/portal/docs?id=1741469487859714)|Use this endpoint to get smart fix task results.|Creative Management > Smart Fix Fix > Fix Task management|
#|-|[/creative_fatigue/get/](https://business-api.tiktok.com/portal/docs?id=1767568466842626)|Use this endpoint to detect whether Creative Fatigue occurred for an ad within a specified time range in the past.|Reporting > Creative Fatigue Insight|
|Custom Conversions |-|-|-|
#|-|[/custom_conversion/list/](https://business-api.tiktok.com/portal/docs?id=1842225174460673)|Get Custom Conversions associated with an event source.|Custom Conversion Management > Read Custom Conversion|
#|-|[/custom_conversion/get/](https://business-api.tiktok.com/portal/docs?id=1842225206555074)|Get the details of a Custom Conversion.|Custom Conversion Management > Read Custom Conversion|
#|-|[/custom_conversion/create/](https://business-api.tiktok.com/portal/docs?id=1842225226006530)|Create a Custom Conversion.|Custom Conversion Management > Create and Update Custom Conversion|
#|-|[/custom_conversion/update/](https://business-api.tiktok.com/portal/docs?id=1842225247176705)|Update a Custom Conversion.|Custom Conversion Management > Create and Update Custom Conversion|
#|-|[/custom_conversion/delete/](https://business-api.tiktok.com/portal/docs?id=1842225270315074)|Delete a Custom Conversion.|Custom Conversion Management > Create and Update Custom Conversion|
|Discovery|-|-|-|
#|-|[/discovery/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119032526849)|Get popular hashtags.|Creative Management > Discovery|
#|-|[/discovery/detail/](https://business-api.tiktok.com/portal/docs?id=1825119047070721)|Get details of a popular hashtag.|Creative Management > Discovery|
#|-|[/discovery/video_list/](https://business-api.tiktok.com/portal/docs?id=1825119056527362)|Get trending videos related to hashtags.|Creative Management > Discovery|
#|-|[/discovery/cml/trending_list/](https://business-api.tiktok.com/portal/docs?id=1825119063013505)|Get popular tracks from the Commercial Music Library.|Creative Management > Discovery|
#|-|[/discovery/cml/video_list/](https://business-api.tiktok.com/portal/docs?id=1825119068941314)|Get trending videos related to tracks.|Creative Management > Discovery|
#|-|[/discovery/trending/search/](https://business-api.tiktok.com/portal/docs?id=1832798345014338)|Get trending search keywords.|Creative Management > Discovery|
#|-|[/discovery/trending/search/keyword/](https://business-api.tiktok.com/portal/docs?id=1832798361818114)|Get recommended search keywords.|Creative Management > Discovery|
|Dynamic Scene<br> (Deprecated) |-|-|-|
#|-|[/dynamic_scene/material/submit/](https://business-api.tiktok.com/portal/docs?id=1740317263937538)<br> {-Deprecated}|Use this endpoint to submit materials for dynamic scene creation.|Creative Management > Dynamic Scene > Core Functions|
#|-|[/dynamic_scene/task/create/](https://business-api.tiktok.com/portal/docs?id=1740317320231938)<br> {-Deprecated}|Use this endpoint to create a task that generates videos. You can get a maximum of 8 videos for each task.|Creative Management > Dynamic Scene > Core Functions|
#|-|[/dynamic_scene/task/get/](https://business-api.tiktok.com/portal/docs?id=1740317337705473)<br> {-Deprecated}|Use this endpoint to get task results.|Creative Management > Dynamic Scene > Core Functions|
#|-|[/dynamic_scene/get/](https://business-api.tiktok.com/portal/docs?id=1740317360823297)<br> {-Deprecated}|Use this endpoint to get all videos that are generated from a material package.|Creative Management > Dynamic Scene > Core Functions|
#|-|[/dynamic_scene/report/get/](https://business-api.tiktok.com/portal/docs?id=1740317382510593)<br> {-Deprecated}|Use this endpoint to run a report of dynamic scene videos.|Creative Management > Dynamic Scene > Core Functions|
|Events 2.0|-|-|-|
#|-|[/event/track/](https://business-api.tiktok.com/portal/docs?id=1771101303285761) |Report a single App, Web, or Offline event, or multiple App, Web, or Offline events in batch.|Measurement > Report Conversion Event|
|Events 1.0|-|-|-|
#|-|[/app/track/](https://business-api.tiktok.com/portal/docs?id=1740859196043266)|Report a single app event.|Measurement > Report App Event|
#|-|[/app/batch/](https://business-api.tiktok.com/portal/docs?id=1740859218541569)|Report app events in bulk.|Measurement > Report App Event|
#|-|[/app/info/](https://business-api.tiktok.com/portal/docs?id=1740859272887297)|Obtain the details of an App.|App Management > Read Apps|
#|-|[/app/create/](https://business-api.tiktok.com/portal/docs?id=1740859280958466)|Create a mobile App.|App Management > Create and Update Apps|
#|-|[/app/update/](https://business-api.tiktok.com/portal/docs?id=1740859300069378)|Update a mobile App.|App Management > Create and Update Apps|
#|-|[/app/list/](https://business-api.tiktok.com/portal/docs?id=1740859313270786)|Obtain the list of apps under your account|App Management > Read Apps|
#|-|[/app/optimization_event/](https://business-api.tiktok.com/portal/docs?id=1740859338750977)|Obtain information about an app conversion event|App Management > Read Apps|
#|-|[/app/optimization_event/retargeting/](https://business-api.tiktok.com/portal/docs?id=1740859371033601)|Obtain information about an App Retargeting Event|App Management > Read Apps|
#|-|[/pixel/track/](https://business-api.tiktok.com/portal/docs?id=1740858531237890)|Report a single pixel event.|Measurement > Report Pixel Event|
#|-|[/pixel/batch/](https://business-api.tiktok.com/portal/docs?id=1740858565852225)|Report pixel events in bulk.|Measurement > Report Pixel Event|
#|-|[/pixel/list/](https://business-api.tiktok.com/portal/docs?id=1740858697598978)|Obtain a list of Pixel information.|Pixel Management > Read Pixels|
#|-|[/pixel/create/](https://business-api.tiktok.com/portal/docs?id=1740858779758593)|Create a Pixel.|Pixel Management > Create and Update Pixels|
#|-|[/pixel/update/](https://business-api.tiktok.com/portal/docs?id=1740858799524865)|Update a Pixel.|Pixel Management > Create and Update Pixels|
#|-|[/pixel/event/create/](https://business-api.tiktok.com/portal/docs?id=1740858807646209)|Choose either an industry or custom template to define your events.|Pixel Management > Create and Update Pixels|
#|-|[/pixel/event/update/](https://business-api.tiktok.com/portal/docs?id=1740858823774210)|Update the name of a Pixel event and the conversion value.|Pixel Management > Create and Update Pixels|
#|-|[/pixel/event/delete/](https://business-api.tiktok.com/portal/docs?id=1740858862104578)|Delete Pixel events.|Pixel Management > Create and Update Pixels|
#|-|[/pixel/instant_page/event/](https://business-api.tiktok.com/portal/docs?id=1740898639412226)|Get the supported events for Instant Pages based on your objective types and optimization goals.|Pixel Management > Create and Update Pixels|
#|-|[/pixel/event/stats/](https://business-api.tiktok.com/portal/docs?id=1740858904557570)|View statistics of an event data over a period of time.|Pixel Management > Pixel Level Reporting|
#|-|[/offline/create/](https://business-api.tiktok.com/portal/docs?id=1758427576470529)|Create a new Offline Event set.| Offline Events Management > Create/Manage Offline Events|
#|-|[/offline/update/](https://business-api.tiktok.com/portal/docs?id=1765596741157889)|Update an Offline Event set.| Offline Events Management > Create/Manage Offline Events|
#|-|[/offline/delete/](https://business-api.tiktok.com/portal/docs?id=1765596790860802)|Delete an Offline Event set.| Offline Events Management > Create/Manage Offline Events|
#|-|[/offline/get/](https://business-api.tiktok.com/portal/docs?id=1765596808589313)|Get Offline Event sets.| Offline Events Management > Read Offline Events |
#|-|[/offline/track/](https://business-api.tiktok.com/portal/docs?id=1758428013689857)| Report an offline event.| Offline Events Management > Report Offline Events One by One|
#|-|[/offline/batch/](https://business-api.tiktok.com/portal/docs?id=1758428053652482)|Report offline events in bulk.| Offline Events Management > Report Offline Events in Bulk|
#|-|[/crm/list/](https://business-api.tiktok.com/portal/docs?id=1780896521680898)|Get CRM Event Sets.| CRM Event Management > Read CRM Event Sets|
#|-|[/crm/create/](https://business-api.tiktok.com/portal/docs?id=1858830704718658)|Create a CRM Event Set within an advertiser account.| CRM Event Management > Create/Manage CRM Event Sets|
#|-|[/ctm/message_event_set/get/](https://business-api.tiktok.com/portal/docs?id=1816979158055937)|Get the message event sets for ad creation.|CTX Events Management > Create/Manage CTM event sets|
|Files|-|-|-|
#|-|[/file/temporarily/upload/](https://business-api.tiktok.com/portal/docs?id=1737720893061122)|Upload a file.|No permission needed.|
#|-|[/file/start/upload/](https://business-api.tiktok.com/portal/docs?id=1737721229896705)|Start chunked file upload.|No permission needed.|
#|-|[/file/transfer/upload/](https://business-api.tiktok.com/portal/docs?id=1737721489945601)|Transfer file chunk.|No permission needed.|
#|-|[/file/finish/upload/](https://business-api.tiktok.com/portal/docs?id=1737721642924033)|Finish chunk upload.|No permission needed.|
#|-|[/file/name/check/](https://business-api.tiktok.com/portal/docs?id=1759130033155073)|Check the names of files.|Creative Management  > File Name Check|
|GMV Max|-|-|-|
#|-|[/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177)|Get GMV Max Campaigns.|Ads Management > Campaign > Read Campaigns|
#|-|[/campaign/gmv_max/info/](https://business-api.tiktok.com/portal/docs?id=1822000968821762)|Get the details of a GMV Max Campaign.|Ads Management > Campaign > Read Campaigns|
#|-|[/campaign/gmv_max/create/](https://business-api.tiktok.com/portal/docs?id=1822000988713089)|Create a GMV Max Campaign.|Ads Management > Campaign > Create and Update Campaigns|
#|-|[/campaign/gmv_max/update/](https://business-api.tiktok.com/portal/docs?id=1822001009002497)|Update a GMV Max Campaign.|Ads Management > Campaign > Create and Update Campaigns|
#|-|[/gmv_max/bid/recommend/](https://business-api.tiktok.com/portal/docs?id=1822001024720897)|Get the recommended GMV Max ROI target and budget.|Ads Management > Campaign > Read Campaigns|
#|-|[/campaign/gmv_max/session/create/](https://business-api.tiktok.com/portal/docs?id=1835246967275522)|Create a max delivery or creative boost session.|Ads Management > GMV MAX > Session|
#|-|[/campaign/gmv_max/session/update/](https://business-api.tiktok.com/portal/docs?id=1835247009119233)|Update a max delivery or creative boost session.|Ads Management > GMV MAX > Session|
#|-|[/campaign/gmv_max/session/list/](https://business-api.tiktok.com/portal/docs?id=1835246996436162)|Get max delivery or creative boost sessions within a campaign.|Ads Management > GMV MAX > Session|
#|-|[/campaign/gmv_max/session/get/](https://business-api.tiktok.com/portal/docs?id=1835247031331842)|Get details of max delivery or creative boost sessions.|Ads Management > GMV MAX > Session|
#|-|[/campaign/gmv_max/session/delete/](https://business-api.tiktok.com/portal/docs?id=1835246983475217)|Delete a max delivery or creative boost session.|Ads Management > GMV MAX > Session|
#|-|[/campaign/gmv_max/creative/update/](https://business-api.tiktok.com/portal/docs?id=1861260625563202)|Remove or add back creatives in a GMV Max Campaign|Ads Management > GMV MAX > Identity And Video|
#|-|[/gmv_max/store/list/](https://business-api.tiktok.com/portal/docs?id=1822001044479041)|Get TikTok Shops for GMV Max Campaigns.|Ads Management > GMV MAX > Store Management|
#|-|[/gmv_max/store/shop_ad_usage_check/](https://business-api.tiktok.com/portal/docs?id=1822001084174338)|Check the availability of a TikTok Shop for Product GMV Max Campaigns.|Ads Management > GMV MAX > Store Management|
#|-|[/gmv_max/identity/get/](https://business-api.tiktok.com/portal/docs?id=1822001101474882)|Get identities for GMV Max Campaigns.|Ads Management > GMV MAX > Identity And Video|
#|-|[/gmv_max/occupied_custom_shop_ads/list/ ](https://business-api.tiktok.com/portal/docs?id=1822001136924674)|Check the occupancy of identities or products in Shopping Ads |Ads Management > GMV MAX > Store Management|
#|-|[/gmv_max/video/get/](https://business-api.tiktok.com/portal/docs?id=1822001168512129)|Get posts for a Product GMV Max Campaign.|Ads Management > GMV MAX > Identity And Video|
#|-|[/gmv_max/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs?id=1830215925061633)|Get details of videos in customized posts.|Ads Management > GMV MAX > Identity And Video|
#|-|[/gmv_max/creation/custom_anchor_video_list/create/](https://business-api.tiktok.com/portal/docs/create-shop-level-customized-tiktok-posts/v1.3)|Create shop-level customized TikTok posts.|Ads Management > GMV MAX > custom anchor|
#|-|[/gmv_max/creation/custom_anchor_video_list/get/](https://business-api.tiktok.com/portal/docs/get-customized-tiktok-posts/v1.3)|Get customized TikTok posts.|Ads Management > GMV MAX > custom anchor|
#|-|[/gmv_max/creation/custom_anchor_video_list/delete/](https://business-api.tiktok.com/portal/docs/delete-customized-tiktok-posts/v1.3)|Delete customized TikTok posts.|Ads Management > GMV MAX > custom anchor|
#|-|[/gmv_max/creation/shop_video/video_anchors/](https://business-api.tiktok.com/portal/docs/get-product-linkage-details-of-videos-in-customized-posts/v1.3)|Get product linkage details of videos in customized posts.|Ads Management > GMV MAX > custom anchor|
#|-|[/gmv_max/exclusive_authorization/get/](https://business-api.tiktok.com/portal/docs?id=1822001184635905)|Get the TikTok Shop exclusive authorization status of an ad account.|Ads Management > GMV MAX > Exclusive Authorization|
#|-|[/gmv_max/exclusive_authorization/create/](https://business-api.tiktok.com/portal/docs?id=1822001200356354)|Grant an ad account exclusive authorization for a TikTok Shop.|Ads Management > GMV MAX > Exclusive Authorization|
#|-|[/gmv_max/report/get/](https://business-api.tiktok.com/portal/docs?id=1824721673497601)|Run a GMV Max Campaign report.|Reporting > GMV MAX Report|
|Identity|-|-|-|
#|-|[/identity/create/](https://business-api.tiktok.com/portal/docs?id=1740654203526146)|Create an identity.|Creative Management > TikTok Posts Management > Create Identity|
#|-|[/identity/delete/](https://business-api.tiktok.com/portal/docs?id=1759155939236865)|Delete an identity.|Creative Management > TikTok Posts Management > Delete Identity|
#|-|[/identity/get/](https://business-api.tiktok.com/portal/docs?id=1740218420781057)|Get the identity list.|Creative Management > TikTok Posts Management > Query Identity|
#|-|[/identity/info/](https://business-api.tiktok.com/portal/docs?id=1740218453385217)|Get info about an identity.|Creative Management > TikTok Posts Management > Query Identity|
#|-|[/identity/video/get/](https://business-api.tiktok.com/portal/docs?id=1740218475032577)|Get videos under an identity.|Creative Management > TikTok Posts Management > Query Identity|
#|-|[/identity/live/get/](https://business-api.tiktok.com/portal/docs?id=1785238052792321)|Get live videos under an identity.|Creative Management > TikTok Posts Management > Query Identity|
#|-|[/identity/music/authorization/](https://business-api.tiktok.com/portal/docs?id=1740218495869954)|Get music authorization info of a video.|Creative Management > TikTok Posts Management > Query Identity|
#|-|[/identity/video/info/](https://business-api.tiktok.com/portal/docs?id=1740218522178562)|Get info about TikTok posts.|Creative Management > TikTok Posts Management > Query Identity|
|Images|-|-|-|
#|-|[/file/image/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1739067433456642)|Upload an image to the creative repository.|Creative Management > Image Management > Create and Update Images|
#|-|[/file/image/ad/update/](https://business-api.tiktok.com/portal/docs?id=1740039806891010)|Update image name.|Creative Management > Image Management > Create and Update Images|
#|-|[/file/image/ad/info/](https://business-api.tiktok.com/portal/docs?id=1740051721711618)|Get image information.|Creative Management > Image Management > Read Image Library|
#|-|[/file/image/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740052016789506)|Search for image creative in an advertising account's material library.|Creative Management > Image Management >Read Image Library|
|Leads|-|-|-|
#|-|[/page/lead/mock/create/](https://business-api.tiktok.com/portal/docs?id=1739047132614722)|Create test lead.|Lead Management > Test Leads|
#|-|[/page/lead/mock/get/](https://business-api.tiktok.com/portal/docs?id=1739048460493826)|Get test leads.|Lead Management > Test Leads|
#|-|[/page/lead/mock/delete/](https://business-api.tiktok.com/portal/docs?id=1739049313198081)|Delete test lead.|Lead Management > Test Leads|
#|-|[/page/lead/task/](https://business-api.tiktok.com/portal/docs?id=1739052936772610)|Create lead download task.|Lead Management > Test Leads|
#|-|[/page/lead/task/download/](https://business-api.tiktok.com/portal/docs?id=1739053563877378)|Download the lead data after the task completes.|Lead Management > Test Leads|
#|-|[/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489)| Get form libraries.|Creative Management > Instant Page Management|
#|-|[/page/library/transfer/](https://business-api.tiktok.com/portal/docs?id=1740029931324417)|Migrate leads to Business Center.|-|
#|-|[/page/field/get/](https://business-api.tiktok.com/portal/docs?id=1739054080455681)|You can use this endpoint to get the fields of an instant page.|Lead Management > Test Leads|
#|-|[/lead/field/get/](https://business-api.tiktok.com/portal/docs?id=1832112377641026)|Get fields of an Instant Form or direct message leads.|Lead Management > Leads Retrieval|
#|-|[/lead/get/](https://business-api.tiktok.com/portal/docs?id=1832112833212434)|Get an Instant Form lead or a direct message lead.|Lead Management > Leads Retrieval|
|Media Mix Modeling|-|-|-|
#|-|[/mmm/api/create/](https://business-api.tiktok.com/portal/docs/create-an-mmm-data-request/v1.3)|Create an MMM data request.|Reporting > MMM Data Report|
#|-|[/mmm/api/check/](https://business-api.tiktok.com/portal/docs/check-the-status-of-an-mmm-data-request/v1.3)|Check the status of an MMM data request.|Reporting > MMM Data Report|
#|-|[/mmm/api/download/](https://business-api.tiktok.com/portal/docs/obtain-the-download-url-for-mmm-data/v1.3)|Obtain the download URL for MMM data.|Reporting > MMM Data Report|
#|-|[/mmm/api/history/](https://business-api.tiktok.com/portal/docs/get-the-mmm-data-request-history/v1.3)|Get the MMM data request history.|Reporting > MMM Data Report|
|Mentions|-|-|-|
#|-|[/business/mention/video/list/](https://business-api.tiktok.com/portal/docs?id=1825107955294402)|Get top 1000 mentioned posts.|Mentions > Content|
#|-|[/business/mention/video/get/](https://business-api.tiktok.com/portal/docs?id=1831359360477185)|Get the details of a mentioned post from mentions webhook.|Mentions > Content|
#|-|[/business/mention/top_word/list/](https://business-api.tiktok.com/portal/docs?id=1825107971247170)|Get frequent hashtags used in top 1000 mentioned posts.|Mentions > Content|
#|-|[/business/mention/top_hashtag/list/](https://business-api.tiktok.com/portal/docs?id=1825107978583042)|Get frequent keywords used in top 1000 mentioned posts.|Mentions > Content|
#|-|[/business/mention/hashtag/video/list/](https://business-api.tiktok.com/portal/docs?id=1825108001123522)|Get mention content for top 1000 brand hashtag posts.|Mentions > Content|
#|-|[/business/mention/hashtag/verify/list/](https://business-api.tiktok.com/portal/docs?id=1833862470219842)|Get valid brand mention hashtags for a Business Account.|Mentions > Content|
#|-|[/business/mention/hashtag/add/](https://business-api.tiktok.com/portal/docs?id=1833831427732481)|Enable brand hashtags for a Business Account.|Mentions > Content|
#|-|[/business/mention/hashtag/manage/list/](https://business-api.tiktok.com/portal/docs?id=1833832557996034)|Get all enabled brand hashtags for a Business Account.|Mentions > Content|
#|-|[/business/mention/hashtag/remove/](https://business-api.tiktok.com/portal/docs?id=1833833401871362)|Delete an enabled brand hashtag for a Business Account.|Mentions > Content|
#|-|[/business/mention/comment/list/](https://business-api.tiktok.com/portal/docs?id=1825107985360898)|Get top 1000 comment mentions on posts.|Mentions > Comment|
#|-|[/business/mention/comment/get/](https://business-api.tiktok.com/portal/docs?id=1831358502098946)|Get the details of a comment mention from webhooks.|Mentions > Comment|
|Music|-|-|-|
#|-|[/file/music/upload/](https://business-api.tiktok.com/portal/docs?id=1740052650395650)|Upload music.|Creative Management > Music management > Upload music file|
#|-|[/file/music/get/](https://business-api.tiktok.com/portal/docs?id=1740053909509122)|Get music list.|Creative Management > Music management > Read music library|
|Negative Keywords|-|-|-|
#|-|[/search_ad/negative_keyword/get/](https://business-api.tiktok.com/portal/docs?id=1775104887052289)|Get negative keywords.|Ads Management > Search Ads > Negative Keywords|
#|-|[/search_ad/negative_keyword/add/](https://business-api.tiktok.com/portal/docs?id=1775104895291393)|Create negative keywords.|Ads Management > Search Ads > Negative Keywords|
#|-|[/search_ad/negative_keyword/update/](https://business-api.tiktok.com/portal/docs?id=1775104902283266)|Update a negative keyword.|Ads Management > Search Ads > Negative Keywords|
#|-|[/search_ad/negative_keyword/delete/](https://business-api.tiktok.com/portal/docs?id=1775104910010369)|Delete negative keywords.|Ads Management > Search Ads > Negative Keywords|
#|-|[/search_ad/negative_keyword/download/](https://business-api.tiktok.com/portal/docs?id=1775104916876290)|Download negative keywords.|Ads Management > Search Ads > Negative Keywords|
|Page|-|-|-|
#|-|[/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586)|Get the page ID.|Creative Management > Instant Page Management > Instant Page Management|
#|-|[/oauth2/access_token/tip_sdk/create/](https://business-api.tiktok.com/portal/docs?id=1820826755283969)|Create a TIP Editor SDK access token.|No permission needed.|
#|-|[/oauth2/access_token/tip_sdk/validate/](https://business-api.tiktok.com/portal/docs?id=1820826826000385)|Validate a TIP Editor SDK access token.|No permission needed.|
#|-|[/oauth2/access_token/tip_sdk/renew/](https://business-api.tiktok.com/portal/docs?id=1820826847199234)|Renew a TIP Editor SDK access token.|No permission needed.|
|Pangle|-|-|-|
#|-|[/pangle_block_list/get/](https://business-api.tiktok.com/portal/docs?id=1740039957181441)| Get Pangle block list.|Ad Account Management > Pangle Block List Management > Pangle Block List Management|
#|-|[/pangle_block_list/update/](https://business-api.tiktok.com/portal/docs?id=1740040139110466)|Update Pangle block list.|Ad Account Management > Pangle Block List Management > Pangle Block List Management|
#|-|[/pangle_audience_package/get/](https://business-api.tiktok.com/portal/docs?id=1740040177229826)|Get the audience for Pangle placement.|Ad Account Management > Pangle Audience Packages > Pangle Audience Packages|
|Playable ads|-|-|-|
#|-|[/playable/upload/](https://business-api.tiktok.com/portal/docs?id=1737730926765057)|You can use this endpoint to upload Playable Ad contents.|Creative Management > Playable Ads > Create/Manage Playable Ads|
#|-|[/playable/validate/](https://business-api.tiktok.com/portal/docs?id=1737732054892545)|Check the status of the playable.|Creative Management > Playable Ads > Read Playable Ads|
#|-|[/playable/save/](https://business-api.tiktok.com/portal/docs?id=1737732203851777)|Use this endpoint to save a playable.|Creative Management > Playable Ads > Create/Manage Playable Ads|
#|-|[/playable/get/](https://business-api.tiktok.com/portal/docs?id=1737732758495234)|Use this endpoint to get a list of playables.|Creative Management > Playable Ads > Read Playable Ads|
#|-|[/playable/delete/](https://business-api.tiktok.com/portal/docs?id=1737733368940546)|Delete a playable ad. |Creative Management > Playable Ads > Create/Manage Playable Ads|
|Reach & Frequency|-|-|-|
#|-|[/rf/inventory/estimate/](https://business-api.tiktok.com/portal/docs?id=1738235487941633)|Get inventory estimates.|Reach & Frequency > Inventory and Orders|
#|-|[/adgroup/rf/create/](https://business-api.tiktok.com/portal/docs?id=1738235338194945)|Create R&F ad group.|Reach & Frequency > Create and Update Ad Groups |
#|-|[/adgroup/rf/update/](https://business-api.tiktok.com/portal/docs?id=1738235402874882)|Update R&F ad group.|Reach & Frequency > Create and Update Ad Groups|
#|-|[/rf/order/cancel/](https://business-api.tiktok.com/portal/docs?id=1740489099367425)|Cancel R&F ad order.|Reach & Frequency > Inventory and Orders|
#|-|[/adgroup/rf/estimated/info/](https://business-api.tiktok.com/portal/docs?id=1740489551354881)|Get estimated daily cost and frequency distribution.|Reach & Frequency > Create and Update Ad Groups|
#|-|[/rf/contract/query/](https://business-api.tiktok.com/portal/docs?id=1740489855138818)|Validate R&F contracts.|Reach & Frequency > Tools|
#|-|[/rf/delivery/timezone/](https://business-api.tiktok.com/portal/docs?id=1740490147129345)|Get time zones based on country or region codes.|Reach & Frequency > Tools|
|Reporting|-|-|-|
#|-|[/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353)|Run an integrated report to get data about ad spend and performance, audience data, playable ads, or DSA. This is a `GET` request to get synchronous reporting data only.|Reporting > Consolidated Report|
#|-|[/report/task/create/](https://business-api.tiktok.com/portal/docs?id=1740302766489602)|Create a standard (synchronous) report and an asynchrounous report.|Reporting > Consolidated Report|
#|-|[/report/task/check/](https://business-api.tiktok.com/portal/docs?id=1740302781443073)|Check the status of an asynchronous report task.|Reporting > Consolidated Report|
#|-|[/report/task/download/](https://business-api.tiktok.com/portal/docs?id=1740302808815618)|Download the data of an asynchronous report.|Reporting > Consolidated Report|
#|-|[/report/task/cancel/](https://business-api.tiktok.com/portal/docs?id=1803615367145537)|Cancel an asynchronous report task|Reporting > Consolidated Report|
|Showcase|-|-|-|
#|-|[/showcase/identity/get/](https://business-api.tiktok.com/portal/docs?id=1759233549899778)|Get identities with Showcase permission under an ad account.|Ads Management > Ad > Read Ads|
#|-|[/showcase/region/get/](https://business-api.tiktok.com/portal/docs?id=1759233561597954)|Get the available regions for a Showcase via identity.|Ads Management > Ad > Read Ads|
#|-|[/showcase/product/get/](https://business-api.tiktok.com/portal/docs?id=1759233576199169)|Get the available products in a Showcase.|Ads Management > Ad > Read Ads|
|Smart Creative |-|-|-|
#|-|[/ad/aco/create/](https://business-api.tiktok.com/portal/docs?id=1739473063234626)|Create Smart Creative ads. |Ads Management > Ad > Create and Update Ads|
#|-|[/ad/aco/get/](https://business-api.tiktok.com/portal/docs?id=1739473020978177)|Get Smart Creative materials.|Ads Management > Ad > Read Ads|
#|-|[/ad/aco/update/](https://business-api.tiktok.com/portal/docs?id=1739473077112833)|Update Smart Creative materials. |Ads Management > Ad > Create and Update Ads|
#|-|[/ad/aco/material_status/update/](https://business-api.tiktok.com/portal/docs?id=1739506701165570)|Update the statuses of Smart Creative materials. |Ads Management > Ad > Create and Update Ads|
|(To be deprecated) Smart+|-|-|-|
#|-|[/campaign/spc/quota/get/](https://business-api.tiktok.com/portal/docs?id=1831564498409473){-to-be-deprecated}|Get the dynamic quota on active Smart+ Campaigns. |Ads Management > Campaign > Read Campaigns|
#|-|[/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362){-to-be-deprecated}|Create a Smart+ Campaign. |Ads Management > Campaign > Create and Update Campaigns|
#|-|[/campaign/spc/update/](https://business-api.tiktok.com/portal/docs?id=1767334250066945){-to-be-deprecated}|Update a Smart+ Campaign. |Ads Management > Campaign > Create and Update Campaigns|
#|-|[/campaign/spc/get/](https://business-api.tiktok.com/portal/docs?id=1767334299811842){-to-be-deprecated}|Get information of Smart+ Campaigns.  |Ads Management > Campaign > Read Campaigns|
#|-|[/campaign/spc/material_status/update/](https://business-api.tiktok.com/portal/docs?id=1837969177953361){-to-be-deprecated}|Disable or enable creatives in a Smart+ Campaign. |Ads Management > Campaign > Create and Update Campaigns|
#|-|[/campaign/spc/report/get/](https://business-api.tiktok.com/portal/docs?id=1776454619412481){-to-be-deprecated}|Run a Smart+ Campaign report.|Reporting > Consolidated Report|
|Upgraded Smart+|-|-|-|
#|-|[/smart_plus/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1843312818332930)|Get Upgraded Smart+ Campaigns.|Ads Management > Read Campaigns|
#|-|[/smart_plus/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1843312852800706)|Create an Upgraded Smart+ Campaign.|Ads Management > Create and Update Campaigns|
#|-|[/smart_plus/campaign/update/](https://business-api.tiktok.com/portal/docs?id=1843312876001282)|Update an Upgraded Smart+ Campaign.|Ads Management > Create and Update Campaigns|
#|-|[/smart_plus/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1843312888885314)|Update the operation statuses of Upgraded Smart+ Campaigns.|Ads Management > Create and Update Campaigns|
#|-|[/smart_plus/campaign/copy/task/create/](https://business-api.tiktok.com/portal/docs/create-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3)|Create an asynchronous copy task for an Upgraded Smart+ Campaign.|Ads Management  Create and Update Campaigns|
#|-|[/smart_plus/campaign/copy/task/check/](https://business-api.tiktok.com/portal/docs/get-the-results-of-an-asynchronous-copy-task-for-an-upgraded-smart-campaign/v1.3)|Get the results of an asynchronous copy task for an Upgraded Smart+ Campaign.|Ads Management > Read Campaigns|
#|-|[/smart_plus/adgroup/get/](https://business-api.tiktok.com/portal/docs?id=1843314879617026)|Get Upgraded Smart+ Ad Groups.|Ads Management > Ad Group > Read Ad Groups |
#|-|[/smart_plus/adgroup/create/](https://business-api.tiktok.com/portal/docs?id=1843314887930946)|Create an Upgraded Smart+ Ad Group.|Ads Management > Ad Group > Create and Update Ad Groups|
#|-|[/smart_plus/adgroup/update/](https://business-api.tiktok.com/portal/docs?id=1843314894279682)|Update an Upgraded Smart+ Ad Group.|Ads Management > Ad Group > Create and Update Ad Groups|
#|-|[/smart_plus/adgroup/status/update/](https://business-api.tiktok.com/portal/docs?id=1843314908153858)|Update the operation statuses of Upgraded Smart+ Ad Groups.|Ads Management > Ad Group > Create and Update Ad Groups|
#|-|[/smart_plus/adgroup/budget/update/](https://business-api.tiktok.com/portal/docs?id=1843314914438466)|Update the budgets of Upgraded Smart+ Ad Groups.|Ads Management > Ad Group > Create and Update Ad Groups|
#|-|[/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs?id=1843317378982914)|Get Upgraded Smart+ Ads.|Ads Management > Ad > Read Ads|
#|-|[/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522)|Create an Upgraded Smart+ Ad.|Ads Management > Ad > Create and Update Ads |
#|-|[/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921)|Update an Upgraded Smart+ Ad.|Ads Management > Ad > Create and Update Ads |
#|-|[/smart_plus/ad/status/update/](https://business-api.tiktok.com/portal/docs?id=1843317423164482)|Update the operation statuses of Upgraded Smart+ Ads.|Ads Management > Ad > Create and Update Ads |
#|-|[/smart_plus/ad/material_status/update/](https://business-api.tiktok.com/portal/docs?id=1843317433577601)|Disable or enable creatives in an Upgraded Smart+ Ad.|Ads Management > Ad > Create and Update Ads |
#|-|[/smart_plus/ad/preview/](https://business-api.tiktok.com/portal/docs?id=1843317445798914)|Preview Upgraded Smart+ Ads.|Creative Management > Creative tool|
#|-|[/smart_plus/ad/review_info/](https://business-api.tiktok.com/portal/docs?id=1843317465695233)|Get the review info of Upgraded Smart+ Ads.|Ads Management > Ad > Read Ads|
#|-|[/smart_plus/material/review_info/](https://business-api.tiktok.com/portal/docs?id=1843317474334722)|Get the review info of Upgraded Smart+ Ad creatives.|Ads Management > Ad > Read Ads|
#|-|[/smart_plus/ad/appeal/](https://business-api.tiktok.com/portal/docs?id=1843317522553106)|Appeal rejection of an Upgraded Smart+ Ad.|Ads Management > Ad > Create and Update Ads |
#|-|[/smart_plus/material_report/overview/](https://business-api.tiktok.com/portal/docs?id=1843317489576961)|Run an Upgraded Smart+ Creative Overview Report.|Reporting > Upgraded Smart+ Report|
#|-|[/smart_plus/material_report/breakdown/](https://business-api.tiktok.com/portal/docs?id=1843317510389761)|Run an Upgraded Smart+ Creative Breakdown Report.|Reporting > Upgraded Smart+ Report|
|Spark Ads |-|-|-|
#|-|[/tt_video/info/](https://business-api.tiktok.com/portal/docs?id=1738376324021250)|Get Spark Ad post.|Creative Management > TikTok Posts Management > Query TikTok Posts|
#|-|[/tt_video/authorize/](https://business-api.tiktok.com/portal/docs?id=1738376435339265)|Apply authorization code.|Creative Management > TikTok Posts Management > Authorize TikTok Posts|
#|-|[/tt_video/list/](https://business-api.tiktok.com/portal/docs?id=1738376465972226)|Get Spark Ad posts.|Creative Management > TikTok Posts Management > Authorize TikTok Posts|
#|-|[/tt_video/unbind/](https://business-api.tiktok.com/portal/docs?id=1738376509936641)|Unbind Spark Ad post.|Creative Management > TikTok Posts Management > Authorize TikTok Posts|
|Spark Ads Recommendation|-|-|-|
#|-|[/business/video/recommend/](https://business-api.tiktok.com/portal/docs?id=1825128684907522)|Get Spark Ads video recommendations for a Business Account.|Business Recommendation > Video Spark Ads Recommendation|
#|-|[/spark_ad/recommend/](https://business-api.tiktok.com/portal/docs?id=1825128700959746)|Get Spark Ads video recommendations for a TTO account.|Business Recommendation > Creator Spark Ads Recommendation|
#|-|[/business/spark_ad/create/](https://business-api.tiktok.com/portal/docs?id=1829744071179330)|Create a campaign, an ad group, and a Spark Ad in one step.|Ads Management > Campaign > Create and Update Campaigns|
|Split test|-|-|-|
#|-|[/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201)|Create a split test.|Ads Management >Split Test > Split Test|
#|-|[/split_test/update/](https://business-api.tiktok.com/portal/docs?id=1742666501911553)|Update split test time.|Ads Management >Split Test > Split Test|
#|-|[/split_test/end/](https://business-api.tiktok.com/portal/docs?id=1742666522399746)|End a split test.|Ads Management >Split Test > Split Test|
#|-|[/split_test/result/get/ ](https://business-api.tiktok.com/portal/docs?id=1742666557045761)|Get split test results.|Ads Management >Split Test > Split Test|
#|-|[/split_test/promote/](https://business-api.tiktok.com/portal/docs?id=1742666543736834)|Run the winning ad group.|Ads Management >Split Test > Split Test|
|Subscription|-|-|No permission needed.|
#|-|[/subscription/subscribe/](https://business-api.tiktok.com/portal/docs?id=1739092028876801)|Create a subscription.|-|
#|-|[/subscription/get/](https://business-api.tiktok.com/portal/docs?id=1739093832125442)|Get subscriptions.|-|
#|-|[/subscription/unsubscribe/](https://business-api.tiktok.com/portal/docs?id=1739094758789122)|Cancel subscription.|-|
|Terms|-|-|-|
#|-|[/term/get/](https://business-api.tiktok.com/portal/docs?id=1737191909315585)| Get agreements (terms) that you need to sign before you can use certain features.|Ad Account Management > Terms and Agreements  > Terms and Agreements|
#|-|[/term/confirm/](https://business-api.tiktok.com/portal/docs?id=1737192284435457)|Use this endpoint to sign the agreement for the Lead Generation Ads feature.|Ad Account Management > Terms and Agreements  > Terms and Agreements|
#|-|[/term/check/](https://business-api.tiktok.com/portal/docs?id=1737192304571394)|This interface confirms the signing status of an agreement. |Ad Account Management > Terms and Agreements  > Terms and Agreements|
|TikTok One|-|-|-|
#|-|[/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1768493902119937)|Get, renew or revoke a Creator access token. |No permission needed.|
#|-|[/tt_user/token_info/get/](https://business-api.tiktok.com/portal/docs?id=1765926674648065)|Obtain the authorized Creator permissions. |No permission needed.|
#|-|[/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146)|Get authorized TTO Creator Marketplace accounts.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/tcm/creator/status/get/](https://business-api.tiktok.com/portal/docs?id=1837799887531010)|Check TTO Creator status.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/oauth2/info/](https://business-api.tiktok.com/portal/docs?id=1831975745134593)|Get the details of a TTO Creator Marketplace account.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/creator/authorized/](https://business-api.tiktok.com/portal/docs?id=1834913529338882)|Get Authorized Account Insights.|TikTok Creator > Creator User >  Get Creator Insights|
#|-|[/tto/creator/authorized/video/list/](https://business-api.tiktok.com/portal/docs?id=1834913554657282)|Get Authorized Media Insights.|TikTok Creator > Creator Media >  Get Creator Media|
#|-|[/tto/tcm/creator/public/](https://business-api.tiktok.com/portal/docs?id=1828185358712897)|Get TTO Public Account Insights.|TikTok Creator > Creator User >  Get Creator Insights|
#|-|[/tto/tcm/creator/public/video/list/](https://business-api.tiktok.com/portal/docs?id=1828185411810305)|Get TTO Public Media Insights.|TikTok Creator > Creator Media >  Get Creator Media|
#|-|[/tto/tcm/category/label/](https://business-api.tiktok.com/portal/docs?id=1816372654109698)|Get TTO creator ranking or search labels.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/tcm/rank/](https://business-api.tiktok.com/portal/docs?id=1816378813274113)|Get top TTO creator rankings.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/tcm/creator/discover/](https://business-api.tiktok.com/portal/docs?id=1825017307843585)|Discover TTO creators.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/tcm/brand/profile/create/](https://business-api.tiktok.com/portal/docs?id=1830335387914241)|Create a Brand Profile for your TTO account.|TikTok Creator Marketplace (TCM) > Create and Update TCM Account|
#|-|[/tto/tcm/brand/profile/get/](https://business-api.tiktok.com/portal/docs?id=1830335437705217)|Get the Brand Profiles for your TTO account.|TikTok Creator Marketplace (TCM) > Create and Update TCM Account|
#|-|[/tto/tcm/campaign/create/](https://business-api.tiktok.com/portal/docs?id=1815693549459458)|Create or update a TTO Creator Marketplace campaign.|TikTok Creator Marketplace (TCM) > Create and Update TCM Account|
#|-|[/tto/tcm/campaign/update/](https://business-api.tiktok.com/portal/docs?id=1830338948169794)|Update a TTO Creator Marketplace campaign.|TikTok Creator Marketplace (TCM) > Create and Update TCM Account|
#|-|[/tto/tcm/campaign/](https://business-api.tiktok.com/portal/docs?id=1815693562720258)|Get TTO Creator Marketplace campaigns.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/tcm/campaign/link/](https://business-api.tiktok.com/portal/docs?id=1824449956282370)|Send or revoke a TTO video linking request.|TikTok Creator Marketplace (TCM) > Create and Update TCM Account|
#|-|[/tto/tcm/campaign/link/status/](https://business-api.tiktok.com/portal/docs?id=1824450033584129)|Get TTO video linking requests.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/tcm/report/](https://business-api.tiktok.com/portal/docs?id=1815693573340162)|Report on TTO Creator Marketplace videos.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tcm/tt_video/apply/](https://business-api.tiktok.com/portal/docs?id=1751098904942594)|Apply for Spark Ads authorization for a given order.|TikTok Creator Marketplace (TCM) > Send Spark Ads Invitation to Creators and Get the Status|
#|-|[/tcm/tt_video/status/](https://business-api.tiktok.com/portal/docs?id=1751098935842817)|Get the authorization status of a given order.|TikTok Creator Marketplace (TCM) > Send Spark Ads Invitation to Creators and Get the Status|
#|-|[/tto/tcm/anchor/create/](https://business-api.tiktok.com/portal/docs?id=1826564934723585)|Create a webpage anchor.|TikTok Creator Marketplace (TCM) > Create and Update TCM Account|
#|-|[/tto/tcm/anchor/get/](https://business-api.tiktok.com/portal/docs?id=1826564965840066)|Get webpage anchors.|TikTok Creator Marketplace (TCM) > Read TCM Account|
#|-|[/tto/tcm/anchor/delete/](https://business-api.tiktok.com/portal/docs?id=1826564985760770)|Delete a draft anchor.|TikTok Creator Marketplace (TCM) > Create and Update TCM Account|
#|-|[/tto/creator/campaign/join/](https://business-api.tiktok.com/portal/docs?id=1836584318657537)|Join a TTO Creator Marketplace campaign as a creator.|TikTok Creator > Creator Order > Update Creator TCM Order|
#|-|[/tto/creator/campaign/video/link/](https://business-api.tiktok.com/portal/docs?id=1836584551347201)|Link a video to a TTO Creator Marketplace campaign as a creator.|TikTok Creator > Creator Order > Update Creator TCM Order|
#|-|[/tto/creator/link/request/get/](https://business-api.tiktok.com/portal/docs?id=1837247726007298)|Get TTO video linking requests as a creator.|TikTok Creator > Creator Order > Update Creator TCM Order|
#|-|[/tto/creator/link/request/confirm/](https://business-api.tiktok.com/portal/docs?id=1837247744649345)|Approve or reject a TTO video linking request as a creator.|TikTok Creator > Creator Order > Update Creator TCM Order|
|TikTok Store|-|-|
#|-|[/store/list/](https://business-api.tiktok.com/portal/docs?id=1752267762718722)|Get available stores under an ad account.|Onsite Commerce Store|
#|-|[/store/product/get/](https://business-api.tiktok.com/portal/docs?id=1793482248880130)|Get products within a store.|Onsite Commerce Store|
|Tools|-|-|-|
#|-|[/tool/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1761236883355649)|Search for location targeting tags.|No permission needed.|
#|-|[/tool/targeting/info/](https://business-api.tiktok.com/portal/docs?id=1761237001980929)|Obtain details about location targeting tags by ID.|No permission needed.|
#|-|[/tool/region/](https://business-api.tiktok.com/portal/docs?id=1737189539571713)|Get available locations based on different settings.|No permission needed.|
#|-|[/search/region/](https://business-api.tiktok.com/portal/docs?id=1773644763581441)|Get available locations based on advertiser ID.|No permission needed.|
#|-|[/tool/language/](https://business-api.tiktok.com/portal/docs?id=1737188554152962)|Get languages.|No permission needed.|
#|-|[/targeting/search/](https://business-api.tiktok.com/portal/docs?id=1796638342793218)|Search for or list targeting categories and hashtags for interests and behaviors.|No permission needed.|
#|-|[/tool/interest_category/](https://business-api.tiktok.com/portal/docs?id=1737174348712961)|Get general interest categories.|No permission needed.|
#|-|[/tool/interest_keyword/recommend/](https://business-api.tiktok.com/portal/docs?id=1763590884474882)|Search for additional interest categories.|No permission needed.|
#|-|[/tool/interest_keyword/get/](https://business-api.tiktok.com/portal/docs?id=1763590894544897)|Get additional interest categories by ID.|No permission needed.|
#|-|[/tool/action_category/](https://business-api.tiktok.com/portal/docs?id=1737166752522241)|Get action categories.|No permission needed.|
#|-|[/tool/hashtag/recommend/](https://business-api.tiktok.com/portal/docs?id=1736271339521025)|Search for targeting hashtags.|No permission needed.|
#|-|[/tool/hashtag/get/](https://business-api.tiktok.com/portal/docs?id=1736280889167874)|Get targeting hashtags by ID.|No permission needed.|
#|-|[/tool/targeting_category/recommend/](https://business-api.tiktok.com/portal/docs?id=1736275204260866)|Get recommended interest and action categories based on historical performance data in the same industries.|No permission needed.|
#|-|[/tool/search_keyword/recommend/](https://business-api.tiktok.com/portal/docs?id=1838630375757825)|Get recommended search keywords.|No permission needed.|
#|-|[/tool/diagnosis/search/health/](https://business-api.tiktok.com/portal/docs?id=1848591212970082)|Get Search Ads Campaign Health diagnoses.|No permission needed.|
#|-|[/tool/search_keyword/keyword_idea/](https://business-api.tiktok.com/portal/docs?id=1855460985594881)|Discover new keywords.|No permission needed.|
#|-|[/tool/os_version/](https://business-api.tiktok.com/portal/docs?id=1738308662898689)|Get OS versions.|No permission needed.|
#|-|[/tool/device_model/](https://business-api.tiktok.com/portal/docs?id=1737172880570369)|Get device models.|No permission needed.|
#|-|[/tool/carrier/](https://business-api.tiktok.com/portal/docs?id=1737168013095938)|Get carriers.|No permission needed.|
#|-|[/tool/targeting/list/](https://business-api.tiktok.com/portal/docs?id=1762962378261506)|Get internet service providers.|No permission needed.|
#|-|[/tool/contextual_tag/get/](https://business-api.tiktok.com/portal/docs?id=1747747118654465)|Get available contextual tags.|No permission needed.|
#|-|[/tool/contextual_tag/info/](https://business-api.tiktok.com/portal/docs?id=1747747180830722)|Get info of contextual tags.|No permission needed.|
#|-|[/tool/content_exclusion/get/](https://business-api.tiktok.com/portal/docs?id=1769738074686465)|Get available content exclusion categories.|No permission needed.|
#|-|[/tool/content_exclusion/info/](https://business-api.tiktok.com/portal/docs?id=1769738105333826)|Get info of content exclusion categories.|No permission needed.|
#|-|[/tool/bid/recommend/](https://business-api.tiktok.com/portal/docs?id=1737107845597186)|Get a suggested bid value for your ad group based on basic campaign and ad group settings like objectives, conversion events, and locations.|No permission needed.|
#|-|[/tool/vbo_status/](https://business-api.tiktok.com/portal/docs?id=1770016073586753)|Check Value-Based Optimization eligibility.|No permission needed.|
#|-|[/tool/brand_safety/partner/authorize/status/](https://business-api.tiktok.com/portal/docs?id=1738456743621634)|Get Brand Safety partner authorization status.|No permission needed.|
#|-|[/tool/url_validate/](https://business-api.tiktok.com/portal/docs?id=1774487686007810)|Get the verification results of a URL.|No permission needed.|
#|-|[/tool/phone_region_code/](https://business-api.tiktok.com/portal/docs?id=1774488637039618)|Get region calling codes and region codes for phone numbers.|No permission needed.|
#|-|[/tool/timezone/](https://business-api.tiktok.com/portal/docs?id=1738455961470977)|Get time zones.|No permission needed.|
#|-|[/tool/open_url/](https://business-api.tiktok.com/portal/docs?id=1738457335681026)|Get TikTok in-app links.|No permission needed.|
#|-|[/campaign_label/get/](https://business-api.tiktok.com/portal/docs?id=1851286489283585)|Get the campaign labels of an ad account.|Ads Management > Campaign Label (Management) > Read Campaign Labels|
#|-|[/minis/get/](https://business-api.tiktok.com/portal/docs?id=1853450329535490)|Get the TikTok Minis within an ad account.|Minis Management > Read > Get Minis|
#|-|[/identity/native_series/get/](https://business-api.tiktok.com/portal/docs/get-the-available-tiktok-series-within-an-ad-account/v1.3)|Get the available TikTok Series within an ad account.|Creative management > TikTok Posts Management > Query identity|
|User|-|-|No permission needed.|
#|-|[/user/info/](https://business-api.tiktok.com/portal/docs?id=1739665513181185)|Get authorized user by access token.|-|
|Videos|-|-|-|
#|-|[/file/video/ad/upload/](https://business-api.tiktok.com/portal/docs?id=1737587322856449)|Upload a video.|Creative Management > Video Management > Create and Update Videos|
#|-|[/file/video/ad/update/](https://business-api.tiktok.com/portal/docs?id=1740049987749890)|Update video name.|Creative Management > Video Management > Create and Update Videos|
#|-|[/file/video/ad/info/](https://business-api.tiktok.com/portal/docs?id=1740050161973250)|Get video information.|Creative Management > Video Management > Read Video Library|
#|-|[/file/video/ad/search/](https://business-api.tiktok.com/portal/docs?id=1740050472224769)|Search for a video.|Creative Management > Video Management > Read Video Library|
#|-|[/file/video/suggestcover/](https://business-api.tiktok.com/portal/docs?id=1740051189071873)|Get suggested thumbnails.|Creative Management > Video Management > Generate Thumbnails for Videos|
|Welcome Messages|-|-|-|
#|-|[/creative/auto_message/create/](https://business-api.tiktok.com/portal/docs?id=1822106113771521)|Create a welcome message within an ad account.|Creative Management > TikTok Message Management|
#|-|[/creative/auto_message/get/](https://business-api.tiktok.com/portal/docs?id=1822106498804738)|Get welcome messages within an ad account.|Creative Management > TikTok Message Management|
```

