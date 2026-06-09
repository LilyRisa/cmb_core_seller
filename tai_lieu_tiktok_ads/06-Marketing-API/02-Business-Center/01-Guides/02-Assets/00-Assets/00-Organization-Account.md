---
title: "Organization Account"
doc_id: 1855026897423361
path: "Marketing API / Business Center / Guides / Assets / Assets / Organization Account"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Organization Account

This guide explains what Organization Accounts are, their benefits, and how they fit into your workflow.

An Organization Account is a profile controlled by a verified organization (such as enterprise or institution) or individual business on relevant business platforms, where applicable. Using these accounts provides centralized control and enhanced security for your business.

Learn more [about Organization Accounts](https://ads.tiktok.com/help/article/about-organization-accounts) on TikTok Business Help Center.

## Benefits
- **Clear control**: The organization retains permanent ownership of the account, preventing access loss when employees leave.
- **Enhanced security**: Logins are protected by verification codes, reducing risks from compromised passwords.
- **Multi-user Login**: Multiple team members can log in and manage the same account simultaneously.
- **Role-based permissions**: Admins can assign granular access levels (Admin, Operator, Analyst) to different team members.

## Create Organization Accounts
By creating an Organization Account, which represents the organization itself, you can enhance account security, authorize multiple members to access the account, and allocate permissions based on roles. Organization Accounts created in Business Center will have access to functions exclusive to Registered Business Accounts such as Business Suite features. For these accounts, business registration will be approved without requiring any business registration documentation.

### Prerequisites
Before you begin, ensure the following conditions are met:
- **Review feature limitations**: Familiarize yourself with [the list of unavailable features for Organization Accounts](https://ads.tiktok.com/help/article/list-of-unavailable-features-for-organization-accounts).
- **Verify your Business Center**: Use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and confirm the `verification_status` field in the response is `VERIFIED`.
- **Complete Business Center information**: Add contact information and billing information to your Business Center on the Business Center UI if these details are missing.
- **Confirm Admin permissions**: You must have Admin access to the Business Center. Use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and confirm whether the returned `user_role` is `ADMIN`.
- **Apply for the allowlist**: Creating Organization Accounts in a Business Center through API is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

### Steps
The creation process depends on your Business Center type. Use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) to check the `type` of your Business Center.

#### For Direct Business Centers
If your Business Center `type` is `DIRECT` or `SELF_SERVICE`, you can create an Organization Account directly.
- Call the [/bc/oa/create/](https://business-api.tiktok.com/portal/docs?id=1855027199571138) endpoint.
- You don't need to specify a `qualification_id` in the request.

#### For Agency Business Centers
If your Business Center `type` is `AGENCY` or `SELF_SERVICE_AGENCY`, you must create Organization Accounts on behalf of your clients. This is a three-step process to link the Organization Account to your client's verified qualification.
1. **Create a client ad account and qualification**: Call [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) and include the `qualification_info` object for your client. A successful call generates a verified `qualification_id`.
2. **Retrieve the verified qualification ID**: Call [/bc/advertiser/qualification/get/](https://business-api.tiktok.com/portal/docs?id=1770118680584194) and set the `filtering` parameter to `{"verified": true}` to obtain the verified `qualification_id`.
3. **Create the Organization Account**: Call [/bc/oa/create/](https://business-api.tiktok.com/portal/docs?id=1855027199571138). In the request, include the `qualification_id` you retrieved. This associates the new Organization Account with the client's verified qualification.

## Manage Organization Accounts
You can assign Organization Accounts to other members in your Business Center. 
<table>
    <thead>
        <tr>
            <th style="width:31%"><b>Scenario</b></th>
            <th style="width:42%"><b>Endpoint to use</b></th>
            <th style="width:27%"><b>Note</b></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Retrieve the list of Organization Accounts in Business Center</td>
            <td><a href="https://business-api.tiktok.com/portal/docs?id=1739432717798401" target="_blank">/bc/asset/get/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1739433007779841" target="_blank">/bc/asset/admin/get/</a></td>
            <td rowspan="3">Set <code>asset_type</code> to any of the following values: <ul><li><code>TT_ACCOUNT</code><ul><li>You can run ads from these TikTok accounts.</li></ul></li><li><code>MANAGED_BUSINESS_ACCOUNT</code><ul><li>You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Assign an Organization Account to a member</td>
            <td><a href="https://business-api.tiktok.com/portal/docs?id=1739438211077121" target="_blank">/bc/asset/assign/</a></td>
        </tr>
        <tr>
            <td>Revoke a member's access to an Organization Account</td>
            <td><a href="https://business-api.tiktok.com/portal/docs?id=1739448126749698" target="_blank">/bc/asset/unassign/</a></td>
        </tr>
    </tbody>
</table>


## Integrate Organization Accounts into ad workflows
Once an Organization Account exists in your Business Center, you can use the relevant endpoints to manage its linkage with ad accounts for ad delivery.

<table>
    <thead>
        <tr>
            <th style="width:25%"><b>Scenario</b></th>
            <th style="width:26%"><b>Details</b></th>
            <th style="width:23%"><b>Endpoint to use</b></th>
            <th style="width:26%"><b>Note</b></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Link an Organization Account to an ad account in Business Center</td>
            <td>Once the linkage is completed, Business Center members with access to the linked ad accounts can use the Organization Account for ad delivery across these ad accounts.</td>
            <td><a href="https://business-api.tiktok.com/portal/docs?id=1846868953025538" target="_blank">/bc/asset/advertiser/assign/</a></td>
            <td rowspan="3">Set <code>asset_type</code> to any of the following values: <ul><li><code>TT_ACCOUNT</code><ul><li>You can run ads from these TikTok accounts.</li></ul></li><li><code>MANAGED_BUSINESS_ACCOUNT</code><ul><li>You can run ads from these TikTok accounts, and manage messages, leads, forms, comments, product links, and insights in your TikTok account.</li></ul></li></ul></td>
        </tr>
        <tr>
            <td>Unlink an Organization Account from an ad account in Business Center</td>
            <td>Once the linkage is removed, Business Center members with access to the ad account previously linked to the TikTok account can no longer use the TikTok account for ad delivery from the ad account.</td>
            <td><a href="https://business-api.tiktok.com/portal/docs?id=1855027260369921" target="_blank">/bc/asset/advertiser/unassign/</a></td>
        </tr>
        <tr>
            <td>Check ad accounts linked to an Organization Account in Business Center</td>
            <td>N/A</td>
            <td><a href="https://business-api.tiktok.com/portal/docs?id=1855027294743554" target="_blank">/bc/asset/advertiser/assigned/</a></td>
        </tr>
    </tbody>
</table>

