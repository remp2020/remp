# REMP Commons

## Installation

To include this set of tools within the project, update your `composer.json` file accordingly:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        {
            "type": "path",
            "url": "../Composer/remp-commons"
        }
    ],
    "require": {
        // ...
        "remp/remp-commons": "*"
    }
}
```

## Usage

### Multi-armed bandit

```php
$machine = new Machine(1000);
$machine->addLever(new Lever('variant-a', 10, 1000));
$machine->addLever(new Lever('variant-b', 5, 900));

$result = $machine->run();

echo $result->getWinningLever()->getId(); // variant-a
echo $result->getWinningLeverProbability(); // 0.875
```