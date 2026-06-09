---
title: "Audience types"
doc_id: 1746998648212482
path: "Marketing API / Audience Management / Guides / Audience types"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Audience types

TikTok API for Business supports creating Custom Audiences and Lookalike Audiences.  
* Custom Audience is an ad targeting option that lets you find people who already know or have engaged with your business. You can create custom audiences by rule to define Engagement audience, App Activity audience or Website Traffic audience. You can leverage Customer File API to build custom audiences. 
* If you are a data partner that enables advertisers to activate audiences on TikTok, check out <a href="https://business-api.tiktok.com/portal/docs?id=1739566528222210" target="_blank">Streaming API</a>-our real-time batch processing tool to suit your high throughput needs.
* To find audiences that share commonalities with your existing customers, create Lookalike Audiences.

See the table below to get a glimpse of audience types and relevant APIs:

<table>
<thead>
  <tr>
    <th style="width:15%;">Audience type</th>
    <th style="width:15%;">Audience subcategory</th>
    <th style="width:40%;">Description</th>
    <th style="width:30%;">Relevant API</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td rowspan="8">Custom Audience</td>
    <td>Customer File Audience</td>
    <td>Created via Customer File API<br>You can create a Customer File Audience using Customer File API. A minimum audience size of 1,000 is required to target Custom Audiences in an ad group. See <a href="https://ads.tiktok.com/help/article?aid=6721963343558475781">here</a> to learn more about Customer File.<br>Note: Creating or updating an audience through file upload requires 30-60 minutes for the audience to be processed before it becomes active (varies according to the service status).</td>
	<td>Customer File API<br><ul><li><a href="https://business-api.tiktok.com/portal/docs?id=1739940567842818">/dmp/custom_audience/file/upload/</a></li><li><a href="https://business-api.tiktok.com/portal/docs?id=1739940570793985">/dmp/custom_audience/create/</a></li><li><a href="https://business-api.tiktok.com/portal/docs?id=1739940572667906">/dmp/custom_audience/update/</a></li></ul></td>
  </tr>
  <tr>
    <td>Engagement Audience</td>
    <td>Users who have previously engaged with a brand's organic TikTok videos, ad content, ad solutions (such as Instant Page ads and App Profile Page ads), or TikTok LIVE content.<br>See <a href="https://ads.tiktok.com/help/article?aid=6721963298155134982">here</a> to learn more about Engagement Audiences.</td>
    <td rowspan="7"><a href="https://business-api.tiktok.com/portal/docs?id=1739940509849601">/dmp/custom_audience/rule/create/</a></td>
  </tr>
  <tr>
    <td>App Activity Audience</td>
    <td>Users who have completed certain events within your app.<br>See <a href="https://ads.tiktok.com/help/article?aid=6721962999092871173">here</a> to learn more about App Activity Audiences.</td>
  </tr>
  <tr>
    <td>Website Traffic Audience</td>
    <td>Users who have visited or taken a specific action on your website.<br>See <a href="https://ads.tiktok.com/help/article?aid=6721962903081058309">here</a> to learn more about Website Traffic Audiences.</td>
  </tr>
  <tr>
    <td>Lead Generation Audience</td>
    <td>Users who have engaged with your Lead Ads by viewing the form or submitting the form.<br>See <a href="https://ads.tiktok.com/help/article?aid=10001711">here</a> to learn more about Lead Generation Audiences.</td>
  </tr>
  <tr>
    <td>Shop Activity Audience</td>
    <td>Users who have interacted with your TikTok Shop. Only available in markets with TikTok Shop.<br>See <a href="https://ads.tiktok.com/help/article/shop-retargeting?lang=en">here</a> to learn more about Shop Activity Audience.</td>
  </tr>
  <tr>
    <td>Business Account Audience</td>
    <td>Users who are followers of your Business Account or engaged with your content.<br>See <a href="https://ads.tiktok.com/help/article/business-account-audience">here</a> to learn more about Business Account Audience.</td>
  </tr>
  <tr>
    <td>Offline Activity Audience</td>
    <td>Users who interacted with your offline events activity.<br>See <a href="https://business-api.tiktok.com/portal/docs?id=1746998648212482">here</a> to learn more about Offline Activity Audiences.</td>
  </tr>
  <tr>
    <td>Lookalike Audience</td>
    <td>/</td>
    <td>Build Lookalike Audiences based on existing Custom Audiences. It could help you find audiences that are interested in your product and expand your audience range.<br>See <a href="https://ads.tiktok.com/help/article/lookalike-audience?redirected=2">here</a> to learn more about Lookalike Audiences.</td>
    <td><ul><li><a href="https://business-api.tiktok.com/portal/docs?id=1739940511844353">/dmp/custom_audience/lookalike/create/</a><br></li><li><a href="https://business-api.tiktok.com/portal/docs?id=1758616888158209">/dmp/custom_audience/lookalike/update/</a></li></ul></td>
  </tr>
</tbody>
</table>


