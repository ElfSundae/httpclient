# Changelog

All notable changes to `httpclient` will be documented in this file.

## [Unreleased]

- Added support to configure global default request options, via static methods: `defaultOptions`, `setDefaultOptions`
- `option` method can accept array
- Rename `withExceptions()` to `catchExceptions()`, added `areExceptionsCaught()`

## 1.1.0 - 2017/06/17

- Added magic request methods: `get`, `head`, `put`, `post`, `patch`, `delete`. [23a8ebc](https://github.com/ElfSundae/httpclient/commit/23a8ebc3eae9dc10d4590764c6ef629327f86780)

## 1.0.0 - 2017/06/15

- Initial release
