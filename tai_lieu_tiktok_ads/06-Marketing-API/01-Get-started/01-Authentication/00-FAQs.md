---
title: "FAQs"
doc_id: 1766041812130817
path: "Marketing API / Get started / Authentication / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

This article collects some common questions and error messages about authentication. 
# Questions
**For an existing access token, will it be available to new advertiser accounts as these accounts are being created, or do I need to generate a new authentication code and a new access token every time a new account is created?**

If the authentication user receives access to a new ad account, you will not need to re-authenticate and create the access token again. The developer application will automatically have access to the data from this new ad account. 

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
    <td>"Access token is invalid, when you call access_token or refresh_token api, old token will become invalid".</td>
    <td>The access token has expired or does not exist.</td>
    <td>Ensure you are using the latest access token and the access token is entered correctly.</td>
  </tr>
  <tr>
    <td>"Advertiser xxxx doesn't exist or has been deleted."</td>
    <td></td>
    <td>Ensure that you enter the correct advertiser ID.</td>
  </tr>
  <tr>
    <td>"No access to operation advertiser xxx".</td>
    <td>The access token does not match the advertiser ID.</td>
    <td>Ensure that you enter the correct access token for the advertiser ID.</td>
  </tr>
</tbody>
</table>
