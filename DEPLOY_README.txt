# Clean deployment package for Corevia / Stonesoft Payroll & HR

Upload this folder's contents to your Hostinger subdomain folder.

Best setup:
- Subdomain document root: public_html/payroll/public
- Use public/.htaccess only.

If Hostinger cannot point the subdomain to /public:
- Upload contents to public_html/payroll
- Keep the root .htaccess and public/.htaccess included here.

Do not upload the original LibosecMs root template files like index.php, accordion.php, tables-*.php, etc.
They are old theme demo pages and are not part of the MVC app.
