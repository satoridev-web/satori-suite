# Working with Ms Chat — Best Practices
*Last updated: 2025-08-19*

This guide captures tips for working with ChatGPT (Ms Chat) during the SATORI Suite project.

---

## Session Stability
- Do not refresh the browser while a response is still streaming.
- If the session stalls, wait 20–30 seconds before refreshing.
- If you must refresh, copy the last chunk of text Ms Chat provided into your notes before reloading so you can resume smoothly.

## File Handling
- Always **download deliverables promptly** (PDFs, DOCXs, zips) after they are generated; the sandbox is temporary.
- Save files with **versioned filenames** (`scope-v0.2.pdf`, `satori-suite-v0.2.zip`) to avoid overwriting and to track revisions.

## Workflow Resilience
- Keep all generated documents and code synced to your local **`satori-suite/`** folder.
- Push early and often to **GitHub** (private repo is fine) to maintain an offsite backup and collaboration point.
- Treat the repo as the single source of truth for project assets.

## Browser Choice
- For long or heavy project sessions: **Chrome or Edge** (fewer hiccups).
- For everyday browsing or quick checks: Firefox is fine.
- Safari works but may have issues with file downloads.

## Quick Recovery Checklist
1. Copy any partial response before refreshing if the session drops.
2. Download files immediately and store locally.
3. Tag and push to GitHub after each milestone to protect progress.

---

**Remember:** Ms Chat is your collaborative partner — keep communication clear, download artifacts promptly, and maintain the repo as the ground truth.
