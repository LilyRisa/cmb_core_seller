---
title: "About Streaming API"
doc_id: 1739566528222210
path: "Marketing API / Audience Management / Guides / About Streaming API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# About Streaming API

Streaming API is our real-time batch update tool designed to support high throughput needs of data partners and certain high-volume advertisers. For partners that enable multiple advertisers to activate their audiences on TikTok, we recommend them to use Streaming API.

> <span style="color: DodgerBlue">**Note**</span><br>Streaming API is reserved for audience activation partners or certain high-volume advertisers. For your audience management needs, please use Customer File API.<br>High-volume advertisers are defined as:<ul><li> Advertisers that upload at least 5 million IDs to ad accounts.</li><li> Need a real-time data refresh to activate audiences & launch campaigns.</li><li> Have at least a USD $10k in monthly spend.</li></ul>Please connect with your account manager if you meet the requirements for a high-volume advertiser.

# How to request access to Streaming API
1. Create an app and request API access for "**Read Custom Audiences**" under Audience Management.
2. Once approved, go within the app > Allowlist Management > Select "**Apply**" under Allowlist Management
3. Under App feature request, select "**Streaming API**":
<br><image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f8ef4c80a36135da1d36597f76ed951f" width="40%" style="color:#808080"/>
4. Once approved, you will see Streaming API under "Allowlist features" in the Allowlist Management section.


# Comparing Customer File API and Streaming API
You can find the differences between Customer File API and Streaming API in the table below.

``` xtable
|{20%} | <strong>Customer File API</strong>{40%}  | <strong> Streaming API</strong>{40%}  |
| --- | --- | --- |
| <strong> Type</strong> | File Upload API - Upload entire pre-prepared files through the API | Streaming API - Make real-time batch update or send "stream" of data through the API |
| <strong> Who should use this API</strong>  | <ul><li>Direct Advertisers and Agencies looking to upload their own Customer File to create a Custom Audience</li><li>Companies leveraging first-party data</li></ul> | <ul><li>Data partners supporting multiple advertisers to use the high throughput capacity of the API to make batch requests</li><li>High-volume advertisers</li></ul> |
|<strong> When should you use this API</strong> | <ul><li>When you have low throughput needs (file-based)</li><li>When you need the ability to have a file based request where you can upload an entire pre-prepared file and send it to the server</li></ul> | <ul><li>When you have high throughput needs </li><li>When you need the ability to make a batch request to the server by sending a stream of data when the audience information is ready |
| <strong> Target Audience</strong>  | First-party audience targeting | First-party and Third-party audience targeting |
| <strong> Throughput</strong>  | Low (file-based, manual upload) | High |
|<strong> Rule-based Audiences Support</strong>  | Yes | No |
| <strong> Supported ID types</strong>  | <ul><li>MAID (SHA 256, MD5) <br>Apple: IDFA <br> Google: GAID, AAID</li><li>Email, Phone (SHA 256)<br> PHONE_SHA256<br>EMAIL_SHA256 | <ul><li>MAID (SHA 256, MD5)<br>Apple: IDFA<br>Google: GAID, AAID</li><li>Email, Phone (SHA 256)<br>PHONE_SHA256<br>EMAIL_SHA256 |
|<strong>API Query Limit</strong>  | File upload API ([/dmp/custom_audience/file/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739940567842818)) - Rate Limit is 1 QPS per advertiser account with max 250MB per file.<br>Audience Creation API ([/dmp/custom_audience/create/](https://ads.tiktok.com/marketing_api/docs?id=1739940570793985)) - Rate Limit is 1 QPS per advertiser account.<br>Audience Update API ([/dmp/custom_audience/update/](https://ads.tiktok.com/marketing_api/docs?id=1739940572667906)) - Rate Limit is 1 QPS per advertiser account. | Segment Creation API ([/segment/audience/](https://ads.tiktok.com/marketing_api/docs?id=1739940583739393)) - Rate Limit is 1 QPS per advertiser account.<br>Segment Mapping API ([/segment/mapping/](https://ads.tiktok.com/marketing_api/docs?id=1739940585975809)) <ul><li> Rate Limit is 5000 QPS per advertiser account. </li><li>You can specify a maximum of 10 audience IDs in one request.</li><li>You can specify a maximum of 1 billion IDs (user or device IDs) per day per advertiser account.</li></ul>|
| <strong> No. of Audiences maximum per account</strong> | 400 | 400 |
| <strong> SLA</strong> | Upon making the API call, you'll immediately receive a success/error response. In case of success, our system will process the IDs asynchronously, for which the SLA is 48 hours. | Upon making the API call, you'll immediately receive a success/error response. In case of success, our system will process the IDs asynchronously, for which the SLA is 48 hours. |
```
  
## Customer File API workflow
1. Use [/dmp/custom_audience/file/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739940567842818) to upload your Customer File. You will get a `file_path` in the response, which will be used in the next step.
  
You can upload a file with one or multiple ID types. To upload a file with multiple ID types, `calculate_type` should be `MULTIPLE_TYPES` and headers are required. The supported headers are "MAID", "phone_SHA256", "email_SHA256", "MAID_SHA256", "MAID_MD5". All headers are case insensitive, and the number of fields specified in each data row should match the number of headers. 

  
<strong>Sample file with one ID type: </strong>
<table>
<tbody>
 <tr>
   <td>9f5326721cb782c</td>
 </tr>
 <tr>
   <td>9f5326721cb782c</td>
 </tr>
</tbody>
</table>
  


<strong>Sample file with multiple ID types: </strong>
  
|email_SHA256|phone_SHA256|MAID|
|---|---|---|
|9f5326721cb782c|9f5326721cb782c|9f5326721cb782c|
|9f5326721cb782c|9f5326721cb782c|9f5326721cb782c|

  
2. Use [/dmp/custom_audience/create/](https://ads.tiktok.com/marketing_api/docs?id=1739940570793985) to create audience by file. You will get a unique Custom Audience ID (`custom_audience_id`) in the response. 
3. Use `custom_audience_id` in [/dmp/custom_audience/update/](https://ads.tiktok.com/marketing_api/docs?id=1739940572667906) to make batch audience update individually for different Custom Audiences.
  
<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b22e1f728b1ae3f8624b1d59bbbd18ec" width="60%" />
  

## Streaming API workflow
1. Use [/segment/audience/](https://ads.tiktok.com/marketing_api/docs?id=1739940583739393) to create audience segments. You will get a unique audience ID (`audience_id`) in the response. 
2. Use `audience_id` in [/segment/mapping/](https://ads.tiktok.com/marketing_api/docs?id=1739940585975809) to make batch segment mappings for different ad accounts under the same Business Account.
  
  <img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/13145072b4c7e4a7be7eea60723db7d1" width="60%" />

3. If you want to delete segment mappings, use [/segment/mapping/](https://ads.tiktok.com/marketing_api/docs?id=1739940585975809) and follow the procedures below:
  - Set `action` as `delete`.
  - Pass in `data` when you want to delete single ID mapping (one audience ID per user or device) or `batch_data` when you want to delete multiple ID mapping (multiple audience IDs per user or device).
 
4. If you want to delete an audience, follow the following steps. Note that audiences need to be disconnected from ad groups before they can be deleted. 
    1. Use [/dmp/custom_audience/apply/log/](https://business-api.tiktok.com/portal/docs?id=1758615737388033) to check which ad groups are associated with the audience you want to delete. Provide `advertiser_id` and `custom_audience_ids` as parameters and get `adgroup_id` in the response.
    2. Use [/dmp/custom_audience/apply/](https://business-api.tiktok.com/portal/docs?id=1758615097524225) to disconnect the audience from the ad group. Pass `advertiser_id`, `custom_audience_id` and `adgroup_ids`, and set `action_mode` to `disconnect`.
    3. After disconnecting, use [/segment/audience/](https://business-api.tiktok.com/portal/docs?id=1739940583739393) to delete the audience.  Pass `advertiser_id` and `delete_audience_id`, and set `action` to `delete`.
 

## Important Notes for Streaming API
### Multiple Advertisers Associated with a Business Account
- A Business Account can have multiple advertisers that are associated with it. For each business account, you only need to receive authorization token once to access data associated with all its advertisers.
- To create audience segments for multiple advertisers that belong to the same Business Account, you can use the same authorization token requested from their Business Account for any advertisers.

### ID Type Supported
The current Streaming API supports multiple ID types by default. 


  
