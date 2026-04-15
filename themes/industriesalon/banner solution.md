Parent Container (.iss-front-hero__shell):
Set to display: flex with flex-direction: column.
Restored a gap: 2.5rem to control the natural distance between the text and the banner slot.
Ensured overflow: visible so the banner can be pushed outside the boundaries.
Banner Slot (.iss-front-banner-slot):
Removed position: absolute, returning it to the normal document flow.
Applied transform: translateY(50%) to push it down half its height outside the container.
Added margin-bottom: -100px to counteract the extra space created by the transform, ensuring the rest of the page content flows correctly.
Ensured z-index: 10 so it stays above any background elements or sections below it.
