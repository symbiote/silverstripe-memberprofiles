# Run PHPStan locally

Install PHPStan 0.9.2 using Composer and run this from the project folder to test updates to the module.

```
vendor/bin/phpstan analyse vendor/symbiote/silverstripe-memberprofiles/src/ vendor/symbiote/silverstripe-memberprofiles/tests/ -c "vendor/symbiote/silverstripe-memberprofiles/phpstan.neon" -a "vendor/symbiote/silverstripe-memberprofiles/tests/bootstrap-phpstan.php" --level 3
```

# Contributing

For information on contributing, please visit:
[https://www.symbiote.com.au/contributing-open-source](https://www.symbiote.com.au/contributing-open-source)
