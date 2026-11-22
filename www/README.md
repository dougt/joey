# Project Joey retrospective site

A static site that explains what Project Joey (Mozilla Labs, 2007) was, how the
three pieces in this repository fit together, and what the product looked like.

Open `index.html` in a browser, or serve the directory:

    python3 -m http.server --directory www 8000

## Layout

| Path | What it is |
|------|------------|
| `index.html` | Overview: purpose, how it worked, who built it, why it stopped |
| `architecture.html` | System design, content types, wire protocol, data model, deployment |
| `firefox-addon.html` | The XUL extension and `mocoJoey` XPCOM component in `addons/ff` |
| `server.html` | The CakePHP 1.1 app and the `joeyd` Python daemon in `server/` |
| `mobile.html` | The Java ME MIDlet in `clients/j2me`, the iPhone view, the WAP view |
| `screenshots.html` | Gallery and method notes |
| `history.html` | Timeline, people, 2007 context, sources |
| `css/site.css` | Shared stylesheet |
| `img/brand/` | Original logo and icon assets copied from the source tree |
| `img/diagrams/` | Hand-drawn SVG diagrams |
| `img/screenshots/` | Regenerated screenshots (see below) |
| `recreations/` | Static HTML reconstructions used to produce the screenshots, plus the Playwright scripts |

## About the screenshots

The original service at joey.labs.mozilla.com is gone and no captures were kept
with the code, so every image was regenerated for this site:

* **Web pages**: the original `.thtml` templates, CSS and images from
  `server/app/webroot`, with the PHP replaced by static sample data, rendered in
  Chromium. Highest fidelity.
* **Firefox add-on**: HTML mock-ups of the XUL overlay and dialogs, using the
  exact strings from `joey.dtd` / `joey.properties` and the original skin PNGs.
* **Java ME client**: HTML mock-ups of the MIDlet screens using `messages.txt`,
  `polish.css` colours and the original resource icons.

To regenerate (Node with Playwright's Chromium installed):

    cd www/recreations
    node shoot-web.js
    node shoot-clients.js
