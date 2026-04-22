# Quality

## Meta

This document tracks system quality.

It contains:
- bugs (bugXX)
- test runs (testXX)

Use it for:
- reproducible issues
- verification

Do NOT include:
- ideas
- tasks

---

## 🐞 Bugs

### bug01 Popup desync

Feature: feat01  
Status: open  

**Description**
Popup not synchronized with view.

**Reproduction**
1. open popup
2. navigate
3. mismatch occurs

**Expected**
Popup matches view state.

---

## 🧪 Tests

### test01 Popup sync test

Feature: feat01  
Result: failed  

**Steps**
- open popup
- navigate forward

**Outcome**
desync detected
