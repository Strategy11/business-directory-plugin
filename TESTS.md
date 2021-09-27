# CodeCeption Testing

## SETUP

### Local Servers
Install ChromeDriver, GeckoDriver or Selenium Server. These will be used to emulate the browser acceptance tests
For each tests, the browser will have to be restarted

### Composer
Ensure composer in installed. Run `composer install` to install dependencies

### CodeCept
Install and configure Codecept

## Testing

### All Tests

Run the following in the `business-directory-plugin` plugin folder

```
#!bash 
$ cd <path-to-wordpress>/wp-content/plugins/business-directory-plugin

$ codeception run

```

### Acceptance Tests

```
#!bash 
$ cd <path-to-wordpress>/wp-content/plugins/business-directory-plugin

$ codeception run acceptance

```

### Functional Tests

```
#!bash 
$ cd <path-to-wordpress>/wp-content/plugins/business-directory-plugin

$ codeception run functional

```


### WPUnit Tests

```
#!bash 
$ cd <path-to-wordpress>/wp-content/plugins/business-directory-plugin

$ codeception run wpunit

```

### Unit Tests

```
#!bash 
$ cd <path-to-wordpress>/wp-content/plugins/business-directory-plugin

$ codeception run unit

```