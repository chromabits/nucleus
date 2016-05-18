> **DEPRECATED:** This package is no longer maintained. It has been replaced by https://github.com/sellerlabs/nucleus.

# Nucleus [![Build Status](https://travis-ci.org/chromabits/nucleus.svg?branch=master)](https://travis-ci.org/chromabits/nucleus) ![](https://img.shields.io/packagist/v/chromabits/nucleus.svg) [![](https://img.shields.io/badge/ApiGen-reference-blue.svg)](http://chromabits.github.io/nucleus)

A standalone PHP utility library. Inspired heavily by similar projects like libphutil. Currently used in personal and work projects. Designed to make working wth PHP a little bit nicer and predictable.

Requires HHVM 3.6 or PHP 5.6

## Goal

- Provide a consistent API for common operations.
- Gather the most useful bits and snippets under one library.
- Strong emphasis on strict type checking (e.g. `float !== integer`).
- Functional programming ideas and concepts are welcome (I'm new to this).

## Core pieces

While Nucleus has a bunch of random crap in it, there are some useful classes worth mentioning:

- **Spec**: A multi-purpose constraint checking framework that can be easily extended.
- **Validator**: Built upon Spec, the Validator component provides an interface for generating UX-friendly messages for a SpecResult.
- **Impersonator**: A constructor dependency automocker. Useful for testing classes that have many external dependencies and projects that heavily use container dependency injection.
- **View**: An set of classes and utilities for generating clean and safe HTML/Text. It sort of looks like XHP without the XML or de-sugarized React.js code.
- **Std, Arr, and others**: We all know the PHP standard library is a mess. There are many failed attempts to fix this out there. This is another one of those attempts. I've created a few classes with a bunch of static aliases that attempt to improve upon it. Yes, there is a performance penalty (validation + at least one more function call), but it makes many operations more predictable and safer.

## Building documentation

```sh
cd docs
pip install -r requirements.txt
make html
```

## Contributing

Pull requests are accepted on GitHub. Bug fixes and small improvements are welcome. Big ideas will be reviewed and discussed.

Code Standard: PSR-2 with some additions. See https://github.com/chromabits/standard for more details.

## Security

If you discover any security related issues, please email ed+security@chromabits.com instead of using the issue tracker.

## License

This code is licensed under the MIT license. See LICENSE for more information.
