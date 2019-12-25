# suffixer

The suffix-fixer for tools that require a specific performance profile (fast!) and update frequency (often!).

The format for the files output, as well as statistics and a reasonably fresh copy of the output is provided at [mns-llc/public-suffix-data](https://github.com/mns-llc/public-suffix-data). You should probably go there. There's nothing for you here. :music:

A build is run nightly in Travis to:
* Ensure the [publicsuffix/list](https://github.com/publicsuffix/list) repo has the same data as the currently published PSL
* Ensure no ICANN-recognized TLDs are missing from the PSL
* Parse the current PSL using recent PHP7
* Test a few example domain lookups
* Ensure that [mns-llc/public-suffix-data](https://github.com/mns-llc/public-suffix-data) is up to date
