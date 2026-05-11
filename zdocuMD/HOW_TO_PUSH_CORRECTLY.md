# How to Push Correctly - Step by Step Guide

## The Correct Push Workflow

### Step 1: Check Your Current Status
```bash
cd C:\xampp\htdocs\hrs
git status
```

**What you should see:**
- ✅ "On branch main"
- ✅ List of modified files (if you made changes)
- ✅ "nothing to commit" (if no changes)

---

### Step 2: Pull Latest Changes First (IMPORTANT!)
```bash
git pull origin main
```

**Why?** This gets any changes from GitHub that you don't have locally.

**What you should see:**
- ✅ "Already up to date" (if no remote changes)
- ✅ "Updating..." (if there are remote changes)

---

### Step 3: Make Your Changes
Edit your files in VS Code or any editor.

---

### Step 4: Check What Changed
```bash
git status
```

**You'll see:**
- Red files = Modified but not staged
- Green files = Staged and ready to commit

**To see the actual changes:**
```bash
git diff
```

---

### Step 5: Stage Your Changes
```bash
# Stage all changes
git add .

# OR stage specific files
git add client/src/components/MyComponent.vue
git add server/api/my_api.php
```

**Check staging worked:**
```bash
git status
```
Files should now be green (staged).

---

### Step 6: Commit Your Changes
```bash
git commit -m "Your descriptive message here"
```

**Good commit messages:**
```bash
git commit -m "feat: Add delete confirmation modal"
git commit -m "fix: Resolve OCR scanning accuracy issues"
git commit -m "docs: Update development guide"
git commit -m "chore: Remove root node_modules folder"
```

**Bad commit messages:**
```bash
git commit -m "update"        # ❌ Too vague
git commit -m "fix"           # ❌ What did you fix?
git commit -m "changes"       # ❌ What changes?
```

---

### Step 7: Pull Again (Safety Check)
```bash
git pull origin main --rebase
```

**Why?** In case someone pushed while you were working.

**What you should see:**
- ✅ "Already up to date" (good, no conflicts)
- ⚠️ "Merge conflict" (need to resolve - see below)

---

### Step 8: Push to GitHub
```bash
git push origin main
```

**What you should see:**
```
Enumerating objects: X, done.
Counting objects: 100% (X/X), done.
Writing objects: 100% (X/X), done.
To https://github.com/CS0747/hrs-v2.git
   abc1234..def5678  main -> main
```

✅ **Success!** Your changes are now on GitHub.

---

### Step 9: Verify on GitHub
1. Go to https://github.com/CS0747/hrs-v2
2. Check that your commit appears
3. Check that your files are updated

---

## Complete Example Session

```bash
# 1. Navigate to project
cd C:\xampp\htdocs\hrs

# 2. Check status
git status

# 3. Pull latest (ALWAYS DO THIS FIRST!)
git pull origin main

# 4. Make your changes in VS Code
# ... edit files ...

# 5. Check what changed
git status
git diff

# 6. Stage changes
git add .

# 7. Commit
git commit -m "feat: Add new feature"

# 8. Pull again (safety check)
git pull origin main --rebase

# 9. Push
git push origin main

# 10. Verify
git status
# Should say: "Your branch is up to date with 'origin/main'"
```

---

## Quick Daily Workflow

### Morning (Start of Work)
```bash
cd C:\xampp\htdocs\hrs
git pull origin main
```

### During Work (After Each Feature/Fix)
```bash
git add .
git commit -m "descriptive message"
git pull origin main --rebase
git push origin main
```

### Evening (End of Work)
```bash
git status
# Make sure everything is pushed
```

---

## Handling Common Issues

### Issue 1: Push Rejected
```
! [rejected]        main -> main (fetch first)
```

**Solution:**
```bash
git pull origin main --rebase
git push origin main
```

---

### Issue 2: Merge Conflicts
```
CONFLICT (content): Merge conflict in file.txt
```

**Solution:**
```bash
# 1. Open the conflicted file
# Look for these markers:
<<<<<<< HEAD
Your changes
=======
Remote changes
>>>>>>> origin/main

# 2. Edit the file to keep what you want
# Remove the markers (<<<, ===, >>>)

# 3. Stage the resolved file
git add file.txt

# 4. Continue the rebase
git rebase --continue

# 5. Push
git push origin main
```

**Or abort and try again:**
```bash
git rebase --abort
# Start over with a different approach
```

---

### Issue 3: Forgot to Pull First
```bash
# You already committed locally
# Now push is rejected

# Solution:
git pull origin main --rebase
git push origin main
```

---

### Issue 4: Want to Undo Last Commit
```bash
# Undo commit but keep changes
git reset --soft HEAD~1

# Undo commit and discard changes
git reset --hard HEAD~1
```

---

### Issue 5: Accidentally Committed to Wrong Branch
```bash
# Move commit to correct branch
git checkout correct-branch
git cherry-pick <commit-hash>
git checkout wrong-branch
git reset --hard HEAD~1
```

---

## What NOT to Do

### ❌ DON'T: Commit on GitHub Website
```
GitHub.com → Edit file → Commit
❌ This causes divergence!
```

### ❌ DON'T: Force Push (Unless You Know What You're Doing)
```bash
git push --force
❌ This can delete other people's work!
```

### ❌ DON'T: Skip Pulling Before Pushing
```bash
git commit -m "message"
git push origin main  # ❌ Might be rejected!
```

### ❌ DON'T: Commit Without Checking
```bash
git add .
git commit -m "update"  # ❌ What did you update?
git push origin main
```

---

## What TO Do

### ✅ DO: Pull Before You Push
```bash
git pull origin main
# ... make changes ...
git push origin main
```

### ✅ DO: Write Good Commit Messages
```bash
git commit -m "feat: Add user authentication"
git commit -m "fix: Resolve login button not working"
git commit -m "docs: Update API documentation"
```

### ✅ DO: Check Status Frequently
```bash
git status  # Use this often!
```

### ✅ DO: Review Changes Before Committing
```bash
git diff
git status
# Then commit
```

### ✅ DO: Commit Often
```bash
# Small, focused commits are better than one huge commit
git commit -m "feat: Add login form"
git commit -m "feat: Add login validation"
git commit -m "feat: Add login error handling"
```

---

## Verification Checklist

Before you close your terminal, verify:

- [ ] `git status` shows "nothing to commit, working tree clean"
- [ ] `git status` shows "Your branch is up to date with 'origin/main'"
- [ ] Check GitHub.com and see your latest commit
- [ ] Your files on GitHub match your local files

---

## Troubleshooting Commands

### Check if you're in sync
```bash
git status
git fetch origin
git log --oneline --graph --all -5
```

### See what's different between local and remote
```bash
git fetch origin
git diff main origin/main
```

### See your recent commits
```bash
git log --oneline -10
```

### See who changed what
```bash
git log --oneline --all --graph
```

---

## Emergency Commands

### Discard all local changes
```bash
git reset --hard HEAD
git clean -fd
```

### Get back to remote state
```bash
git fetch origin
git reset --hard origin/main
```

### Save changes temporarily
```bash
git stash
# Do something else
git stash pop  # Restore changes
```

---

## Pro Tips

### 1. Use Aliases
Add to your git config:
```bash
git config --global alias.st status
git config --global alias.co checkout
git config --global alias.br branch
git config --global alias.cm commit
git config --global alias.pl "pull origin main --rebase"
git config --global alias.ps "push origin main"

# Now you can use:
git st    # instead of git status
git pl    # instead of git pull origin main --rebase
git ps    # instead of git push origin main
```

### 2. Check Before You Commit
```bash
git diff --staged  # See what you're about to commit
```

### 3. Commit Message Template
Create `.gitmessage` in your home directory:
```
# <type>: <subject>
# 
# <body>
# 
# <footer>

# Types: feat, fix, docs, style, refactor, test, chore
```

Set it:
```bash
git config --global commit.template ~/.gitmessage
```

### 4. See Pretty Logs
```bash
git log --oneline --graph --decorate --all
```

Or create an alias:
```bash
git config --global alias.lg "log --oneline --graph --decorate --all"
git lg  # Use it
```

---

## Summary: The Perfect Push

```bash
# 1. Pull first
git pull origin main

# 2. Make changes
# ... edit files ...

# 3. Stage
git add .

# 4. Commit
git commit -m "feat: descriptive message"

# 5. Pull again (safety)
git pull origin main --rebase

# 6. Push
git push origin main

# 7. Verify
git status
```

**That's it! Follow these steps every time and you'll never have push issues.**

---

## Remember:

1. **ALWAYS pull before you push**
2. **NEVER commit on GitHub website**
3. **ALWAYS write descriptive commit messages**
4. **ALWAYS check git status**
5. **Commit often, push often**

---

## Need Help?

If something goes wrong:
1. Don't panic
2. Run `git status` to see what's happening
3. Read the error message carefully
4. Check this guide
5. If stuck, run `git stash` to save your work, then ask for help
