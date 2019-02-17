# Setup

## Application
### General Config
Contains General application config settings like stop on exceptions, is debug mode etc

Location: ./conf/app.json
  

### Site Backup Config
Contains what folders/mysqldumps backs up to what folders
    
Location: ./conf/siteconfig.json
  
  
  
## Providers 
### Box
1) Create a Box Developer App - https://app.box.com/developers/console/newapp
2) Authentication Method - OAuth 2 JWT (Server Auth)
3) Generate Public Key Pair
4) Download App settings as json to `./conf/providers/box/config.json`
### Drive
### Dropdown


# Todo
 - Check storage left, if available storage below limit email admins
 - Database Dumps
 - On exceptions email admin
 - Implement other storage providers
   - Google Drive
   - Dropbox
 - Introduce backup schedules
   - Set frequency for sites to update
   - Blacklist days/times; eg(Update every 3rd hour except 12 or dont update on sundays)
 - Implement app config