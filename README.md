# Mati-Core | Sentry

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
    release: 'project-name@version'
    user_fields: [] # optional values <website id>
    priority_mapping: [] #custom priorities mapping customeName: debug|info|warning|error|fatal
```

Browser setup
-------------

create JS file in your assets directory with name: _sentry.js_

```js
Sentry.init({
	dsn: "your-dsn.sentry.io",
	release: "project-name@version",
	integrations: [new Sentry.Integrations.BrowserTracing()],
	tracesSampleRate: 1.0,
});
```

Include files in you layout:

```html

<script src="https://browser.sentry-cdn.com/6.3.5/bundle.tracing.min.js"
		integrity="sha384-0RpBr4PNjUAqckh8BtmPUuFGNC082TAztkL1VE2ttmtsYJBUvqcZbThnfE5On6h1"
		crossOrigin="anonymous"></script>
<script src="{$basePath}/assets/js/sentry.js"></script>
```

Using
-----
HaHa, It's simple! Every exception caught with Tracy or user browser will be sent automatically into issues in sentry.io.

__More info:__ https://sentry.io