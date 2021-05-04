# Mati-Core  | Sentry

[![Latest Stable Version](https://poser.pugx.org/mati-core/sentry/v)](//packagist.org/packages/mati-core/sentry)
[![Total Downloads](https://poser.pugx.org/mati-core/sentry/downloads)](//packagist.org/packages/mati-core/sentry)
![Integrity check](https://github.com/mati-core/sentry/workflows/Integrity%20check/badge.svg)
[![Latest Unstable Version](https://poser.pugx.org/mati-core/sentry/v/unstable)](//packagist.org/packages/mati-core/sentry)
[![License](https://poser.pugx.org/mati-core/sentry/license)](//packagist.org/packages/mati-core/sentry)

Install
-------

Composer command:
```bash
composer require mati-core/sentry
```

Configuration:

```neon
sentry:
    dsn: null # required DNS from sentry.io
    environment: local # optional <local | production>
    user_fields: [] # optional values <website id>
    priority_mapping: [] #custom priorities mapping customeName: debug|info|warning|error|fatal
```