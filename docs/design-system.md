# ExamHub design system

The interface layer, as it stands after Phase 4. This document is the
reference for anyone adding a screen: what the tokens mean, which components
already exist, and the rules that keep twenty pages looking like one product.

The whole of the application's CSS is six files and roughly 2,300 lines. There
is no framework underneath it and no build step; a change to a token is a
change to one line.

---

## 1. Architecture

```
public/assets/css/
├── tokens.css       Design decisions, named. Draws nothing.
├── base.css         Bare HTML elements, typography, focus, motion, print.
├── components.css   The reusable vocabulary. Used on more than one page.
├── layout.css       The application shell and its responsive behaviour.
├── pages.css        Compositions belonging to exactly one screen.
└── exam.css         The examination interface, loaded only by that page.
```

They are loaded in that order, each as its own `<link>`, and each URL carries
the file's modification time (`?v=1757...`) so they can be cached hard and
still never be served stale.

**The rule that keeps it honest:** the moment a pattern appears on a second
screen it moves from `pages.css` into `components.css` as a modifier. Copying
it is how the previous stylesheet ended up with four different card
treatments.

**The rule that makes theming work:** a component asks for a semantic role,
never a primitive scale. Dark mode redefines the roles and nothing else, so a
component that asked correctly follows the theme automatically and one that
reached past it would not. See section 3.

Layering also applies within a file. `tokens.css` defines primitive scales
(`--n-500`, `--b-600`) and then the semantic roles built on them
(`--color-text-muted`, `--color-primary`). Components reference the roles. A
component reaching for a primitive scale is usually a sign the palette is
missing a role.

---

## 2. Tokens

### Colour

Two primitive scales carry the identity — a slightly cool neutral so surfaces
read as paper rather than grey plastic, and a cobalt brand blue that is
serious without being institutional navy — plus green, red and amber for
meaning.

The roles are what screens actually use:

| Role | Purpose |
|---|---|
| `--color-bg` | The page behind everything |
| `--color-surface` | Cards, tables, the top bar, the sidebar |
| `--color-surface-secondary` | Table headers, card footers, quiet fills |
| `--color-surface-sunken` | Inset wells, hover on a neutral control |
| `--color-text` / `--color-text-strong` | Body copy / headings and figures |
| `--color-text-muted` | **All** secondary text |
| `--color-text-subtle` | Icons and swatches. **Never text** — see below |
| `--color-border` | Dividers and hairlines |
| `--color-border-strong` | The outline of anything you can operate |
| `--color-border-hover` | That outline under the pointer |
| `--color-primary` (+ `-hover`, `-active`, `-soft`) | The one accent |
| `--color-success` / `-warning` / `-danger` / `-info` | Meaning, each with a `-soft` fill, a `-border` and a `-text` |

Two decisions worth keeping:

- **There are two greys for text, not three.** `--color-text-subtle` was
  originally a third, lighter grade; at 2.58:1 it failed WCAG AA everywhere it
  was used. Rather than darken it until it collapsed into the muted grey, it
  was reassigned to non-text use only — icons, swatches, disabled glyphs —
  where the bar is 3:1 rather than 4.5:1. Anything a reader has to read uses
  `--color-text-muted`.
- **A control's outline is a different role from a divider.** WCAG 1.4.11 asks
  for 3:1 on the boundary of anything operable, which a decorative hairline
  does not reach. `--color-border-strong` is a step darker for that reason, and
  is what text fields, secondary buttons and answer options use.

### Typography

The system UI stack, deliberately. The previous sheet pulled DM Sans and DM
Serif from Google Fonts through an `@import` that the Phase 3
Content-Security-Policy had been blocking all along, so every page was already
rendering in a fallback face. A system stack costs no request, paints on the
first frame, and is what the pages were being read in anyway.

| Token | Size | Used for |
|---|---|---|
| `--fs-2xs` | 11px | Badges, table eyebrows |
| `--fs-xs` | 12px | Captions, helper text |
| `--fs-sm` | 13px | Dense cells, meta |
| `--fs-md` | 14px | Secondary body, controls |
| `--fs-base` | 15px | Body |
| `--fs-lg` | 17px | Lead paragraphs, card titles |
| `--fs-xl` | 19px | h3, question text |
| `--fs-2xl` | 24px | Page titles |
| `--fs-3xl` | 30px | Statistics, greetings |
| `--fs-display` | `clamp(2rem … 2.875rem)` | The landing hero |

Weights are 400/500/600/700; 600 is the working weight for anything
structural. Headings carry negative letter-spacing (`--ls-tight`,
`--ls-tighter`) because at these sizes the system faces track too loose.
Anything numeric — timers, scores, table figures, pagination — is
`font-variant-numeric: tabular-nums`, so a digit changing does not shift the
column.

### Spacing, radius, elevation, motion

- **Spacing** is a 4px scale, `--sp-1` (4px) to `--sp-24` (96px). The page
  gutter is a token that shrinks on small screens, so no page sets its own.
- **Radii** run `--r-xs` (4px) to `--r-full`. Cards are `--r-lg`, controls
  `--r-md`, chips and badges `--r-full`.
- **Elevation** is four two-part shadows — a tight contact shadow plus a wider,
  softer one. A single large blur reads as a glow.
- **Motion** is `--dur-fast` (120ms) for state changes, `--dur-base` (180ms)
  for things that appear, `--dur-slow` (280ms) for the drawer. Easing is
  `--ease-out` for entrances and `--ease-in-out` for state.

---

## 3. Light and dark

Three states: **Match system** (the default), **Light** and **Dark**. System is
the one most people should stay on, because it already follows whatever
schedule their operating system runs; the other two are overrides for when
that does not suit the room.

### How it is applied

The choice lives in a cookie, and the layout stamps it onto `<html>`
server-side:

```html
<html lang="en" data-theme="dark">
```

That ordering is the whole point. Reading the preference in JavaScript would
mean painting the light theme first and correcting it a frame later — the
white flash every dark mode is judged by — and the Content-Security-Policy
rightly refuses the inline `<script>` that is the usual way of dodging it. A
cookie is readable on the very first request, before anything else has run, so
the page simply arrives in the right colours.

A cookie rather than the session, because the preference has to survive
signing out. Deliberately not `HttpOnly`, because the toggle updates it from
JavaScript so switching is instant rather than a page load; it holds a colour
name and nothing else. Its value is checked against the allowlist on the way
in — it ends up in an HTML attribute, and a cookie is client-supplied data
like any other.

### The three CSS blocks

```css
:root                     { /* light: the complete palette */ }

@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) { /* system dark */ }
}

:root[data-theme="dark"]  { /* explicit choice, wins either way */ }
```

The `:not([data-theme="light"])` guard is what stops a reader who asked for
light on a machine set to dark from getting dark anyway. The two dark blocks
cannot be merged — one exists only inside a media query — but they assign from
a single `--dk-*` scale, so a colour still has one place to change.

### Designing the dark palette

It is not the light one inverted. The decisions that matter:

- Surfaces are near-black with a blue cast, not `#000`, which makes shadows
  invisible and text edges harsh.
- The lift from page to card is slight; separation comes mostly from the
  border. That is what keeps a dark interface calm rather than making it look
  like a stack of floating panels.
- **Solid fills go darker, not lighter.** They still carry white text and have
  to clear 4.5:1 to do it. Text and icon colours go lighter for the same
  reason. This is the step a naive inversion gets backwards, and it is why
  `--color-success` and `--color-success-text` are separate roles.
- Soft tints become near-black mixes of their hue; the pale washes light mode
  uses would glow.
- Shadow alphas roughly triple, because there is no bright ground to lift off.

Both palettes were verified twice: once as arithmetic over the tokens, and
once by measuring what the browser actually renders on all 15 pages — walking
every element with visible text, compositing translucent backgrounds down to
an opaque one, and checking the pair. That second pass caught a real bug the
first missed: the correct-answer letter in the question manager was taking
`--color-success` (a fill, tuned to carry white) where it needed
`--color-success-text`.

### The picker

Built on `<details>` rather than the scripted dropdown beside it, because it
has to work without JavaScript: someone who needs the light theme in order to
read the screen should not need scripting to ask for it. Each option is a
submit button in a CSRF-protected POST to `theme.php`, which stores the cookie
and returns them to the page they were on — after checking the return path is
local, since an unchecked "send me back" field is an open redirect.

With JavaScript, `app.js` intercepts the click, writes the same cookie, moves
the attribute and repaints in place, so switching costs no round trip.

### Print

Printing forces the light roles back regardless of the screen. A near-black
page through a printer comes out as a grey wash, and nobody asked for it.

---

## 4. Components

All in `components.css`. Every one of these is used; none was built
speculatively.

| Component | Notes |
|---|---|
| **Buttons** | `primary`, `secondary`, `ghost`, `danger`, `danger-ghost`, `success`, `on-dark`; sizes `sm`/`lg`/`block`/`icon`. Variants are driven by four custom properties, so a new one is four lines. `is-loading` shows a spinner while keeping the label in place so the button does not change width. |
| **Fields** | `.field` + `.field-label` + `.field-input` covers input, select, textarea. Plus `.field-hint`, `.field-error`, `.field-affix` (leading icon), `.field-pair` (side by side), `.field-optional`. Invalid state is a class, not `:invalid`, so an untouched empty required field is not already red. |
| **Choice** | Checkbox and radio rows with a 44px target. |
| **Cards** | `.card` with optional `header`/`body`/`footer`, `.card-interactive` for a card that is a link, `.card-form` for a form capped to a readable measure. |
| **Tables** | `.table-card` owns the border and scroll; `.table` never sets a width that pushes the page sideways. `.table-toolbar` for search and filters, `.table-caption` for counts, `.cell-primary` / `-muted` / `-numeric` / `-actions` / `-lead` / `-sub`. `.table-stack` restacks each row as a labelled block below 768px. |
| **Badges** | Five tones, uppercase, for a fixed classification. |
| **Status** | A dot plus a word. The word carries the meaning, so it survives without colour vision. |
| **Progress / meter** | A bar, and a bar-plus-figure for use inside a cell. |
| **Alerts** | Four tones, each with its own icon so severity does not rest on colour. `role="alert"` for errors, `role="status"` for the rest. Success alerts auto-dismiss; errors stay until dealt with. |
| **Modal dialog** | Native `<dialog>`. Focus trapping, Escape, `inert` background and the top layer come from the platform rather than from several hundred lines of JavaScript. |
| **Dropdown menu** | The account menu. Plain markup with `hidden`; the script toggles it and keeps `aria-expanded` in step. |
| **Segmented control** | Mutually exclusive views, rendered as links so each has a URL. |
| **Pagination** | Summary plus a windowed page list (`1 … 6 7 8 … 42`). |
| **Breadcrumbs** | Used wherever a screen is inside another. |
| **Empty states** | Icon, what is missing, and the action that fills it. |
| **Stat tiles** | One accent per row, applied to the value only. |
| **Icons** | `Icons::render()` / the `icon()` helper. 44 line icons on a 24x24 grid, emitted once per document as an inline `<symbol>` sprite and referenced with `<use>`. Decorative by default. |

### Why not toasts

The component was written and then removed rather than shipped unused. Every
state-changing action in this application is a full POST that redirects and
returns a server-rendered flash message, which survives a reload and is
announced by a screen reader; a toast would have been a less reliable copy of
something that already works.

---

## 5. Major changes by screen

| Screen | What changed |
|---|---|
| **Landing** | Leads with what the product does and the guarantees the code actually makes good on, rather than a gradient and four emoji tiles. |
| **Sign in / register (×3)** | One split composition, form left and context right, sharing a partial so the three cannot drift apart. Autocomplete tokens added so password managers and phone keyboards behave. |
| **Student dashboard** | Leads with the exam to sit next — the action most students came for — instead of leaving it on another page. |
| **Exam list** | Split into "to sit" and "completed". An exam with no questions says so instead of offering a start button that fails. |
| **Exam interface** | Rebuilt. See below. |
| **Results** | Pass/fail filter as links; the single result states its outcome in words and draws the score as a conic-gradient ring. |
| **Admin dashboard** | Statistics without a five-colour rainbow; empty states that offer the next step. |
| **Exams / questions / admins** | Breadcrumbs, real empty states, and destructive prompts that state the consequence drawn from the row. |
| **Students / results** | Server-side search, sorting and pagination. |

### The examination interface

The screen a candidate spends longest on, and the one where a moment of
confusion costs marks. It is deliberately quieter than the rest of the
application: one accent, and every piece of colour carries meaning.

- **One timer**, in a bar pinned below the top bar at every width, so it is
  never off screen on a phone and there is no second copy competing with it.
  It changes colour at five minutes and at one, and says so in words too.
- **Its own chrome** — brand and account only. Links to the rest of the
  application do not belong beside a running clock.
- **Question states**: current, answered, unanswered, and flagged. Flagging is
  a client-side aid held in `sessionStorage`; it is never submitted and never
  influences the score. Each navigator tile carries its state in its accessible
  name, because its colour is not available to everyone.
- **Submission asks first** and lists which questions are unanswered rather
  than only counting them.
- **No artificial delay.** On expiry the answers post immediately; the previous
  version paused 1.8 seconds for effect, which on an expired attempt is 1.8
  seconds closer to the grace period running out.
- **Works without JavaScript.** One question at a time is an enhancement, so a
  `<noscript>` block reveals the whole paper and swaps the scripted controls
  for a plain submit button. Verified by posting a completed paper over raw
  HTTP with scripting off.

---

## 6. Responsive behaviour

Breakpoints: **1024px** (sidebar becomes a drawer), **900px** (split layouts
stack), **768px** (tables restack), **640px** (phone adjustments).

Layouts are reconsidered at each, not shrunk:

- **The admin sidebar was `display: none` below 900px with nothing in its
  place**, which left every administration screen unreachable from a phone.
  The same markup is now the persistent sidebar on a wide screen and an
  off-canvas drawer on a narrow one, with a scrim, a scroll lock, focus moved
  in on open and returned on close, and Escape to dismiss.
- **The control that opens that drawer is labelled**, because the first
  version was not and the drawer may as well not have existed. It was a bare
  38px icon with a transparent border and no fill, sitting next to the brand
  mark — and below 640px the brand *text* is hidden, so the corner was two
  unlabelled icons side by side. It read as a logo lockup, not a button, and
  an administrator reported that the Administrators screen had disappeared on
  mobile. It now carries a border, a fill and the word "Menu", and shows its
  open state. Discoverability is not decoration: a control nobody recognises
  is a feature nobody has.
- **Tables restack.** Below 768px each row becomes a labelled block, with the
  name promoted to a heading and the actions on their own line. Scrolling a
  seven-column table sideways on a phone is not a design.
- **Student navigation stays in the bar** as icons rather than moving behind a
  hamburger: three destinations fit, and one tap beats two. The labels remain
  for screen readers.
- **Nothing is hidden at a breakpoint unless it exists somewhere else.** The
  public bar used to drop its "Administrators" link below 560px. That link was
  the only route to `/admin/login.php` anywhere in the application — not the
  footer, not the landing page, not the student sign-in screen — so a media
  query locked an entire role out of the product on a phone. Two rules now
  apply when deciding what a narrow bar sheds: prefer dropping what is
  **duplicated** (the two buttons the hero repeats full-width directly below)
  over what is **unique**, and give anything unique a second home that no
  breakpoint can take away. The admin route is now in the top bar at every
  width, in the footer, and on both student authentication screens.
- **The exam's previous/next pair becomes a fixed bottom bar** on narrow
  screens — where the thumb is — with `env(safe-area-inset-bottom)` respected.
- **Forms** collapse from two columns to one; every target is at least 44px.

Verified with no horizontal overflow at 390px on every page.

---

## 7. Accessibility

- **Contrast.** Every text pair in the palette meets WCAG AA (4.5:1) against
  the surfaces it appears on, and the boundary of every operable control meets
  1.4.11's 3:1. Five failures were found by measuring rather than by eye and
  fixed at the token level, so they cannot recur one component at a time.
- **Colour is never the only signal.** Pass/fail is a word beside a dot; alert
  severity has an icon; timer urgency changes the label as well as the colour;
  a flagged question is a corner marker, not a fill.
- **Semantics.** One `<h1>` per page and no skipped heading levels; `<main>`,
  `<nav>` and `<aside>` landmarks; `<th scope>` on every table; the exam's
  options are a `<fieldset>` whose `<legend>` is the question, so a screen
  reader reads it before each choice instead of announcing four unlabelled
  radio buttons.
- **Keyboard.** A skip link is the first tab stop on every page. Focus is
  visible everywhere from one `:focus-visible` rule defined once, so no
  component can quietly opt out. The drawer and both dialogs manage focus.
  Arrow-key navigation in the exam never steals a key from the radio group.
- **Live regions used sparingly.** The timer was an `aria-live` region
  updating every second, which made a screen reader read the clock aloud
  continuously. It now announces at four milestones.
- **Icons** are `aria-hidden` unless they carry meaning no nearby text
  repeats; icon-only buttons carry an `aria-label`.
- **Motion.** `prefers-reduced-motion` is honoured globally in `base.css`, so
  no component has to remember to.
- **Placeholders are never the only label.** Every field has a visible one.

Audited programmatically across all 16 pages: labels, accessible names,
heading order, landmarks, table headers, duplicate ids and `lang`. Contrast is
audited separately at runtime, in both themes, by measuring what the browser
renders rather than what the palette intends.

---

## 8. Performance

- **No webfont request.** The system stack paints on the first frame.
- **No UI library.** Total shipped CSS is ~52KB uncompressed across six files;
  JavaScript is two files totalling ~21KB, neither minified nor needed for the
  page to be usable.
- **Both scripts are `defer`red**, so neither blocks parsing.
- **The icon sprite is inline**, so twenty icons cost one copy of each path and
  no extra request. It has to be inline: an external sprite referenced through
  `<use>` cannot inherit `currentColor` in any current browser, which would
  cost the whole point of the set.
- **Assets are cache-busted by modification time**, so they can be cached hard.
- **Admin listings page server-side**, so a large cohort does not mean a large
  page.

---

## 9. Remaining UX issues

Known, deliberate, and worth picking up next:

- **The theme is per-browser, not per-account.** It rides in a cookie, so a
  student who signs in on a shared machine gets whatever the last person
  chose, and their own choice does not follow them to their phone. Storing it
  against the account would fix both, at the cost of a column and a write.

- **Tables cannot be sorted on a phone.** The sort controls live in the table
  header, which is hidden when rows restack. A sort `<select>` in the toolbar
  at that breakpoint would fix it.
- **Search has no debounce or autocomplete.** It is a plain GET form, which is
  robust and works without JavaScript, but requires pressing the button.
- **The admin exam list is not paginated.** Exams are authored by hand and
  number in the tens; students and results, which grow without bound, are.
- **Flags do not survive a different tab or device**, being `sessionStorage`.
  Persisting them would mean an authenticated endpoint, which is a Phase 5
  question rather than a styling one.
- **No autosave during an exam**, unchanged from Phase 3 and noted there: a
  student who closes the browser mid-exam still loses the attempt. The
  interface now warns before unload, which narrows the window but does not
  close it.
- **`style-src` still allows inline**, because progress bars, the score ring
  and the pagination window set a numeric value with a style attribute. A CSS
  custom property set from a class per decile would let it be tightened.
- **Empty-state illustrations are icons, not drawings.** Adequate, not
  delightful.
