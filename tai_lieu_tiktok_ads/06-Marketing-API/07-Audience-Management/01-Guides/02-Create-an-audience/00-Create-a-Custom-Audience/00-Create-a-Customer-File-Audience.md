---
title: "Create a Customer File Audience"
doc_id: 1747012327159809
path: "Marketing API / Audience Management / Guides / Create an audience / Create a Custom Audience / Create a Customer File Audience"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a Customer File Audience

This article introduces how to create **Customer File Audiences** using Customer File API and Streaming API. 

# Customer File API
1. Use [/dmp/custom_audience/file/upload/](https://ads.tiktok.com/marketing_api/docs?id=1739940567842818) to upload data files and obtain a globally unique `file_path`.

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


2. Use [/dmp/custom_audience/create/](https://ads.tiktok.com/marketing_api/docs?id=1739940570793985) to create audience by files. You must pass in the `file_path` obtained in the previous step. If your call is successful, the response will return a `custom_audience_id`, which can be used to update, get or delete the audience.

# Streaming API
1. Use [/segment/audience/](https://ads.tiktok.com/marketing_api/docs?id=1739940583739393) to create audience segments. You will get a unique audience ID (`audience_id`) in the response. 
2. Use `audience_id` in [/segment/mapping/](https://ads.tiktok.com/marketing_api/docs?id=1739940585975809) to make batch segment mappings for different ad accounts under the same Business Account.
