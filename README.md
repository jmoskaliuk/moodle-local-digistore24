# eLeDia.OS

eLeDia.OS is a structured operating system for AI-assisted software development.

It defines how ideas, implementation, documentation, and testing interact in a consistent workflow — enabling humans and AI (e.g. Claude, ChatGPT) to collaborate effectively without chaos.

---

## Why eLeDia.OS exists

Working with AI on software projects introduces a new problem:

> The limiting factor is no longer writing code — it is managing context.

Typical issues:
- requirements, decisions, and implementation drift apart
- important context is lost in chat history
- user documentation is forgotten
- technical documentation becomes outdated
- bugs and tests are tracked inconsistently
- AI produces correct but contextually wrong solutions

eLeDia.OS addresses this by introducing a **minimal but strict structure** for how information is organized and maintained.

---

## Core idea

eLeDia.OS separates software development into **five distinct perspectives**:

| Perspective | File | Question |
|------------|------|----------|
| Features | `01-features.md` | What are we building and why? |
| User | `02-user-doc.md` | How does a user interact with it? |
| Developer | `03-dev-doc.md` | How is it actually implemented? |
| Work | `04-tasks.md` | What are we doing right now? |
| Quality | `05-quality.md` | Does it work and what is broken? |

Each file has a **single responsibility**.

---

## System logic

The system is built around a simple but powerful principle:

> Different types of information must not be mixed.

Why?

Because mixing:
- planning + implementation
- user view + developer view
- ideas + tasks + bugs

leads to:
- confusion
- duplication
- inconsistent decisions
- poor AI reasoning

---

### Separation of concerns

- **Features (`01-features.md`)**  
  Defines intent and expected behavior.  
  → This is the *source of truth* for what the system should do.

- **User Documentation (`02-user-doc.md`)**  
  Explains how real users interact with the system.  
  → Forces clarity and usability.

- **Developer Documentation (`03-dev-doc.md`)**  
  Describes the actual implementation.  
  → Reflects reality, not intention.

- **Tasks (`04-tasks.md`)**  
  Contains active work, questions, and verification steps.  
  → The operational center.

- **Quality (`05-quality.md`)**  
  Tracks bugs and test results.  
  → The reality check.

---

## The most important rule

> A feature is only done when all perspectives are consistent.

This means:

- Feature is defined (`01-features.md`)
- User can understand it (`02-user-doc.md`)
- Implementation is documented (`03-dev-doc.md`)

If one is missing → the feature is not done.

---

## Workflow

**Idea → Feature → Task → Implementation → Test → Bug → Fix → Done → Documentation Sync**

## Workflow (Detailed)

eLeDia.OS follows a structured but lightweight workflow that separates thinking, implementation, and validation.

### 1. Idea

A new idea, observation, or requirement emerges.

- Source: user feedback, testing, bug, or new feature need
- Action: create or extend a feature in `01-features.md`

---

### 2. Feature Definition (`featXX`)

The idea is formalized as a feature.

Defined in: `01-features.md`

Includes:
- goal (why it exists)
- behavior (what should happen)
- non-goals (what is intentionally excluded)
- decisions (how it should work conceptually)

👉 At this stage, there is no code — only intent.

---

### 3. Task Creation (`taskXX`)

The feature is broken down into concrete work items.

Defined in: `04-tasks.md`

Tasks should:
- be small and executable
- reference a feature (`featXX`)
- have a clear outcome

👉 This is where planning becomes actionable.

---

### 4. Implementation

Tasks are implemented in code.

- may involve multiple iterations
- may raise new questions → go back to tasks or feature
- may reveal inconsistencies → update feature

👉 Reality starts diverging from intention here — this is expected.

---

### 5. Testing (`testXX`)

The implemented behavior is verified.

- manual testing (UI, flows)
- automated testing (if available)

Recorded in: `05-quality.md`

👉 This step validates whether implementation matches intent.

---

### 6. Bug Handling (`bugXX`)

If something fails:

- create a bug in `05-quality.md`
- describe:
  - expected behavior
  - actual behavior
  - reproduction steps

Then:
- create a new task to fix it

---

### 7. Fix

Bug is resolved through a new task.

- update code
- retest
- link fix to bug and feature

👉 Bugs are part of the normal loop, not an exception.

---

### 8. Done (Verification)

A task or feature is considered done when:

- behavior works as expected
- tests (manual or automated) pass
- no blocking bugs remain

---

### 9. Documentation Sync (Critical Step)

Now the system enforces consistency:

Update:
- `02-user-doc.md` → how users interact with the feature
- `03-dev-doc.md` → how it is implemented
- `01-features.md` → if behavior changed during implementation

👉 This step ensures long-term clarity and prevents knowledge loss.

---

## 🔄 Important: This is a loop, not a line

The workflow is iterative:

- implementation may trigger new tasks
- testing may reveal bugs
- bugs create new tasks
- tasks may require feature refinement

👉 eLeDia.OS is designed for continuous refinement, not linear execution.

## 🧭 Workflow Overview

        ┌──────────┐
        │   Idea   │
        └────┬─────┘
             ↓
     ┌───────────────┐
     │  Feature      │  (featXX)
     │  Definition   │
     └────┬──────────┘
          ↓
     ┌───────────────┐
     │   Tasks       │  (taskXX)
     └────┬──────────┘
          ↓
     ┌───────────────┐
     │ Implementation│
     └────┬──────────┘
          ↓
     ┌───────────────┐
     │    Testing    │  (testXX)
     └────┬──────────┘
          ↓
     ┌───────────────┐
     │     Bug?      │  (bugXX)
     └────┬──────────┘
          │ yes
          ↓
     ┌───────────────┐
     │     Fix       │
     └────┬──────────┘
          ↓
          └───────────────┐
                          ↓
                   ┌──────────────┐
                   │     Done     │
                   └────┬─────────┘
                        ↓
               ┌──────────────────┐
               │ Documentation    │
               │ Sync (critical)  │
               └──────────────────┘
