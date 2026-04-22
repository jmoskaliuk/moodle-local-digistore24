# Skills

Dieser Ordner bündelt die **Claude-Skills**, die für die Moodle-Plugin-Entwicklung im eLeDia.OS-Kontext relevant sind. Jeder Skill ist eine eigenständige `.md`-Datei mit YAML-Frontmatter (Name + Trigger-Beschreibung) und dem vollständigen Anleitungstext.

## Sinn und Zweck

Die Skills sind die **wiederverwendbare Arbeitsanweisung** für Claude / ChatGPT beim Arbeiten an eLeDia-Plugins. Während eLeDia.OS (die `0x-*.md`-Files) beantwortet **„was wird gebaut und wo steht es?"**, beantworten die Skills **„wie wird es gebaut?"**.

Zusammenspiel:

- `00-master.md` … `05-quality.md` → projekt-spezifischer Zustand (Features, Tasks, Bugs, Docs)
- `Playbooks/*.md` → projekt-spezifische Deploy- und Release-Abläufe
- `Skills/*.md` → **generische**, projektübergreifende Expertise (Architektur, CI, Submission, UX)

## Inhalte

| Skill | Zweck | Trigger |
|---|---|---|
| [`moodle-framework.md`](./moodle-framework.md) | Konsolidiertes Meta-Framework für Moodle-Plugin-Entwicklung (Architektur + CI + Deploy + Submission + UX). | jede Moodle-Aufgabe |
| [`moodle-dev.md`](./moodle-dev.md) | Deep-Expert-Wissen für Moodle 5.x: Plugin-Typen, APIs, Hooks, Events, XMLDB, Web Services, Coding Standards. | Plugin-Entwicklung, API-Fragen |
| [`moodle-deploy.md`](./moodle-deploy.md) | Deploy eines Moodle-Plugins aus dem Workspace in eine lokale Moodle-Instanz (Orb + Docker). | „deploy", „push to Moodle", „purge caches" |
| [`moodle-plugin-submit.md`](./moodle-plugin-submit.md) | End-to-End-Playbook für die Einreichung eines Plugins im Moodle Plugins Directory. | „submit", „publish", „plugin approval" |
| [`moodle-design-system.md`](./moodle-design-system.md) | Authoritative Referenz für `@moodlehq/design-system` (MDS): Tokens `--mds-*`, Button-API, SCSS-Entrypoints inkl. scssphp-Legacy, Testing, Guardrails. | `@moodlehq/design-system`, `--mds-*`, MDS, ZeroHeight, DTCG-Tokens, Moodle-5.2-React-UI |
| [`eledia-moodle-ux.md`](./eledia-moodle-ux.md) | eLeDia-UX-Patterns für Moodle-Plugins (Layout, Farben, Komponenten, Icons). Verweist für DS-Tiefe auf `moodle-design-system.md`. | UI-Arbeit, „eLeDia style", „make it look like LeitnerFlow" |
| [`webui-accessibility-auditor.md`](./webui-accessibility-auditor.md) | Accessibility-Audit nach WCAG 2.2 AA mit EN 301 549-, BITV- und BFSG-Mapping. | Barrierefreiheits-Prüfung |

## Nutzung

### Für Claude / ChatGPT

1. Session starten und `00-master.md` lesen.
2. Projektkontext laden (`01-features.md`, `04-tasks.md`).
3. Bei Moodle-Themen **immer zuerst** `Skills/moodle-framework.md` konsultieren und — je nach Aufgabe — die spezifischen Skills dazunehmen.
4. Bei UI-Arbeit zusätzlich `Skills/eledia-moodle-ux.md`.
5. Vor Submission das komplette `Skills/moodle-plugin-submit.md` durchgehen.

### Für Menschen

Die Skills funktionieren auch ohne KI als Referenzdokumente. Sie enthalten Code-Patterns, Anti-Patterns, Entscheidungsbäume und Checklisten, die im Team-Alltag verwendet werden können.

## Pflege

- Skills werden **nicht** mit dem Projektstand vermischt. Projektbezogene Besonderheiten (z. B. der konkrete Container-Name, die konkrete Quiz-Endpoint-URL) gehören in einen Playbook unter `Playbooks/`, nicht in den Skill.
- Ändert sich Moodle-Core (neue Hook-API, neue Context-Klasse, neue Precheck-Regel), **aktualisiere den Skill**, nicht das Playbook.
- Jeder Skill hat oben ein YAML-Frontmatter (`name`, `description`). Dieses ist für KI-Systeme, die Skills automatisch laden; bitte beim Editieren nicht entfernen.

## Stand

**2026-04-22** — Neuer Skill `moodle-design-system.md` für das offizielle MDS-npm-Paket:

- Basis: Snapshot von [github.com/moodlehq/design-system](https://github.com/moodlehq/design-system) v2.1.1 (2026-03-12) inkl. `.github/copilot-instructions.md` und den drei scoped Instructions-Files (components / stories-tests / tokens).
- Deckt die echte Package-API ab: Exports, Subpath-Imports, SCSS-Entrypoints (inkl. `scssphp`-Legacy-Bridge), vollständiger `--mds-*` Token-Katalog, Button-API (`label`-Prop statt children, Varianten `primary|secondary|danger|outline-*`, Sizes `sm|lg`), Double-Class-Specificity (`.mds-btn.btn.btn-<var>`), i18n/RTL-Contract, Testing mit Vitest + Storybook/Playwright, Release-Please-Workflow.
- **Korrekturen** in `eledia-moodle-ux.md` und `moodle-framework.md`: Token-Prefix `--ds-*` → `--mds-*`, Button-API (`variant="destructive"` → `"danger"` / `"outline-danger"`; `children` → `label`; `size="md"` entfernt — gibt es nicht), React-Version 18 → 19.2, DS-Pfad `theme/boost/scss/moodle/design-system/` → npm-Paket `@moodlehq/design-system`, Migrations-Tabelle auf echte Tokens aktualisiert.

**2026-04-21 (abends)** — Moodle-Marketplace-Update eingebaut:

- `moodle-plugin-submit.md` → neue Sektionen **„Marketplace vs Plugins Directory — two tracks (2026+)"**, **„Marketplace-spezifische Pflicht-Ergänzungen"**, **„Marketplace-Approval-Blockers (2026+ Liste)"**, **„Phase 2.5 — Marketplace-Provider-Setup"** und **„Phase 2.6 — Marketplace-Plugin-Page einrichten"**. Skill-Titel umbenannt in „Moodle Plugin Submission Playbook — Directory & Marketplace". Frontmatter-Description erweitert für Marketplace-Trigger (Provider, Paid-Plugin, Marketplace-Launch).

Basis: Moodle Marketplace Plugin Submission Guidelines vom 30.03.2026.

**2026-04-21** — Moodle-5.2-Update eingebaut:

- `moodle-framework.md` → neuer Abschnitt **„Moodle 5.2 — React & Design-System"** mit Import-Map-Specifiern, Mustache-`{{#react}}`-Helper, Grunt-Targets, Build-Pipeline und einer Checkliste für neue 5.2-Plugins.
- `eledia-moodle-ux.md` → neuer Abschnitt **„Moodle 5.2+ Design System"** mit SCSS-Token-Liste, `@moodlehq/design-system`-Komponenten, Migrations-Tabelle `--lf-*` → `--ds-*` und aktualisierter Button-Hierarchie.
- `moodle-dev.md` → Kurz-Notiz zu React/DS in 5.2 sowie erweitertes Anti-Pattern für Inline-Scripts.
- `moodle-plugin-submit.md` → neuer Unterabschnitt in Phase 1 zu Version-Requirements und `js/react/build/` im Release-ZIP.

Basis: Tickets MDL-87759, MDL-87765, MDL-87908, MDL-87922, MDL-87987, MDL-87730, MDL-87909 (alle gegen `MOODLE_502_STABLE` gemergt).

