---
title: "Guidelines for identifier normalization"
doc_id: 1850030360880129
path: "Marketing API / Audience Management / Guides / About Streaming API / Guidelines for identifier normalization"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Guidelines for identifier normalization

The following table outlines the guidelines for normalizing identifiers used to build audiences with the [Customer File API](https://business-api.tiktok.com/portal/docs?id=1739940501023746) or [Streaming API](https://business-api.tiktok.com/portal/docs?id=1739940504185857).

<table>
    <thead>
        <tr>
            <th style="width:8%">ID Type</th>
            <th style="width:12%">Required Header Description for Customer File</th>
            <th style="width:15%">Recommended Format <br>- Original Value</th>
            <th style="width:15%">Recommended Format <br>- Hashed</th>
            <th style="width:15%">Also supported <br>- Only Hashed</th>
            <th style="width:15%">Example 1 <br>- Original Value</th>
            <th style="width:20%">Example 2 <br>- Hashed</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Email</td>
            <td><font background_color="light_green">Email_SHA256</font></td>
            <td><ul><li>Remove spaces</li><li>All lowercase</li></ul></td>
            <td><ul><li>Remove spaces</li><li>Convert all characters to lowercase</li><li>SHA-256 hashing required</li></ul></td>
            <td></td>
            <td>johndoe@gmail.com</td>
            <td><ul><li>Normalized: "janedoe@gmail.com"</li><li>Hashed: d6117306485ed0e50afab3ac871e98f81699151f30281527d63ff5f233656c69</li></ul></td>
        </tr>
        <tr>
            <td>Phone</td>
            <td>Phone_SHA256</td>
            <td><ul><li>E.164 format</li><li>"+"[country code][phone number]</li></ul></td>
            <td><ul><li>E.164 format, "+"[country code][phone number]</li><li>SHA-256 hashing required</li></ul></td>
            <td><ul><li>Without "+"</li><li>Without "+" and "country code"</li></ul></td>
            <td><ul><li>US: +16577770000</li><li>INTL: +37493828222</li></ul></td>
            <td><ul><li>Normalized: <ul><li>US: +16577770000</li><li>INTL: +37493828222</li></ul></li><li>Hashed: 65b7e6dcf6f4310f8d7cbfbd78289a753062fb054d6664fb9aaac5df66a422d4</li></ul></td>
        </tr>
        <tr>
            <td>MAID</td>
            <td>MAID or MAID_MD5 or MAID_SHA256</td>
            <td><ul><li>Use GAID (Android) or IDFA (iOS)</li><li>Case: all uppercase or all lowercase</li><li>Remove any spaces at the beginning or end</li></ul></td>
            <td><ul><li>Case: all uppercase or all lowercase</li><li>Remove any spaces at the beginning or end</li><li>SHA-256 or MD5 hashing required</li></ul></td>
            <td></td>
            <td>aece52e7-03ee-455a-b3c4-e57283966239</td>
            <td><ul><li>Normalized: 5f3a2b18-9c4d-4e7f-b8e2-17d3a1e69b25</li><li>Hashed: 83dc3f5f32b9472613b0f038d36f22585a609d018af6f6067f50da318bf354bd</li></ul></td>
        </tr>
    </tbody>
</table>

