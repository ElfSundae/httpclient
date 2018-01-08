# Changelog

All notable changes to `httpclient` will be documented in this file.

## [Unreleased]

- Added support to configure global default request options, via static method `setDefaultOptions`
- Added docblock for the HttpClient class
- Dynamically handle all response methods, e.g. `getHeaders`, `getHeaderLine`
- Dynamically handle all options setter methods, e.g. `auth`, `json`, `httpErrors`
- `option` method can accept array
- Added custom `catch_exceptions` option
- Renamed `withExceptions()` to `catchExceptions()`, added `areExceptionsCaught()`
- Renamed `options()` to `mergeOptions()`
- Renamed `removeOptions()` to `removeOption()`
- Removed `getOptions()`, use `getOption()` instead
- Refactored `__call`

## 1.1.0 - 2017/06/17

- Added magic request methods: `get`, `head`, `put`, `post`, `patch`, `delete`. [23a8ebc](https://github.com/ElfSundae/httpclient/commit/23a8ebc3eae9dc10d4590764c6ef629327f86780)

## 1.0.0 - 2017/06/15

- Initial release
