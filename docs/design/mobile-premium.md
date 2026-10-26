# Premium mobile polish for event pages

Feedback: on a phone, the race page (in the homepage phone mock and in the dashboard preview)
didn't feel premium. The causes and the fixes:

| Problem on a phone | Fix |
|---|---|
| Two Register buttons on screen at once (the hero button plus the sticky bar) | The sticky bar (`landings/partials/sticky-register`, every template) slides in only once the hero button (`[data-hero-cta]`) has scrolled away, and steps aside over the registration form. Without JavaScript it stays visible. |
| The sticky bar was a coral block on a light strip | A slim solid ink bar: "26 Dec · Entries from ₹599" on the left, one Heat **Register →** button, and safe-area padding for notched phones. |
| Gun Lap hero: three boxed chips stacked on separate lines | One quiet line on phones: date · Limited bibs, with the venue below. The chips stay on tablet and desktop. |
| Oversized outline headline | A smaller headline (13vw), tighter leading and a thinner outline stroke on phones. |
| Hero filled the screen with empty fallback | The hero is 86% of the screen height on phones, so the next section peeks in. The fallback line work is fainter on phones. |
| Distances: the price dropped under a huge numeral | One tidy row per distance: numeral, then name and places left, then the price on the right. |
| Dashboard mobile preview squeezed and clipped on a phone | The preview renders a real 390×844 page and scales it to fit, inside a phone frame. |
| The homepage phone mock was very large on phones | It is smaller on phones and re-captured from the new design. |

## Before → after (Gun Lap, 390px)

| Before | After |
|---|---|
| ![Before](mobile-premium/before-gun-lap.jpg) | ![After](mobile-premium/after-gun-lap.jpg) |

After scrolling, the slim bar appears. The distance rows are tidy:

![Scrolled](mobile-premium/after-gun-lap-scrolled.jpg)

Homepage phone mock, and the dashboard's mobile preview on a phone:

| Homepage | Dashboard preview |
|---|---|
| ![Home](mobile-premium/after-home-phone.jpg) | ![Overview](mobile-premium/after-overview-phone.jpg) |

Tests: `tests/Feature/Events/MobileLandingTest.php`.
