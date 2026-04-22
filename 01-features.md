# Features

## Meta

This document defines what the product should do.

It describes:
- features (featXX)
- intended behavior
- product-level decisions

This document represents the **intended behavior of the system**.

---

## How to use this document

### For humans

Use this document to:
- define new features before implementation
- clarify behavior during development
- document decisions and constraints
- ensure a shared understanding of the product

This is the place to think about:
→ *What should the product do and why?*

---

### For AI

When working with this document:

- treat it as the **source of truth for expected behavior**
- do not assume behavior that is not defined here
- if something is unclear → raise a clarification instead of guessing
- ensure implementation and documentation align with this document

---

## What belongs here

Include:
- feature definitions (featXX)
- goals and purpose
- expected behavior (including edge cases)
- non-goals (explicit exclusions)
- design decisions

---

## What does NOT belong here

Do NOT include:
- tasks (→ 04-tasks.md)
- implementation details (→ 03-dev-doc.md)
- test results or bugs (→ 05-quality.md)
- user instructions (→ 02-user-doc.md)

---

## Product Overview

This section describes the product as a whole.

### Purpose
Describe the overall goal of the system.

### Core Concepts
List the key ideas or building blocks of the system.

### Key Features
High-level overview of main features and how they relate.

### Constraints
Technical, business, or conceptual limitations.

---

## Features

---

### featXX [Feature Name]

**Goal**  
Why does this feature exist?  
What problem does it solve?

---

**Behavior**  
What exactly should happen?

- describe main flow
- include edge cases
- define expected system reactions

---

**Non-goals**  
What is explicitly NOT part of this feature?

- prevents scope creep
- avoids wrong assumptions

---

**Decisions**  
Important design decisions:

- why this approach was chosen
- rejected alternatives (optional)

---

**Open Questions (optional)**  
Only include if clarification is needed.

- unresolved behavior
- unclear constraints

---

## Rules

- Every feature must have a unique ID (featXX)
- Keep descriptions precise and unambiguous
- Avoid technical implementation details
- Update this document if behavior changes

---

## Key Principle

> This document defines what should happen — not how it is implemented.
