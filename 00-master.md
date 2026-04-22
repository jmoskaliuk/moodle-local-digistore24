# eLedia.OS — Master

## 1. Project Meta

- **Name:** moodle-local-digistore24
- **Goal:** Connect Moodle's payment subsystem to Digistore24 so that paid enrolments (and other Moodle payment areas) can be processed via Digistore24 as a payment provider.
- **Short Description:** Moodle plugin that integrates Digistore24 as a payment gateway for Moodle's core payment API.
- **Tech Stack:** Moodle 5.x (PHP), Moodle Payment subsystem (`core_payment` / `paygw_*`), Digistore24 API & IPN

---

## 2. Session Start (for AI)

1. Read this document completely
2. Read `04-tasks.md`
3. Identify open tasks (taskXX)
4. Read relevant features in `01-features.md`
5. Start with the highest-priority task

---

## 3. File System

| File | Purpose |
|------|--------|
| 01-features.md | What we build and why (intended behavior) |
| 02-user-doc.md | User perspective and usage |
| 03-dev-doc.md | Technical implementation (actual state) |
| 04-tasks.md | Tasks and operational workflow |
| 05-quality.md | Bugs and test results |

---

## 4. ID System

- `feat01` → feature  
- `task01` → task  
- `bug01` → bug  
- `test01` → test  

**Example:**  
feat01 → task02 → bug01 → test03

---

## 5. 🔁 Workflow

Idea → Feature → Task → Implementation → Test → Bug → Fix → Done → Documentation Sync

---

## 6. 🔥 Core Rule (Definition of Done)

A feature is only considered **done** when all perspectives are consistent, complete, and aligned:

### 6.1 Feature Definition (`01-features.md`)

Must clearly define:
- Goal (why it exists)
- Behavior (including edge cases)
- Non-goals (explicit exclusions)
- Decisions (key design choices)

No ambiguity should block implementation.

---

### 6.2 User Documentation (`02-user-doc.md`)

Must ensure:
- clear explanation of functionality
- step-by-step usage
- expected outcomes
- relevant limitations

A user must be able to use the feature **without reading code**.

---

### 6.3 Developer Documentation (`03-dev-doc.md`)

Must describe:
- architecture and components
- data flow
- technical decisions
- constraints and limitations

Another developer must understand and extend the feature **without guessing**.

---

### 6.4 Consistency Check

All three must match:

- intended behavior (`01-features.md`)
- user experience (`02-user-doc.md`)
- implementation (`03-dev-doc.md`)

If any mismatch exists → the feature is NOT done.

---

### 6.5 Verification

Additionally:

- all related tasks (taskXX) are completed
- no blocking bugs (bugXX) remain
- relevant tests (testXX) pass or are verified

---

## 7. 🚫 Not Done If

A feature is NOT done if:

- documentation is missing or outdated
- behavior is unclear or inconsistent
- implementation differs from intended behavior
- user documentation does not match reality
- known bugs affect core functionality

---

## 8. 🧠 Principle

> Implementation alone is not completion.  
> A feature is only complete when it is **understood, usable, and maintainable**.

---

# 9. 🤖 Prompt Shortcuts

These prompts are used to control AI behavior in a structured way.

---

## 9.1 #status

Analyze:

- 01-features.md  
- 04-tasks.md  
- 05-quality.md  

Output:

1. implemented features  
2. features in progress  
3. open tasks (grouped by feature)  
4. open bugs (prioritized)  
5. verification items  
6. risks or inconsistencies  

End with:
→ 3 concrete next steps

---

## 9.2 #next

Identify the 1–3 highest-value next tasks.

Use:

- 04-tasks.md  
- 05-quality.md  
- 01-features.md  

For each:
- explain WHY  
- identify dependencies  
- estimate complexity  

Priority:
1. blockers  
2. bugs  
3. incomplete core features  

---

## 9.3 #plan

Break a feature (featXX) into tasks.

Each task:
- clear goal  
- executable  
- linked to feature  

Format:

taskXX Title  
- Goal  
- Steps  
- Dependencies  
- Expected result  

Also:
- risks  
- unclear areas  

---

## 9.4 #refine

Analyze a feature (featXX).

Identify:
- unclear behavior  
- missing edge cases  
- conflicts  

Ask structured questions:

- behavior  
- UX  
- technical constraints  

Do NOT answer them.

---

## 9.5 #implement

Execute task (taskXX):

1. restate goal  
2. identify components  
3. implement step-by-step  
4. document decisions  

If unclear:
→ move to clarification

---

## 9.6 #test

Design tests for feature (featXX):

- manual tests  
- automated tests  
- failure scenarios  

Output:
→ checklist for `05-quality.md`

---

## 9.7 #bugs

Analyze bugs:

- summarize issue  
- link feature  
- severity  
- fix approach  

Then:
- prioritize  
- detect patterns  

---

## 9.8 #verify

Create verification checklist:

- what to test  
- expected result  
- location  

Group by feature  
→ ready for `04-tasks.md`

---

## 9.9 #doc

Check consistency:

- 01-features.md  
- 02-user-doc.md  
- 03-dev-doc.md  

For each feature:
- implementation vs definition  
- user doc accuracy  
- dev doc accuracy  

Fix only relevant parts.

---

## 9.10 #userdoc

Write user documentation:

- goal  
- steps  
- examples  
- mistakes  

Avoid technical details.

---

## 9.11 #devdoc

Write developer documentation:

- architecture  
- components  
- data flow  
- dependencies  

Goal:
→ enable extension without guessing

---

## 9.12 #consistency

Check entire system:

- features vs implementation  
- tasks vs reality  
- bugs vs unresolved issues  
- missing documentation  

Output:
- issues  
- fixes  

---

## 9.13 #review

Evaluate solution:

1. what works  
2. risks  
3. complexity  
4. simplifications  

---

## 10. 📌 Usage Notes

- Use prompts directly in AI chats  
- Always reference IDs (featXX, taskXX, etc.)  
- Prefer small, focused prompts  
- Avoid unnecessary rewrites  

---

## 11. 🚀 Recommended Daily Workflow

1. #status  
2. #next  
3. #implement  
4. #test  
5. #doc  

Repeat.

---
