---
title: "FAQs"
doc_id: 1746998681953281
path: "Marketing API / Audience Management / Guides / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

This article summarizes FAQs about the Audience Management API.

1. **How do I upload audience files?**

You can create all audiences via API or TikTok Ads Manager. You can use a combination of both to create an audience if you'd like to. You can create audiences in TikTok Ads Manager and add additional audience files via API.  You can upload multiple files consecutively in both routes. To learn about how to create audiences in TikTok Ads Manager, see [Customer File](https://ads.tiktok.com/help/article?aid=9609). With API, you can [Upload audience files](https://ads.tiktok.com/marketing_api/docs?id=1739940567842818) and [Create audience by file](https://ads.tiktok.com/marketing_api/docs?id=1739940570793985).

2. **What is the max file size?**

The max file size is 250MB for API and 1GB for TikTok Ads Manager. 

3. **What are the accepted encryptions for audience files?**

You can find info about accepted encryption types in [Enumeration - Encryption Types](https://ads.tiktok.com/marketing_api/docs?id=1737174886619138). The content of the file needs to be in all uppercase or lowercase before hashing. 

4. **What is the QPS limit?**

- For Customer File Creation API([/dmp/custom_audience/create/](https://ads.tiktok.com/marketing_api/docs?id=1739940570793985)), you can create up to a max of 400 audiences for an advertiser account. Existing audiences need to be deleted before creating new ones if the advertiser account exceeds this limit. This limit is at the advertiser level. 
- For Customer File Update API([/dmp/custom_audience/update/](https://ads.tiktok.com/marketing_api/docs?id=1739940572667906)), there is a daily quota of 24 audience update operations (`Append`, `Remove`, `Replace`). Please wait for current operations to be completed on an audience before re-trying. This limit is at the audience level. Only one file-based audience `Replace` operation is allowed per day. 
- For details about our general rate limits, see [Rate limits](https://ads.tiktok.com/marketing_api/docs?id=1740029171730433). 

> <span style="color: DodgerBlue">**Note**</span><br>When uploading via TikTok Ads Manager, you have to wait for the first file in an audience to be completely processed. Subsequent files can be uploaded without waiting.

5. **How can I make test API calls? Is there a sandbox environment?**

Yes, you can access our sandbox environment after you create a developer app. To learn about how to create and use sandbox accounts, see [Sandbox accounts](https://ads.tiktok.com/marketing_api/docs?id=1738855331457026).

6. **How long after audience creation is it available?**

Newly created audiences require about an hour to be analyzed and processed before they become active for use in ad campaigns or for modification. 

7. **What is the expiration policy for audiences?**

Audiences expire after 1 year if they're not used or refreshed. For details, see [Audience Expiration Policy](https://ads.tiktok.com/help/article?aid=10001592). You can also use the values of the `is_expiring` and `expired_time` fields (in the response of [/dmp/custom_audience/get/](https://ads.tiktok.com/marketing_api/docs?id=1739940507792385)) to determine whether an audience has expired or when it will expire.

