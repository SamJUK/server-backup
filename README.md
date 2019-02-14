# Setup
### Box account
1) Create a Box Developer App - https://app.box.com/developers/console/newapp
2) Authentication Method - OAuth 2 JWT (Server Auth)
3) Generate Public Key Pair
4) Download App settings as json to `./conf/config.json`

### Backup config
1) Copy `./conf/siteconfig.example.json` to `./conf/siteconfig.json`
2) Modify to fit server paths & Auth details


# Todo
 - Check Box storage left, if available storage below limit email admins
 - Database Dumps

