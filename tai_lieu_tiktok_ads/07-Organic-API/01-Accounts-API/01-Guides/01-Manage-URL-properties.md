---
title: "Manage URL properties"
doc_id: 1769324038780930
path: "Organic API / Accounts API / Guides / Manage URL properties"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage URL properties

Verifying ownership of video URLs is an essential step to comply with TikTok's policies around copyrighted content and user safety. This guide will walk you through the process of adding and verifying URL properties, so that you can successfully publish a public video to your owned TikTok account using the [/business/video/publish/](https://ads.tiktok.com/marketing_api/docs?id=1762228496095234) endpoint. 

Before calling `/business/video/publish/`, it's essential to verify that the video URL you plan to pass to the `video_url` parameter is from an owned, verified URL property. This means that you need to have a website or other online platform where you can upload and host the video, and then provide the URL of that video to TikTok when you publish it.

> <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>Starting from November 16th, 2023, you can no longer publish videos using unverified video URLs. To ensure a smooth API integration, we recommend you verify ownership of URL properties as soon as possible.</li><li>After November 16th, 2023, if you want to publish a test video through [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) using a test video URL that doesn't require verification, use the [test URL](https://sf16-va.tiktokcdn.com/obj/eden-va2/uvpapzpbxjH-aulauvJ-WV[[/ljhwZthlaukjlkulzlp/3min.mp4) `https://sf16-va.tiktokcdn.com/obj/eden-va2/uvpapzpbxjH-aulauvJ-WV[[/ljhwZthlaukjlkulzlp/3min.mp4`.</li><li> Note that if you are a developer or advertiser who used the [/business/video/publish/](https://business-api.tiktok.com/portal/docs?id=1762228496095234) endpoint to publish videos prior to April 7th, 2023, we have automatically set the corresponding domains of video URLs specified through the `video_url` parameter to the "verified" status. Therefore, we recommend that you use the [/business/property/list/](https://business-api.tiktok.com/portal/docs?id=1769325368603650) endpoint to confirm whether the domains you utilize for hosting videos have been automatically verified.</li></ul>



# URL property types and ownership verification rules

TikTok offers two types of URL properties that can be verified: domains and URL prefixes. Using video URLs from verified domains or URL prefixes ensures that your videos are legitimate and comply with TikTok's policies.

## Domain

A domain is the name of a website that you own or control. For example, if you own the website `www.example.com`, then `example.com` is the domain.

### Verification rule

To verify a domain, you need to authenticate your ownership of the domain by adding a TXT record of the domain in the DNS configuration. This verification method is for domain owners who own a domain.
 
Once your domain is verified, you can use any video URL that comes from that domain to publish videos to your TikTok account using the `/business/video/publish/` endpoint. For instance, if you have verified the domain `video.example.com`, then URLs like `https://public.video.example.com/tiktok/example.mp4` are considered verified, while URLs like `https://example.com/video/example.mp4` are still considered unverified.

 ## URL prefix

A URL prefix is the beginning part of a URL that identifies a specific section of a website. For example, if you do not own the website `www.example.com` , but own a section `www.example.com/videos` to which you can upload files, then `https://www.example.com/videos/` is the URL prefix.
 
The format for a URL prefix is: `https://` + host (must be a domain) + path + `/`. For instance, in the URL prefix `https://www.example.com/folder/` , the host is the domain `www.example.com` and the path is `/folder`. Note that redirections are not followed. URLs that return HTTP 3xx are considered invalid.

 ###  Verification rule

To verify a URL prefix, you need to upload a signature file to the page that corresponds to the URL prefix.
 
Once your URL prefix is verified, you can use any video URL that begins with that prefix to publish videos to your TikTok account using the `/business/video/publish/` endpoint. For instance, if you have already verified the URL prefix `https://www.example.com/folder/user/`, then URLs like `https://www.example.com/folder/user/public/example.mp4` are considered verified, while URLs like `https://www.example.com/folder/public/user/example.mp4` are still considered unverified.

 # Steps

 This section outlines the steps for adding and verifying URL properties.
 1. Add URL properties to an ad account using [/business/property/add/](https://ads.tiktok.com/marketing_api/docs?id=1769325280876545).
 Depending on the `property_type` you specify, the response will include the following parameters:
- If the `property_type` is domain, the response will include the parameter `signature` alone.
- If the `property_type` is URL prefix, the response will include the parameters `signature` and `file_name`.
 2. Verify ownership of the URL properties.
 Use one of the following methods, depending on the `property_type` specified:        
- To verify a domain that has been added to an ad account, add a TXT record of the domain in the DNS configuration. The content of the added TXT record should be the signature string (the value of the parameter `signature` obtained from Step 1). To find out how to add a new DNS TXT record, refer to the article [here](https://www.domain.com/help/article/dns-management-how-to-update-txt-spf-records).
- To verify a URL prefix that has been added to an ad account, upload a verification file under the URL prefix. The name of the verification file should match the signature 
file name (the value of the parameter `file_name` obtained from Step 1), and the content should be the signature string (the value of the parameter `signature` obtained from 
Step 1).
> <span style="color:DodgerBlue">**Note**</span><br>
> When uploading your verification file to verify ownership of a URL prefix, ensure that the file is publicly accessible.
<br><br>If the name of the uploaded file has an additional file extension of ".txt", remove it so that the file name matches the required format with only one ".txt" file extension.

<p align="center">
  <img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6db6f93524a16b6c88866dc6f12933a8" alt="Sample File for Verifying Ownership of a URL Prefix" title="Sample File for Verifying Ownership of a URL Prefix" width="60%" style="color:#808080"/>
  <br>Sample File for Verifying Ownership of a URL Prefix
</p>

 3. Check the result of the ownership verification for the URL property using [/business/property/verify/](https://ads.tiktok.com/marketing_api/docs?id=1769325308162050).
- If the ownership verification succeeds, the returned `property_status` will be 1 (verified).
- If the DNS TXT record is not updated correctly, or if the file is not uploaded correctly in Step 2, an error will occur. In this case, you need to go back to Step 2 to retry.

> <span style="color:DodgerBlue">**Note**</span><br>
> If you no longer own or control the domain or URL prefix associated with the verified ownership, you may want to use [/business/property/delete/](https://ads.tiktok.com/marketing_api/docs?id=1769325351919617) to delete the verified ownership of a URL property.


# Next Steps
 - [Publish a public video to an owned account](https://ads.tiktok.com/marketing_api/docs?id=1762228496095234)

# API reference
Use these endpoints to manage URL properties.
- [Add a URL property to an ad account](https://ads.tiktok.com/marketing_api/docs?id=1769325280876545) <br>
- [Check the URL property verification result](https://ads.tiktok.com/marketing_api/docs?id=1769325308162050) <br>
- [Delete the verified ownership of a URL property](https://ads.tiktok.com/marketing_api/docs?id=1769325351919617) <br>
- [Get the list of added URL properties under an ad account](https://ads.tiktok.com/marketing_api/docs?id=1769325368603650) <br>

