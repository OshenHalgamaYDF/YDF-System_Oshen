Uploads/documents directory

This folder stores uploaded employee documents (used by the HR employee form).

Security notes:

- `index.html` prevents easy browsing in a browser.
- `.htaccess` blocks direct access by default and allows common document file types.

If you serve uploaded files through PHP, be sure to validate file types and implement access checks. If you want different rules, tell me and I can update `.htaccess` or the upload path in code.
