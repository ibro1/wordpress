# Wookiee Decor → Turnkey Niche E-Commerce Theme — Scoping v2

**Status:** Planning only. No code in this phase — this document exists to size up what "install anywhere, configure it, boom it's a unique 1-niche store" actually requires, before committing to a build order.

**Relationship to v1:** `docs/workflow/v1/spec.md` fixed Wookiee-the-specific-site based on stakeholder feedback. This document is about a different, larger goal: turning the theme itself into a reusable product that can spin up *any* single-niche store, not just Wookiee's.

---

## 1. What's actually hardcoded to Wookiee right now (audited, not guessed)

| What | Where | Scale |
|---|---|---|
| Brand name "Wookiee" as copy (not just function prefixes) | Scattered across `inc/static-content.php`, `header.php`, `footer.php` | 34 lines |
| Niche vocabulary (storage/organisation/shelving/drawer/bathroom/declutter) | Mostly `inc/static-content.php` | ~100 occurrences |
| Page copy (About narrative, policy boilerplate, hero text, philosophy section, etc.) | `inc/static-content.php` | 1,041 lines total — the large majority of this is niche-specific prose |
| Dummy product/category definitions | `inc/static-content.php` → `wookiee_create_dummy_products()` | 6 products, 4 categories, all storage-themed |
| Function/option naming | `wookiee_*` prefix throughout | 43 distinct functions/options |
| Business details (address, phone, email, company number) | Already solved | ✅ Wookiee Settings admin page, done in v1 |

**Read on this:** the business-detail layer (name, address, contact, shipping rate) is already properly separated from code — that part of "configure it" already works today via the settings panel. The part that *isn't* separated yet is everything else: the actual words on the page. About page copy, policy page prose, hero headlines, product descriptions — all of it is English sentences written specifically for a UK home-storage brand, sitting in PHP as literal strings. That's the bulk of the "install anywhere, any niche" gap.

---

## 2. What "install anywhere, any niche" actually requires

Breaking the vision into distinct capabilities, since they have very different build complexity and risk profiles:

### 2a. Business identity via Companies House lookup
**Complexity: low. Risk: none.** Companies House provides a free, official public API (`api.company-information.service.gov.uk`) built for exactly this — look up a company number, get back registered name, address, incorporation date, status, SIC codes. Requires a free API key registration (one-time, by whoever runs a given site).

Feeds directly into the Wookiee Settings panel already built: instead of manually typing registered address/company number, an admin enters the company number once and those fields populate automatically. Still editable manually afterward for anything the API doesn't cover (returns address, contact email, social URLs, shipping rate — none of that lives on Companies House).

### 2b. Niche-agnostic page content

**Policy pages: the mechanism is mostly already solved — the text itself is NOT reused across sites.** To be explicit, since this is easy to misread: each install still gets its own freshly-generated policy text, reflecting that store's actual business details (name, address, company number, returns window, delivery times). What's already solved is everything *around* that generation step — the business-detail plumbing (Wookiee Settings + §2a Companies House lookup) and, as of this planning pass, a tested prompt that turns those details into compliant policy text. The remaining work is wiring, not writing: feed real business details into the existing prompt at setup time, run it, save the output as that site's real page content. Three files already sit in `docs/` that weren't part of this spec until discovered during this planning pass:

- `docs/policy writing law.txt` — a UK-specific prompt (Consumer Rights Act 2015, UK GDPR, PECR, Consumer Contracts Regs) that reviews/rewrites policies against real business details, explicitly refusing to invent facts and flagging anything needing a solicitor's review. Written against placeholder business "Anyora Limited" — confirmed this is the site that inspired Wookiee's build, not a real second install; the placeholder details are just an example, not live data.
- `docs/policy audit new.txt` — a broader US/Google Merchant Center + FTC + privacy compliance audit prompt, scored output, explicitly rejects "AI-generated sounding text" and demands natural, non-templated writing.
- `docs/exif and webp.txt` — confirms product images are meant to come from a **real source** (supplier/dropship platform) and only get *technical* processing (WebP conversion, EXIF/metadata stripping, resizing, Google Merchant-safe naming/color conventions) — explicitly "Do NOT regenerate the product image with AI." This independently confirms the §2c position below: images aren't something to fabricate, they come from wherever the actual product data comes from.

This means the policy-page piece of "niche-agnostic content" already has a working, tested prompt — the remaining engineering work is wiring it up (feed business details from Wookiee Settings + Companies House lookup into this prompt, run it, save the output as real page content) rather than designing a new one from scratch.

**Non-policy copy (About narrative, hero text, philosophy section) still needs its own approach**, since the existing prompts don't cover brand-voice/marketing copy:
- **(a) AI-generated at setup time**: admin describes a niche once during setup, AI generates the About narrative, hero copy, etc. in that voice, saved as real editable page content afterward (one-time generation, not regenerated live).
- **(b) Structured templates with fill-in-the-blank slots**: less flexible, more predictable, no AI dependency, faster to build, weaker/more obviously-templated output.

### 2c. Product catalog: research, generation, and legal boundaries
**Complexity: high. Risk: real legal exposure if done wrong — see below.**

What was proposed (research from Amazon/Walmart, AI edits descriptions/images) has a genuine problem: scraping product data and especially *images* from those platforms for republishing violates their Terms of Service and likely infringes copyright on the photography (usually owned by the brand or the platform's photography vendor, not freely reusable regardless of how the text around it is edited). This isn't a style preference — it's the kind of thing that gets a site's payment processor account or hosting terminated, or draws a takedown/legal notice. **This document does not include a scraper for those platforms, and any future phase touching this needs a different data source.**

Legitimate alternatives that achieve the same actual goal (a real, single-niche, uniform-feeling product catalog):
- **Licensed dropship/wholesale supplier APIs** (AliExpress, CJ Dropshipping, Spocket, or UK-specific wholesalers/distributors). These exist specifically for "build a store from a licensed product catalog" and — critically — come with actual fulfillment behind them, which anything sourced from Amazon/Walmart never would, since you can't fulfill an order for a product you don't have a supply relationship for.
- **AI-generated original descriptions/categorization** within a niche brief (not copied from any specific existing listing), paired with either the theme's existing stock-photo approach or supplier-provided/licensed images.
- Either way: AI-created products should land as **drafts requiring human review before publish**, not auto-live. A turnkey tool that lets anyone spin up live, orderable products with zero human verification is a real consumer-protection problem (UK Consumer Rights Act — misrepresenting goods that can't actually be fulfilled as described) the moment a real customer places an order.

### 2d. Setup/onboarding flow
**Complexity: medium.** Ties 2a–2c together into an actual "activate theme → configure → done" experience: a setup wizard (likely a dedicated admin screen, not the WP Customizer) that walks through: niche selection/description → business identity (Companies House lookup) → content generation (2b) → product sourcing (2c) → review/publish. This is the part that makes it feel "turnkey" rather than "here are some separate settings pages."

---

## 3. Suggested phased roadmap

Roughly in order of (value delivered) ÷ (risk + complexity) — cheapest wins first, riskiest/most novel last:

1. **Companies House lookup** (§2a) — small, clean, immediately useful even for Wookiee itself right now, no legal complexity. ✅ Built — Settings → Wookiee Settings now has an API key field and a "Look up on Companies House" button next to Company number; fills registered name/address for review before save. Needs an admin to register a free API key and click-test it live.
2. **Draft-only AI product content generator** (§2c, without a live supplier integration yet) — AI drafts description/categorization from a niche brief you provide, using stock or your own uploaded images, sitting as drafts for review. Proves out the "AI creates catalog content responsibly" piece without the bigger supplier-API integration. ✅ Built — Appearance → Wookiee Product Generator: enter a niche brief and a count (max 8), the configured LLM drafts titles/categories/descriptions/prices/photo-briefs, each lands as a real WooCommerce product in **Draft** status. No image is fetched or AI-generated; the "photo needed" brief is shown so a human sources/shoots the real photo before publishing. Needs an LLM API key added to Wookiee Settings and a live test run.

**LLM backend (updated):** `inc/ai-client.php` now talks to any OpenAI-compatible Chat Completions endpoint instead of being hardcoded to Anthropic - configured entirely from 3 Wookiee Settings fields: LLM API key, LLM base URL (defaults to `https://api.openai.com/v1`), and LLM default model (defaults to `gpt-4o-mini`). Switching provider (OpenRouter, Groq, a self-hosted vLLM/llama.cpp server, Anthropic via an OpenAI-compat shim, etc.) is a settings change, not a code change.
3. **Niche-agnostic page content generation** (§2b) — the biggest lift, best done after 1–2 are proven out since it benefits from the same "AI generates, human reviews, then it's saved as real content" pattern. ✅ Built (approach (a), AI-generated brand voice, for consistency with the product generator) — Appearance → Wookiee Content Generator: pick from About narrative / homepage hero & philosophy copy / the 5 policy pages, generate against the shared niche brief. Each result is a new page titled "(AI Draft)" in Draft status, never overwriting the theme's existing live pages. The 5 policy prompts are adapted from `docs/policy writing law.txt`'s UK-compliance instructions (real business details from Settings, no invented facts, solicitor-review disclaimer) reproduced directly in the theme code, since deployment only ships the theme folder, not `docs/`. **Now closed:** `front-page.php`'s hero (eyebrow/headline/subheadline) and philosophy section (heading/paragraph) read from 5 new settings instead of hardcoded copy (defaults preserve the current live text, so nothing changed on deploy). The Content Generator parses the homepage_copy response and an explicit "Apply reviewed homepage copy to live site" button writes it into those settings — a deliberate separate action from generation, so nothing goes live until the admin has read the draft page and chosen to apply it. `docs/policy audit new.txt` is now wired up too, adapted for UK law rather than used as-is (resolves open question 6 - see below) - a "Policy compliance audit" section on the same screen runs a selected policy draft through a UK-adapted version of that audit format (GMC risk, UK legal risk, scored issues, missing-information callout) and returns a report only; it never edits the page itself.
4. **Supplier API integration** (§2c, full version) — connecting to an actual dropship/wholesale source for real, fulfillable inventory. This is its own significant integration project depending on which supplier(s) you want to support. ✅ Built against **CJ Dropshipping** — free API access and UK/EU warehouse filtering made it the better fit over Spocket (paid API access) and AliExpress (shipping times conflict with the site's existing "3-5 day" promise). Appearance → Wookiee Supplier Catalog: search CJ's real catalog, import a result as a WooCommerce Draft (real title/description/price/photos from the supplier). Once published, `woocommerce_order_status_processing` automatically pushes any CJ-sourced line items to CJ for fulfillment and logs an order note. **Caveat:** written against CJ's documented API shape with no live account available to test against — needs a real-credential smoke test (auth, search, import, and one real order push) before depending on it for actual orders.
5. **Setup wizard** (§2d) — once the underlying pieces exist, wrap them in a proper onboarding flow. ✅ Built — a single dashboard walking business identity → niche brief → content generation → product sourcing → shipping → review, with live counts of draft pages/products still awaiting a human review pass and direct links into each generator and into the filtered draft lists. Doesn't duplicate any generator's logic - purely a status view over what's already built. Now also redirects the admin here automatically right after the theme is activated (`after_switch_theme` + a one-time transient), so "activate, then configure" doesn't depend on someone finding the menu item themselves.

Also closed outside the original numbered order:
- The **actual WooCommerce flat-rate shipping method** (checkout math, not just messaging) - `inc/shipping.php` creates a "United Kingdom" shipping zone with a flat rate method on first load and keeps its cost in sync with the `shipping_rate` setting whenever it's changed, so there's one real source of truth for the number instead of messaging-only.
- **Admin menu consolidation** - the 5 screens above used to be separate items buried inside Appearance; `inc/admin-menu.php` now groups all of them under one top-level "Wookiee" menu with a submenu, matching how WooCommerce organizes its own admin pages.

## 4. Open questions

| # | Question |
|---|---|
| 1 | ~~Which supplier/dropship platform?~~ **Resolved** — went with CJ Dropshipping (free API, UK/EU warehouse filtering); see roadmap step 4. |
| 2 | ~~AI-generated brand voice (a) vs. templates (b)?~~ **Resolved** — built (a) for consistency with the product generator. |
| 3 | Is the near-term goal "make Wookiee itself real" or "build the reusable product for resale to others"? These can share the same underlying code, but affects what to prioritize first — Wookiee's actual catalog vs. the general-purpose tooling. |
| 4 | Do you have (or want to get) a Companies House API key now, so §2a can start immediately? |
| 5 | ~~Is "Anyora Limited" a real second site?~~ **Resolved** — it was the inspiration reference for Wookiee's build, not a live install. |
| 6 | ~~Use `docs/policy audit new.txt` as-is, adapt it, or reserve it?~~ **Resolved** — adapted: kept the audit's format/rigor but swapped US frameworks (FTC, CCPA/CPRA) for the UK ones already used by the generation prompt, since running a US legal audit against a UK-only store would be actively misleading. See roadmap step 3. |
