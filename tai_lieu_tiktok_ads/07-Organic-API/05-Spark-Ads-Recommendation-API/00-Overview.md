---
title: "Overview"
doc_id: 1825128393150530
path: "Organic API / Spark Ads Recommendation API / Overview"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Overview

Spark Ads Recommendation API empowers businesses and TikTok One (TTO) partners with tailored video recommendations for Spark Ads, strategically enhancing campaign impact for brands and creators.

# Why use Spark Ads Recommendation API?
Integrating with Spark Ads Recommendation API provides businesses and TTO partners with an opportunity to leverage additional signals for optimal video promotion, maximizing engagement, and ensuring campaign success on TikTok. 

# How does Spark Ads Recommendation API work?

- Businesses can use the endpoint [/business/video/recommend/](https://business-api.tiktok.com/portal/docs?id=1825128684907522) to submit a **Business Account identifier** and a list of `video_ids` that they would like Spark Ad recommendations for, as well as additional parameters such as time period. The API will provide a list of recommended video posts, a recommendation level indicating the suitability of each post for Spark Ads, and other information. It will also provide an `item_id` which can then be used to create a new Spark Ad.
- TTO partners can use the endpoint [/spark_ad/recommend/](https://business-api.tiktok.com/portal/docs?id=1825128700959746) to submit a **TTO Creator Marketplace account ID** and a list of `video_ids` that they would like Spark Ad recommendations for, as well as additional parameters such as time period and objective type. The API will provide a list of recommended video posts, a recommendation level indicating the suitability of each post for Spark Ads, and other information. It will also provide an `item_id` which can then be used to create a new Spark Ad.
