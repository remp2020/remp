# Multi Armed Bandit evaluator

## Installation

To include the SSO connector within the project, update your `composer.json` file accordingly:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        {
            "type": "path",
            "url": "../Composer/multi-armed-bandit"
        }
    ],
    "require": {
        // ... 
        "remp/multi-armed-bandit": "*"
    }
}
```

## Usage

```php
$machine = new Machine(1000);
$machine->addLever(new Lever('variant-a', 10, 1000));
$machine->addLever(new Lever('variant-b', 5, 900));

$result = $machine->run();

echo $result->getWinningLever()->getId(); // variant-a
echo $result->getWinningLeverProbability(); // 0.875
```