# Customer engagement API overview

> Source: https://partner.tiktokshop.com/docv2/page/customer-engagement-api-overview
> Section: API Reference
> Scraped: 2026-05-20T23:56:17.659Z

---

Customer engagement is crucial for businesses looking to build lasting relationships with their customers and drive conversions. Therefore, we offer a suite of Customer Engagement API endpoints that enable businesses to integrate their CRM platforms with TikTok Shop to capitalize on TikTok's extensive reach.

Benefits of integrating with TikTok Shop:  
**\-** **Increased customer engagement** by reaching a vast, active user base on TikTok and engage potential customers in a platform-native way.  
**\-** **Interactive product promotion** through TikTok's dynamic content formats, including products, coupons, and live streams.  
**\-** **Enhanced product discovery** through relevant, targeted content directly on TikTok Shop.  
**\-** **Data-driven optimization** by leveraging performance metrics to enhance marketing effectiveness and maximize ROI.

# Key features

**Engage customers with targeted messaging**  
Create custom messages or leverage predefined message templates to send targeted messages to past buyers based on their purchase activity, such as promoting new products, or offering discounts. This helps keep customers engaged with your brand throughout their shopping journey.

**Manage and optimize engagement tasks**  
Create and track engagement tasks to organize your marketing efforts. Retrieve detailed performance metrics, such as message reads and order conversions, to refine engagement strategies and drive higher customer interaction and conversions.

# Key usage flow

Access to the Customer Engagement API is subject to [eligibility requirements](https://seller-us.tiktok.com/university/essay?knowledge_id=5403048260945710#EB6900D1). Some features, such as creating custom messages, also require specific permissions. Use **Get Feature Permissions** to check your access permissions first.

# Important Concepts

## Engagement Task

An [engagement task](https://partner.tiktokshop.com/docv2/page/67777e436b61b002f60f01da) acts as a container for grouping messages with similar content and rules, allowing sellers to track and compare [task performance](https://partner.tiktokshop.com/docv2/page/67777e44b482920307dd4dd8) across different types of content. This helps sellers refine their messaging strategies over time. To link tasks with messages, you must assign the relevant task ID when [sending the messages](https://partner.tiktokshop.com/docv2/page/67777e448e882e030d29676e).  
A task can be used for both one-time and ongoing automated messaging, and multiple recipients can be targeted within a single API call by assigning the relevant [buyer's email](https://partner.tiktokshop.com/docv2/page/650aa8ccc16ffe02b8f167a0) when sending the messages. Note that each task has a mandatory end time, and once expired, it cannot be used to send additional messages.

## TikTok Instant Messaging (IM)

Engagement messages are sent to buyers through TikTok Instant Messaging, which is located in the **TikTok App** > **Inbox** > **TikTok Shop** > \[Shop Name\].

## Message Template

TikTok Shop offers a library of predefined [message templates](https://partner.tiktokshop.com/docv2/page/67777e44223fde02fdfdc157) which you can use directly in your customer engagement communications. These templates are categorized into various scenarios, allowing sellers to select from them based on their preferences. Each template supports adding up to 4 product cards and 1 coupon card, enabling sellers to create engaging and impactful messages tailored to their audience.  
You can also create custom messages if none of the templates fit your needs.

## Product card

An interactive card sent in TikTok Shop instant messages to promote a product, displaying its details, such as title, image, description, and price, enabling customers to easily explore and purchase items directly from the message. Refer to [Get Product](https://partner.tiktokshop.com/docv2/page/6509d85b4a0bb702c057fdda?external_id=6509d85b4a0bb702c057fdda) or [Search Products](https://partner.tiktokshop.com/docv2/page/65854ffb8f559302d8a6acda?external_id=65854ffb8f559302d8a6acda) to obtain the ID of the product that you'd like to promote.

## Coupon card

An interactive card sent in TikTok Shop instant messages that provides discounts or promotional codes, enabling customers to redeem special offers and save on purchases directly from the message with a simple tap. Refer to [Get Coupon](https://partner.tiktokshop.com/docv2/page/6699dce0de15e502ed219e37?external_id=6699dce0de15e502ed219e37) or [Search Coupons](https://partner.tiktokshop.com/docv2/page/6699dcdf115ebe02f841e4cd) for the types of coupons available and to obtain the corresponding coupon ID.

![](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/448d02e2c25344aea71f5908205a6c65~tplv-k9wyc2ijk0-image.image)

# Requirements and limitations

## Supported markets

-   United States local sellers

## Limitations

| **Limitations** | **Description** |
| --- | --- |
| 
Message senders  

 | 

Only eligible sellers are permitted to use Customer Engagement APIs and features. Use **Get Feature Permissions** to check your access permissions.  
For more information about how to obtain access, refer to [TikTok Shop Academy - Customer Engagement Tools](https://seller-us.tiktok.com/university/essay?knowledge_id=5403048260945710&role=1&course_type=1&from=search)

 |
| 

Message recipients  

 | 

You can send messages to buyers who have placed **at least one order in the shop within the past 365 days**.  
**Note**: It may take up to 48 hours for a buyer to be added to the eligible buyer list after a purchase. For example, if a buyer places an order on day **T**, they will become eligible to receive messages starting from day **T+2**.

 |
| 

Engagement task limit  

 | 

A maximum of **100 active tasks** can be running simultaneously. Tasks will automatically expire after 180 days, releasing the quota for new tasks.

 |
| 

Message sending limit

 | 

**1 message** per calendar week, shared across all channels (API and Customer Engagement Tool).

 |
| 

Performance data retrieval time

 | 

The performance data for each task is available **48 hours** after execution.

 |

# Frequently asked questions

-   Q: Can the message templates be customized?  
    A: Selected sellers can use the Create Custom Engagement Task to create custom messages. Use Get Feature Permissions to check your access permissions.

# Related topics

-   [TikTok Shop Academy - Customer Engagement Tools](https://seller-us.tiktok.com/university/essay?knowledge_id=5403048260945710&role=1&course_type=1&from=search)
    
-   [Products API](https://partner.tiktokshop.com/docv2/page/650b23eef1fd3102b93d2326)
    
-   [Orders API](https://partner.tiktokshop.com/docv2/page/650b1b4bbace3e02b76d1011)
    
-   [Promotion API](https://partner.tiktokshop.com/docv2/page/650da1ab55bc3202b76f8d21)
