---
title: "Return codes"
doc_id: 1737172488964097
path: "Appendix / Return codes"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Return codes

The TikTok API for Business return codes are unique status codes designed specifically to capture the status of your API call and provide information about the reason for failure if the call is unsuccessful. 

These return codes take precedence over HTTP status codes in determining whether a call to a TikTok API for Business endpoint is successful. For instance, a response with an HTTP status code of `200` along with the return code `40002` indicates an unsuccessful call, because the return code `40002` belongs to the return codes for unsuccessful calls. To learn more about the recommended actions for different HTTP Status Codes, refer to [HTTP Status Codes]( https://ads.tiktok.com/marketing_api/docs?id=1749734684478466).   

The following tables introduce the recommended error handling methods, example error messages, and main causes for common error codes. However, if you find it challenging to handle errors independently and need customer support, submit a ticket on [Developer Ticket Platform](https://ads.tiktok.com/athena/requester/boards?identify_key=b25033ef7782d41050cbac74f751d547e9d8ad35a39a541a0f0efbbd51fe722f).   


## Return codes for successful calls

```xtable
|Code{30%}|Description{70%}|
|---|---|
|0|Successful|
|20001|Partially successful.|
```

## Return codes for unsuccessful calls

>  <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>Return codes starting with 5 (for instance, 51003) indicate service errors and you can refer to the response message for details.</li><li>The `404 page not found` HTTP error might occur in the following scenarios:<ul><li>An incorrect API method is used. For example, if the endpoint requires a GET method, but you use a POST method instead.</li><li>An incorrect API path is used. For example, if the correct endpoint path is `/tt_user/oauth2/refresh_token/`, but you make a call to `/tt_user/oauth2/refresh_token` without including the trailing backslash (`/`).</li></ul></li></ul>

### General  
```xtable
|Code{30%}|Description{70%}|
|---|---|
| 40000 | The parameters provided are invalid. <br><strong>Error handling</strong>: Refer to the API documentation for the correct usage of the parameters and try again. |
| 40001 | No permission to perform the related operation. <br><strong>Example error message</strong>: `You don't have permission to operate this catalog:&#123;catalog_id&#125;.` <br><strong>Error handling</strong>: Ensure that you have the necessary permissions and try again. To learn about different levels of permission scope, see [Permission scope](https://ads.tiktok.com/marketing_api/docs?id=1753986142651394). |
| 40002 | Parameter error. Please see error message for details. <br><strong>Common causes</strong>: <ul><li> missing required fields.</li><li> incorrect format of field values.</li><li> unsupported field values or fields.</li><li> incompatibility between passed fields or field values.</li><li>invalid data accuracy.</li><li>invalid number.</li><li>audience. <ul><li><strong>Example error message</strong>: `Audience is not editable, please check audience status.`</li><li><strong>Common causes</strong>: 1. The audience was created with an audience size of 0 (less than 1000 matches were found). 2. The audience is currently being updated or under processing. </li><li><strong>Error handling</strong>:Ensure adequate audience size, check the audience status and try again.</li></ul></li></ul><br><strong>Example error message</strong>: `Time format should be %Y-%m-%dT%H:%M. Please try again.` <br><strong>Error handling</strong>: Refer to the API documentation for the correct usage of the parameters and try again. |
| 40006 |The specified API version is incompatible with the provided parameters. <br><strong>Common causes</strong>: <ul><li> using a product that is not supported in the specified API version.</li><li> using an API version that doesn't exist. </li></ul><strong>Example error message</strong>: `The old API version does not support DPA ad.`<br><strong>Error handling</strong>: Ensure that you are using the current API version for the provided parameters and try again.. |
| 40010 |The domain is not supported in the current version of API. <br><strong>Error handling</strong>: Ensure that the base_url in the request URL is the correct one (https://business-api.tiktok.com/open_api) and try again. To find out the request URL format, refer to [API Reference-Request URL format](https://ads.tiktok.com/marketing_api/docs?id=1735713875563521#item-link-Request%20URL%20format). |
|40051 | Invalid API version. <br><strong>Error handling</strong>: Ensure that you are using the correct API version and try again. |
|40007 | The operation/object does not exist.<br><strong>Common causes</strong>:<ul><li> specifying an object (campaign, ad group, ad, etc. ) that doesn't exist. </li><li> specifying a deprecated URL or a file that doesn't exist.</li></ul><strong>Example error message</strong>: `The ad {} does not exist. ` <br><strong>Error handling</strong>: Ensure that the object you are operating on exists and validate any URLs used, then try again. |
|40008 | The interface (endpoint) has not been implemented. <br><strong>Common causes</strong>: <ul><li> using an endpoint that does not exist. </li><li> using an unsupported endpoint in sandbox environment.</li></ul><strong>Error handling</strong>: Ensure that the endpoint path is correct and try again. When testing in the sandbox environment, only use supported endpoints. Refer to [Sandbox accounts - Endpoints supported in sandbox accounts](https://business-api.tiktok.com/portal/docs?id=1738855331457026#item-link-Endpoints%20supported%20in%20sandbox%20accounts) to learn about the supported endpoints. |
| 40009 | The parameter or method is not supported in sandbox. <br><strong>Example error message</strong>: `Unsupported data_level in sandbox environment: %s. ` <br><strong>Error handling</strong>: Ensure the parameter values supported in the sandbox environment and try again. |
| 40011 | Too many IDs are provided in a single request. <br><strong>Error handling</strong>: Reduce the total number of IDs that is queried in a request, or divide the IDs into batches to be queried in multiple requests, then try again. |
| 40013 |Sandbox account does not exist. <br><strong>Error handling</strong>: Refer to [Sandbox accounts](https://ads.tiktok.com/marketing_api/docs?id=1738855331457026) to learn about how to set up your sandbox account and try again. |
| 40014 |Requested functionality, operation, or endpoint is not supported in the sandbox environment.<br><strong>Error handling</strong>: Refer to [Sandbox accounts](https://ads.tiktok.com/marketing_api/docs?id=1738855331457026) for the features and endpoints supported in the sandbox environment. If the functionality, operation, or endpoint is not supported in the sandbox environment, you may need to test it in the production environment. |
| 40050 |Duplicated requests have been sent. |
| 40052 |ACO material already exists. <br><strong>Error handling</strong>: Refer to Automated Creative Optimization and Create ACO ads to find out an introduction to Automated Creative Optimization, and a step-by-step guide on how to create ACO ads. |
|40053 | The Video ID is invalid. <br><strong>Error handling</strong>: Ensure that you are using a valid video ID and try again. To obtain a valid video ID, you can use [/file/video/ad/info/](https://ads.tiktok.com/marketing_api/docs?id=1740050161973250) or [/file/video/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740050472224769). |
```
### Rate limit or quota related 
```xtable
|Code{30%}|Description{70%}|
|---|---|---|
| 40016 |Requests made too frequently.<br><strong>Common causes</strong>: The rate limit for the used endpoint at the developer application level has been reached, thus your request is throttled. <br><strong>Error handling</strong>: Refer to [Rate limits](https://ads.tiktok.com/marketing_api/docs?id=1740029171730433) to learn about global and endpoint-specific rate limits. |
| 40100 |Requests made too frequently. <br><strong>Common causes</strong>: The rate limit for the endpoint at the developer application level has been reached, thus your request is throttled.<br><strong>Error handling</strong>: Refer to [Rate limits](https://ads.tiktok.com/marketing_api/docs?id=1740029171730433) to learn about global and endpoint-specific rate limits. |
| 40133 |Requests made too frequently. <br><strong>Common causes</strong>: The rate limit for the used endpoint at the advertiser level has been reached, thus your request is throttled.<br><strong>Example error message</strong>: `Advertiser (&#123;ID&#125;)'s QPS (&#123;current QPS&#125;) reaches QPS limit &#123;QPS limit&#125; for current path, request is temporarily restricted.`<br><strong>Error handling</strong>: Lower the number of calls that you make through the advertiser account to the API endpoint per second. |
| 40502 |The maximum ad limit has been reached. <br><strong>Error handling</strong>: Try creating an ad in another ad group or campaign, or deleting existing ads. |
| 40132 |Requests made too frequently for a certain field value.<br><strong>Common causes</strong>: The `pixel_code` (pixel) passed in [/pixel/track/](https://ads.tiktok.com/marketing_api/docs?id=1740858531237890) or [/pixel/batch/](https://ads.tiktok.com/marketing_api/docs?id=1740858565852225) has been denied because <ul><li>you have used the pixel in too many calls, resulting in a QPS limit being imposed on the `pixel_code`, or </li><li>your access token may have been leaked and used for malicious attacks. </li></ul> <strong>Example error message</strong>: `The request QPS is limited by pixel_code's value.`<br><strong>Error handling</strong>: Retry with a new field value for the field mentioned in the error message, or submit a ticket on [Developer Ticket Platform](https://ads.tiktok.com/athena/requester/boards?identify_key=b25033ef7782d41050cbac74f751d547e9d8ad35a39a541a0f0efbbd51fe722f). |
```


### Authentication related
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
| 40110| Invalid authorization code. <br><strong>Common causes</strong>: The authorization code is cancelled, is already used or has expired. <br><strong>Example error message</strong>: `The auth_code is canceled. Please re-authorize and try again with a new auth_code.`  <br><strong>Error handling</strong>: Use a valid authorization code and try again. <br> To learn about how to obtain the authorization code for Marketing API, refer to [Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738373141733378). <br>To learn about how to obtain the authorization code for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738083939371009) and [Creator Marketplace-Get authorization from creators](https://ads.tiktok.com/marketing_api/docs?id=1740027719016450) respectively. |
| 40101| Invalid parameter values for authentication. <br><strong>Common causes</strong>: <ul><li> Secret and app ID do not match.</li><li> The authorization code is invalid.</li></ul><strong>Example error message</strong>: `Invalid auth_code. Not able to get long-term access_token with an old auth_code. Please retry with the old auth_code. `<br><strong>Error handling</strong>: Ensure that you are using valid values for authentication and try again. <br>To learn about how to generate a long-term access token for Marketing API, refer to [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). <br>To learn about how to generate an access token for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738084387220481) and [Creator Marketplace-Get authorization from creators](https://ads.tiktok.com/marketing_api/docs?id=1740027719016450) respectively.|
| 40102|The access token has expired. The interface (endpoint) needs to be called with the latest access token. <br><strong>Error handling</strong>: Use a valid access token and try again. <br>To learn about how to generate a long-term access token for Marketing API, refer to [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). <br>To learn about how to generate an access token for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738084387220481) and [Creator Marketplace-Get authorization from creators](https://ads.tiktok.com/marketing_api/docs?id=1740027719016450) respectively.|
| 40104|The access token is empty. <br><strong>Error handling</strong>: Use a valid access token and try again. <br>To learn about how to generate a long-term access token for Marketing API, refer to [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). <br>To learn about how to generate an access token for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738084387220481) and [Creator Marketplace-Get authorization from creators](https://ads.tiktok.com/marketing_api/docs?id=1740027719016450) respectively.|
| 40105|Invalid or incorrect access token. <br><strong>Error handling</strong>: Use a valid access token and try again. <br>To learn about how to generate a long-term access token for Marketing API, refer to [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). <br>To learn about how to generate an access token for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738084387220481) and [Creator Marketplace-Get authorization from creators](https://ads.tiktok.com/marketing_api/docs?id=1740027719016450) respectively.|
| 40106|The core user is invalid. <br><strong>Error handling</strong>: Ensure that you are using a valid access token (`Access-Token`) for the specified advertiser ID (`advertiser_id`) and try again. To learn about how to generate a long-term access token for Marketing API, refer to [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
| 40103|The refresh token has expired. <br><strong>Error handling</strong>: Use a valid refresh token and try again. <br> To obtain a valid refresh token, request the user to re-authorize their application via the user authorization workflow. To learn about how to generate a new access token and a new refresh token for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738084387220481) and [Creator Marketplace-Get authorization from creators](https://ads.tiktok.com/marketing_api/docs?id=1740027719016450) respectively. |
| 40107|Invalid or incorrect refresh token.  <br><strong>Common causes</strong>:<ul><li> using a refresh token to refresh a long-time access token for Marketing API.</li><li> using an invalid refresh token. </li></ul><strong>Error handling</strong>: Use a valid refresh token to refresh only the access token for Accounts API or TikTok Creator Marketplace API. To learn about the differences between the access token expiry for Marketing API and that for Accounts API or TikTok Creator Marketplace API, refer to [FAQs](https://ads.tiktok.com/marketing_api/docs?id=1766042058952706). |
| 40108|Invalid authorization type. <br><strong>Example error message</strong>:` Invalid value for grant_type: &#123;grant_type&#125; is not supported. `<br><strong>Error handling</strong>: Use a valid value for `grant_type` and try again. To learn about the supported `grant_type` values for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738084387220481) and [Creator Marketplace-Get authorization from creators](https://ads.tiktok.com/marketing_api/docs?id=1740027719016450) respectively. |
| 40131|Authentication error when calling `/tt_user/oauth2/token/` or `/tt_user/oauth2/refresh_token/`.<br><strong>Common causes</strong>:<ul><li>App ID or secret is incorrect.</li><li>The authorization code has expired.</li><li>Refresh token is invalid or expired.</li></ul><br><strong>Example error message</strong>: `"Authorization code is expired."`<br><br><strong>Error handling</strong>: <ul><li>If you're calling `/tt_user/oauth2/token/`, ensure you use the correct App ID, secret and authorization code and try again.<ul><li>To learn about how to obtain the authorization code for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authorization](https://ads.tiktok.com/marketing_api/docs?id=1738083939371009) and [Get authorization from creators (New)](https://ads.tiktok.com/marketing_api/docs?id=1768506960432130) respectively.</li></ul></li><li>If you're calling `/tt_user/oauth2/refresh_token/`, ensure you use a valid refresh token and try again.<ul><li>To obtain a valid refresh token, request the user to re-authorize their application via the user authorization workflow. To learn about how to generate a new access token and a new refresh token for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738084387220481) and [Get authorization from creators (New)](https://ads.tiktok.com/marketing_api/docs?id=1768506960432130) respectively.</li></ul></li></ul>
| 40109|Deciphering Error. |
| 40112|The provided password is incorrect. |
|40113|The app has been blocked or does not exist. <br><strong>Common causes</strong>: <ul><li> incorrect App ID.</li><li> incorrect secret for the App.</li></ul><strong>Example error message</strong>: `The app_id is inconsistent with the token's app information. Please retry after correcting it. ` <br><strong>Error handling</strong>: Find the correct <strong>App ID</strong> and <strong>Secret</strong> by navigating to <strong>[My Apps](https://ads.tiktok.com/marketing_api/apps/)</strong> > <strong>App Detail</strong> > <strong>Basic Information</strong>, and try again. |
| 40115|The authentication timestamp has expired. <br><strong>Common causes</strong>: <ul><li> The authorization code is invalid.</li><li> The Authentication endpoint doesn't match the authorization code.</li></ul> <strong>Error handling</strong>: Ensure that you are using valid values for authentication and try again. <br>To learn about how to generate a long-term access token for Marketing API, refer to [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). <br>To learn about how to generate an access token for Accounts API or TikTok Creator Marketplace API, refer to [Accounts-Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738084387220481) and [Creator Marketplace-Get authorization from creators](https://ads.tiktok.com/marketing_api/docs?id=1740027719016450) respectively.|
|40116| The authentication signature is invalid. |
|40117| The specified method cannot be used. |
| 40118|The app or advertiser is not on the allowlist for accessing the feature. <br><strong>Common causes</strong>: <ul><li> The feature is only available to advertisers in certain countries or regions.</li><li> The endpoint is allowlist-only, and you haven't applied for allowlisting.</li></ul>|
| 40119|Developer and advertisers do not belong to the same company. <br><strong>Error handling</strong>: Ensure that you are using a valid access token (`Access-Token`) for the specified advertiser ID (`advertiser_id`) and try again. To learn about how to generate a long-term access token for Marketing API, refer to [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |
| 40121|The TikTok user is not a TCM (TikTok Creator Marketplace) creator. <br><strong>Common causes</strong>: The endpoint requires the information of a TCM creator, such as the handle name, and the provided information is of a non-TCM creator. <br><strong>Error handling</strong>: Provide the information of a TCM creator instead or invite the creator to join TCM, then try again. To find out a step-by-step guide on how to invite creators to TCM, refer to [Invite creators to join TTCM](https://ads.tiktok.com/marketing_api/docs?id=1740027740710914). |
| 40122|The TCM creator is not in valid regions. <br><strong>Error handling</strong>: Provide the information of a TCM creator in another region or country and try again. |
| 40124|The developer profile is not filled in and approved. <br><strong>Error handling</strong>: Visit https://ads.tiktok.com/marketing_api/developer/register to complete your profile. After your profile has been approved, try again. <br>If you encounter any difficulties in getting your profile approved, submit a ticket on [Developer Ticket Platform](https://ads.tiktok.com/athena/requester/boards?identify_key=b25033ef7782d41050cbac74f751d547e9d8ad35a39a541a0f0efbbd51fe722f). |
| 40125|The developer doesn't have the permission. <br><strong>Common causes</strong>: <ul><li> missing necessary permissions. </li><li> unsupported fields.</li></ul> <strong>Error handling</strong>: Ensure that you have the necessary permissions and use supported fields, then try again. To learn about different levels of permission scope, see [Permission scope](https://ads.tiktok.com/marketing_api/docs?id=1753986142651394). |
| 41001|The interface is offline. <br><strong>Error handling</strong>: Ensure that the endpoint path is correct and supported by the specified API version, then try again. |
|41002| The fields provided are not in use. <br><strong>Error handling</strong>: Refer to the API documentation for the correct usage of the parameters and try again. |
```

### Advertising policy related
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
|40065|The targeting options are not supported for targeting the age group 13-17 in the US.<br><br><strong>Example error message</strong>: `"Based on one or more of your choices, ad delivery to US audiences will be subject to age targeting restrictions"`<br><br><strong>Error handling</strong>: <ul><li>If you want to target the age group 13-17 in the US, ensure that you only use the supported targeting options and try again. To learn about the supported targeting options in such cases, see [New age restrictions for ads on TikTok](https://business-api.tiktok.com/portal/docs?id=1788755983247362).</li><li>If you don't want to target the age group 13-17 in the US, manually specify an `age_groups` value that does not contain `AGE_13_17` and try again.</li></ul>|
```

### Async task error
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
|40200|Task error.<br><strong>Error handling</strong>: Refer to the API documentation for the correct usage of the parameters and try again. |
|40201|Task is not ready.<br><strong>Error handling</strong>: Wait for the task to complete and try again.|
```
### Runtime error
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
|40202|Write or update entity conflict.<br><strong>Error handling</strong>: Retry may resolve this issue.|
```

### Advertisers
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
| 40300|The advertiser does not exist or has been deleted. <br><strong>Common causes</strong>: incorrect advertiser ID (`advertiser_id`). <br><strong>Error handling</strong>: Ensure that the advertiser ID is valid and try again. |
| 40301| The advertiser cannot be matched. <br><strong>Common causes</strong>:  incorrect advertiser ID (`advertiser_id`). <br><strong>Error handling</strong>: Ensure that the advertiser ID is valid and try again. |
```

### Internal service error
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
|40700|Internal service validation error.<br><strong>Error handling</strong>: Refer to the API documentation for the correct usage of the parameters and try again.|
```

### File management related
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
| 40901| Video transcoding in process. <br><strong>Error handling</strong>: Wait for the video transcoding to complete and try again. |
| 40902| Unable to fetch the URL. <br><strong>Error handling</strong>: Retry may resolve this issue. |
| 40903| The image URL is unavailable. <br><strong>Error handling</strong>: Retry may resolve this issue. |
| 40913| Unable to fetch the image. <br><strong>Error handling</strong>: Retry may resolve this issue. |
| 40905| The file does not exist. <br><strong>Error handling</strong>: Provide a correct file path or file URL and try again. |
| 40906| The file is empty. <br><strong>Error handling</strong>: Provide a correct file path or file URL and try again. |
|40907| The file is too large. <br><strong>Error handling</strong>: Reduce the file size below the size limit and try again. |
|40910| The file has expired. <br><strong>Error handling</strong>: Provide a correct file path or file URL and try again. |
| 40914| The file you want to upload is invalid.  <br><strong>Error handling</strong>: Ensure that the file path or file URL is valid and try again. |
| 40908| The file type is unsupported. <br><strong>Error handling</strong>: Ensure that you use a supported file type and try again. |
| 40900| The signature we calculated does not match the signature you provided for the file.  <br><strong>Common causes</strong>: incorrect signature for the image, video, or music.<br><strong>Error handling</strong>: Ensure that the signature you provide matches the corresponding file and try again. |
| 40909|The encryption type (`calculate_type`) is unsupported. <br><strong>Error handling</strong>: Ensure that you use a supported `calculate_type` value and try again. |
| 40911|The material's name has a duplicate. <br><strong>Error handling</strong>: Specify a different name for the material and try again. You can use [/file/name/check/](https://ads.tiktok.com/marketing_api/docs?id=1759130033155073) to check whether a file name has been used for an image or a video. |
| 40904| Illegal image content. <br><strong>Common causes</strong>: The format of the image is not supported. <br><strong>Error handling</strong>: Ensure that you use a supported image format and try again. |
|40912| The image URL is unavailable. <br><strong>Error handling</strong>: Ensure that you are using a valid image URL and try again. To obtain a valid image URL, you can use [/file/image/ad/info/](https://ads.tiktok.com/marketing_api/docs?id=1740051721711618) or [/file/image/ad/search/](https://ads.tiktok.com/marketing_api/docs?id=1740052016789506). |
|40915|The file you want to upload does not meet the specifications. <br><strong>Error handling</strong>: Ensure that the file meet the specific requirements of the endpoint and try again.|
```
### Blocked access
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
|41000|The client IP is on the banned country list.|
```

### System related
```xtable
|Code{30%}|Description{70%}|
|--- |--- |
|50000|System error|
|50002|Error processing request on TikTok side. Please see error message for details.|
|51305|Satellite service error.|
```

### Maintenance

```xtable
|Code{30%}|Description{70%}|
|--- |--- |
|60001|The system is in maintenance. <br><strong>Error handling</strong>: Wait for the maintenance to complete and retry. |
```

### TikTok Creator Marketplace
See [Error codes](https://ads.tiktok.com/marketing_api/docs?id=1740027843211265&rid=fwdmsud24u9) for details. 
