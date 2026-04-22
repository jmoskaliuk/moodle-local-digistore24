# User Documentation

## Meta

This document describes how users interact with the product and its features.

It serves two purposes:
1. Explain the product from a user perspective
2. Describe how individual features (featXX) are used

This document is the **source of truth for user experience**.

---

## How to use this document

### For humans

Use this document to:
- describe how users interact with the system
- ensure features are understandable and usable
- document flows, steps, and expected outcomes
- validate usability independently from implementation

Think:
→ *How does a user experience this product?*

---

### For AI

When working with this document:

- treat it as the **source of truth for user-facing behavior**
- do not introduce technical explanations
- ensure consistency with `01-features.md`
- ensure it matches actual behavior (`03-dev-doc.md`)
- if unclear → request clarification

---

## What belongs here

Include:
- user flows
- step-by-step interactions
- expected results
- constraints from a user perspective
- usage examples

---

## What does NOT belong here

Do NOT include:
- implementation details → `03-dev-doc.md`
- internal logic or architecture
- tasks or planning → `04-tasks.md`
- bugs or test logs → `05-quality.md`

---

# Product Usage Overview

## Target Users
Who are the main users?

- user type 1
- user type 2

---

## Main Use Cases
What are the primary things users want to achieve?

- use case 1
- use case 2
- use case 3

---

## Typical Workflow

Describe a typical end-to-end flow:

1. user starts
2. user performs action
3. system responds
4. user continues
5. outcome

---

## Key Concepts (User Perspective)

Explain important concepts in simple terms.

- concept 1
- concept 2

---

# Feature Usage

Each feature describes how a user interacts with it.

All entries must:
- reference a feature (featXX)
- focus on usability
- avoid technical details

---

## Feature Template

---

### [Feature Name] (featXX)

**What does it do?**  
Describe the feature in simple terms.

---

**When do I use it?**  
Explain the context or use case.

---

**How to use it**

Step-by-step:

1. step
2. step
3. step

---

**Expected Result**

What should happen after using it?

---

**Examples (optional)**

Provide a realistic usage example.

---

**Limitations / Notes**

- constraints
- edge cases relevant to users

---

**Common Mistakes (optional)**

- mistake 1
- mistake 2

---

# Rules

- Every feature must reference a `featXX`
- Keep language simple and user-focused
- Avoid technical terminology
- Keep instructions actionable
- Update when user-facing behavior changes

---

# Key Principle

> This document explains how the product feels and works for the user — not how it is built.
