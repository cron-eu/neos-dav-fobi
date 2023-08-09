# CRON.DAV.Fobi

## Abstract

This package provides a widget for the DAV training tool. It integrates the Fobi widgets from BCM.
Widgets can be added as Nodes in the backend.

Make sure to allow `CRON.DAV.Fobi:FobiWidget` in the places where you want it to be added.

## Features

This Package contains:
* A sample CSS for the widget size function see here ```Resources/Public/styles/Demo.css```
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
composer require cron/dav-fobi:dev-master
```

### Configuration

Use environment variables for the secrets:

`DAV_FOBI_URI`: the URI of the Fobi server widget loader (i.e. https://widgets.davfobi.de/loader.js)
`DAV_FOBI_ISSUER`: the token issuer
`DAV_FOBI_AUDIENCE`: the target domain for which the token was issued.
`DAV_FOBI_SECRET`: the token shared passphrase
`DAV_FOBI_EXPIRESAFTER`: token expiration, as a string which can be passed to the php DateInterval constructor (i.e. `PT6H`)

Furthermore, configure a proper mapping between the Flow roles and the name of the ACLs for Fobi, i.e.:

```yaml
CRON:
  DAV:
    Fobi:
      rolesMapping:
        # Map flow roles to strings used in Fobi
        'CRON.DazSite:DazSubscriber': 'DAZ-Abo'
        'CRON.DazSite:ELearningUser': 'eLearning-Benutzer'
```
