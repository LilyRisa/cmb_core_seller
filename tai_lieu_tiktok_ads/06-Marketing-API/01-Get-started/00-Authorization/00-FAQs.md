---
title: "FAQs"
doc_id: 1766037914914818
path: "Marketing API / Get started / Authorization / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

This article collects some common questions and error messages about authorization.

 
# Questions

**What are the authorization URL, authorization code expiry time, authentication endpoint and access token expiry time for Marketing API?**

The following table lists the authorization URL, authorization code expiry time, authentication endpoint and access token expiry time for Marketing API, Accounts API, Mentions API, TikTok Creator Marketplace API, TikTok One (TTO) API, Discovery API, and Spark Ads Recommendation API. 

<div style="overflow-x:auto;">
<table style="width:1800px">
  <thead>
  <tr>
    <th style="width:10%"></th>
    <th style="width:15%">Marketing API</th>
    <th style="width:15%">Accounts API/Mentions API</th>
    <th style="width:15%">TikTok Creator Marketplace API</th>
    <th style="width:15%">TTO API</th>
    <th style="width:15%">Discovery API</th>
    <th style="width:15%">Spark Ads Recommendation API</th>
  </tr></thead>
<tbody>
  <tr>
    <td>Description</td>
    <td>The authorization for Marketing API is performed via TikTok For Business advertiser.</td>
    <td>The authorization for Accounts API/Mentions API is performed via TikTok organic account.</td>
    <td>The authorization for TikTok Creator Marketplace (TTCM) API is performed via TTCM account and TikTok organic account.<br><br><p><span style="color:darkred"><b>Note</b></span>: TikTok Creator Marketplace API is in closed beta. You need to apply for access first.</td>
    <td>The authorization for TTO API is performed via TTO Creator Marketplace account.</td>
    <td>The authorization for Discovery API is performed via TikTok For Business advertiser or TikTok organic account.</td>
    <td>The authorization for Spark Ads Recommendation API is performed via TTCM account or TikTok organic account.</td>
  </tr>
  <tr>
    <td>Authorization URL</td>
    <td>Advertiser authorization URL on <a href="https://business-api.tiktok.com/portal/apps" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1738373141733378" target="_blank">Authorization - Step 1</a>.</td>
    <td>TikTok account holder authorization URL on <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1738083939371009" target="_blank">Accounts - Authorization</a>.</td>
    <td><ul><li>TTCM account authorization: Advertiser authorization URL on <a href="https://business-api.tiktok.com/portal/apps" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1781189065082881" target="_blank">Obtain authorization and authentication from your TTCM account</a>.</li><li>Creator authorization: TikTok account holder authorization URL on <a href="https://business-api.tiktok.com/portal/apps" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1768506960432130" target="_blank">Get authorization from Creators - Step 1</a>.</li></ul></td>
    <td>Advertiser authorization URL on <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1825117875361793" target="_blank">Obtain authorization and authentication from your TTO Creator Marketplace account</a>.</td>
    <td><ul><li>Advertiser authorization (when the endpoint is related to hashtags): Advertiser authorization URL on <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1738373141733378" target="_blank">Authorization - Step 1</a> for detailed steps.</li><li>TikTok account user authorization (when the endpoint is related to tracks): TikTok account holder authorization URL on My Apps. See <a href="https://business-api.tiktok.com/portal/docs?id=1738083939371009" target="_blank">Accounts API Authorization</a>.</li></ul></td>
    <td><ul><li>TTCM account authorization (for <a href="https://business-api.tiktok.com/portal/docs?id=1825128700959746" target="_blank">/spark_ad/recommend/</a>): Advertiser authorization URL on <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>. See <a href="https://business-api.tiktok.com/portal/docs?id=1781189065082881" target="_blank">Obtain authorization and authentication from your TTCM account</a>.</li><li>TikTok account user authorization (for <a href="https://business-api.tiktok.com/portal/docs?id=1825128684907522" target="_blank">/business/video/recommend/</a>): TikTok account holder authorization URL on My Apps. See <a href="https://business-api.tiktok.com/portal/docs?id=1738083939371009" target="_blank">Accounts API Authorization</a>.</li></ul></td>
  </tr>
  <tr>
    <td>Authorization code expiry</td>
    <td>Valid for one hour and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</td>
	<td>Valid for 10 minutes and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</td>
    <td><ul><li>TTCM account authorization code: Valid for one hour and can be used only once.</li><li>Creator authorization code: Valid for 10 minutes and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li></ul></td>
    <td>Valid for one hour and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</td>
    <td><ul><li>Advertiser authorization code: Valid for one hour and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li><li>TikTok account user authorization code: Valid for 10 minutes and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li></ul></td>
    <td><ul><li>TTCM account authorization code: Valid for one hour and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li><li>TikTok account user authorization code: Valid for 10 minutes and can be used only once. After the <code>auth_code</code> expires, you need to start over and perform the authorization steps again.</li></ul></td>
  </tr>
  <tr>
    <td>Authentication endpoint</td>
    <td><a href="https://ads.tiktok.com/marketing_api/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (Advertiser access token)</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20an%20access%20token" target="_blank">/tt_user/oauth2/token/</a> (TikTok account access token)</td>
    <td><ul><li><a href="https://business-api.tiktok.com/portal/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (TTCM account access token)</li><li><a href="https://business-api.tiktok.com/portal/docs?rid=7xusz43oh3h&id=1768493902119937#item-link-Get%20an%20access%20token" target="_blank">/tt_user/oauth2/token/</a> (Creator access token)</li></ul></td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (TTO access token)</td>
    <td><ul><li><a href="https://business-api.tiktok.com/portal/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (Advertiser access token)</li><li><a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token" target="_blank">/tt_user/oauth2/token/</a> (TikTok account access token)</li></ul></td>
    <td><ul><li><a href="https://business-api.tiktok.com/portal/docs?id=1738373164380162" target="_blank">/oauth2/access_token/</a> (TTCM access token)</li><li><a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Get%20a%20TikTok%20account%20access%20token" target="_blank">/tt_user/oauth2/token/</a> (TikTok account access token)</li></ul></td>
  </tr>
  <tr>
    <td>Access token expiry</td>
    <td>The advertiser access token does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</td>
    <td>The TikTok account access token is valid for one day. Once an access token has expired, you need to renew the access token via <a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Renew%20an%20access%20token" target="_blank">/tt_user/oauth2/refresh_token/</a>.</td>
    <td><ul><li>TTCM account access token: It does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</li><li>Creator access token: It is valid for one day. Once an access token has expired, you need to renew the access token via <a href="https://business-api.tiktok.com/portal/docs?id=1768493902119937#item-link-Renew%20an%20access%20token" target="_blank">/tt_user/oauth2/refresh_token/</a>.</li></ul></td>
    <td>TTO account access token: It does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</td>
    <td><ul><li>Advertiser access token (when the endpoint is related to hashtags): It does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</li><li>TikTok account access token (when the endpoint is related to tracks): It is valid for one day. Once the access token has expired, you need to renew the access token via <a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Revoke%20a%20TikTok%20account%20access%20token" target="_blank">/tt_user/oauth2/refresh_token/</a>.</li></ul></td>
	<td><ul><li>TTCM access token (for <code>/spark_ad/recommend/</code>): It does not expire. If you want to invalidate the token, use <a href="https://ads.tiktok.com/marketing_api/docs?id=1739965949088770" target="_blank">/oauth2/revoke_token/</a>.</li><li>TikTok account access token (for <code>/business/video/recommend/</code>): It is valid for one day. Once the access token has expired, you need to renew the access token via <a href="https://business-api.tiktok.com/portal/docs?id=1738084387220481#item-link-Revoke%20a%20TikTok%20account%20access%20token" target="_blank">/tt_user/oauth2/refresh_token/</a>.</li></ul></td>
  </tr>
</tbody></table>
</div>


<br>
<br>

**Where can I find the secret key to my developer application?**

It can be found in the Basic Information section for your app under <a href="https://ads.tiktok.com/i18n/login?redirect=https%3A%2F%2Fads.tiktok.com%2Fmarketing_api%2Fapps%2F" target="_blank">My Apps</a>.
<br>
<br>
**Why is the authentication code(auth_code) missing when my advertiser is redirected to the redirect URL?**

The redirect url that you provide is being redirected . For example, <a href="https://abc.com/auth_code=123" target="_blank">ABC.com/auth_code=123</a> is being redirected to <a href="https://abc.com/" target="_blank">abc.com/</a>. Please use another URL as your redirect URL or contact your website admin to resolve the issue. 
<br>
<br>
**What is the role of the callback address in the authorization process?**

After getting the authorizated URL from the developer, the advertiser enters the URL in a browser. They will then be navigated to a page where they can review the permissions that are requested by the developer. After they click **Confirm** to grant the permissions, a new page will open. The URL of the page is the callback address appended by the authorization code, in the format of `<callback_address>...&auth_code=<auth_code>`.
<br>
<br>

**How to set up the callback address?**

The callback address must be an extranet address. Intranet addresses such as <http://localhost:0001> do not work. You can use the URL of your company website or other public Web links. 

If you know how to parse the callback address to extract the authorization code, you can set up a proper callback address. When a POST request is made to this address, you'll get notified and your code can fetch the authorization code for you.
<br>
<br>

**How to revoke a developer's access to ad accounts that a TikTok For Business user has authorized?**

To revoke a developer's access to ad accounts, you can follow these steps:

- If you are an advertiser (TikTok For Business user) and want to cancel a developer's access to manage your ad accounts, go to the [TikTok For Business authorization setting page](https://ads.tiktok.com/ac/page/authorizations). From there, click **Remove** to revoke the authorization that you previously granted to that specific developer.
- If you are a developer and need to cancel the access on behalf of the advertiser, you can use the [/oauth2/revoke_token/](https://business-api.tiktok.com/portal/docs?id=1739965949088770) endpoint to revoke the access token associated with the authorization.


# Common error messages
<table style="undefined;table-layout: fixed; width: 993px">
<colgroup>
<col style="width: 262.003906px">
<col style="width: 265.003906px">
<col style="width: 347.003906px">
</colgroup>
<thead>
  <tr>
    <th>Common error message</th>
    <th>Cause</th>
    <th>Solution</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td>"Advertiser does not grant you the permission for xxx", where xxxx is an endpoint.</td>
    <td>The advertiser (TikTok for Business user) that you sent the authentication link to did not grant you permission to access that endpoint.</td>
    <td>Select the permissions you need and get re-authorization from the advertiser. See <a href="https://ads.tiktok.com/marketing_api/docs?id=1738855280338946" target="_blank">Update app permissions</a>  and <a href="https://ads.tiktok.com/marketing_api/docs?id=1738373141733378&rid=gct6uellnh7" target="_blank">Authorization</a> for detailed steps. </td>
  </tr>
  <tr>
    <td>When the advertiser accesses the Authorized URL, the page displays an error message "Redirect Url Not Match".</td>
    <td>The redirect_url in the Authorized URL is not consistent with the corresponding developer application callback address.</td>
    <td>Ensure the redirect_url part in the Authorized URL is the same as the callback address that you configured for the developer application.</td>
  </tr>
  <tr>
    <td>After the advertiser completes the authorization, the page being redirected displays an error.</td>
    <td>The callback address is not a valid Internet address, or an Intranet address (such as <a href="https://localhost/">https://localhost</a>:port_number/) is defined as the callback address.</td>
    <td>Ensure that the callback address is a valid address that is accessible on the Internet.</td>
  </tr>
  <tr>
    <td>"Invalid auth code" when getting a long-term access token.</td>
    <td>auth_code has expired. It is only valid for 10 minutes after it is generated.</td>
    <td>You need to do the authorization again. Send the Authorized URL to your advertiser, and let them approve the authorization again. Then use the new auth_code to get the access token.</td>
  </tr>
  <tr>
	<td> "You don't have permission to operate advertiser xxxx" where xxxx is an advertiser ID. <br><p>
"Insufficient advertiser permission: Please ask the BC admin with resource permission to grant access to the advertisers (adv_id:xxxx)."</td>
	<td>The advertiser (TikTok for Business user) who authorizes the access token does not have access to the corresponding ad accounts in Business Center or the advertiser is not the owner of the ad account.</td>
	<td><ul><li> Contact BC admin to <a href="https://ads.tiktok.com/help/article/add-users-tiktok-business-center?lang=en" target="_blank"> add permission to the advertiser</a>. </li>
	  <li>[Or] Get authorization from a new user who has access to the ad account. </li>
</tbody>
</table>
