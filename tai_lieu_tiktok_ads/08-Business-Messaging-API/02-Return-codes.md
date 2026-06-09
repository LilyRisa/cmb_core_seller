---
title: "Return codes"
doc_id: 1832184306529874
path: "Business Messaging API / Return codes"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Return codes

The following table lists the common return codes for unsuccessful calls to Business Messaging API.

```xtable
| Code {10%} | Description{60%} |
|---|---|
| 40001 | No permission to perform the related operation.<br><br><b>Error handling</b>: Ensure that you have the necessary permissions and try again. |
| 40002 | Parameter error. Please see error message for details.<br><br><b>Common causes</b>:<ul><li>missing required fields.</li><li>incorrect format of field values.</li><li>unsupported field values or fields.</li><li>incompatibility between passed fields or field values.</li></ul><br><b>Error handling</b>: Refer to the API documentation for the correct usage of the parameters and try again. |
| 40007 | The operation/object does not exist.<br><br><b>Common causes</b>:<ul><li>specifying an object that doesn't exist.</li><li>specifying a deprecated URL or a file that doesn't exist.</li></ul><br><b>Error handling</b>: Ensure that the object you are operating on exists and validate any URLs used, then try again. |
| 40100 | Requests made too frequently.<br><br><b>Common causes</b>: The rate limit level has been reached, thus your request is throttled.<br><br><b>Error handling</b>: Refer to [Rate limits](https://business-api.tiktok.com/portal/docs?id=1740029171730433) to learn about global rate limits or contact TikTok representative to find out which rate limit rule restricts your requests. |
| 40105 | Invalid or incorrect access token.<br><br><b>Error handling</b>: Use a valid access token and try again.<br><br>To learn about how to generate an access token for Business Messaging API, refer to [Authentication](https://business-api.tiktok.com/portal/docs?id=1832184175482945). |
| 40908 | The file type is unsupported.<br><br><b>Error handling</b>: Ensure that you use a supported file type and try again. |
| 40064 | The message is blocked due to limitation of direct message rules.<br><br><b>Error handling</b>: Contact TikTok representative to find out which rule restricts your messages. |
| 51065 | System error.<br><br><b>Error handling</b>: Retry the call. If retries don't work, contact TikTok representative for help. |
```
