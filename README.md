# CRON.DAV.Fobi

## Abstract

This package provides a widget for the DAV training tool. 
Which can be easily integrated via the backend on the site.

## Features
This Package contains:
* An Eel helper to generate a token to identify the user to be passed to the JS widget
* A JS widget which is loaded from an external service, this also loads the CSS for the widget 
* NodeTypes which contains the widget for the Neos backend
    
## Installation and configuration

### Install

Configure the repo in composer.json

```json
"repositories": {
    "cron-dav-fobi": {
        "type": "git",
        "url": "git@github.com:cron-eu/neos-dav-fobi.git"
    }
}
```

Then use composer to install this package:

```bash
composer require cron/cron-dav-fobi:dev-master
```


### Configuration
```yaml
CRON:
  DAV:
    Fobi:
      widgetLoaderUri: 'https://widgets.davfobi.de/loader.js'
      token:
        # The issuer of the token
        issuer: ''
        # The target domain for which the token was issued.
        audience: ''
        # Password for basic auth when contacting  server
        secret: ''
        # Token expiration: a string which can be passed to the php DateInterval constructor
        expiresAfter: ''
        rolesMapping:
          # Map flow roles to strings used in Fobi
          'CRON.DazSite:DazSubscriber': 'DAZ-Abo'
          'CRON.DazSite:ELearningUser': 'eLearning-Benutzer'
``` 
