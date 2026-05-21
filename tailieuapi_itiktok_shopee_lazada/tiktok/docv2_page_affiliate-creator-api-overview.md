# Affiliate Creator API overview

> Source: https://partner.tiktokshop.com/docv2/page/affiliate-creator-api-overview
> Section: API Reference
> Scraped: 2026-05-20T23:57:31.278Z

---

📌 **Note**: Affiliate APIs are currently **not** available in the **UK** and **EU** markets.

# Context

Development partners can integrate with the TikTok Shop Affiliate APIs to help TikTok Shop stakeholders create, manage, matchmake, track, monetize, and collaborate across TikTok Shop Affiliate Collaborations and Partner Campaigns. Listing an Affiliate API integrated app within the TikTok Shop App Store offers development partners numerous advantages:

-   **Support for Existing Customers**: Satisfy and support your existing customers who are already on TikTok Shop or will be joining soon.
    
-   **Increased Exposure**: Gain visibility among TikTok Shop sellers looking for affiliate solutions within the app store.
    
-   **Marketing Opportunities**: Benefit from inclusion in marketing initiatives related to the TikTok Shop App Store.
    
-   **Competitive Differentiation**: Stand out from competitors that are not integrated with TikTok Shop.
    
-   **Enhanced Revenue Generation**: Provide a down-funnel, measurable solution that focuses on performance and revenue generation, beyond just top-of-funnel awareness and engagement marketing.
    

* * *

## Types of Affiliate APIs

There are 3 types of APIs available for affiliate management:

-   **Affiliate Seller API**: Help sellers maximize product visibility through open and target collaborations with TikTok Shop Creator Affiliates, facilitate seamless product promotion, and track the overall conversions from their affiliate marketing efforts.
    
-   **Affiliate Creator API**: Help TikTok Shop Creator Affiliates manage their collaborations and product showcases on TikTok Shop, while also tracking the conversion from their marketing efforts.
    
-   **Affiliate Partner API**: Help TikTok Affiliate Partners (TAPs) effectively manage campaigns that match-make sellers with TikTok Shop Creator Affiliates, optimizing product promotion for sellers while offering enhanced monetization opportunities for TikTok Shop Creator Affiliates.
    

Development partners can use a combination of these APIs to:

-   Enroll seller's catalog into open collaboration
    
-   Create open collaboration
    
-   Create target collaboration
    
-   Create partner campaign
    
-   Help sellers find creators
    
-   and more...
    

* * *

# Important Concepts

## TikTok Shop Affiliate Program

The TikTok Shop Affiliate Program consists of **Affiliate Collaborations** and **Affiliate Partner Campaigns**, both of which allow sellers and TikTok Shop Creator Affiliates to collaborate and build the next era of social commerce based on engaging shoppable content on TikTok.

-   **TikTok Shop Creator Affiliates** are creators that are eligible to sell e-commerce products on TikTok Shop. To be eligible, creators need to fulfil these requirements:
    -   Registered in the US, UK, and SEA
    -   US: 1K+ followers
    -   UK, SEA: 5K+ followers
    -   18+ years old
    -   No more than 3 violations on their account

Creators can apply to be included in the affiliate program [here](https://business.tiktokshop.com/us/creator/).  
(Henceforth, TikTok Shop Creator Affiliates will be generally referred to as creators.)

📌 **Note**: Currently there is no way for external partners to facilitate or moderate creator onboarding programmatically. We will improve upon this in a future iteration, but for now, you can check whether a creator is a TikTok Shop Creator Affiliate in 2 ways:

-   The Creator has a "Showcase" icon on their profile
-   The Creator is able to successfully authorize the partner

Note that TikTok Shop Creator Authorization is currently separate from TikTok for Business or TikTok for Developers.

  

-   **Shoppable content** in the context of TikTok Shop refers to engaging TikTok videos created by creators, where they tag seller products within the videos and embed native anchor links that allow the end-user to directly shop on the creator's content. Key characteristics of e-commerce based on shoppable content:
    -   Unlike traditional e-commerce affiliates (links, coupon codes, social media redirects), this type of content eliminates issues with attribution and cookie durations since users engage and purchase without navigating away from the app.
        
    -   It enables straightforward and performance-based monetization, eliminating ambiguities often found in traditional marketing approaches. Creators earn a commission (determined by the seller) on the purchases they directly drive (no view attribution), while sellers receive the remaining payout from the sales after taxes and fees.
        

💡**What this means for stakeholders?**

-   **Buyers** (TikTok App users) gain exposure to products through trusted creators and enjoy a direct path to purchasing.
-   **Sellers** benefit from engaging TikTok-style shoppable content via creators and a pay-for-performance model without significant upfront costs.
-   **Creators** benefit from high potential for performance-based compensation and diverse collaboration opportunities with sellers to promote products.

* * *

## Affiliate Collaborations

One of the most basic ways to monetize through affiliates is through direct affiliate collaborations between sellers and creators. There are 2 kinds of collaboration modes in the TikTok Shop Affiliate program:

-   **Open Collaboration**  
    An Open Collaboration refers to a collaboration model where a seller enrolls products into an Open Plan, making the **products available for all TikTok Shop Creator Affilitates** to see on the Creator Marketplace.  
    You can think of an Open Collaboration as a club which is open to creators, the only requirement being that the creator has registered and is approved to be a TikTok Shop Creator Affiliate.  
    In some cases, sellers can require approval on an Open Collaboration. This means the Open Collaboration is visible to creators but they must apply and pass seller approval to participate. You can think of this as a club bouncer giving partygoers a quick vibe check.
    
-   **Target Collaboration**  
    A Target Collaboration refers to a collaboration model where a seller enrolls products into a Target Plan, making the **products available only to TikTok Shop Creator Affilitates that are explicitly invited** by the seller.  
    You can think of a Target Collaboration as a club which is invite only. Creators can only get into the club if they are invited by the seller. Once inside, creators must abide by the club's rules and can be removed at anytime.  
    Target Collaborations are a great way for sellers to find creators that suit the seller's unique voice on TikTok, and typically involve creators who have proven themselves through historical performance or possess a strong content portfolio, often carrying higher commission rates.
    

💡**What this means for stakeholders?** Development Partners can leverage from these collaborations by potentially charging a SaaS fee to sellers or creators, or by taking a cut of the matchmaking efforts outside of the TikTok Shop platform directly with the seller or creator.

* * *

## Affiliate Partner Campaigns

Affiliate Partner Campaigns refers to a collaboration model where TikTok Affiliate Partners (TAPs) help sellers and creators matchmake more efficiently. This model is very similar to sub-affiliates in the traditional affiliate marketing space, with TAPs acting as sub-affiliates, referring the best-fit creators to sellers.  
Continuing off the analogy above regarding clubs, you can think of a TAP as a club promoter - someone who knows which club is best for whom. Club promoters have a pulse on the local club scene, including the age group, DJs, music genres, trends, and overall vibes.  
In the context of TikTok Shop, TAP can recommend viral TikTok trends, products, and help both sellers and creators succeed at creating content for the social commerce era. Some examples of TAP initiatives include creator monetization apps, creator or influencer marketing platforms, sub-affiliates etc.

The nature of Affiliate Partner Campaigns differs slightly based on the region.

-   **US/EMEA**: One(TAP)-to-one(seller) campaigns are the norm. This is no different from an Open or Target Collaboration, except that the TAP facilitates the matchmaking process, thereby taking a cut off the commission. You can think of a TAP as a sub-affiliate network.
    
-   **Rest of world**: One-to-many campaigns are the norm. TAPs often design campaigns that allow for collaboration with multiple TikTok Shop sellers and creators under one broader campaign initiative. These campaigns are geared towards TAPs supporting multiple brands at once, and provide flexibility in commission structures:
    
    -   Enables third party commission sharing (payout to both TAP and creators upon sales)
    -   Different customization levers around commissions

Despite the differences, commission sharing is a universal feature across all Affiliate Partner Campaigns. It is key to how TAPs monetize their efforts.

💡**What this means for stakeholders?** TikTok Affiliate Partners (TAPs) that are API-enabled can now efficiently manage Affiliate Partner Campaigns through automation by using these APIs. Essentially, Affiliate Partner Campaigns function similarly to Open or Target Collaborations, except that TAPs can directly monetize off the transaction. If you are a partner that prefers to monetize off SaaS fees or other means, we recommend that you leverage from the Open or Target Collaboration model.

* * *

# **TikTok Shop Partner Center Policies**

[https://partner.tiktokshop.com/docv2/page/6506bbf2de672602b7bc0697](https://partner.tiktokshop.com/docv2/page/6506bbf2de672602b7bc0697)
