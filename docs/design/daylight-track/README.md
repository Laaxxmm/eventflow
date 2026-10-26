# Daylight Track mockups (reference only)

These are the design mockups for the EventFlow redesign, exported from the design canvas.
They are the visual source of truth for colours, spacing, copy and layout. Do not ship them.

How to read a `.dc.html` file:
- The markup inside `<x-dc>` is the screen. Styles are inline, so every value is visible.
- `{{ name }}` is filled from `renderVals()` in the `<script type="text/x-dc">` block at the bottom.
  Sample data (events, participants, template palettes) lives there.
- `<sc-for list="{{ items }}" as="it">` repeats its contents per item; `<sc-if value="{{ x }}">` shows it when true.
- `<dc-import name="Sidebar" active="events">` places `Sidebar.dc.html` with that menu item active.
- Gradient blocks labelled "Photo: …" are image slots; see the image manifest in the brief.

Files by area:
- Marketing: Main (homepage), Pricing, Templates, Signup, Login
- Race pages: GunLap, StadiumSplit, EquipmentClose, NightCircuit, HeritageCrest; phone: Event, Review, PassReady
- Organiser app: Sidebar, Dashboard, Events, Overview, Wizard, Participants, RaceSheet, Certificates,
  Payments, Notifications, Domain, Billing, Modules, Profile, PlatformOrgs, Checkin (phone)
- Tokens: design values and components
