# suffixer
The suffix-fixer for dnstrace tools that require a specific performance profile.

### Notes

The format for the files output, as well as statistics and a fresh copy of the output (daily, 1:50am EST) is provided at [dnstrace/public-suffix-data](https://github.com/dnstrace/public-suffix-data). You should probably go there unless you have a specific reason to be here. Seriously. Domain data is very slow to propagate, even the Public Suffix List maintainers recommend weekly updates instead of daily.

...you're sure you want to run this on your own? Alright - just for you:

### Quick Start

```
bash setup.sh
bash load_data.sh
php parse.php
```

After running once, setup.sh does not need to be run again. Please note that load_data.sh fetches a fresh copy of the Public Suffix List, so per the request of the PSL maintainers (Mozilla, very nice people) please do not run load_data.sh more than once per day.

Go ham with parse.php though, no harm in running that whenever you like. Data is saved to suffixer/data under icann.json and private.json. Not detailed enough for you? Questions about domain stuff? Contact the [maintainer](https://github.com/tweedge).
