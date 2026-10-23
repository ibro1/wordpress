# Google Ads API — Basic Access Design Documentation

(Submitted as supporting documentation for the Basic Access application, per
Google's "Sample Design Documentation" template.)

**Website note:** Our company website, davebukartechnologies.com, is
currently under development and does not yet reflect our business in
detail. This documentation describes our business model and the API use
case directly, per the option offered in Google's own review guidance for
applicants whose website is not yet fully built out.

**Company Name:** Dave Bukar Technologies

**Business Model:** We are a software development company that builds
WordPress themes and tools for ecommerce businesses. Our current product is
"Wookiee Decor," a turnkey WordPress ecommerce theme that we deploy across
multiple client stores. The theme includes an AI-assisted product research
feature that helps store owners identify which products to stock. We do not
manage advertising campaigns or accounts for any client or third party —
this integration is read-only keyword research, built into software we
develop and deploy, not an agency service.

**Tool Access/Use:** The Google Ads API credentials (developer token, OAuth
client) are configured once, by us, as the tool's developer. Each
deployment of the Wookiee Decor theme (one per client store) uses this same
integration to pull keyword data for that store's own product research —
individual store administrators do not need their own Google Ads API
credentials or developer accounts to use the feature. The tool is used
internally within each store's own WordPress admin dashboard only, has no
public-facing interface, and produces no external reports or exports. It
runs on-demand when a store administrator requests new product ideas —
there is no scheduled/hourly sync job.

**Tool Design:** When a store administrator requests new product ideas, the
tool calls the Google Ads API's Keyword Plan Idea Service to pull real
UK search-volume and CPC estimates for the relevant niche. These numbers
are used only to help our AI rank which product concepts are worth sourcing
(i.e., ones with real search demand), and are displayed alongside each
generated product idea in the store's admin dashboard. No campaigns, ad
groups, or bids are created, read, or modified — this is a read-only
integration limited to keyword-idea data.

**API Services Called:**
- Retrieve keyword ideas (search volume, competition, CPC range) via
  `generateKeywordIdeas` (Keyword Plan Idea Service)

**Tool Mockups:** [Insert a screenshot of the Product Generator admin page
here — the one with the "Est. demand" column and the generated-products
table — showing the real, already-working UI on one of our deployed
client stores.]
