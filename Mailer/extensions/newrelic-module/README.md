# Mailer NewRelic Module

## Enabling module

You can enable NewRelic module by adding load extension to your configuration file:

```neon
extensions: 
    newrelic: Remp\NewrelicModule\DI\NewrelicModuleExtension
```

## Configuration

You can configure if NewRelic module should log errors (e.g: missing `newrelic` php extension) from `NewrelicRequestListener`.

```neon
newrelic:
	logRequestListenerErrors: false # default: true
```
