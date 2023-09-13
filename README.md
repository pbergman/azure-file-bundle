## Azure File Bundle

A simple lightweight implementation for the [Azure File REST Api](https://learn.microsoft.com/en-us/rest/api/storageservices/file-service-rest-api) which can be used for direct accessing files over the api instead of mounting the filesystem.   

At the moment this bundle only support list, get and meta operations and will be updated on need.

### Config

The bundle config is separate in 2 parts, `shares` which will hold account credentials and `directories` will have the references to credentials and directory.     

```
activin_azure_file:
  shares:
    share_id:
      account:  <ACCOUNT_NAME>
      key:      <ACCOUNT_KEY>
  directories:
    images:
      path:  /data/
      share: share_id
```