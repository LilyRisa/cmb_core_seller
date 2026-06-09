---
title: "Manage Payment Portfolios"
doc_id: 1839157972427970
path: "Marketing API / Business Center / Guides / Finance / Manage Payment Portfolios"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Manage Payment Portfolios

This article walks you through how to manage Payment Portfolios using Payment Portfolios API.

# Introduction
Payment Portfolio is a one stop workspace that allows you to view and and manage your options for making payments, including credit lines, cash balance, ad credit, and payment methods, in a unified manner across TikTok for Business platforms.

To learn more about Payment Portfolio, see [About Payment Portfolio](https://ads.tiktok.com/help/article/about-payment-portfolio).

# Prerequisites
- You've gained access to TikTok API for Business. See [Get Started - Step by step workflow](https://business-api.tiktok.com/portal/docs?id=1735713609895937) for details. 
  - To manage Payment Portfolios, you need relevant permissions. See [API Reference](https://business-api.tiktok.com/portal/docs?id=1735713875563521) to find out permissions required for endpoints (including the endpoints listed in the "Steps" section) and see [Update app permissions](https://business-api.tiktok.com/portal/docs?id=1738855280338946) to find out how to configure permissions.  


# Use cases overview
The table below outlines the use cases and relevant endpoints to effectively manage Payment Portfolios:

<table><thead>
  <tr>
    <th style="width:40%">Use Case</th>
    <th style="width:60%">Endpoint to Use</th>
  </tr></thead>
<tbody>
  <tr>
    <td>Retrieve Payment Portfolios</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1839151931301954"  target="_blank">/payment_portfolio/get/</a></td>
  </tr>
  <tr>
    <td>Create a Payment Portfolio</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1839151986039809"  target="_blank">/payment_portfolio/create/</a></td>
  </tr>
  <tr>
    <td>Link existing ad accounts to a Payment Portfolio</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1839151992566081"  target="_blank">/payment_portfolio/advertiser/update/</a></td>
  </tr>
  <tr>
    <td>Allocate the credit Line to Payment Portfolios</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1839151998450754"  target="_blank">/payment_portfolio/credit_line/update/</a></td>
  </tr>
  <tr>
    <td>Get ad accounts linked to a Payment Portfolio</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1839152005599234"  target="_blank">/payment_portfolio/advertiser/get/</a></td>
  </tr>
  <tr>
    <td>Get authorized users for a Payment Portfolio</td>
    <td><a href="https://business-api.tiktok.com/portal/docs?id=1839152031645121"  target="_blank">/payment_portfolio/user/get/</a></td>
  </tr>
</tbody></table>

