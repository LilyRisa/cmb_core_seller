---
title: "Messaging limits for Business Messaging API"
doc_id: 1854823717597185
path: "Business Messaging API / Guides / Manage direct messages for a Business Account / Messaging limits for Business Messaging API"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Messaging limits for Business Messaging API

For a Business Account's non-mutual follow conversations handled via the Business Messaging API, the following messaging limits apply:
- **Initial 48-hour window**: After receiving the first message from a TikTok user, Business Account can send up to 10 messages within the next 48 hours.
- **Active conversation window**: If the TikTok user replies to the Business Account's message, a new interaction window starts. Within 48 hours after each last reply from the TikTok user, the Business Account can send an unlimited number of messages to that user.
- **Inactive conversation limit**: If more than 48 hours have passed since the TikTok user's last reply, the Business Account is limited to sending a maximum of three additional messages. This restriction remains in effect until the TikTok user responds again, which will reactivate the unlimited messaging window for the next 48 hours.
- **Example flow**: 
  <br>TikTok user sends first message 
  <br>→ Business Account can send up to 10 messages in 48 hours 
  <br>→ TikTok user replies 
  <br>→ Business Account can send unlimited messages in the next 48 hours 
  <br>→ If TikTok user doesn't reply for over 48 hours 
  <br>→ Business Account can only send three more messages 
  <br>→ TikTok user replies again 
  <br>→ Unlimited messaging window reopens for 48 hours, and additional three more messages after 48 hours until the TikTok user replies again.
