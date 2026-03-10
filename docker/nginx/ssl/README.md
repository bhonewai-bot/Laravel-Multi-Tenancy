# Origin TLS Files

Place your Cloudflare Origin Certificate files in this folder for production compose:

- `origin.crt`
- `origin.key`

The production nginx config expects:

- `/etc/nginx/ssl/origin.crt`
- `/etc/nginx/ssl/origin.key`

Do not commit real private keys to git.
